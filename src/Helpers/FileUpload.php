<?php

declare(strict_types=1);

namespace App\Helpers;

use finfo;
use InvalidArgumentException;
use RuntimeException;

/**
 * Validasi & penyimpanan upload foto bukti (NFR-005, FR-003).
 * - Ukuran <= 5MB (5.242.880 bytes).
 * - Ekstensi whitelist: jpg, jpeg, png, webp.
 * - MIME asli diverifikasi via finfo_file() dan harus konsisten dengan ekstensi.
 * - Disimpan dengan nama acak agar tidak bisa ditebak/ditimpa.
 */
class FileUpload
{
    public const MAX_SIZE = 5242880;

    /** @var string[] */
    public const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /** @var array<string, string> */
    private const EXTENSION_MIME_MAP = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    /**
     * @param array<string, mixed> $file Entri tunggal gaya $_FILES (name, type, tmp_name, error, size).
     * @return string Nama file tersimpan (tanpa path direktori).
     *
     * @throws InvalidArgumentException Jika validasi gagal.
     * @throws RuntimeException Jika direktori/file tidak bisa ditulis.
     */
    public static function uploadImage(array $file, string $uploadDir): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(self::uploadErrorMessage((int) $error));
        }

        $tmpName = isset($file['tmp_name']) && is_string($file['tmp_name']) ? $file['tmp_name'] : '';
        $originalName = isset($file['name']) && is_string($file['name']) ? $file['name'] : '';
        if ($tmpName === '' || !is_file($tmpName)) {
            throw new InvalidArgumentException('File foto tidak ditemukan.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_SIZE) {
            throw new InvalidArgumentException('Ukuran file foto melebihi batas maksimal 5MB.');
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException('Format foto harus JPG, JPEG, PNG, atau WebP.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
        if ($mime !== self::EXTENSION_MIME_MAP[$extension]) {
            throw new InvalidArgumentException('Isi file foto tidak sesuai dengan formatnya.');
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Direktori upload tidak bisa dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        $moved = is_uploaded_file($tmpName)
            ? move_uploaded_file($tmpName, $target)
            : rename($tmpName, $target);

        if (!$moved) {
            throw new RuntimeException('File foto gagal disimpan.');
        }

        return $filename;
    }

    private static function uploadErrorMessage(int $error): string
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'Ukuran file foto melebihi batas maksimal 5MB.';
        }

        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Foto bukti wajib diunggah.';
        }

        return 'File foto gagal diunggah. Silakan coba lagi.';
    }
}
