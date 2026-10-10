<?php

declare(strict_types=1);

namespace App\Middlewares;

/**
 * Membatasi akses route /admin/* hanya untuk role admin (NFR-002, FR-001).
 * Non-admin menerima HTTP 403.
 */
class AdminMiddleware
{
    public static function check(): bool
    {
        return ($_SESSION['user_role'] ?? null) === 'admin';
    }

    public static function handle(): void
    {
        if (self::check()) {
            return;
        }

        http_response_code(403);
        $view = dirname(__DIR__, 2) . '/views/errors/403.php';
        if (is_file($view)) {
            require $view;
        } else {
            echo '403 - Akses ditolak.';
        }
        exit;
    }
}
