<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$usuarioNombre = $_SESSION['usuario_nombre'] ?? "Admin Invitado";
$usuarioAvatar = $_SESSION['usuario_avatar'] ?? null;

// Iniciales para fallback
$iniciales = strtoupper(substr($usuarioNombre, 0, 2));

// 🔹 Asegúrate de definir BASE_URL globalmente en config.php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/LumiSpace/');
    $BASE = rtrim(BASE_URL, '/') . '/';

}
?>
<header class="topbar">
  <div class="left">
    <h1>Dashboard Admin</h1>
    <p>Bienvenido de nuevo, <strong><?= htmlspecialchars($usuarioNombre) ?></strong></p>
  </div>

  <div class="right">
    <!-- Hora local -->
    <div id="header-time" class="chip-time" aria-label="Hora local">🕒</div>

    <!-- Avatar con menú -->
    <div class="user-dropdown">
      <button class="user-mini" id="userMenuBtn" aria-haspopup="true" aria-expanded="false">
        <?php if ($usuarioAvatar): ?>
          <img src="<?= htmlspecialchars($usuarioAvatar) ?>" alt="Avatar de <?= htmlspecialchars($usuarioNombre) ?>">
        <?php else: ?>
          <span><?= $iniciales ?></span>
        <?php endif; ?>
      </button>
      <div class="user-menu hidden" id="userMenu">
        <p class="user-name"><?= htmlspecialchars($usuarioNombre) ?></p>
        <a href="<?= $BASE ?>views/perfil.php">👤 Perfil</a>
        <a href="<?= $BASE ?>logout.php">🚪 Cerrar sesión</a>
      </div>
    </div>
  </div>
</header>

<style>
/* ===== TOPBAR ===== */
.topbar {
  display:flex; align-items:center; justify-content:space-between;
  padding:1rem 1.5rem;
  background: var(--card-bg-1);
  border:1px solid var(--card-bd);
  border-radius: var(--radius);
  backdrop-filter: blur(12px);
  box-shadow: var(--shadow);
  margin-bottom: 24px;
}
.topbar .left h1 {
  margin:0; font-size:1.4rem; font-weight:700; color:var(--text);
}
.topbar .left p {
  margin:0; font-size:.95rem; color:var(--muted);
}
.topbar .right {
  display:flex; align-items:center; gap:1rem;
}

/* Chip hora */
.chip-time {
  padding:6px 12px; border-radius:20px;
  background: var(--card-bg-2);
  border:1px solid var(--card-bd);
  font-size:.9rem; font-weight:600;
  color:var(--act1);
  box-shadow: var(--shadow);
  backdrop-filter: blur(8px);
  min-width: 100px;
  text-align:center;
}

/* Avatar */
.user-mini {
  width:42px; height:42px;
  border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-weight:700; font-size:1rem; cursor:pointer;
  background: var(--act1); color:#fff;
  border:none; outline:none;
  transition: transform .2s ease, box-shadow .2s ease;
}
.user-mini:hover { transform: scale(1.05); box-shadow:0 4px 12px rgba(0,0,0,.3); }
.user-mini img {
  width:100%; height:100%; object-fit:cover; border-radius:50%;
}

/* Dropdown */
.user-dropdown { position:relative; }
.user-menu {
  position:absolute; right:0; top:55px; min-width:200px;
  background: var(--card-bg-1);
  border:1px solid var(--card-bd);
  box-shadow: var(--shadow);
  border-radius:12px;
  padding:.6rem; display:flex; flex-direction:column; gap:.6rem;
  backdrop-filter: blur(14px);
  animation: fadeIn .25s ease;
  z-index:1000;
}
.user-menu.hidden { display:none; }
.user-menu .user-name {
  font-weight:600; padding:.3rem .5rem;
  border-bottom:1px solid var(--card-bd);
  margin-bottom:.3rem; color:var(--text);
}
.user-menu a {
  text-decoration:none; color:var(--text);
  padding:.4rem .6rem; border-radius:8px;
  font-size:.9rem; transition: background .2s ease;
}
.user-menu a:hover {
  background: var(--card-bg-2);
}

/* Animación */
@keyframes fadeIn {
  from { opacity:0; transform:translateY(-6px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>

<script>
// Toggle menú usuario
const btn = document.getElementById("userMenuBtn");
const menu = document.getElementById("userMenu");

if(btn && menu){
  btn.addEventListener("click", ()=>{
    menu.classList.toggle("hidden");
    btn.setAttribute("aria-expanded", menu.classList.contains("hidden") ? "false" : "true");
  });

  // Cierra si clic fuera
  document.addEventListener("click",(e)=>{
    if(!btn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.add("hidden");
      btn.setAttribute("aria-expanded", "false");
    }
  });

  // Cierra al hacer clic en un enlace
  menu.querySelectorAll("a").forEach(link=>{
    link.addEventListener("click", ()=>{
      menu.classList.add("hidden");
      btn.setAttribute("aria-expanded", "false");
    });
  });
}

// 🔹 Actualizar hora local
function updateTime(){
  const now = new Date();
  const options = { hour: "2-digit", minute: "2-digit", second: "2-digit" };
  document.getElementById("header-time").textContent = now.toLocaleTimeString([], options);
}
setInterval(updateTime, 1000);
updateTime();
</script>
