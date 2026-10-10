<?php

declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Data access untuk laporan pelanggaran parkir (FR-002 s.d. FR-004).
 * Semua query memakai PDO prepared statements + bindValue (NFR-001).
 */
class ReportModel
{
    /**
     * Status yang dihitung sebagai laporan aktif (D-010).
     *
     * @var string[]
     */
    public const ACTIVE_STATUSES = ['Menunggu', 'Diproses', 'Ditindaklanjuti'];

    /**
     * Seluruh status valid alur Satpam (FR-005).
     *
     * @var string[]
     */
    public const ALL_STATUSES = ['Menunggu', 'Diproses', 'Ditindaklanjuti', 'Selesai'];

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @param array<string, mixed> $data user_id, license_plate, location, category,
     *                                 description, photo (nullable), status (opsional),
     *                                 admin_notes (nullable, opsional).
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `report`'
            . ' (`user_id`, `license_plate`, `location`, `category`, `description`, `photo`, `status`, `admin_notes`)'
            . ' VALUES (:user_id, :license_plate, :location, :category, :description, :photo, :status, :admin_notes)'
        );
        $stmt->bindValue(':user_id', (int) ($data['user_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':license_plate', (string) ($data['license_plate'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':location', (string) ($data['location'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':category', (string) ($data['category'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':description', (string) ($data['description'] ?? ''), PDO::PARAM_STR);

        $photo = $data['photo'] ?? null;
        if ($photo === null) {
            $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':photo', (string) $photo, PDO::PARAM_STR);
        }

        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Menunggu'), PDO::PARAM_STR);

        $adminNotes = $data['admin_notes'] ?? null;
        if ($adminNotes === null) {
            $stmt->bindValue(':admin_notes', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':admin_notes', (string) $adminNotes, PDO::PARAM_STR);
        }

        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function countActiveByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `report`'
            . ' WHERE `user_id` = :user_id'
            . " AND `status` IN ('Menunggu', 'Diproses', 'Ditindaklanjuti')"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM `report` WHERE `user_id` = :user_id ORDER BY `created_at` DESC, `id` DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `report` WHERE `id` = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * Ringkasan KPI dashboard Satpam (FR-005).
     *
     * @return array<string, int> total, menunggu, diproses, ditindaklanjuti, selesai.
     */
    public function getSummaryCounts(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS `total`,'
            . " SUM(`status` = 'Menunggu') AS `menunggu`,"
            . " SUM(`status` = 'Diproses') AS `diproses`,"
            . " SUM(`status` = 'Ditindaklanjuti') AS `ditindaklanjuti`,"
            . " SUM(`status` = 'Selesai') AS `selesai`"
            . ' FROM `report`'
        );
        $stmt->execute();

        $row = $stmt->fetch();
        $counts = is_array($row) ? $row : [];

        return [
            'total' => (int) ($counts['total'] ?? 0),
            'menunggu' => (int) ($counts['menunggu'] ?? 0),
            'diproses' => (int) ($counts['diproses'] ?? 0),
            'ditindaklanjuti' => (int) ($counts['ditindaklanjuti'] ?? 0),
            'selesai' => (int) ($counts['selesai'] ?? 0),
        ];
    }

    /**
     * Daftar laporan dengan pencarian (pelat/lokasi) dan filter status opsional.
     * Nilai status di luar ALL_STATUSES diabaikan (tampil semua).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFilteredReports(?string $search, ?string $status): array
    {
        $sql = 'SELECT * FROM `report` WHERE 1 = 1';
        $hasSearch = $search !== null && trim($search) !== '';
        $hasStatus = $status !== null && in_array($status, self::ALL_STATUSES, true);
        if ($hasSearch) {
            $sql .= ' AND (`license_plate` LIKE :search_plate OR `location` LIKE :search_location)';
        }
        if ($hasStatus) {
            $sql .= ' AND `status` = :status';
        }
        $sql .= ' ORDER BY `created_at` DESC, `id` DESC';

        $stmt = $this->pdo->prepare($sql);
        if ($hasSearch) {
            $stmt->bindValue(':search_plate', '%' . trim((string) $search) . '%', PDO::PARAM_STR);
            $stmt->bindValue(':search_location', '%' . trim((string) $search) . '%', PDO::PARAM_STR);
        }
        if ($hasStatus) {
            $stmt->bindValue(':status', (string) $status, PDO::PARAM_STR);
        }
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }

    /**
     * Ubah status + catat audit secara atomik (FR-005).
     * Mengembalikan false bila laporan tidak ditemukan.
     * Bila koneksi sudah berada dalam transaksi (mis. test rollback),
     * method bergabung ke transaksi tersebut tanpa commit/rollback sendiri.
     *
     * @throws InvalidArgumentException Bila status baru tidak valid.
     */
    public function updateStatus(int $id, string $newStatus, ?string $adminNotes, int $adminId): bool
    {
        if (!in_array($newStatus, self::ALL_STATUSES, true)) {
            throw new InvalidArgumentException('Status laporan tidak valid.');
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $current = $this->findById($id);
            if ($current === null) {
                if ($ownsTransaction) {
                    $this->pdo->rollBack();
                }

                return false;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE `report` SET `status` = :status, `admin_notes` = :admin_notes WHERE `id` = :id'
            );
            $stmt->bindValue(':status', $newStatus, PDO::PARAM_STR);
            if ($adminNotes === null) {
                $stmt->bindValue(':admin_notes', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':admin_notes', $adminNotes, PDO::PARAM_STR);
            }
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            (new ReportLogModel($this->pdo))->createLog(
                $id,
                $adminId,
                (string) ($current['status'] ?? 'Menunggu'),
                $newStatus,
                $adminNotes
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownsTransaction) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Agregasi pelanggar berulang per pelat, terurut pelanggaran terbanyak (FR-006).
     * Hanya pelat dengan total >= $minReports yang tampil (default 2).
     *
     * @return array<int, array<string, mixed>> license_plate, total, last_date, top_location.
     */
    public function getRepeatOffenders(?string $search = null, int $minReports = 2): array
    {
        $sql = 'SELECT `r`.`license_plate`, COUNT(*) AS `total`, MAX(`r`.`created_at`) AS `last_date`,'
            . ' (SELECT `r2`.`location` FROM `report` AS `r2`'
            . ' WHERE `r2`.`license_plate` = `r`.`license_plate`'
            . ' GROUP BY `r2`.`location` ORDER BY COUNT(*) DESC, MAX(`r2`.`created_at`) DESC LIMIT 1)'
            . ' AS `top_location`'
            . ' FROM `report` AS `r`';
        $hasSearch = $search !== null && trim($search) !== '';
        if ($hasSearch) {
            $sql .= ' WHERE `r`.`license_plate` LIKE :search';
        }
        $sql .= ' GROUP BY `r`.`license_plate`'
            . ' HAVING COUNT(*) >= :min ORDER BY `total` DESC, `last_date` DESC';

        $stmt = $this->pdo->prepare($sql);
        if ($hasSearch) {
            $stmt->bindValue(':search', '%' . trim((string) $search) . '%', PDO::PARAM_STR);
        }
        $stmt->bindValue(':min', $minReports, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }
}
