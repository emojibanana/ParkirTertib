<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Audit trail perubahan status laporan oleh Satpam (FR-005).
 * Semua query memakai PDO prepared statements + bindValue (NFR-001).
 */
class ReportLogModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createLog(
        int $reportId,
        int $adminId,
        string $previousStatus,
        string $newStatus,
        ?string $note = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `report_log`'
            . ' (`report_id`, `admin_id`, `previous_status`, `new_status`, `note`)'
            . ' VALUES (:report_id, :admin_id, :previous_status, :new_status, :note)'
        );
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
        $stmt->bindValue(':previous_status', $previousStatus, PDO::PARAM_STR);
        $stmt->bindValue(':new_status', $newStatus, PDO::PARAM_STR);
        if ($note === null) {
            $stmt->bindValue(':note', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':note', $note, PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Timeline audit satu laporan, terurut kronologis, beserta nama Satpam.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByReportId(int $reportId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT `report_log`.*, `user`.`name` AS `admin_name`'
            . ' FROM `report_log` JOIN `user` ON `user`.`id` = `report_log`.`admin_id`'
            . ' WHERE `report_log`.`report_id` = :report_id'
            . ' ORDER BY `report_log`.`created_at` ASC, `report_log`.`id` ASC'
        );
        $stmt->bindValue(':report_id', $reportId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        return $rows;
    }
}
