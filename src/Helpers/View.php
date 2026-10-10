<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * View renderer sederhana (T-002).
 * Merender template PHP di /views dengan data terekstrak sebagai variabel lokal.
 */
class View
{
    private static string $basePath = '';

    public static function basePath(): string
    {
        if (self::$basePath === '') {
            self::$basePath = dirname(__DIR__, 2) . '/views';
        }

        return self::$basePath;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $file = self::basePath() . '/' . ltrim($template, '/');
        if (!is_file($file)) {
            throw new \InvalidArgumentException('View tidak ditemukan: ' . $template);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }
}
