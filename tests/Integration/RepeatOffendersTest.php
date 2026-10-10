<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AdminController;
use App\Models\ReportModel;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test halaman pelanggar berulang (FR-006).
 */
class RepeatOffendersTest extends TestCase
{
    private PDO $pdo;
    private AdminController $admin;
    private int $userId;

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
            'repeat.pelapor@mhs.unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT)
        );

        $this->admin = new AdminController($this->pdo);
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
    private function seed(array $overrides = []): void
    {
        (new ReportModel($this->pdo))->create(array_merge([
            'user_id' => $this->userId,
            'license_plate' => 'BK0000AA',
            'location' => 'Auditorium',
            'category' => 'Parkir Sembarangan',
            'description' => 'Seed.',
            'photo' => null,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function testRepeatOffendersSortedDescending(): void
    {
        $this->seed(['license_plate' => 'BK6060AA', 'location' => 'Auditorium']);
        $this->seed(['license_plate' => 'BK6060AA', 'location' => 'Auditorium']);
        $this->seed(['license_plate' => 'BK6060AA', 'location' => 'Fakultas MIPA']);
        $this->seed(['license_plate' => 'BK7070BB', 'location' => 'Fakultas Teknik']);
        $this->seed(['license_plate' => 'BK7070BB', 'location' => 'Fakultas Teknik']);
        $this->seed(['license_plate' => 'BK8080CC', 'location' => 'Auditorium']);

        $html = $this->admin->repeatOffenders(null);

        $posA = strpos($html, 'BK6060AA');
        $posB = strpos($html, 'BK7070BB');
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);
        $this->assertLessThan($posB, $posA);
        $this->assertStringContainsString('3x Pelanggaran', $html);
        $this->assertStringContainsString('Auditorium', $html);
        $this->assertStringNotContainsString('BK8080CC', $html);
        $this->assertMatchesRegularExpression('/\d{1,2} \w+ \d{4}, \d{2}:\d{2} WIB/', $html);
    }

    public function testRepeatOffendersSearch(): void
    {
        $this->seed(['license_plate' => 'BK9090DD']);
        $this->seed(['license_plate' => 'BK9090DD']);

        $html = $this->admin->repeatOffenders('9090');
        $this->assertStringContainsString('BK9090DD', $html);

        $empty = $this->admin->repeatOffenders('TIDAKADA');
        $this->assertStringContainsString('Belum ada pelanggar berulang.', $empty);
    }

    public function testRepeatOffendersEmptyState(): void
    {
        $html = $this->admin->repeatOffenders(null);

        $this->assertStringContainsString('Belum ada pelanggar berulang.', $html);
    }
}
