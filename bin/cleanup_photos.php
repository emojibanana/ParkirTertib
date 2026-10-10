<?php

declare(strict_types=1);

/*
 * CLI: hapus foto laporan berstatus Selesai yang diperbarui > 7 hari lalu (FR-007, D-011).
 * Referensi kolom photo di-NULL-kan; metadata teks laporan dipertahankan.
 * Kegagalan hapus per-file dicatat ke STDERR tanpa menghentikan proses.
 *
 * Penggunaan: php bin/cleanup_photos.php   (dari root proyek)
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Skrip ini hanya bisa dijalankan via CLI.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/database.php';

/**
 * @return array{checked: int, deleted: int, missing: int, failed: int}
 */
function cleanupResolvedPhotos(PDO $pdo, string $uploadDir): array
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
            fwrite(STDERR, 'Gagal menghapus: ' . $path . "\n");
            continue;
        } else {
            $stats['deleted']++;
        }

        $clear->bindValue(':id', (int) ($row['id'] ?? 0), PDO::PARAM_INT);
        $clear->execute();
    }

    return $stats;
}

if (basename((string) ($_SERVER['argv'][0] ?? '')) === basename(__FILE__)) {
    $pdo = createPDOConnection();
    $uploadDir = dirname(__DIR__) . '/' . env('UPLOAD_DIR', 'public/uploads');
    $stats = cleanupResolvedPhotos($pdo, $uploadDir);
    fwrite(STDOUT, sprintf(
        "Cleanup selesai: %d diperiksa, %d file dihapus, %d sudah hilang, %d gagal.\n",
        $stats['checked'],
        $stats['deleted'],
        $stats['missing'],
        $stats['failed']
    ));
}
