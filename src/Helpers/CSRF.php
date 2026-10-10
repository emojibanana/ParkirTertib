<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * CSRF token helper (NFR-007).
 * Token disimpan di session dan diverifikasi dengan hash_equals().
 */
class CSRF
{
    public const SESSION_KEY = 'csrf_token';
    public const FIELD_NAME = 'csrf_token';

    public static function generateToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function verifyToken(?string $submittedToken): bool
    {
        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        if (!is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::generateToken(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . self::FIELD_NAME . '" value="' . $token . '">';
    }
}
