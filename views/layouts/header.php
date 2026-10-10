<?php
// Layout: header bersama. Variabel: $title (string, opsional).
// Flash: $_SESSION['flash'] = ['type' => 'success|error|warning', 'message' => '...'].
$title = isset($title) ? (string) $title : 'ParkirTertib UNIMED';
$role = $_SESSION['user_role'] ?? null;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$flashStyles = [
    'success' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
    'error' => 'bg-rose-50 text-rose-800 border border-rose-200',
    'warning' => 'bg-amber-50 text-amber-800 border border-amber-200',
];

$cssPath = dirname(__DIR__, 2) . '/public/assets/css/style.css';
$cssMtime = filemtime($cssPath);
$cssVer = $cssMtime === false ? 1 : (int) $cssMtime;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= $cssVer ?>">
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
<header class="bg-emerald-700 text-white shadow">
    <nav class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <a href="/" class="flex items-center gap-2 font-bold">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white font-bold text-emerald-700">P</span>
            <span>ParkirTertib UNIMED</span>
        </a>
        <div class="hidden items-center gap-2 sm:flex">
            <?php if ($role === 'admin'): ?>
                <a href="/admin" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Dashboard</a>
                <a href="/admin/repeat-offenders" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Pelanggar Berulang</a>
                <a href="/logout" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Keluar</a>
            <?php elseif ($role === 'user'): ?>
                <a href="/report/create" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Buat Laporan</a>
                <a href="/report/history" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Riwayat</a>
                <a href="/logout" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Keluar</a>
            <?php else: ?>
                <a href="/login" class="rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Masuk</a>
                <a href="/register" class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Daftar</a>
            <?php endif; ?>
        </div>
        <button id="nav-toggle" type="button" class="rounded-lg p-2 hover:bg-emerald-800 sm:hidden" aria-label="Buka menu navigasi">☰</button>
    </nav>
    <div id="nav-drawer" class="hidden border-t border-emerald-600 px-4 py-2 sm:hidden">
        <?php if ($role === 'admin'): ?>
            <a href="/admin" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Dashboard</a>
            <a href="/admin/repeat-offenders" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Pelanggar Berulang</a>
            <a href="/logout" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Keluar</a>
        <?php elseif ($role === 'user'): ?>
            <a href="/report/create" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Buat Laporan</a>
            <a href="/report/history" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Riwayat</a>
            <a href="/logout" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Keluar</a>
        <?php else: ?>
            <a href="/login" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Masuk</a>
            <a href="/register" class="block rounded-lg px-3 py-2 text-sm hover:bg-emerald-800">Daftar</a>
        <?php endif; ?>
    </div>
</header>
<main class="mx-auto w-full max-w-5xl px-4 py-6">
    <?php if (is_array($flash) && isset($flash['message'])): ?>
        <?php $flashClass = $flashStyles[$flash['type'] ?? ''] ?? $flashStyles['warning']; ?>
        <div class="mb-4 rounded-lg px-4 py-3 text-sm <?= $flashClass ?>" role="alert">
            <?= htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
