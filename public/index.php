<?php

declare(strict_types=1);

/**
 * Front Controller (ADR-01) - satu-satunya entry point HTTP.
 * Semua request non-file dirutekan ke sini via public/.htaccess.
 */

$isHttps = ($_SERVER['HTTPS'] ?? '') === 'on'
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Lax',
]);
session_start();

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Controllers\AuthController;
use App\Controllers\ReportController;
use App\Controllers\AdminController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\AdminMiddleware;

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') === '' ? '/' : rtrim($uri, '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

/**
 * @param array<string, mixed> $result
 */
function respondFormResult(array $result): void
{
    if (isset($result['redirect']) && is_string($result['redirect'])) {
        header('Location: ' . $result['redirect']);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo (string) ($result['html'] ?? '');
    exit;
}

if ($uri === '/login') {
    $auth = new AuthController(createPDOConnection());
    if ($method === 'POST') {
        /** @var array<string, string> $post */
        $post = $_POST;
        respondFormResult($auth->login($post));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $auth->showLogin();
    exit;
}

if ($uri === '/register') {
    $auth = new AuthController(createPDOConnection());
    if ($method === 'POST') {
        /** @var array<string, string> $post */
        $post = $_POST;
        respondFormResult($auth->register($post));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $auth->showRegister();
    exit;
}

if ($uri === '/logout' && $method === 'GET') {
    $auth = new AuthController(createPDOConnection());
    header('Location: ' . $auth->logout());
    exit;
}

if ($uri === '/report/create') {
    AuthMiddleware::handle();
    $reports = new ReportController(createPDOConnection());
    $userId = (int) $_SESSION['user_id'];
    if ($method === 'POST') {
        /** @var array<string, string> $post */
        $post = $_POST;
        /** @var array<string, mixed> $photo */
        $photo = $_FILES['photo'] ?? [];
        $uploadDir = dirname(__DIR__) . '/' . env('UPLOAD_DIR', 'public/uploads');
        respondFormResult($reports->store($userId, $post, $photo, $uploadDir));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $reports->create($userId);
    exit;
}

if ($uri === '/report/history' && $method === 'GET') {
    AuthMiddleware::handle();
    $reports = new ReportController(createPDOConnection());
    $uploadDir = dirname(__DIR__) . '/' . env('UPLOAD_DIR', 'public/uploads');
    header('Content-Type: text/html; charset=utf-8');
    echo $reports->history((int) $_SESSION['user_id'], $uploadDir);
    exit;
}

if ($uri === '/admin' && $method === 'GET') {
    AuthMiddleware::handle();
    AdminMiddleware::handle();
    $admin = new AdminController(createPDOConnection());
    /** @var string|null $search */
    $search = isset($_GET['search']) && is_string($_GET['search']) ? $_GET['search'] : null;
    /** @var string|null $status */
    $status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : null;
    header('Content-Type: text/html; charset=utf-8');
    echo $admin->dashboard($search, $status);
    exit;
}

if ($uri === '/admin/repeat-offenders' && $method === 'GET') {
    AuthMiddleware::handle();
    AdminMiddleware::handle();
    $admin = new AdminController(createPDOConnection());
    /** @var string|null $search */
    $search = isset($_GET['search']) && is_string($_GET['search']) ? $_GET['search'] : null;
    header('Content-Type: text/html; charset=utf-8');
    echo $admin->repeatOffenders($search);
    exit;
}

if ($uri === '/admin/report/detail') {
    AuthMiddleware::handle();
    AdminMiddleware::handle();
    $admin = new AdminController(createPDOConnection());
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $uploadDir = dirname(__DIR__) . '/' . env('UPLOAD_DIR', 'public/uploads');
    if ($method === 'POST') {
        /** @var array<string, string> $post */
        $post = $_POST;
        $result = $admin->updateStatus($id, $post, (int) $_SESSION['user_id'], $uploadDir);
        if (isset($result['redirect']) && is_string($result['redirect'])) {
            header('Location: ' . $result['redirect']);
            exit;
        }
        if (isset($result['not_found'])) {
            http_response_code(404);
            echo 'Laporan tidak ditemukan.';
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        echo (string) ($result['html'] ?? '');
        exit;
    }
    $page = $admin->showDetail($id, $uploadDir);
    if (!$page['found']) {
        http_response_code(404);
        echo 'Laporan tidak ditemukan.';
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo $page['html'];
    exit;
}

if ($uri === '/' && $method === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    echo (new ReportController(createPDOConnection()))->home();
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
require dirname(__DIR__) . '/views/errors/404.php';
