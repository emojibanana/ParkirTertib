<?php
// Daftar riwayat laporan user (mobile-first). Variabel: $reports (array).
use App\Helpers\Sanitizer;

$reports = isset($reports) && is_array($reports) ? $reports : [];
?>
<div class="mx-auto max-w-xl">
    <div class="flex items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Riwayat Laporan</h1>
        <a href="/report/create"
            class="inline-flex items-center justify-center px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all cursor-pointer">+ Buat Laporan</a>
    </div>

    <?php if ($reports === []): ?>
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <p class="text-lg font-semibold text-slate-800">Belum Ada Laporan</p>
            <p class="mt-1 text-sm sm:text-base text-slate-600 leading-relaxed">Anda belum pernah mengirimkan laporan pelanggaran parkir.</p>
            <a href="/report/create"
                class="mt-4 inline-flex items-center justify-center px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-medium text-sm rounded-lg shadow-sm transition-all">Buat Laporan Pertama</a>
        </div>
    <?php else: ?>
        <div class="mt-4 space-y-3">
            <?php foreach ($reports as $report): ?>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex gap-3">
                        <?php if (!empty($report['photo_url'])): ?>
                            <img src="<?= Sanitizer::escape((string) $report['photo_url']) ?>" alt="Foto bukti"
                                class="h-20 w-20 shrink-0 rounded-lg object-cover">
                        <?php else: ?>
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-center text-xs text-slate-500">Foto tidak tersedia</div>
                        <?php endif; ?>
                        <div class="min-w-0 flex-1">
                            <p class="font-mono font-bold text-slate-900"><?= Sanitizer::escape((string) $report['license_plate']) ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?= Sanitizer::escape((string) $report['category']) ?> &middot; <?= Sanitizer::escape((string) $report['location']) ?></p>
                            <p class="mt-1">
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium <?= Sanitizer::escape((string) $report['badge']) ?>"><?= Sanitizer::escape((string) $report['status']) ?></span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500"><?= Sanitizer::escape((string) $report['date_label']) ?></p>
                        </div>
                    </div>
                    <?php if (!empty($report['description'])): ?>
                        <p class="mt-2 text-sm sm:text-base text-slate-600 leading-relaxed"><?= Sanitizer::escape((string) $report['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($report['admin_notes'])): ?>
                        <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <p class="text-xs font-medium text-slate-500">Respon Satpam:</p>
                            <p><?= Sanitizer::escape((string) $report['admin_notes']) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
