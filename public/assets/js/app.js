/* ParkirTertib: toast, bunyi, dan polling notifikasi (vanilla JS, tanpa dependensi). */
window.ParkirTertib = (function () {
  'use strict';

  function ensureContainer() {
    var box = document.getElementById('toast-container');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toast-container';
      box.className = 'fixed bottom-4 right-4 z-50 w-80 max-w-[calc(100vw-2rem)] space-y-2';
      document.body.appendChild(box);
    }
    return box;
  }

  function toast(message, type) {
    var styles = {
      success: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
      error: 'bg-rose-50 text-rose-800 border border-rose-200',
      info: 'bg-sky-50 text-sky-800 border border-sky-200',
    };
    var el = document.createElement('div');
    el.className = 'rounded-lg px-4 py-3 text-sm shadow ' + (styles[type] || styles.info);
    el.setAttribute('role', 'status');
    el.textContent = message;
    ensureContainer().appendChild(el);
    window.setTimeout(function () { el.remove(); }, 8000);
  }

  function beep() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = new Ctx();
      var osc = ctx.createOscillator();
      osc.frequency.value = 880;
      osc.connect(ctx.destination);
      osc.start();
      window.setTimeout(function () { osc.stop(); ctx.close(); }, 250);
    } catch (e) {
      /* audio tidak tersedia: abaikan */
    }
  }

  function poll(url, onData, intervalMs) {
    var timer = null;
    function tick() {
      if (document.hidden) return;
      fetch(url, { headers: { Accept: 'application/json' } })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) { if (data) onData(data); })
        .catch(function () { /* jaringan gagal: coba lagi interval berikut */ });
    }
    tick();
    timer = window.setInterval(tick, intervalMs);
    return timer;
  }

  return { toast: toast, beep: beep, poll: poll };
})();
