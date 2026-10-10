<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Membuat dan mengembalikan instance PDO terkonfigurasi.
 *
 * - ERRMODE_EXCEPTION untuk error handling eksplisit.
 * - FETCH_ASSOC sebagai fetch mode default.
 * - utf8mb4 untuk dukungan karakter penuh.
 * - Sesi MySQL diset ke WIB agar kolom TIMESTAMP terbaca sebagai waktu lokal (D-015).
 */
function createPDOConnection(): PDO
{
    $baseDir = dirname(__DIR__);
    loadEnv($baseDir . '/.env');

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $name = env('DB_NAME', 'parkir_tertib');
    $user = env('DB_USER', 'root');
    $pass = env('DB_PASS', '');

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $host,
        $port,
        $name
    );

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+07:00'");

    return $pdo;
}
