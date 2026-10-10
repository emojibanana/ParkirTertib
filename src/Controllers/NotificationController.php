<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ReportModel;
use PDO;

/**
 * Endpoint JSON untuk polling notifikasi real-time (D-016).
 * Tanpa tabel tambahan: status dibaca dari data laporan yang ada,
 * perubahan dideteksi di sisi klien dengan membandingkan snapshot.
 */
class NotificationController
{
    private ReportModel $reports;

    public function __construct(private PDO $pdo)
    {
        $this->reports = new ReportModel($pdo);
    }

    /**
     * Status terkini seluruh laporan milik user (untuk halaman riwayat).
     *
     * @return array<string, mixed>
     */
    public function userUpdates(int $userId): array
    {
        $items = [];
        foreach ($this->reports->findByUserId($userId) as $row) {
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'license_plate' => (string) ($row['license_plate'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }

        return ['reports' => $items, 'server_time' => date('Y-m-d H:i:s')];
    }

    /**
     * 20 laporan terbaru + ringkasan KPI (untuk dashboard Satpam).
     *
     * @return array<string, mixed>
     */
    public function adminUpdates(): array
    {
        $items = [];
        foreach (array_slice($this->reports->getFilteredReports(null, null), 0, 20) as $row) {
            $items[] = [
                'id' => (int) ($row['id'] ?? 0),
                'license_plate' => (string) ($row['license_plate'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        return [
            'reports' => $items,
            'counts' => $this->reports->getSummaryCounts(),
            'server_time' => date('Y-m-d H:i:s'),
        ];
    }
}
