<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\CSRF;
use App\Helpers\View;
use App\Models\UserModel;
use PDO;

/**
 * Autentikasi & manajemen sesi (FR-001).
 * Method login()/register() mengembalikan array hasil agar mudah diuji;
 * Front Controller yang menerjemahkannya menjadi redirect/HTML.
 */
class AuthController
{
    private UserModel $users;

    public function __construct(private PDO $pdo)
    {
        $this->users = new UserModel($pdo);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function showLogin(array $data = []): string
    {
        return $this->page('Masuk - ParkirTertib', 'auth/login.php', $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function showRegister(array $data = []): string
    {
        return $this->page('Daftar - ParkirTertib', 'auth/register.php', $data);
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    public function register(array $input): array
    {
        if (!CSRF::verifyToken($this->stringOrNull($input, CSRF::FIELD_NAME))) {
            return $this->fail('register', ['Token keamanan tidak valid. Silakan muat ulang halaman.'], $input);
        }

        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        $errors = [];
        if ($name === '') {
            $errors[] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'Nama lengkap maksimal 100 karakter.';
        }

        if ($email === '') {
            $errors[] = 'Email wajib diisi.';
        } elseif (mb_strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Format email tidak valid.';
        } elseif ($this->users->findByEmail($email) !== null) {
            $errors[] = 'Email sudah terdaftar. Silakan masuk.';
        }

        if ($password === '') {
            $errors[] = 'Password wajib diisi.';
        } elseif (mb_strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }

        if ($errors !== []) {
            return $this->fail('register', $errors, $input);
        }

        // Role SELALU 'user': input role dari user diabaikan untuk mencegah eskalasi hak akses.
        $id = $this->users->create($name, $email, password_hash($password, PASSWORD_BCRYPT), 'user');
        $this->startSession($id, 'user', $name);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Pendaftaran berhasil. Selamat datang di ParkirTertib!',
        ];

        return ['ok' => true, 'redirect' => '/report/history'];
    }

    /**
     * @param array<string, string> $input
     * @return array<string, mixed>
     */
    public function login(array $input): array
    {
        if (!CSRF::verifyToken($this->stringOrNull($input, CSRF::FIELD_NAME))) {
            return $this->fail('login', ['Token keamanan tidak valid. Silakan muat ulang halaman.'], $input);
        }

        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->fail('login', ['Email dan password wajib diisi.'], $input);
        }

        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, (string) ($user['password'] ?? ''))) {
            return $this->fail('login', ['Email atau password salah.'], $input);
        }

        $role = (string) ($user['role'] ?? 'user');
        $this->startSession((int) ($user['id'] ?? 0), $role, (string) ($user['name'] ?? ''));

        return ['ok' => true, 'redirect' => $role === 'admin' ? '/admin' : '/report/history'];
    }

    public function logout(): string
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anda telah keluar. Sampai jumpa!'];

        return '/login';
    }

    /**
     * @param array<string, string> $input
     * @param string[] $errors
     * @return array<string, mixed>
     */
    private function fail(string $form, array $errors, array $input): array
    {
        $old = ['name' => $input['name'] ?? '', 'email' => $input['email'] ?? ''];
        $html = $form === 'register'
            ? $this->showRegister(['errors' => $errors, 'old' => $old])
            : $this->showLogin(['errors' => $errors, 'old' => $old]);

        return ['ok' => false, 'errors' => $errors, 'html' => $html];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function page(string $title, string $template, array $data): string
    {
        return View::render('layouts/header.php', ['title' => $title])
            . View::render($template, $data)
            . View::render('layouts/footer.php');
    }

    private function startSession(int $id, string $role, string $name): void
    {
        $_SESSION['user_id'] = $id;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_name'] = $name;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /**
     * @param array<string, string> $input
     */
    private function stringOrNull(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
