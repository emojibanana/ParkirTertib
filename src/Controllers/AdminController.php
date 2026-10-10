<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\CSRF;
use App\Helpers\View;
use App\Models\ReportLogModel;
use App\Models\ReportModel;
use App\Models\UserModel;
use InvalidArgumentException;
use PDO;

/**
 * Operasional Satpam: dashboard, detail, dan update status (FR-005).
 * Akses dibatasi middleware admin di Front Controller.
 * Semua transisi memakai status valid ALL_STATUSES (maju maupun koreksi).
 */
class AdminController
{
    /** @var array<int, string> */
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    private ReportModel $reports;
    private ReportLogModel $logs;
    private UserModel $users;

    public function __construct(private PDO $pdo)
    {
        $this->reports = new ReportModel($pdo);
        $this->logs = new ReportLogModel($pdo);
        $this->users = new UserModel($pdo);
    }

    public function dashboard(?string $search, ?string $status): string
    {
        $rows = $this->reports->getFilteredReports($search, $status);
        $reports = [];
        foreach ($rows as $row) {
            $reports[] = $this->summarize($row);
        }

        $data = [
            'counts' => $this->reports->getSummaryCounts(),
            'reports' => $reports,
            'search' => $search ?? '',
            'status' => $status ?? '',
            'allStatuses' => ReportModel::ALL_STATUSES,
        ];

        return View::render('layouts/header.php', ['title' => 'Dashboard Satpam - ParkirTertib'])
            . View::render('admin/dashboard.php', $data)
            . View::render('layouts/footer.php');
    }

    /**
     * @return array{found: bool, html: string}
     */
    public function showDetail(int $id, string $uploadDir): array
    {
        return $this->detailPage($id, $uploadDir, []);
    }

    /**
     * Pelanggar berulang: agregasi per pelat, terbanyak dulu (FR-006).
     */
    public function repeatOffenders(?string $search): string
    {
        $rows = $this->reports->getRepeatOffenders($search);
        $offenders = [];
        foreach ($rows as $row) {
            $offenders[] = [
                'license_plate' => (string) ($row['license_plate'] ?? ''),
                'total' => (int) ($row['total'] ?? 0),
                'top_location' => (string) ($row['top_location'] ?? '-'),
                'last_label' => self::formatDateTime((string) ($row['last_date'] ?? '')),
            ];
        }

        $data = ['offenders' => $offenders, 'search' => $search ?? ''];

        return View::render('layouts/header.php', ['title' => 'Pelanggar Berulang - ParkirTertib'])
            . View::render('admin/repeat_offenders.php', $data)
            . View::render('layouts/footer.php');
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    public function updateStatus(int $id, array $input, int $adminId, string $uploadDir): array
    {
        if (!CSRF::verifyToken($this->stringOrNull($input, CSRF::FIELD_NAME))) {
            $page = $this->detailPage($id, $uploadDir, ['Token keamanan tidak valid. Silakan muat ulang halaman.']);

            return $this->failResult(['Token keamanan tidak valid.'], $page);
        }

        $newStatus = trim($input['new_status'] ?? '');
        if (!in_array($newStatus, ReportModel::ALL_STATUSES, true)) {
            $page = $this->detailPage($id, $uploadDir, ['Status baru tidak valid.']);

            return $this->failResult(['Status baru tidak valid.'], $page);
        }

        $note = trim($input['note'] ?? '');
        try {
            $updated = $this->reports->updateStatus($id, $newStatus, $note === '' ? null : $note, $adminId);
        } catch (InvalidArgumentException $e) {
            $page = $this->detailPage($id, $uploadDir, [$e->getMessage()]);

            return $this->failResult([$e->getMessage()], $page);
        }

        if (!$updated) {
            return ['ok' => false, 'not_found' => true, 'errors' => ['Laporan tidak ditemukan.'], 'html' => ''];
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status laporan berhasil diperbarui.'];

        return ['ok' => true, 'redirect' => '/admin/report/detail?id=' . $id];
    }

    /**
     * @param string[] $errors
     * @return array{found: bool, html: string}
     */
    private function detailPage(int $id, string $uploadDir, array $errors): array
    {
        $row = $this->reports->findById($id);
        if ($row === null) {
            return ['found' => false, 'html' => ''];
        }

        $reporter = $this->users->findById((int) ($row['user_id'] ?? 0));
        $status = (string) ($row['status'] ?? 'Menunggu');
        $photo = isset($row['photo']) && is_string($row['photo']) && $row['photo'] !== '' ? $row['photo'] : null;
        $photoPath = $photo !== null ? rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $photo : null;

        $logRows = $this->logs->findByReportId($id);
        $logs = [];
        foreach ($logRows as $log) {
            $logs[] = [
                'previous_status' => (string) ($log['previous_status'] ?? ''),
                'new_status' => (string) ($log['new_status'] ?? ''),
                'note' => isset($log['note']) && is_string($log['note']) ? $log['note'] : null,
                'admin_name' => (string) ($log['admin_name'] ?? ''),
                'date_label' => self::formatDateTime((string) ($log['created_at'] ?? '')),
            ];
        }

        $data = [
            'report' => [
                'id' => $id,
                'license_plate' => (string) ($row['license_plate'] ?? ''),
                'location' => (string) ($row['location'] ?? ''),
                'category' => (string) ($row['category'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'status' => $status,
                'badge' => ReportController::STATUS_BADGES[$status] ?? ReportController::STATUS_BADGES['Menunggu'],
                'admin_notes' => isset($row['admin_notes']) && is_string($row['admin_notes'])
                    ? $row['admin_notes']
                    : null,
                'date_label' => self::formatDateTime((string) ($row['created_at'] ?? '')),
                'photo_url' => $photoPath !== null && is_file($photoPath) ? '/uploads/' . $photo : null,
            ],
            'reporter' => [
                'name' => (string) ($reporter['name'] ?? '-'),
                'email' => (string) ($reporter['email'] ?? '-'),
            ],
            'logs' => $logs,
            'errors' => $errors,
            'allStatuses' => ReportModel::ALL_STATUSES,
        ];

        $html = View::render('layouts/header.php', ['title' => 'Detail Laporan #' . $id . ' - ParkirTertib'])
            . View::render('admin/detail.php', $data)
            . View::render('layouts/footer.php');

        return ['found' => true, 'html' => $html];
    }

    /**
     * @param string[] $errors
     * @param array{found: bool, html: string} $page
     * @return array<string, mixed>
     */
    private function failResult(array $errors, array $page): array
    {
        return ['ok' => false, 'errors' => $errors, 'html' => $page['html'], 'found' => $page['found']];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function summarize(array $row): array
    {
        $status = (string) ($row['status'] ?? 'Menunggu');

        return [
            'id' => (int) ($row['id'] ?? 0),
            'license_plate' => (string) ($row['license_plate'] ?? ''),
            'location' => (string) ($row['location'] ?? ''),
            'category' => (string) ($row['category'] ?? ''),
            'status' => $status,
            'badge' => ReportController::STATUS_BADGES[$status] ?? ReportController::STATUS_BADGES['Menunggu'],
            'date_label' => self::formatDateTime((string) ($row['created_at'] ?? '')),
        ];
    }

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
     * @param array<string, string> $input
     */
    private function stringOrNull(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
