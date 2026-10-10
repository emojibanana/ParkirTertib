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
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Helpers\PhotoCleaner;

if (basename((string) ($_SERVER['argv'][0] ?? '')) === basename(__FILE__)) {
    $pdo = createPDOConnection();
    $uploadDir = dirname(__DIR__) . '/' . env('UPLOAD_DIR', 'public/uploads');
    $stats = PhotoCleaner::run($pdo, $uploadDir);
    fwrite(STDOUT, sprintf(
        "Cleanup selesai: %d diperiksa, %d file dihapus, %d sudah hilang, %d gagal.\n",
        $stats['checked'],
        $stats['deleted'],
        $stats['missing'],
        $stats['failed']
    ));
}
