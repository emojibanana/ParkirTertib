<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AdminController;
use App\Helpers\CSRF;
use App\Models\ReportModel;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test operasional admin: dashboard & update status (FR-005).
 */
class AdminReportTest extends TestCase
{
    private PDO $pdo;
    private AdminController $admin;
    private int $userId;
    private int $adminId;
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
            'Pelapor',
            'admin.pelapor@mhs.unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT)
        );
        $this->adminId = $users->create(
            'Satpam Uji',
            'admin.uji@unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT),
            'admin'
        );

        $this->admin = new AdminController($this->pdo);
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
    private function seed(array $overrides = []): int
    {
        return (new ReportModel($this->pdo))->create(array_merge([
            'user_id' => $this->userId,
            'license_plate' => 'BK0000AA',
            'location' => 'Auditorium',
            'category' => 'Parkir Sembarangan',
            'description' => 'Seed admin.',
            'photo' => null,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function testDashboardShowsSummaryAndTable(): void
    {
        $this->seed(['license_plate' => 'BK1010AA', 'status' => 'Menunggu']);
        $this->seed(['license_plate' => 'BK2020BB', 'status' => 'Selesai']);

        $html = $this->admin->dashboard(null, null);

        $this->assertStringContainsString('Dashboard Laporan', $html);
        $this->assertStringContainsString('BK1010AA', $html);
        $this->assertStringContainsString('BK2020BB', $html);
        $this->assertStringContainsString('/admin/report/detail?id=', $html);
    }

    public function testDashboardFiltersBySearchAndStatus(): void
    {
        $this->seed(['license_plate' => 'BK3030CC', 'location' => 'Auditorium', 'status' => 'Menunggu']);
        $this->seed(['license_plate' => 'BK4040DD', 'location' => 'Fakultas Teknik', 'status' => 'Selesai']);

        $html = $this->admin->dashboard('BK3030', null);
        $this->assertStringContainsString('BK3030CC', $html);
        $this->assertStringNotContainsString('BK4040DD', $html);

        $html = $this->admin->dashboard(null, 'Selesai');
        $this->assertStringContainsString('BK4040DD', $html);
        $this->assertStringNotContainsString('BK3030CC', $html);
    }

    public function testShowDetailRendersReportAndReporter(): void
    {
        $id = $this->seed(['license_plate' => 'BK5050EE', 'description' => 'Motor melintang.']);

        $page = $this->admin->showDetail($id, $this->uploadDir);

        $this->assertTrue($page['found']);
        $this->assertStringContainsString('BK5050EE', $page['html']);
        $this->assertStringContainsString('Motor melintang.', $page['html']);
        $this->assertStringContainsString('Pelapor', $page['html']);
        $this->assertStringContainsString('Simpan Perubahan Status', $page['html']);
    }

    public function testShowDetailForMissingReport(): void
    {
        $page = $this->admin->showDetail(999999, $this->uploadDir);

        $this->assertFalse($page['found']);
    }

    public function testUpdateStatusCreatesLogAndRedirects(): void
    {
        $id = $this->seed(['status' => 'Menunggu']);
        $_SESSION = ['user_id' => $this->adminId, 'user_role' => 'admin'];

        $result = $this->admin->updateStatus($id, [
            CSRF::FIELD_NAME => CSRF::generateToken(),
            'new_status' => 'Diproses',
            'note' => 'Petugas menuju lokasi.',
        ], $this->adminId, $this->uploadDir);

        $this->assertTrue($result['ok']);
        $this->assertSame('/admin/report/detail?id=' . $id, $result['redirect']);

        $row = (new ReportModel($this->pdo))->findById($id);
        $this->assertNotNull($row);
        $this->assertSame('Diproses', $row['status']);

        $page = $this->admin->showDetail($id, $this->uploadDir);
        $this->assertStringContainsString('Menunggu &rarr; Diproses', $page['html']);
        $this->assertStringContainsString('Petugas menuju lokasi.', $page['html']);
        $this->assertStringContainsString('Satpam Uji', $page['html']);
    }

    public function testUpdateStatusRejectsInvalidStatusAndCsrf(): void
    {
        $id = $this->seed(['status' => 'Menunggu']);

        $bad = $this->admin->updateStatus($id, [
            CSRF::FIELD_NAME => CSRF::generateToken(),
            'new_status' => 'StatusNgawur',
            'note' => '',
        ], $this->adminId, $this->uploadDir);
        $this->assertFalse($bad['ok']);
        $this->assertContains('Status baru tidak valid.', $bad['errors']);

        $csrf = $this->admin->updateStatus($id, [
            CSRF::FIELD_NAME => 'palsu',
            'new_status' => 'Selesai',
            'note' => '',
        ], $this->adminId, $this->uploadDir);
        $this->assertFalse($csrf['ok']);

        $row = (new ReportModel($this->pdo))->findById($id);
        $this->assertNotNull($row);
        $this->assertSame('Menunggu', $row['status']);
    }
}
