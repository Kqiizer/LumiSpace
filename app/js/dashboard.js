/* === Ripple effect optimizado === */
document.addEventListener("click", e => {
  const el = e.target.closest(".clickable, .action, .menu .item");
  if (!el) return;

  const rip = document.createElement("span");
  rip.className = "ripple";
  const r = el.getBoundingClientRect();
  rip.style.left = (e.clientX - r.left) + "px";
  rip.style.top  = (e.clientY - r.top) + "px";
  el.appendChild(rip);

  setTimeout(() => rip.remove(), 600);
});

/* CSS que debes agregar a tu stylesheet:
.ripple {
  position: absolute; width: 100px; height: 100px;
  background: var(--act1); opacity: 0.4; border-radius: 50%;
  transform: scale(0); pointer-events: none;
  animation: rippleAnim 0.6s ease-out;
}
@keyframes rippleAnim {
  to { transform: scale(3); opacity: 0; }
}
.clickable, .action, .menu .item { position: relative; overflow: hidden; }
*/

/* === Hora unificada === */
function updateTime() {
  const now = new Date();
  const txt = now.toLocaleDateString("es-ES", { weekday:"long", month:"short", day:"numeric" }) 
            + " - " + now.toLocaleTimeString("es-ES", { hour:"2-digit", minute:"2-digit", second:"2-digit" });

  const s = document.getElementById("local-time");
  const h = document.getElementById("header-time");
  if (s) s.textContent = txt;
  if (h) h.textContent = "🕒 " + txt;
}
setInterval(updateTime, 1000);
updateTime();

/* === Dark mode toggle (con preferencia guardada) === */
const dm = document.getElementById("toggle-dark");

// aplicar preferencia previa o del sistema
if (localStorage.getItem("theme") === "dark" || 
   (!localStorage.getItem("theme") && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
  document.body.classList.add("dark");
  if (dm) dm.textContent = "☀️";
}

if (dm) {
  dm.addEventListener("click", () => {
    const dark = document.body.classList.toggle("dark");
    localStorage.setItem("theme", dark ? "dark" : "light");
    dm.textContent = dark ? "☀️" : "🌙";
  });
}

/* === Sidebar toggle === */
const sbBtn = document.getElementById("toggleSidebar");
const sb    = document.getElementById("sidebar");

if (sbBtn && sb) {
  sbBtn.addEventListener("click", () => {
    sb.classList.toggle("show"); // usar .show con animación CSS
  });
}

/* === Notificaciones dinámicas === */
const nb = document.getElementById("notif-btn");
const np = document.getElementById("notif-panel");

async function loadNotificaciones() {
  const list = document.getElementById("notif-list");
  if (!list) return;

  list.innerHTML = "<li>Cargando...</li>";
  try {
    const res = await fetch("../gestor/notificaciones.php", { cache: "no-store" });
    const data = await res.json();

    list.innerHTML = "";
    if (!data || data.length === 0) {
      list.innerHTML = "<li>No hay notificaciones recientes</li>";
      return;
    }

    data.forEach(n => {
      const li = document.createElement("li");
      li.textContent = `${n.mensaje} • ${new Date(n.fecha).toLocaleTimeString("es-ES",{ hour:"2-digit", minute:"2-digit" })}`;
      list.appendChild(li);
    });
  } catch (err) {
    console.error("Error cargando notificaciones:", err);
    list.innerHTML = "<li>Error cargando notificaciones</li>";
  }
}

// refrescar cada 20s si el panel está abierto
setInterval(() => {
  if (np && !np.classList.contains("hidden")) loadNotificaciones();
}, 20000);

// abrir/cerrar panel
if (nb && np) {
  nb.addEventListener("click", e => {
    e.stopPropagation();
    np.classList.toggle("hidden");
    if (!np.classList.contains("hidden")) loadNotificaciones();
  });

  document.addEventListener("click", e => {
    if (!np.contains(e.target) && !nb.contains(e.target)) {
      np.classList.add("hidden");
    }
  });
}
