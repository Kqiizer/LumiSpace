(() => {
  'use strict';

  /* =========================
     Ripple effect optimizado
     ========================= */
  const prefersReduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  document.addEventListener('click', (e) => {
    if (prefersReduced) return;

    const t = e.target;
    const el = (t instanceof Element) ? t.closest('.clickable, .action, .menu .item') : null;
    if (!el) return;

    // Asegurar contenedor correcto
    const cs = getComputedStyle(el);
    if (cs.position === 'static') el.style.position = 'relative';
    if (cs.overflow !== 'hidden') el.style.overflow = 'hidden';

    const r = el.getBoundingClientRect();
    const diameter = Math.ceil(Math.max(r.width, r.height) * 2); // cubrir esquinas

    const rip = document.createElement('span');
    rip.className = 'ripple';
    rip.style.width = rip.style.height = diameter + 'px';
    rip.style.left = (e.clientX - r.left - diameter / 2) + 'px';
    rip.style.top  = (e.clientY - r.top  - diameter / 2) + 'px';

    // Limpia ripples anteriores (si quedaron)
    el.querySelectorAll('.ripple').forEach(n => n.remove());
    el.appendChild(rip);

    const cleanup = () => rip.remove();
    rip.addEventListener('animationend', cleanup, { once: true });
    setTimeout(cleanup, 800); // fallback
  });

  /* --- Agrega este CSS a tu stylesheet global ---
  .ripple {
    position: absolute; border-radius: 50%; pointer-events: none;
    width: 100px; height: 100px; background: var(--act1);
    opacity: .35; transform: scale(0); animation: rippleAnim .6s ease-out;
  }
  @keyframes rippleAnim { to { transform: scale(1); opacity: 0; } }
  .clickable, .action, .menu .item { position: relative; overflow: hidden; }
  ------------------------------------------------ */

  /* =========================
     Hora unificada
     ========================= */
  const fmtDate = new Intl.DateTimeFormat('es-ES', { weekday: 'long', month: 'short', day: 'numeric' });
  const fmtTime = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

  function updateTime() {
    const now = new Date();
    const txt = `${fmtDate.format(now)} - ${fmtTime.format(now)}`;

    const s = document.getElementById('local-time');
    const h = document.getElementById('header-time');
    if (s) s.textContent = txt;
    if (h) h.textContent = '🕒 ' + txt;
  }
  updateTime();
  const _clock = setInterval(updateTime, 1000);
  window.addEventListener('beforeunload', () => clearInterval(_clock));

  /* =========================
     Dark mode toggle
     ========================= */
  const dm = document.getElementById('toggle-dark');

  (function applyTheme() {
    try {
      const saved = localStorage.getItem('theme');
      const prefers = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      const dark = saved ? (saved === 'dark') : prefers;
      document.body.classList.toggle('dark', !!dark);
      if (dm) dm.textContent = dark ? '☀️' : '🌙';
    } catch (_) { /* noop */ }
  })();

  if (dm) {
    dm.addEventListener('click', () => {
      const dark = document.body.classList.toggle('dark');
      try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (_) {}
      dm.textContent = dark ? '☀️' : '🌙';
    });
  }

  /* =========================
     Sidebar toggle
     ========================= */
  const sbBtn = document.getElementById('toggleSidebar');
  const sb    = document.getElementById('sidebar');
  if (sbBtn && sb) {
    sbBtn.addEventListener('click', () => {
      sb.classList.toggle('show'); // usa tu animación CSS existente
    });
  }

  /* =========================
     Notificaciones dinámicas
     ========================= */
  const nb = document.getElementById('notif-btn');
  const np = document.getElementById('notif-panel');

  let notifAbort = null;

  async function loadNotificaciones() {
    const list = document.getElementById('notif-list');
    if (!list) return;

    // cancelar petición anterior si sigue en vuelo
    if (notifAbort) notifAbort.abort();
    notifAbort = ('AbortController' in window) ? new AbortController() : null;

    list.innerHTML = '<li>Cargando...</li>';
    try {
      const res = await fetch('../gestor/notificaciones.php', {
        cache: 'no-store',
        signal: notifAbort ? notifAbort.signal : undefined
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);

      const data = await res.json().catch(() => []);
      list.innerHTML = '';
      if (!Array.isArray(data) || data.length === 0) {
        list.innerHTML = '<li>No hay notificaciones recientes</li>';
        return;
      }

      const fmtHour = new Intl.DateTimeFormat('es-ES', { hour: '2-digit', minute: '2-digit' });
      data.forEach(n => {
        const li = document.createElement('li');
        const when = n && n.fecha ? fmtHour.format(new Date(n.fecha)) : '';
        li.textContent = `${n && n.mensaje ? n.mensaje : 'Notificación'} • ${when}`;
        list.appendChild(li);
      });
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      console.error('Error cargando notificaciones:', err);
      list.innerHTML = '<li>Error cargando notificaciones</li>';
    }
  }

  // refrescar cada 20s si el panel está abierto
  const _notifTimer = setInterval(() => {
    if (np && !np.classList.contains('hidden')) loadNotificaciones();
  }, 20000);
  window.addEventListener('beforeunload', () => clearInterval(_notifTimer));

  // abrir/cerrar panel
  if (nb && np) {
    nb.addEventListener('click', (e) => {
      e.stopPropagation();
      np.classList.toggle('hidden');
      if (!np.classList.contains('hidden')) loadNotificaciones();
    });

    document.addEventListener('click', (e) => {
      if (!np.contains(e.target) && !nb.contains(e.target)) {
        np.classList.add('hidden');
      }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !np.classList.contains('hidden')) {
        np.classList.add('hidden');
      }
    });
  }
})();
