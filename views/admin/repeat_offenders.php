<?php
// Pelanggar berulang (desktop). Variabel: $offenders, $search.
use App\Helpers\Sanitizer;

$offenders = isset($offenders) && is_array($offenders) ? $offenders : [];
$search = isset($search) ? (string) $search : '';
?>
<div class="lg:grid lg:grid-cols-12 lg:gap-6">
    <aside class="mb-6 lg:col-span-3 lg:mb-0">
        <nav class="rounded-xl bg-slate-900 p-4 text-white lg:sticky lg:top-6">
            <p class="px-2 text-xs font-medium uppercase tracking-widest text-slate-400">Satpam</p>
            <a href="/admin" class="mt-2 block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Dashboard</a>
            <a href="/admin/repeat-offenders" class="block rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium">Pelanggar Berulang</a>
            <a href="/logout" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Keluar</a>
        </nav>
    </aside>

    <section class="lg:col-span-9">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Pelanggar Berulang</h1>
        <p class="mt-1 text-sm sm:text-base text-slate-600 leading-relaxed">Kendaraan dengan 2 laporan atau lebih, terurut pelanggaran terbanyak.</p>

        <form method="get" action="/admin/repeat-offenders" class="mt-4 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <input name="search" type="text" value="<?= Sanitizer::escape($search) ?>" placeholder="Cari nomor pelat…"
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            <button type="submit"
                class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm rounded-lg shadow-sm transition-all cursor-pointer">Cari</button>
        </form>

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Nomor Pelat</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Sering di</th>
                        <th class="px-4 py-3">Terakhir</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($offenders === []): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada pelanggar berulang.</td></tr>
                    <?php else: ?>
                        <?php foreach ($offenders as $offender): ?>
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                                <td class="px-4 py-3"><span class="w-max rounded bg-slate-100 px-2 py-1 font-mono text-base font-bold text-slate-900"><?= Sanitizer::escape((string) $offender['license_plate']) ?></span></td>
                                <td class="px-4 py-3"><span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-800"><?= (int) $offender['total'] ?>x Pelanggaran</span></td>
                                <td class="px-4 py-3"><?= Sanitizer::escape((string) $offender['top_location']) ?></td>
                                <td class="px-4 py-3 text-xs text-slate-500"><?= Sanitizer::escape((string) $offender['last_label']) ?></td>
                                <td class="px-4 py-3"><a href="/admin?search=<?= urlencode((string) $offender['license_plate']) ?>" class="font-medium text-emerald-700 hover:underline">Lihat Laporan</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
