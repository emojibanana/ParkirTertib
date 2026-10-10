<?php

declare(strict_types=1);

namespace App\Helpers;

use PDO;

/**
 * Pembersih foto laporan Selesai > 7 hari (FR-007, D-011).
 * Dipakai CLI (bin/cleanup_photos.php) dan tombol dashboard Satpam.
 * Referensi kolom photo di-NULL-kan; metadata teks dipertahankan.
 * Kegagalan per-file dicatat ke error log tanpa menghentikan proses.
 */
class PhotoCleaner
{
    /**
     * @return array{checked: int, deleted: int, missing: int, failed: int}
     */
    public static function run(PDO $pdo, string $uploadDir): array
    {
        $stmt = $pdo->prepare(
            'SELECT `id`, `photo` FROM `report`'
            . " WHERE `status` = 'Selesai' AND `updated_at` < (NOW() - INTERVAL 7 DAY)"
            . ' AND `photo` IS NOT NULL'
        );
        $stmt->execute();

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $stmt->fetchAll();

        $stats = ['checked' => 0, 'deleted' => 0, 'missing' => 0, 'failed' => 0];
        $clear = $pdo->prepare('UPDATE `report` SET `photo` = NULL WHERE `id` = :id');

        foreach ($rows as $row) {
            $stats['checked']++;
            $photo = (string) ($row['photo'] ?? '');
            $path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $photo;

            if (!is_file($path)) {
                $stats['missing']++;
            } elseif (!@unlink($path)) {
                $stats['failed']++;
                error_log('ParkirTertib cleanup gagal menghapus: ' . $path);
                continue;
            } else {
                $stats['deleted']++;
            }

            $clear->bindValue(':id', (int) ($row['id'] ?? 0), PDO::PARAM_INT);
            $clear->execute();
        }

        return $stats;
    }
}
