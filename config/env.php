<?php

declare(strict_types=1);

/**
 * Lightweight .env parser (D-008).
 *
 * Membaca file .env dan memuat tiap KEY=VALUE ke $_ENV dan getenv().
 * Mendukung komentar (#), nilai kosong, serta tanda kutip tunggal/ganda.
 */
function loadEnv(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        if ($key === '') {
            continue;
        }

        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, $len - 2);
            }
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

/**
 * Ambil nilai environment dengan fallback default.
 */
function env(string $key, string $default = ''): string
{
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }

    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return (string) $value;
}
