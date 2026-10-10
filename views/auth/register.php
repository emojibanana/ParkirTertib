<?php
// Fragmen konten registrasi. Variabel: $errors (string[]), $old (array).
use App\Helpers\CSRF;
use App\Helpers\Sanitizer;

$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
?>
<div class="mx-auto max-w-md">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Daftar Akun</h1>
        <p class="mt-1 text-sm sm:text-base text-slate-600 leading-relaxed">Buat akun pelapor ParkirTertib UNIMED.</p>

        <?php if ($errors !== []): ?>
            <div class="mt-4 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-4 py-3 text-sm" role="alert">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= Sanitizer::escape((string) $error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/register" class="mt-4 space-y-4">
            <?= CSRF::field() ?>
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                <input id="name" name="name" type="text" required maxlength="100" autocomplete="name"
                    value="<?= Sanitizer::escape((string) ($old['name'] ?? '')) ?>"
                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input id="email" name="email" type="email" required maxlength="150" autocomplete="email"
                    value="<?= Sanitizer::escape((string) ($old['email'] ?? '')) ?>"
                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password (minimal 8 karakter)</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            </div>
            <button type="submit"
                class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all cursor-pointer">Daftar Akun</button>
        </form>

        <p class="mt-4 text-sm text-slate-600">Sudah punya akun? <a href="/login" class="font-medium text-emerald-700 hover:underline">Masuk</a></p>
    </div>
</div>
