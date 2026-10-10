<?php

declare(strict_types=1);

namespace App\Middlewares;

/**
 * Menegakkan sesi login (NFR-002).
 * Dipakai di semua route yang butuh autentikasi.
 */
class AuthMiddleware
{
    public const LOGIN_PATH = '/login';

    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    public static function handle(): void
    {
        if (self::check()) {
            return;
        }

        header('Location: ' . self::LOGIN_PATH);
        exit;
    }
}
