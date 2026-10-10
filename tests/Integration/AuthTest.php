<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\AuthController;
use App\Helpers\CSRF;
use App\Models\UserModel;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Integration test autentikasi (FR-001) memakai skema uji terisolasi
 * `parkir_tertib_test` yang di-rollback setiap test.
 */
class AuthTest extends TestCase
{
    private PDO $pdo;
    private AuthController $auth;

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
        $this->auth = new AuthController($this->pdo);
    }

    protected function tearDown(): void
    {
        $this->pdo->rollBack();
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
            'name' => 'Budi Pelapor',
            'email' => 'budi@mhs.unimed.ac.id',
            'password' => 'Rahasia123!',
        ];
    }

    public function testRegisterCreatesUserWithBcryptAndSession(): void
    {
        $result = $this->auth->register($this->validInput());

        $this->assertTrue($result['ok']);
        $this->assertSame('/report/history', $result['redirect']);

        $users = new UserModel($this->pdo);
        $row = $users->findByEmail('budi@mhs.unimed.ac.id');
        $this->assertNotNull($row);
        $this->assertSame('user', $row['role']);
        $this->assertTrue(password_verify('Rahasia123!', (string) $row['password']));

        $this->assertSame((int) $row['id'], $_SESSION['user_id']);
        $this->assertSame('user', $_SESSION['user_role']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->assertTrue($this->auth->register($this->validInput())['ok']);

        $second = $this->validInput();
        $second['name'] = 'Nama Lain';
        $result = $this->auth->register($second);

        $this->assertFalse($result['ok']);
        $this->assertContains('Email sudah terdaftar. Silakan masuk.', $result['errors']);
    }

    public function testRegisterRejectsInvalidInput(): void
    {
        $bad = $this->validInput();
        $bad['email'] = 'bukan-email';
        $bad['password'] = 'pendek';
        $bad['name'] = '';
        $result = $this->auth->register($bad);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testRegisterIgnoresRoleEscalation(): void
    {
        $input = $this->validInput();
        $input['role'] = 'admin';

        $result = $this->auth->register($input);

        $this->assertTrue($result['ok']);
        $users = new UserModel($this->pdo);
        $row = $users->findByEmail('budi@mhs.unimed.ac.id');
        $this->assertNotNull($row);
        $this->assertSame('user', $row['role']);
        $this->assertSame('user', $_SESSION['user_role']);
    }

    public function testRegisterRejectsInvalidCsrf(): void
    {
        $input = $this->validInput();
        $input[CSRF::FIELD_NAME] = 'token-palsu';

        $result = $this->auth->register($input);

        $this->assertFalse($result['ok']);
        $this->assertContains(
            'Token keamanan tidak valid. Silakan muat ulang halaman.',
            $result['errors']
        );
    }

    public function testLoginSuccessSetsSessionAndRoleRedirect(): void
    {
        $input = $this->validInput();
        unset($input['name']);
        $result = $this->auth->login($input);

        $this->assertFalse($result['ok']);

        $this->assertTrue($this->auth->register($this->validInput())['ok']);
        $_SESSION = [];

        $login = $this->validInput();
        unset($login['name']);
        $result = $this->auth->login($login);

        $this->assertTrue($result['ok']);
        $this->assertSame('/report/history', $result['redirect']);
        $this->assertSame('user', $_SESSION['user_role']);
    }

    public function testLoginAsSeedAdminRedirectsToAdmin(): void
    {
        $result = $this->auth->login([
            CSRF::FIELD_NAME => CSRF::generateToken(),
            'email' => 'satpam@unimed.ac.id',
            'password' => 'Password123!',
        ]);

        $this->assertTrue($result['ok']);
        $this->assertSame('/admin', $result['redirect']);
        $this->assertSame('admin', $_SESSION['user_role']);
    }

    public function testLoginRejectsWrongPassword(): void
    {
        $this->assertTrue($this->auth->register($this->validInput())['ok']);
        $_SESSION = [];

        $login = $this->validInput();
        unset($login['name']);
        $login['password'] = 'SalahPassword1!';
        $result = $this->auth->login($login);

        $this->assertFalse($result['ok']);
        $this->assertContains('Email atau password salah.', $result['errors']);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }

    public function testLogoutClearsSession(): void
    {
        $this->assertTrue($this->auth->register($this->validInput())['ok']);
        $this->assertArrayHasKey('user_id', $_SESSION);

        $redirect = $this->auth->logout();

        $this->assertSame('/login', $redirect);
        $this->assertArrayNotHasKey('user_id', $_SESSION);
    }
}
