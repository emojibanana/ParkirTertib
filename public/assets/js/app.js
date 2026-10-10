/* ParkirTertib: toast, bunyi, dan polling notifikasi (vanilla JS, tanpa dependensi). */
window.ParkirTertib = (function () {
  'use strict';

  function ensureContainer() {
    var box = document.getElementById('toast-container');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toast-container';
      box.className = 'fixed top-4 left-1/2 z-50 w-full max-w-md px-4 space-y-2';
      box.style.transform = 'translateX(-50%)';
      document.body.appendChild(box);
    }
    return box;
  }

  function toast(message, type) {
    var accents = {
      success: 'border-emerald-500 bg-emerald-50 text-emerald-900',
      error: 'border-rose-500 bg-rose-50 text-rose-900',
      info: 'border-sky-500 bg-sky-50 text-sky-900',
    };
    var marks = { success: '✓', error: '!', info: 'i' };
    var el = document.createElement('div');
    el.className = 'flex items-start gap-3 rounded-xl border-l-4 bg-white px-4 py-3 text-sm font-medium shadow-lg '
      + (accents[type] || accents.info);
    el.setAttribute('role', 'status');
    el.style.opacity = '0';
    el.style.transform = 'translateY(-12px)';
    el.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
    var mark = document.createElement('span');
    mark.className = 'mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white';
    mark.textContent = marks[type] || marks.info;
    var text = document.createElement('span');
    text.className = 'flex-1 leading-relaxed';
    text.textContent = message;
    var close = document.createElement('button');
    close.type = 'button';
    close.setAttribute('aria-label', 'Tutup notifikasi');
    close.className = 'shrink-0 rounded px-1 text-lg leading-none text-slate-400 hover:text-slate-700';
    close.textContent = '×';
    close.addEventListener('click', function () { el.remove(); });
    el.appendChild(mark);
    el.appendChild(text);
    el.appendChild(close);
    ensureContainer().appendChild(el);
    window.requestAnimationFrame(function () {
      el.style.opacity = '1';
      el.style.transform = 'translateY(0)';
    });
    window.setTimeout(function () { el.remove(); }, 9000);
  }

  /* Lonceng lembut: arpeggio C-E-G sine bervolume kecil dengan fade-out. */
  function beep() {
    try {
      var Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx) return;
      var ctx = new Ctx();
      if (ctx.state === 'suspended') ctx.resume();
      var master = ctx.createGain();
      master.gain.value = 0.16;
      master.connect(ctx.destination);
      var notes = [523.25, 659.25, 783.99];
      notes.forEach(function (freq, i) {
        var osc = ctx.createOscillator();
        var gain = ctx.createGain();
        var t0 = ctx.currentTime + i * 0.13;
        osc.type = 'sine';
        osc.frequency.value = freq;
        gain.gain.setValueAtTime(0.0001, t0);
        gain.gain.exponentialRampToValueAtTime(1, t0 + 0.04);
        gain.gain.exponentialRampToValueAtTime(0.0001, t0 + 0.45);
        osc.connect(gain);
        gain.connect(master);
        osc.start(t0);
        osc.stop(t0 + 0.5);
      });
      window.setTimeout(function () { ctx.close(); }, 1400);
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
