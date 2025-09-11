// === Ripple global en TODO lo clicable ===
document.querySelectorAll('.clickable, .action, .menu .item').forEach(el => {
  el.addEventListener('click', e => {
    const rip = document.createElement('span');
    rip.className = 'ripple';
    rip.style.background = getComputedStyle(document.documentElement)
      .getPropertyValue('--act1');
    const r = el.getBoundingClientRect();
    rip.style.left = (e.clientX - r.left - 50) + 'px';
    rip.style.top  = (e.clientY - r.top  - 50) + 'px';
    el.appendChild(rip);
    setTimeout(() => rip.remove(), 650);
  });
});

// === Hora unificada: sidebar (#local-time) + header (#header-time) ===
function updateTime() {
  const now   = new Date();
  const fecha = now.toLocaleDateString("es-ES", { 
    weekday: "long", month: "short", day: "numeric" 
  });
  const hora  = now.toLocaleTimeString("es-ES", { 
    hour: "2-digit", minute: "2-digit", second: "2-digit", hour12: true 
  });
  const txt   = `${fecha} - ${hora}`;
  const s = document.getElementById("local-time");
  const h = document.getElementById("header-time");
  if (s) s.textContent = txt;
  if (h) h.textContent = "🕒 " + txt;
}
setInterval(updateTime, 1000);
updateTime();

// === Dark mode toggle (id="toggle-dark") ===
const dm = document.getElementById("toggle-dark");
if (dm) {
  dm.addEventListener("click", () => {
    document.body.classList.toggle("dark");
    dm.textContent = document.body.classList.contains("dark") ? "☀️" : "🌙";
  });
}

// === Sidebar toggle (id="toggleSidebar", sidebar con id="sidebar") ===
const sbBtn = document.getElementById("toggleSidebar");
const sb    = document.getElementById("sidebar");
if (sbBtn && sb) {
  sbBtn.addEventListener("click", () => sb.classList.toggle("collapsed"));
}

// === Notificaciones dinámicas (id="notif-btn", panel id="notif-panel") ===
const nb = document.getElementById("notif-btn");
const np = document.getElementById("notif-panel");
async function loadNotificaciones() {
  try {
    const res = await fetch("../gestor/notificaciones.php");
    const data = await res.json();
    const list = document.getElementById("notif-list");
    list.innerHTML = "";

    if (!data || data.length === 0) {
      list.innerHTML = "<li>No hay notificaciones recientes</li>";
      return;
    }

    data.forEach(n => {
      const li = document.createElement("li");
      li.textContent = `${n.mensaje} • ${new Date(n.fecha).toLocaleTimeString(
        "es-ES", { hour: "2-digit", minute: "2-digit" }
      )}`;
      list.appendChild(li);
    });
  } catch (err) {
    console.error("Error cargando notificaciones", err);
  }
}

// Refrescar cada 20 segundos
setInterval(loadNotificaciones, 20000);

// Abrir/cerrar panel de notificaciones
if (nb && np) {
  nb.addEventListener("click", () => {
    np.classList.toggle("hidden");
    if (!np.classList.contains("hidden")) {
      loadNotificaciones();
    }
  });

  // Cerrar al hacer clic fuera
  document.addEventListener("click", e => {
    if (!np.contains(e.target) && !nb.contains(e.target)) {
      np.classList.add("hidden");
    }
  });
}
