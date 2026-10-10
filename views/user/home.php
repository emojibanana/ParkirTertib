<?php
// Landing page. Variabel: $role ('user'|'admin'|null).
$role = isset($role) && is_string($role) ? $role : null;
?>
<div class="mx-auto max-w-xl text-center">
    <div class="rounded-2xl bg-emerald-700 px-6 py-10 text-white shadow">
        <p class="text-xs font-medium uppercase tracking-widest text-emerald-200">Universitas Negeri Medan</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">ParkirTertib</h1>
        <p class="mx-auto mt-2 max-w-md text-sm sm:text-base leading-relaxed text-emerald-50">Laporkan parkir sembarangan di kampus. Satpam menindaklanjuti, kampus jadi tertib.</p>
        <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
            <?php if ($role === 'user'): ?>
                <a href="/report/create" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50">+ Buat Laporan</a>
                <a href="/report/history" class="inline-flex items-center justify-center rounded-lg border border-emerald-400 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">Riwayat Laporan</a>
            <?php elseif ($role === 'admin'): ?>
                <a href="/admin" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Buka Dashboard</a>
            <?php else: ?>
                <a href="/register" class="inline-flex items-center justify-center rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Daftar &amp; Lapor</a>
                <a href="/login" class="inline-flex items-center justify-center rounded-lg border border-emerald-400 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800">Masuk</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-6 grid gap-3 text-left sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-lg font-bold text-emerald-700">1</p>
            <h2 class="text-xl font-semibold text-slate-800">Foto &amp; Lapor</h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">Foto pelat dan lokasi, kirim dalam hitungan detik.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-lg font-bold text-emerald-700">2</p>
            <h2 class="text-xl font-semibold text-slate-800">Satpam Tindak</h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">Stiker teguran ditempel di kendaraan pelanggar.</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-lg font-bold text-emerald-700">3</p>
            <h2 class="text-xl font-semibold text-slate-800">Pantau Status</h2>
            <p class="text-sm sm:text-base text-slate-600 leading-relaxed">Lihat progres laporan di riwayat akunmu.</p>
        </div>
    </div>
</div>
