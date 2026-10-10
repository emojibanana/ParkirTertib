</main>
<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-5xl px-4 py-4 text-center text-xs text-slate-500">
        <p>&copy; <?= date('Y') ?> ParkirTertib — Universitas Negeri Medan (UNIMED). Sistem pelaporan parkir partisipatif.</p>
    </div>
</footer>
<script>
document.getElementById('nav-toggle')?.addEventListener('click', function () {
  document.getElementById('nav-drawer')?.classList.toggle('hidden');
});
</script>
<?php
$appJsPath = dirname(__DIR__, 2) . '/public/assets/js/app.js';
$appJsMtime = filemtime($appJsPath);
$appJsVer = $appJsMtime === false ? 1 : (int) $appJsMtime;
?>
<script src="/assets/js/app.js?v=<?= $appJsVer ?>"></script>
<?php $pollRole = $_SESSION['user_role'] ?? null; ?>
<?php if ($pollRole === 'user' || $pollRole === 'admin'): ?>
<script>
(function () {
  if (!window.ParkirTertib) return;
  var BADGES = {
    'Menunggu': 'bg-amber-100 text-amber-800',
    'Diproses': 'bg-sky-100 text-sky-800',
    'Ditindaklanjuti': 'bg-indigo-100 text-indigo-800',
    'Selesai': 'bg-emerald-100 text-emerald-800'
  };
  function setKpi(id, value) {
    var el = document.getElementById(id);
    if (el) el.textContent = value;
  }
  function paintBadge(id, status) {
    var badge = document.querySelector('[data-report-id="' + id + '"] [data-role="status-badge"]');
    if (badge) {
      badge.textContent = status;
      badge.className = 'inline-block rounded-full px-2 py-0.5 text-xs font-medium ' + (BADGES[status] || '');
    }
  }
<?php if ($pollRole === 'admin'): ?>
  var seen = null;
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
          window.ParkirTertib.toast('Laporan #' + id + ' (' + ids[id].license_plate + ') baru masuk.', 'info', 'Laporan Masuk');
          window.ParkirTertib.beep();
          var banner = document.getElementById('new-report-banner');
          if (banner) banner.classList.remove('hidden');
        }
      });
    }
    seen = ids;
  }, 5000);
<?php else: ?>
  var snapshot = null;
  window.ParkirTertib.poll('/api/notifications', function (data) {
    var current = {};
    (data.reports || []).forEach(function (r) { current[r.id] = r.status; });
    if (snapshot) {
      Object.keys(current).forEach(function (id) {
        if (snapshot[id] && snapshot[id] !== current[id]) {
          window.ParkirTertib.toast('Laporan #' + id + ': ' + snapshot[id] + ' menjadi ' + current[id] + '.', 'info', 'Status Berubah');
          window.ParkirTertib.beep();
          paintBadge(id, current[id]);
        }
      });
    }
    snapshot = current;
  }, 5000);
<?php endif; ?>
})();
</script>
<?php endif; ?>
</body>
</html>
