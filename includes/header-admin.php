<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$usuarioNombre = $_SESSION['usuario_nombre'] ?? "Admin Invitado";
$usuarioAvatar = $_SESSION['usuario_avatar'] ?? null;

// Iniciales para fallback
$iniciales = strtoupper(substr($usuarioNombre, 0, 2));
?>
<header class="topbar">
  <div class="left">
    <h1>Panel de Administración</h1>
    <p>Bienvenido de nuevo, <strong><?= htmlspecialchars($usuarioNombre) ?></strong></p>
  </div>

  <div class="right">
    <!-- Hora local -->
    <div id="header-time" class="chip-time" aria-label="Hora local">🕒 Cargando...</div>

    <!-- Avatar con menú -->
    <div class="user-dropdown">
      <button class="user-mini" id="userMenuBtn" aria-label="Menú de usuario">
        <?php if ($usuarioAvatar): ?>
          <img src="<?= htmlspecialchars($usuarioAvatar) ?>" alt="Avatar de <?= htmlspecialchars($usuarioNombre) ?>">
        <?php else: ?>
          <span><?= $iniciales ?></span>
        <?php endif; ?>
      </button>
      <div class="user-menu hidden" id="userMenu">
        <p class="user-name"><?= htmlspecialchars($usuarioNombre) ?></p>
        <a href="../admin/perfil.php">👤 Perfil</a>
        <a href="../logout.php">🚪 Cerrar sesión</a>
      </div>
    </div>
  </div>
</header>

<style>
.topbar {
  display:flex; align-items:center; justify-content:space-between;
  padding:1rem; background:var(--bg-1,#fff);
  border-bottom:1px solid rgba(0,0,0,.1); box-shadow:0 2px 6px rgba(0,0,0,0.05);
}
.topbar .left h1 {
  margin:0; font-size:1.3rem; color:var(--text,#333);
}
.topbar .left p {
  margin:0; font-size:.9rem; color:var(--muted,#666);
}
.topbar .right {
  display:flex; align-items:center; gap:1rem;
}
.chip-time {
  padding:6px 12px; border-radius:20px; background:rgba(0,0,0,0.05);
  font-size:.9rem; font-weight:600; color:var(--act1,#8f5e4b);
}

/* Avatar */
.user-mini {
  width:40px; height:40px; border-radius:50%; overflow:hidden;
  display:flex; align-items:center; justify-content:center;
  font-weight:700; font-size:1rem; cursor:pointer;
  background:var(--act1,#8f5e4b); color:#fff;
  border:none;
}
.user-mini img {
  width:100%; height:100%; object-fit:cover; border-radius:50%;
}

/* Dropdown */
.user-dropdown { position:relative; }
.user-menu {
  position:absolute; right:0; top:50px; min-width:180px;
  background:#fff; border:1px solid rgba(0,0,0,.1);
  box-shadow:0 6px 18px rgba(0,0,0,0.1);
  border-radius:8px; padding:.5rem; display:flex; flex-direction:column; gap:.5rem;
  z-index:999;
}
.user-menu.hidden { display:none; }
.user-menu .user-name {
  font-weight:600; padding:.25rem .5rem; border-bottom:1px solid rgba(0,0,0,0.05);
}
.user-menu a {
  text-decoration:none; color:#333; padding:.4rem .5rem; border-radius:5px;
  font-size:.9rem;
}
.user-menu a:hover {
  background:rgba(0,0,0,0.05);
}
</style>

<script>
// Toggle menú usuario
const btn = document.getElementById("userMenuBtn");
const menu = document.getElementById("userMenu");
if(btn && menu){
  btn.addEventListener("click", ()=> menu.classList.toggle("hidden"));
  document.addEventListener("click",(e)=>{
    if(!btn.contains(e.target) && !menu.contains(e.target)) menu.classList.add("hidden");
  });
}
</script>
