<?php
// Detail laporan + aksi Satpam. Variabel: $report, $reporter, $logs, $errors, $allStatuses.
use App\Helpers\CSRF;
use App\Helpers\Sanitizer;

$report = isset($report) && is_array($report) ? $report : [];
$reporter = isset($reporter) && is_array($reporter) ? $reporter : [];
$logs = isset($logs) && is_array($logs) ? $logs : [];
$errors = isset($errors) && is_array($errors) ? $errors : [];
$allStatuses = isset($allStatuses) && is_array($allStatuses) ? $allStatuses : [];
?>
<div>
    <a href="/admin" class="text-sm font-medium text-emerald-700 hover:underline">&larr; Kembali ke Dashboard</a>
    <h1 class="mt-2 text-2xl font-bold text-slate-900 sm:text-3xl">Laporan #<?= (int) ($report['id'] ?? 0) ?></h1>

    <?php if ($errors !== []): ?>
        <div class="mt-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-4 py-3 text-sm" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= Sanitizer::escape((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="mt-4 lg:grid lg:grid-cols-2 lg:gap-8">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <?php if (!empty($report['photo_url'])): ?>
                <img src="<?= Sanitizer::escape((string) $report['photo_url']) ?>" alt="Foto bukti pelanggaran"
                    class="aspect-video w-full rounded-lg object-cover">
            <?php else: ?>
                <div class="flex aspect-video w-full items-center justify-center rounded-lg bg-slate-100 text-sm text-slate-500">Foto tidak tersedia</div>
            <?php endif; ?>

            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Pelat Nomor</dt><dd class="font-mono font-bold text-slate-900"><?= Sanitizer::escape((string) ($report['license_plate'] ?? '')) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Lokasi</dt><dd class="text-slate-900"><?= Sanitizer::escape((string) ($report['location'] ?? '')) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Kategori</dt><dd class="text-slate-900"><?= Sanitizer::escape((string) ($report['category'] ?? '')) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Pelapor</dt><dd class="text-slate-900"><?= Sanitizer::escape((string) ($reporter['name'] ?? '')) ?> (<?= Sanitizer::escape((string) ($reporter['email'] ?? '')) ?>)</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Waktu Lapor</dt><dd class="text-slate-900"><?= Sanitizer::escape((string) ($report['date_label'] ?? '')) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Status</dt><dd><span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium <?= Sanitizer::escape((string) ($report['badge'] ?? '')) ?>"><?= Sanitizer::escape((string) ($report['status'] ?? '')) ?></span></dd></div>
            </dl>

            <p class="mt-3 text-sm sm:text-base text-slate-600 leading-relaxed"><?= Sanitizer::escape((string) ($report['description'] ?? '')) ?></p>

            <?php if (!empty($report['admin_notes'])): ?>
                <div class="mt-3 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm text-slate-700">
                    <p class="text-xs font-medium text-slate-500">Catatan Satpam terakhir:</p>
                    <p><?= Sanitizer::escape((string) $report['admin_notes']) ?></p>
                </div>
            <?php endif; ?>
        </section>

        <section class="mt-6 lg:mt-0">
            <form method="post" action="/admin/report/detail?id=<?= (int) ($report['id'] ?? 0) ?>"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-800">Ubah Status</h2>
                <?= CSRF::field() ?>
                <div class="mt-3">
                    <label for="new_status" class="block text-sm font-medium text-slate-700 mb-1">Status Baru</label>
                    <select id="new_status" name="new_status" required
                        class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                        <?php foreach ($allStatuses as $option): ?>
                            <option value="<?= Sanitizer::escape((string) $option) ?>" <?= ($report['status'] ?? '') === $option ? 'selected' : '' ?>>
                                <?= Sanitizer::escape((string) $option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mt-3">
                    <label for="note" class="block text-sm font-medium text-slate-700 mb-1">Catatan Satpam</label>
                    <textarea id="note" name="note" rows="3" placeholder="mis. Stiker teguran telah ditempelkan di sepeda motor."
                        class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"></textarea>
                </div>
                <button type="submit"
                    class="mt-4 inline-flex items-center justify-center px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all cursor-pointer">Simpan Perubahan Status</button>
            </form>

            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-xl font-semibold text-slate-800">Riwayat Penanganan</h2>
                <?php if ($logs === []): ?>
                    <p class="mt-2 text-sm text-slate-500">Belum ada perubahan status tercatat.</p>
                <?php else: ?>
                    <ol class="mt-3 space-y-3 border-l-2 border-slate-200 pl-4">
                        <?php foreach ($logs as $log): ?>
                            <li class="text-sm">
                                <p class="font-medium text-slate-900"><?= Sanitizer::escape((string) ($log['previous_status'] ?? '')) ?> &rarr; <?= Sanitizer::escape((string) ($log['new_status'] ?? '')) ?></p>
                                <?php if (!empty($log['note'])): ?>
                                    <p class="text-slate-600"><?= Sanitizer::escape((string) $log['note']) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-slate-500"><?= Sanitizer::escape((string) ($log['admin_name'] ?? '')) ?> &middot; <?= Sanitizer::escape((string) ($log['date_label'] ?? '')) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
