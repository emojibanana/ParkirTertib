<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Input sanitizer (NFR-006 + D-012).
 */
class Sanitizer
{
    /**
     * Escape output HTML untuk mencegah XSS.
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Normalisasi pelat nomor: uppercase + hapus semua spasi.
     * Contoh: 'bk  1234  abc' -> 'BK1234ABC'.
     */
    public static function normalizeLicensePlate(string $plate): string
    {
        return (string) preg_replace('/\s+/', '', strtoupper(trim($plate)));
    }
}
