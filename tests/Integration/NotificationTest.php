<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\NotificationController;
use App\Models\ReportModel;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test endpoint polling notifikasi (D-016).
 */
class NotificationTest extends TestCase
{
    private PDO $pdo;
    private NotificationController $api;
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
            'Notifikasi',
            'notif.uji@mhs.unimed.ac.id',
            password_hash('x', PASSWORD_BCRYPT)
        );

        $this->api = new NotificationController($this->pdo);
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
        return (new ReportModel($this->pdo))->create(array_merge([
            'user_id' => $this->userId,
            'license_plate' => 'BKNOTIF1',
            'location' => 'Auditorium',
            'category' => 'Parkir Sembarangan',
            'description' => 'Seed.',
            'photo' => null,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function testUserUpdatesReflectsStatusChange(): void
    {
        $id = $this->seed();

        $before = $this->api->userUpdates($this->userId);
        $this->assertSame('Menunggu', $before['reports'][0]['status']);

        (new ReportModel($this->pdo))->updateStatus($id, 'Diproses', null, 1);

        $after = $this->api->userUpdates($this->userId);
        $this->assertSame('Diproses', $after['reports'][0]['status']);
        $this->assertArrayHasKey('server_time', $after);
    }

    public function testUserUpdatesHidesOtherUsersReports(): void
    {
        $users = new UserModel($this->pdo);
        $otherId = $users->create('Lain', 'notif.lain@mhs.unimed.ac.id', password_hash('x', PASSWORD_BCRYPT));
        $this->seed(['user_id' => $otherId, 'license_plate' => 'BKRAHASIA']);

        $data = $this->api->userUpdates($this->userId);

        $this->assertSame([], $data['reports']);
    }

    public function testAdminUpdatesReturnsCountsAndNewest(): void
    {
        $this->seed(['license_plate' => 'BKAD1', 'status' => 'Menunggu']);
        $this->seed(['license_plate' => 'BKAD2', 'status' => 'Selesai']);

        $data = $this->api->adminUpdates();

        $this->assertSame(2, $data['counts']['total']);
        $this->assertCount(2, $data['reports']);
        $this->assertSame('BKAD2', $data['reports'][0]['license_plate']);
        $this->assertArrayHasKey('server_time', $data);
    }
}
