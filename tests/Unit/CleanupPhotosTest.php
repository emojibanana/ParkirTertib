<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\AdminController;
use App\Helpers\CSRF;
use App\Helpers\PhotoCleaner;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Unit test cleanup foto (FR-007): helper + aksi dashboard Satpam.
 * Berjalan di atas skema uji terisolasi dengan transaksi rollback.
 */
class CleanupPhotosTest extends TestCase
{
    private PDO $pdo;
    private string $uploadDir = '';

    protected function setUp(): void
    {
        parent::setUp();

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
        parent::tearDown();
    }

    private function seedReport(string $status, ?string $photo, int $daysAgo): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO `report`'
            . ' (`user_id`, `license_plate`, `location`, `category`, `description`, `photo`, `status`)'
            . ' VALUES (2, :plate, :loc, :cat, :desc, :photo, :status)'
        );
        $stmt->bindValue(':plate', 'BKCL' . $daysAgo . 'N' . $status, PDO::PARAM_STR);
        $stmt->bindValue(':loc', 'Auditorium', PDO::PARAM_STR);
        $stmt->bindValue(':cat', 'Parkir Sembarangan', PDO::PARAM_STR);
        $stmt->bindValue(':desc', 'Seed cleanup.', PDO::PARAM_STR);
        if ($photo === null) {
            $stmt->bindValue(':photo', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':photo', $photo, PDO::PARAM_STR);
        }
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        $id = (int) $this->pdo->lastInsertId();
        $this->pdo->exec(
            'UPDATE `report` SET `updated_at` = (NOW() - INTERVAL ' . $daysAgo . ' DAY) WHERE `id` = ' . $id
        );

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findReport(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `report` WHERE `id` = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function testCleanupDeletesOnlyOldResolvedPhotos(): void
    {
        file_put_contents($this->uploadDir . '/tua.png', 'lama');
        file_put_contents($this->uploadDir . '/baru.png', 'baru');
        file_put_contents($this->uploadDir . '/aktif.png', 'aktif');

        $oldDone = $this->seedReport('Selesai', 'tua.png', 8);
        $recentDone = $this->seedReport('Selesai', 'baru.png', 2);
        $oldActive = $this->seedReport('Menunggu', 'aktif.png', 30);
        $oldDoneMissing = $this->seedReport('Selesai', 'tak-ada.png', 10);

        $stats = PhotoCleaner::run($this->pdo, $this->uploadDir);

        $this->assertSame(2, $stats['checked']);
        $this->assertSame(1, $stats['deleted']);
        $this->assertSame(1, $stats['missing']);
        $this->assertSame(0, $stats['failed']);

        $this->assertFileDoesNotExist($this->uploadDir . '/tua.png');
        $this->assertNull($this->findReport($oldDone)['photo']);

        $this->assertFileExists($this->uploadDir . '/baru.png');
        $this->assertSame('baru.png', $this->findReport($recentDone)['photo']);

        $this->assertFileExists($this->uploadDir . '/aktif.png');
        $this->assertSame('aktif.png', $this->findReport($oldActive)['photo']);

        $this->assertNull($this->findReport($oldDoneMissing)['photo']);
    }

    public function testAdminCleanupActionDeletesAndFlashesSummary(): void
    {
        $_SESSION = [];
        file_put_contents($this->uploadDir . '/tua2.png', 'lama');
        $id = $this->seedReport('Selesai', 'tua2.png', 9);

        $admin = new AdminController($this->pdo);
        $result = $admin->cleanupPhotos(
            [CSRF::FIELD_NAME => CSRF::generateToken()],
            $this->uploadDir
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('/admin', $result['redirect']);
        $this->assertFileDoesNotExist($this->uploadDir . '/tua2.png');
        $this->assertNull($this->findReport($id)['photo']);
        $this->assertStringContainsString(
            '1 file dihapus',
            (string) ($_SESSION['flash']['message'] ?? '')
        );
    }

    public function testAdminCleanupActionRejectsInvalidCsrf(): void
    {
        $_SESSION = [];

        $admin = new AdminController($this->pdo);
        $result = $admin->cleanupPhotos([CSRF::FIELD_NAME => 'palsu'], $this->uploadDir);

        $this->assertFalse($result['ok']);
        $this->assertSame('/admin', $result['redirect']);
    }
}
