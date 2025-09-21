<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🔹 Definir BASE_URL (ajústala según tu entorno)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/LumiSpace/');
}

// Detecta página activa automáticamente
$currentPage = basename($_SERVER['PHP_SELF']);
function activeClass(string $page, string $current): string {
  return $page === $current ? 'active' : '';
}
function isGroupActive(array $pages, string $current): string {
  return in_array($current, $pages) ? 'show group-active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <!-- Marca -->
  <div class="brand">
    <div class="logo">A</div>
    <div class="brand-meta">
      <strong>LUMISPACE</strong>
      <small>Panel Administrador</small>
    </div>
  </div>

  <!-- Menú -->
  <nav class="menu" aria-label="Menú Administrador">

    <!-- Dashboard -->
    <a class="item <?= activeClass('dashboard-admin.php', $currentPage) ?>" 
       href="<?= BASE_URL ?>views/dashboard-admin.php">🏠 Dashboard</a>

    <!-- Usuarios -->
    <button class="item has-submenu <?= isGroupActive(['usuarios.php','usuarios-crear.php','usuarios-roles.php'], $currentPage) ?>">
      👥 Usuarios
    </button>
    <div class="submenu-group <?= isGroupActive(['usuarios.php','usuarios-crear.php','usuarios-roles.php'], $currentPage) ?>">
      <a class="subitem <?= activeClass('usuarios.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/usuarios/usuarios.php">📋 Listar</a>
      <a class="subitem <?= activeClass('usuarios-crear.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/usuarios/usuarios-crear.php">➕ Crear</a>
      <a class="subitem <?= activeClass('usuarios-roles.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/usuarios/usuarios-roles.php">🛡️ Roles</a>
    </div>

    <!-- Catálogo -->
    <button class="item has-submenu <?= isGroupActive(['productos.php','inventario.php','proveedores.php','categorias.php'], $currentPage) ?>">
      📦 Catálogo
    </button>
    <div class="submenu-group <?= isGroupActive(['productos.php','inventario.php','proveedores.php','categorias.php'], $currentPage) ?>">
      <a class="subitem <?= activeClass('productos.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/catalogo/productos.php">📚 Productos</a>
      <a class="subitem <?= activeClass('inventario.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/inventario/inventario.php">📦 Inventario</a>
      <a class="subitem <?= activeClass('proveedores.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/proveedores/proveedores.php">🚚 Proveedores</a>
      <a class="subitem <?= activeClass('categorias.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/categorias/categorias.php">🏷️ Categorías</a>
    </div>

    <!-- Roles -->
    <button class="item has-submenu <?= isGroupActive(['gestores.php','cajeros.php'], $currentPage) ?>">
      🛡️ Roles
    </button>
    <div class="submenu-group <?= isGroupActive(['gestores.php','cajeros.php'], $currentPage) ?>">
      <a class="subitem <?= activeClass('gestores.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/roles/gestores.php">📊 Gestores</a>
      <a class="subitem <?= activeClass('cajeros.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/roles/cajeros.php">💰 Cajeros</a>
    </div>

    <!-- Reportes -->
    <button class="item has-submenu <?= isGroupActive(['reportes.php','reportes-ventas.php','reportes-inventario.php'], $currentPage) ?>">
      📊 Reportes
    </button>
    <div class="submenu-group <?= isGroupActive(['reportes.php','reportes-ventas.php','reportes-inventario.php'], $currentPage) ?>">
      <a class="subitem <?= activeClass('reportes.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/reportes/reportes.php">📑 Generales</a>
      <a class="subitem <?= activeClass('reportes-ventas.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/reportes/reportes-ventas.php">📈 Ventas</a>
      <a class="subitem <?= activeClass('reportes-inventario.php', $currentPage) ?>" 
         href="<?= BASE_URL ?>views/reportes/reportes-inventario.php">📦 Inventario</a>
    </div>
  </nav>

  <!-- Footer -->
  <div class="sidebar-footer">
    <button id="darkToggle" class="item toggle-theme" type="button">🌙 Modo Oscuro</button>
    <div class="floating-card">
      <div class="fc-title">Panel de Control</div>
      <div class="fc-sub">Rol: <strong>Administrador</strong></div>
      <div class="fc-small">© <?= date('Y') ?> LUMISPACE</div>
    </div>
  </div>
</aside>

<style>
:root {
  --act1:#a1683a;
  --act2:#8f5e4b;
  --act3:#7a6a4b;
  --sidebar-bg: rgba(58, 47, 38, 0.9);
  --sidebar-hover: linear-gradient(90deg, var(--act1), var(--act2));
  --sidebar-active: linear-gradient(90deg, #ffb366, #e6954d);
  --glow: 0 0 12px rgba(161, 104, 58, .6);
}

/* Sidebar */
.sidebar {
  width: 270px;
  background: var(--sidebar-bg);
  backdrop-filter: blur(12px);
  color: #fff;
  position: fixed; top: 0; left: 0; bottom: 0;
  display: flex; flex-direction: column;
  justify-content: space-between;
  padding: 1.2rem;
  box-shadow: 0 8px 28px rgba(0,0,0,.35);
  overflow-y: auto;
  border-right: 1px solid rgba(255,255,255,.1);
}
.sidebar::-webkit-scrollbar { width: 6px; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,.25); border-radius: 4px; }

/* Marca */
.brand { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.logo {
  background: #fff; color: var(--act1);
  font-weight: bold; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center;
  width: 42px; height: 42px; border-radius: 12px;
  box-shadow: var(--glow);
}
.brand-meta small { color: rgba(255,255,255,.7); font-size: .85rem; }

/* Items */
.menu .item, .subitem, .toggle-theme {
  display: block;
  padding: 10px 14px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: 500;
  color: #fff;
  transition: all .35s ease;
  cursor: pointer;
  background: transparent;
  border: none;
  text-align: left;
  width: 100%;
}
.menu .item:hover, .subitem:hover, .toggle-theme:hover {
  background: var(--sidebar-hover);
  transform: translateX(5px);
  box-shadow: var(--glow);
}
.menu .item.active, .subitem.active,
.menu .item.group-active {
  background: var(--sidebar-active);
  font-weight: 600;
  box-shadow: inset 4px 0 var(--act3), var(--glow);
}

/* Submenús */
.submenu-group {
  display: none;
  flex-direction: column;
  gap: 6px; margin-bottom: 12px;
  animation: fadeSlide .35s ease;
}
.submenu-group.show { display: flex; }
.subitem { font-size: .93rem; padding-left: 26px; }

@keyframes fadeSlide {
  from { opacity: 0; transform: translateY(-6px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Footer */
.sidebar-footer {
  margin-top: auto;
  display: flex; flex-direction: column; gap: 12px;
}
.floating-card {
  padding: 12px;
  background: rgba(255,255,255,0.08);
  border-radius: 12px;
  font-size: .85rem; text-align: center;
  line-height: 1.4;
  box-shadow: var(--glow);
}

/* Dark mode */
body.dark .sidebar {
  background: rgba(28, 24, 20, 0.95);
  --sidebar-hover: linear-gradient(90deg,#d4a373,#c7925b);
  --sidebar-active: linear-gradient(90deg,#e6a86b,#d49454);
}
</style>

<script>
// 🌙 Dark Mode
const body = document.body;
const darkToggle = document.getElementById("darkToggle");
function updateThemeBtn() {
  darkToggle.textContent = body.classList.contains("dark")
    ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
}
if (localStorage.getItem("theme") === "dark") body.classList.add("dark");
updateThemeBtn();
darkToggle?.addEventListener("click", () => {
  body.classList.toggle("dark");
  localStorage.setItem("theme", body.classList.contains("dark") ? "dark" : "light");
  updateThemeBtn();
});

// 📂 Submenús tipo acordeón
document.querySelectorAll(".item.has-submenu").forEach(btn => {
  btn.addEventListener("click", () => {
    const allSubmenus = document.querySelectorAll(".submenu-group");
    const allButtons = document.querySelectorAll(".item.has-submenu");
    const submenu = btn.nextElementSibling;

    allSubmenus.forEach(s => s.classList.remove("show"));
    allButtons.forEach(b => b.classList.remove("group-active"));

    submenu.classList.add("show");
    btn.classList.add("group-active");
  });
});
</script>
