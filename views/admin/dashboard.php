<?php
// Dashboard Satpam (desktop). Variabel: $counts, $reports, $search, $status, $allStatuses.
use App\Helpers\CSRF;
use App\Helpers\Sanitizer;

$counts = isset($counts) && is_array($counts) ? $counts : [];
$reports = isset($reports) && is_array($reports) ? $reports : [];
$search = isset($search) ? (string) $search : '';
$status = isset($status) ? (string) $status : '';
$allStatuses = isset($allStatuses) && is_array($allStatuses) ? $allStatuses : [];
?>
<div class="lg:grid lg:grid-cols-12 lg:gap-6">
    <aside class="mb-6 lg:col-span-3 lg:mb-0">
        <nav class="rounded-xl bg-slate-900 p-4 text-white lg:sticky lg:top-6">
            <p class="px-2 text-xs font-medium uppercase tracking-widest text-slate-400">Satpam</p>
            <a href="/admin" class="mt-2 block rounded-lg bg-slate-800 px-3 py-2 text-sm font-medium">Dashboard</a>
            <a href="/admin/repeat-offenders" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Pelanggar Berulang</a>
            <a href="/logout" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-800">Keluar</a>
        </nav>
    </aside>

    <section class="lg:col-span-9">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Dashboard Laporan</h1>

        <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Total Laporan</p>
                <p id="kpi-total" class="text-2xl font-bold text-slate-900"><?= (int) ($counts['total'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 border-l-4 border-l-amber-500 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Menunggu</p>
                <p id="kpi-menunggu" class="text-2xl font-bold text-slate-900"><?= (int) ($counts['menunggu'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 border-l-4 border-l-sky-500 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Diproses</p>
                <p id="kpi-diproses" class="text-2xl font-bold text-slate-900"><?= (int) ($counts['diproses'] ?? 0) ?></p>
            </div>
            <div class="rounded-xl border border-slate-200 border-l-4 border-l-emerald-500 bg-white p-4 shadow-sm">
                <p class="text-xs text-slate-500">Selesai</p>
                <p id="kpi-selesai" class="text-2xl font-bold text-slate-900"><?= (int) ($counts['selesai'] ?? 0) ?></p>
            </div>
        </div>

        <form method="post" action="/admin/cleanup-photos" class="mt-4 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center"
            onsubmit="return confirm('Hapus foto laporan Selesai yang berumur lebih dari 7 hari?');">
            <?= CSRF::field() ?>
            <p class="flex-1 text-sm text-slate-600">Foto laporan <strong>Selesai &gt; 7 hari</strong> menghabiskan disk. Metadata teks tetap tersimpan.</p>
            <button type="submit"
                class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm rounded-lg shadow-sm transition-all cursor-pointer">Bersihkan Foto Lama</button>
        </form>

        <form method="get" action="/admin" class="mt-4 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <input name="search" type="text" value="<?= Sanitizer::escape($search) ?>" placeholder="Cari pelat / lokasi…"
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            <select name="status"
                class="w-full sm:w-56 px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                <option value="">Semua Status</option>
                <?php foreach ($allStatuses as $option): ?>
                    <option value="<?= Sanitizer::escape((string) $option) ?>" <?= $status === $option ? 'selected' : '' ?>>
                        <?= Sanitizer::escape((string) $option) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit"
                class="inline-flex items-center justify-center px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium text-sm rounded-lg shadow-sm transition-all cursor-pointer">Cari</button>
        </form>

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Pelat Nomor</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports === []): ?>
                        <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Tidak ada laporan yang cocok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reports as $report): ?>
                            <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-500">#<?= (int) $report['id'] ?></td>
                                <td class="px-4 py-3 text-xs text-slate-500"><?= Sanitizer::escape((string) $report['date_label']) ?></td>
                                <td class="px-4 py-3 font-mono font-bold text-slate-900"><?= Sanitizer::escape((string) $report['license_plate']) ?></td>
                                <td class="px-4 py-3"><?= Sanitizer::escape((string) $report['location']) ?></td>
                                <td class="px-4 py-3"><?= Sanitizer::escape((string) $report['category']) ?></td>
                                <td class="px-4 py-3"><span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium <?= Sanitizer::escape((string) $report['badge']) ?>"><?= Sanitizer::escape((string) $report['status']) ?></span></td>
                                <td class="px-4 py-3"><a href="/admin/report/detail?id=<?= (int) $report['id'] ?>" class="font-medium text-emerald-700 hover:underline">Tindak Lanjut</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="new-report-banner" class="hidden mt-4 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 px-4 py-3 text-sm" role="alert">
            Ada laporan baru masuk. <button type="button" onclick="location.reload()" class="font-medium underline">Muat ulang daftar</button>
        </div>
    </section>
</div>
<script src="/assets/js/app.js"></script>
<script>
(function () {
  if (!window.ParkirTertib) return;
  var seen = null;
  function setKpi(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }
  window.ParkirTertib.poll('/api/admin/updates', function (data) {
    if (data.counts) {
      setKpi('kpi-total', data.counts.total || 0);
      setKpi('kpi-menunggu', data.counts.menunggu || 0);
      setKpi('kpi-diproses', data.counts.diproses || 0);
      setKpi('kpi-selesai', data.counts.selesai || 0);
    }
    var ids = {};
    (data.reports || []).forEach(function (r) { ids[r.id] = r; });
    if (seen) {
      Object.keys(ids).forEach(function (id) {
        if (!seen[id]) {
          window.ParkirTertib.toast('Laporan baru #' + id + ' (' + ids[id].license_plate + ')', 'info');
          window.ParkirTertib.beep();
          var banner = document.getElementById('new-report-banner');
          if (banner) banner.classList.remove('hidden');
        }
      });
    }
    seen = ids;
  }, 5000);
})();
</script>
