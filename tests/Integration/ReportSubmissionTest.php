<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\ReportController;
use App\Helpers\CSRF;
use App\Models\ReportModel;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test pengiriman laporan + rate limit (FR-002, FR-003).
 */
class ReportSubmissionTest extends TestCase
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
            'Pelapor Uji',
            'pelapor.uji@mhs.unimed.ac.id',
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
     * @return array<string, string>
     */
    private function validInput(): array
    {
        return [
            CSRF::FIELD_NAME => CSRF::generateToken(),
            'license_plate' => 'bk 1234 abc',
            'location' => 'Fakultas Teknik',
            'category' => 'Parkir Sembarangan',
            'description' => 'Motor menghalangi akses keluar parkir.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validFile(): array
    {
        $tmp = $this->uploadDir . '/in_' . bin2hex(random_bytes(4)) . '.png';
        file_put_contents(
            $tmp,
            (string) base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            )
        );

        return [
            'name' => 'bukti.png',
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp),
        ];
    }

    private function seedActiveReports(int $count): void
    {
        $model = new ReportModel($this->pdo);
        $statuses = ['Menunggu', 'Diproses', 'Ditindaklanjuti'];
        for ($i = 0; $i < $count; $i++) {
            $model->create([
                'user_id' => $this->userId,
                'license_plate' => 'BK999' . $i . 'ZZ',
                'location' => 'Auditorium',
                'category' => 'Parkir Sembarangan',
                'description' => 'Laporan seed ke-' . $i,
                'photo' => null,
                'status' => $statuses[$i % 3],
            ]);
        }
    }

    public function testStoreCreatesReportWithNormalizedPlate(): void
    {
        $result = $this->reports->store($this->userId, $this->validInput(), $this->validFile(), $this->uploadDir);

        $this->assertTrue($result['ok']);
        $this->assertSame('/report/history', $result['redirect']);

        $model = new ReportModel($this->pdo);
        $rows = $model->findByUserId($this->userId);
        $this->assertCount(1, $rows);
        $this->assertSame('BK1234ABC', $rows[0]['license_plate']);
        $this->assertSame('Menunggu', $rows[0]['status']);
        $this->assertFileExists($this->uploadDir . '/' . $rows[0]['photo']);
    }

    public function testFourthActiveReportIsRejected(): void
    {
        $this->seedActiveReports(3);

        $result = $this->reports->store($this->userId, $this->validInput(), $this->validFile(), $this->uploadDir);

        $this->assertFalse($result['ok']);
        $this->assertContains('Anda telah mencapai batas maksimum 3 laporan aktif.', $result['errors']);
        $this->assertCount(3, (new ReportModel($this->pdo))->findByUserId($this->userId));
    }

    public function testResolvedReportsDoNotCountAsActive(): void
    {
        $model = new ReportModel($this->pdo);
        for ($i = 0; $i < 3; $i++) {
            $model->create([
                'user_id' => $this->userId,
                'license_plate' => 'BKS' . $i . 'AA',
                'location' => 'Auditorium',
                'category' => 'Lainnya',
                'description' => 'Sudah selesai ' . $i,
                'photo' => null,
                'status' => 'Selesai',
            ]);
        }

        $result = $this->reports->store($this->userId, $this->validInput(), $this->validFile(), $this->uploadDir);

        $this->assertTrue($result['ok']);
    }

    public function testStoreRejectsInvalidLocationAndPlate(): void
    {
        $input = $this->validInput();
        $input['location'] = 'Planet Mars';
        $input['license_plate'] = '!!!';

        $result = $this->reports->store($this->userId, $input, $this->validFile(), $this->uploadDir);

        $this->assertFalse($result['ok']);
        $this->assertContains('Lokasi parkir tidak valid.', $result['errors']);
        $this->assertCount(0, (new ReportModel($this->pdo))->findByUserId($this->userId));
    }

    public function testStoreRejectsMissingPhoto(): void
    {
        $file = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $result = $this->reports->store($this->userId, $this->validInput(), $file, $this->uploadDir);

        $this->assertFalse($result['ok']);
        $this->assertContains('Foto bukti wajib diunggah.', $result['errors']);
    }

    public function testStoreRejectsInvalidCsrf(): void
    {
        $input = $this->validInput();
        $input[CSRF::FIELD_NAME] = 'palsu';

        $result = $this->reports->store($this->userId, $input, $this->validFile(), $this->uploadDir);

        $this->assertFalse($result['ok']);
    }
}
