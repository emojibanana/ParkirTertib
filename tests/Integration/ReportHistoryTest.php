<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\ReportController;
use App\Models\ReportModel;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test riwayat laporan user (FR-004).
 */
class ReportHistoryTest extends TestCase
{
    private PDO $pdo;
    private ReportController $reports;
    private int $userId;
    private string $uploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306;dbname=parkir_tertib_test;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        $this->pdo->beginTransaction();

        $users = new UserModel($this->pdo);
        $this->userId = $users->create(
            'Melihat Riwayat',
            'riwayat.uji@mhs.unimed.ac.id',
            password_hash('Rahasia123!', PASSWORD_BCRYPT)
        );

        $this->reports = new ReportController($this->pdo);
        $this->uploadDir = dirname(__DIR__) . '/tmp/uploads';
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
        foreach (glob($this->uploadDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function seedReport(array $overrides = []): void
    {
        $model = new ReportModel($this->pdo);
        $model->create(array_merge([
            'user_id' => $this->userId,
            'license_plate' => 'BK1111AA',
            'location' => 'Auditorium',
            'category' => 'Parkir Sembarangan',
            'description' => 'Deskripsi uji.',
            'photo' => null,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function testEmptyHistoryShowsEmptyState(): void
    {
        $html = $this->reports->history($this->userId, $this->uploadDir);

        $this->assertStringContainsString('Belum Ada Laporan', $html);
        $this->assertStringContainsString('Anda belum pernah mengirimkan laporan', $html);
    }

    public function testHistoryShowsOwnReportsWithBadgesAndNotes(): void
    {
        $this->seedReport([
            'license_plate' => 'BK2222BB',
            'status' => 'Ditindaklanjuti',
            'admin_notes' => 'Stiker teguran telah ditempelkan di lokasi.',
        ]);

        $html = $this->reports->history($this->userId, $this->uploadDir);

        $this->assertStringContainsString('BK2222BB', $html);
        $this->assertStringContainsString('Ditindaklanjuti', $html);
        $this->assertStringContainsString('bg-indigo-100', $html);
        $this->assertStringContainsString('Stiker teguran telah ditempelkan di lokasi.', $html);
        $this->assertMatchesRegularExpression('/\d{1,2} \w+ \d{4}, \d{2}:\d{2} WIB/', $html);
        $this->assertStringNotContainsString('Belum Ada Laporan', $html);
    }

    public function testHistoryShowsPhotoThumbnailWhenFileExists(): void
    {
        file_put_contents($this->uploadDir . '/ada.png', 'palsu-tapi-ada');
        $this->seedReport(['photo' => 'ada.png']);

        $html = $this->reports->history($this->userId, $this->uploadDir);

        $this->assertStringContainsString('/uploads/ada.png', $html);
    }

    public function testHistoryFallsBackWhenPhotoMissing(): void
    {
        $this->seedReport(['photo' => 'sudah-dihapus.png']);

        $html = $this->reports->history($this->userId, $this->uploadDir);

        $this->assertStringContainsString('Foto tidak tersedia', $html);
        $this->assertStringNotContainsString('/uploads/sudah-dihapus.png', $html);
    }

    public function testHistoryHidesOtherUsersReports(): void
    {
        $users = new UserModel($this->pdo);
        $otherId = $users->create('Orang Lain', 'lain@mhs.unimed.ac.id', password_hash('x', PASSWORD_BCRYPT));
        $this->seedReport(['user_id' => $otherId, 'license_plate' => 'BK9999ZZ']);

        $html = $this->reports->history($this->userId, $this->uploadDir);

        $this->assertStringNotContainsString('BK9999ZZ', $html);
        $this->assertStringContainsString('Belum Ada Laporan', $html);
    }
}
