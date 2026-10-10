<?php
// Fragmen form laporan (mobile-first). Variabel: $errors, $old, $locations,
// $categories, $activeCount, $maxActive, $limitReached.
use App\Helpers\CSRF;
use App\Helpers\Sanitizer;

$errors = isset($errors) && is_array($errors) ? $errors : [];
$old = isset($old) && is_array($old) ? $old : [];
$locations = isset($locations) && is_array($locations) ? $locations : [];
$categories = isset($categories) && is_array($categories) ? $categories : [];
$activeCount = isset($activeCount) ? (int) $activeCount : 0;
$maxActive = isset($maxActive) ? (int) $maxActive : 3;
$limitReached = isset($limitReached) ? (bool) $limitReached : false;
?>
<div class="mx-auto max-w-lg">
    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Buat Laporan</h1>
    <p class="mt-1 text-sm sm:text-base text-slate-600 leading-relaxed">Laporkan pelanggaran parkir dengan foto bukti.</p>

    <div class="mt-4 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 px-4 py-3 text-sm" role="status">
        Laporan Aktif: <?= $activeCount ?> / <?= $maxActive ?>
    </div>

    <?php if ($limitReached): ?>
        <div class="mt-3 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 px-4 py-3 text-sm" role="alert">
            Anda telah mencapai batas maksimum 3 laporan aktif. Tunggu laporan selesai sebelum membuat laporan baru.
        </div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="mt-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-4 py-3 text-sm" role="alert">
            <ul class="list-disc pl-5">
                <?php foreach ($errors as $error): ?>
                    <li><?= Sanitizer::escape((string) $error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="/report/create" enctype="multipart/form-data" class="mt-4 space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <?= CSRF::field() ?>

        <div>
            <label for="photo" class="block text-sm font-medium text-slate-700 mb-1">Foto Bukti Pelanggaran</label>
            <input id="photo" name="photo" type="file" required <?= $limitReached ? 'disabled' : '' ?>
                accept="image/jpeg,image/png,image/webp" capture="environment"
                class="w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border file:border-slate-300 file:bg-white file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-50">
            <img id="photo-preview" class="mt-2 hidden aspect-video w-full rounded-lg object-cover" alt="Pratinjau foto bukti">
            <button type="button" id="photo-change" class="hidden mt-2 inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium text-sm rounded-lg shadow-sm transition-all">Ganti Foto</button>
        </div>

        <div>
            <label for="license_plate" class="block text-sm font-medium text-slate-700 mb-1">Nomor Pelat Kendaraan (contoh: BK 1234 ABC)</label>
            <input id="license_plate" name="license_plate" type="text" required maxlength="20" autocomplete="off"
                value="<?= Sanitizer::escape((string) ($old['license_plate'] ?? '')) ?>"
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
        </div>

        <div>
            <label for="location" class="block text-sm font-medium text-slate-700 mb-1">Lokasi Parkir</label>
            <select id="location" name="location" required
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                <option value="">— Pilih lokasi —</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?= Sanitizer::escape((string) $location) ?>" <?= (($old['location'] ?? '') === $location) ? 'selected' : '' ?>>
                        <?= Sanitizer::escape((string) $location) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-slate-700 mb-1">Kategori Pelanggaran</label>
            <select id="category" name="category" required
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                <option value="">— Pilih kategori —</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= Sanitizer::escape((string) $category) ?>" <?= (($old['category'] ?? '') === $category) ? 'selected' : '' ?>>
                        <?= Sanitizer::escape((string) $category) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700 mb-1">Deskripsi Kejadian</label>
            <textarea id="description" name="description" rows="3" required maxlength="2000"
                class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded-lg text-slate-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"><?= Sanitizer::escape((string) ($old['description'] ?? '')) ?></textarea>
        </div>

        <button type="submit" <?= $limitReached ? 'disabled' : '' ?>
            class="w-full inline-flex items-center justify-center px-5 py-2.5 font-medium text-sm rounded-lg shadow-sm transition-all <?= $limitReached ? 'bg-slate-300 text-slate-500 cursor-not-allowed' : 'bg-emerald-700 hover:bg-emerald-800 text-white cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500' ?>">Kirim Laporan</button>
    </form>
</div>
<script>
(function () {
  var input = document.getElementById('photo');
  var preview = document.getElementById('photo-preview');
  var changeBtn = document.getElementById('photo-change');
  if (!input || !preview) return;
  function showPreview(file) {
    if (!file || !file.type.startsWith('image/')) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.classList.remove('hidden');
      if (changeBtn) changeBtn.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
  }
  input.addEventListener('change', function () { showPreview(input.files[0]); });
  if (changeBtn) changeBtn.addEventListener('click', function () { input.click(); });
})();
</script>
