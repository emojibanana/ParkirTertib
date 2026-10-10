<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Models\ReportLogModel;
use App\Models\ReportModel;
use App\Models\UserModel;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test operasi model laporan & audit (FR-005, FR-006).
 */
class ReportModelTest extends TestCase
{
    private PDO $pdo;
    private ReportModel $reports;
    private int $userId;
    private int $adminId;

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
            'model.pelapor@mhs.unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT)
        );
        $this->adminId = $users->create(
            'Satpam',
            'model.satpam@unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT),
            'admin'
        );

        $this->reports = new ReportModel($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
        $_SESSION = [];
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function seed(array $overrides = []): int
    {
        return $this->reports->create(array_merge([
            'user_id' => $this->userId,
            'license_plate' => 'BK0000AA',
            'location' => 'Auditorium',
            'category' => 'Parkir Sembarangan',
            'description' => 'Seed.',
            'photo' => null,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function testGetSummaryCounts(): void
    {
        $this->seed(['status' => 'Menunggu']);
        $this->seed(['status' => 'Menunggu']);
        $this->seed(['status' => 'Diproses']);
        $this->seed(['status' => 'Selesai']);

        $counts = $this->reports->getSummaryCounts();

        $this->assertSame(4, $counts['total']);
        $this->assertSame(2, $counts['menunggu']);
        $this->assertSame(1, $counts['diproses']);
        $this->assertSame(0, $counts['ditindaklanjuti']);
        $this->assertSame(1, $counts['selesai']);
    }

    public function testGetFilteredReports(): void
    {
        $this->seed(['license_plate' => 'BK1111AA', 'location' => 'Auditorium', 'status' => 'Menunggu']);
        $this->seed(['license_plate' => 'BK2222BB', 'location' => 'Fakultas Teknik', 'status' => 'Selesai']);

        $this->assertCount(2, $this->reports->getFilteredReports(null, null));
        $this->assertCount(1, $this->reports->getFilteredReports('BK1111', null));
        $this->assertCount(1, $this->reports->getFilteredReports('teknik', null));
        $this->assertCount(1, $this->reports->getFilteredReports(null, 'Selesai'));
        $this->assertCount(1, $this->reports->getFilteredReports('BK2222BB', 'Selesai'));
        $this->assertCount(0, $this->reports->getFilteredReports('BK2222BB', 'Menunggu'));
        $this->assertCount(2, $this->reports->getFilteredReports(null, 'StatusAneh'));
    }

    public function testUpdateStatusWritesReportAndAuditLog(): void
    {
        $id = $this->seed(['status' => 'Menunggu']);

        $ok = $this->reports->updateStatus($id, 'Diproses', 'Petugas meluncur.', $this->adminId);

        $this->assertTrue($ok);
        $row = $this->reports->findById($id);
        $this->assertNotNull($row);
        $this->assertSame('Diproses', $row['status']);
        $this->assertSame('Petugas meluncur.', $row['admin_notes']);

        $logs = (new ReportLogModel($this->pdo))->findByReportId($id);
        $this->assertCount(1, $logs);
        $this->assertSame('Menunggu', $logs[0]['previous_status']);
        $this->assertSame('Diproses', $logs[0]['new_status']);
        $this->assertSame('Petugas meluncur.', $logs[0]['note']);
        $this->assertSame($this->adminId, (int) $logs[0]['admin_id']);
        $this->assertSame('Satpam', $logs[0]['admin_name']);
    }

    public function testUpdateStatusRejectsInvalidStatusAndMissingReport(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reports->updateStatus(1, 'Hilang', null, $this->adminId);
    }

    public function testUpdateStatusReturnsFalseForMissingReport(): void
    {
        $this->assertFalse($this->reports->updateStatus(999999, 'Selesai', null, $this->adminId));
    }

    public function testGetRepeatOffenders(): void
    {
        $this->seed(['license_plate' => 'BK7777XX', 'location' => 'Auditorium']);
        $this->seed(['license_plate' => 'BK7777XX', 'location' => 'Auditorium']);
        $this->seed(['license_plate' => 'BK7777XX', 'location' => 'Fakultas MIPA']);
        $this->seed(['license_plate' => 'BK8888YY', 'location' => 'Fakultas Teknik']);
        $this->seed(['license_plate' => 'BK8888YY', 'location' => 'Fakultas Teknik']);
        $this->seed(['license_plate' => 'BK9999ZZ', 'location' => 'Auditorium']);

        $rows = $this->reports->getRepeatOffenders();

        $this->assertCount(2, $rows);
        $this->assertSame('BK7777XX', $rows[0]['license_plate']);
        $this->assertSame(3, (int) $rows[0]['total']);
        $this->assertSame('Auditorium', $rows[0]['top_location']);
        $this->assertSame('BK8888YY', $rows[1]['license_plate']);

        $filtered = $this->reports->getRepeatOffenders('8888');
        $this->assertCount(1, $filtered);
        $this->assertSame('BK8888YY', $filtered[0]['license_plate']);
    }
}
