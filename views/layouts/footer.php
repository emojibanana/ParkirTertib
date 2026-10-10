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
</body>
</html>
