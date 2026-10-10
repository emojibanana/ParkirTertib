<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\FileUpload;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit test validasi upload foto (NFR-005, FR-003).
 * Memakai file fixture biner asli agar finfo_file() mendeteksi MIME yang benar.
 */
class FileUploadTest extends TestCase
{
    private string $tmpDir = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = dirname(__DIR__) . '/tmp/uploads';
        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function makeUploadFile(string $name, string $content): string
    {
        $path = $this->tmpDir . '/src_' . $name;
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function fileArray(string $originalName, string $tmpPath, int $error = UPLOAD_ERR_OK): array
    {
        return [
            'name' => $originalName,
            'type' => 'application/octet-stream',
            'tmp_name' => $tmpPath,
            'error' => $error,
            'size' => filesize($tmpPath),
        ];
    }

    private function pngBytes(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
    }

    public function testValidPngUploadStoresRandomFilename(): void
    {
        $tmp = $this->makeUploadFile('bukti.png', $this->pngBytes());

        $filename = FileUpload::uploadImage($this->fileArray('bukti.png', $tmp), $this->tmpDir);

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}\.png$/', $filename);
        $this->assertFileExists($this->tmpDir . '/' . $filename);
    }

    public function testUppercaseExtensionIsNormalized(): void
    {
        $tmp = $this->makeUploadFile('foto.JPG', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00" . str_repeat("\x00", 64));

        $filename = FileUpload::uploadImage($this->fileArray('foto.JPG', $tmp), $this->tmpDir);

        $this->assertStringEndsWith('.jpg', $filename);
    }

    public function testFilenamesAreRandomized(): void
    {
        $first = FileUpload::uploadImage(
            $this->fileArray('a.png', $this->makeUploadFile('a.png', $this->pngBytes())),
            $this->tmpDir
        );
        $second = FileUpload::uploadImage(
            $this->fileArray('a.png', $this->makeUploadFile('a.png', $this->pngBytes())),
            $this->tmpDir
        );

        $this->assertNotSame($first, $second);
    }

    public function testRejectsOversizedFile(): void
    {
        $tmp = $this->makeUploadFile('besar.png', $this->pngBytes());
        $file = $this->fileArray('besar.png', $tmp);
        $file['size'] = FileUpload::MAX_SIZE + 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('5MB');
        FileUpload::uploadImage($file, $this->tmpDir);
    }

    public function testRejectsDisallowedExtension(): void
    {
        $tmp = $this->makeUploadFile('dokumen.pdf', $this->pngBytes());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JPG, JPEG, PNG, atau WebP');
        FileUpload::uploadImage($this->fileArray('dokumen.pdf', $tmp), $this->tmpDir);
    }

    public function testRejectsMimeMismatch(): void
    {
        $tmp = $this->makeUploadFile('palsu.jpg', 'ini file teks, bukan gambar');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak sesuai');
        FileUpload::uploadImage($this->fileArray('palsu.jpg', $tmp), $this->tmpDir);
    }

    public function testRejectsMissingFile(): void
    {
        $file = [
            'name' => 'kosong.png',
            'type' => 'application/octet-stream',
            'tmp_name' => $this->tmpDir . '/tidak-ada.png',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $this->expectException(InvalidArgumentException::class);
        FileUpload::uploadImage($file, $this->tmpDir);
    }
}
