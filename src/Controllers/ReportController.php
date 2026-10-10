<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\CSRF;
use App\Helpers\FileUpload;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Models\ReportModel;
use InvalidArgumentException;
use PDO;
use Throwable;

/**
 * Laporan pelanggaran user: form, penyimpanan, dan landing (FR-002, FR-003).
 */
class ReportController
{
    public const MAX_ACTIVE_REPORTS = 3;
    public const RATE_LIMIT_MESSAGE = 'Anda telah mencapai batas maksimum 3 laporan aktif.';

    /** @var string[] */
    public const LOCATIONS = [
        'Auditorium',
        'Gedung Digital Library',
        'Fakultas MIPA',
        'Fakultas Teknik',
        'Fakultas Ilmu Keolahragaan',
        'Lainnya',
    ];

    /** @var array<string, string> */
    public const STATUS_BADGES = [
        'Menunggu' => 'bg-amber-100 text-amber-800',
        'Diproses' => 'bg-sky-100 text-sky-800',
        'Ditindaklanjuti' => 'bg-indigo-100 text-indigo-800',
        'Selesai' => 'bg-emerald-100 text-emerald-800',
    ];

    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /** @var string[] */
    public const CATEGORIES = [
        'Parkir Sembarangan',
        'Menghalangi Akses Jalan',
        'Parkir di Area Terlarang',
        'Lainnya',
    ];

    private ReportModel $reports;

    public function __construct(private PDO $pdo)
    {
        $this->reports = new ReportModel($pdo);
    }

    public function home(): string
    {
        return View::render('layouts/header.php', ['title' => 'ParkirTertib UNIMED'])
            . View::render('user/home.php', ['role' => $_SESSION['user_role'] ?? null])
            . View::render('layouts/footer.php');
    }

    public function create(int $userId): string
    {
        return $this->formPage($userId, [], []);
    }

    /**
     * Riwayat laporan milik user (FR-004).
     * Foto yang sudah dibersihkan otomatis (FR-007) tampil sebagai placeholder.
     *
     * @return string HTML halaman riwayat.
     */
    public function history(int $userId, string $uploadDir): string
    {
        $rows = $this->reports->findByUserId($userId);
        $reports = [];
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'Menunggu');
            $photo = isset($row['photo']) && is_string($row['photo']) && $row['photo'] !== ''
                ? $row['photo']
                : null;
            $photoPath = $photo !== null
                ? rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $photo
                : null;
            $reports[] = [
                'id' => (int) ($row['id'] ?? 0),
                'license_plate' => (string) ($row['license_plate'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'status' => $status,
                'badge' => self::STATUS_BADGES[$status] ?? self::STATUS_BADGES['Menunggu'],
                'admin_notes' => isset($row['admin_notes']) && is_string($row['admin_notes'])
                    ? $row['admin_notes']
                    : null,
                'date_label' => self::formatDateTime((string) ($row['created_at'] ?? '')),
                'photo_url' => $photoPath !== null && is_file($photoPath) ? '/uploads/' . $photo : null,
            ];
        }

        return View::render('layouts/header.php', ['title' => 'Riwayat Laporan - ParkirTertib'])
            . View::render('user/history.php', ['reports' => $reports])
            . View::render('layouts/footer.php');
    }

    /**
     * @param array<string, string> $input
     * @param array<string, mixed> $file Entri tunggal gaya $_FILES.
     * @return array<string, mixed>
     */
    public function store(int $userId, array $input, array $file, string $uploadDir): array
    {
        if (!CSRF::verifyToken($this->stringOrNull($input, CSRF::FIELD_NAME))) {
            return $this->fail($userId, ['Token keamanan tidak valid. Silakan muat ulang halaman.'], $input);
        }

        if ($this->reports->countActiveByUserId($userId) >= self::MAX_ACTIVE_REPORTS) {
            return $this->fail($userId, [self::RATE_LIMIT_MESSAGE], $input);
        }

        [$errors, $clean] = $this->validate($input);
        if ($errors !== []) {
            return $this->fail($userId, $errors, $input);
        }

        try {
            $filename = FileUpload::uploadImage($file, $uploadDir);
        } catch (InvalidArgumentException $e) {
            return $this->fail($userId, [$e->getMessage()], $input);
        } catch (Throwable $e) {
            return $this->fail($userId, ['Foto gagal disimpan. Silakan coba lagi.'], $input);
        }

        $this->reports->create([
            'user_id' => $userId,
            'license_plate' => $clean['license_plate'],
            'location' => $clean['location'],
            'category' => $clean['category'],
            'description' => $clean['description'],
            'photo' => $filename,
            'status' => 'Menunggu',
        ]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Laporan berhasil dikirim. Terima kasih atas laporan Anda!',
        ];

        return ['ok' => true, 'redirect' => '/report/history'];
    }

    /**
     * @param array<string, string> $input
     * @return array{0: string[], 1: array<string, string>}
     */
    private function validate(array $input): array
    {
        $errors = [];

        $plate = Sanitizer::normalizeLicensePlate($input['license_plate'] ?? '');
        if ($plate === '') {
            $errors[] = 'Nomor pelat kendaraan wajib diisi.';
        } elseif (preg_match('/^[A-Z0-9]{1,20}$/', $plate) !== 1) {
            $errors[] = 'Nomor pelat hanya boleh huruf dan angka (maksimal 20 karakter).';
        }

        $location = trim($input['location'] ?? '');
        if (!in_array($location, self::LOCATIONS, true)) {
            $errors[] = 'Lokasi parkir tidak valid.';
        }

        $category = trim($input['category'] ?? '');
        if (!in_array($category, self::CATEGORIES, true)) {
            $errors[] = 'Kategori pelanggaran tidak valid.';
        }

        $description = trim($input['description'] ?? '');
        if ($description === '') {
            $errors[] = 'Deskripsi kejadian wajib diisi.';
        } elseif (mb_strlen($description) > 2000) {
            $errors[] = 'Deskripsi maksimal 2000 karakter.';
        }

        $clean = [
            'license_plate' => $plate,
            'location' => $location,
            'category' => $category,
            'description' => $description,
        ];

        return [$errors, $clean];
    }

    /**
     * @param string[] $errors
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    private function fail(int $userId, array $errors, array $input): array
    {
        return ['ok' => false, 'errors' => $errors, 'html' => $this->formPage($userId, $errors, $input)];
    }

    /**
     * Format: '10 Okt 2026, 14:30 WIB'. Input tak-valid dikembalikan apa adanya.
     */
    private static function formatDateTime(string $value): string
    {
        $time = strtotime($value);
        if ($time === false) {
            return $value;
        }
        $month = self::MONTHS[(int) date('n', $time)];

        return sprintf('%d %s %d, %s WIB', (int) date('j', $time), $month, (int) date('Y', $time), date('H:i', $time));
    }

    /**
     * @param string[] $errors
     * @param array<string, string> $old
     */
    private function formPage(int $userId, array $errors, array $old): string
    {
        $active = $this->reports->countActiveByUserId($userId);
        $data = [
            'errors' => $errors,
            'old' => $old,
            'locations' => self::LOCATIONS,
            'categories' => self::CATEGORIES,
            'activeCount' => $active,
            'maxActive' => self::MAX_ACTIVE_REPORTS,
            'limitReached' => $active >= self::MAX_ACTIVE_REPORTS,
        ];

        return View::render('layouts/header.php', ['title' => 'Buat Laporan - ParkirTertib'])
            . View::render('user/create.php', $data)
            . View::render('layouts/footer.php');
    }

    /**
     * @param array<string, string> $input
     */
    private function stringOrNull(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
