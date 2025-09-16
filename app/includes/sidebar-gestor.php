<?php
// Detecta página activa automáticamente
$currentPage = basename($_SERVER['PHP_SELF']);
function activeClass($page, $current) {
  return $page === $current ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
  <!-- Marca -->
  <div class="brand">
    <div class="logo">L</div>
    <div class="brand-meta">
      <strong>LUMISPACE</strong>
      <small>Iluminaria Premium</small>
    </div>
  </div>

  <!-- Menú -->
  <nav class="menu" aria-label="Menú principal">
    <a class="item <?= activeClass('dashboard-gestor.php', $currentPage) ?>" href="dashboard-gestor.php">🏠 <span>Dashboard</span></a>
    <a class="item <?= activeClass('pos.php', $currentPage) ?>" href="pos.php">🧾 <span>Punto de Venta</span></a>
    <a class="item <?= activeClass('productos.php', $currentPage) ?>" href="productos.php">📚 <span>Catálogo</span></a>
    <a class="item <?= activeClass('inventario.php', $currentPage) ?>" href="inventario.php">📦 <span>Inventario</span></a>
    <a class="item <?= activeClass('facturacion.php', $currentPage) ?>" href="facturacion.php">💳 <span>Facturación</span></a>
    <a class="item <?= activeClass('reportes.php', $currentPage) ?>" href="reportes.php">📊 <span>Estadísticas</span></a>
    <hr>
    <!-- Modo oscuro -->
    <button id="darkToggle" class="item toggle-theme" type="button">🌙 <span>Modo Oscuro</span></button>
    <!-- Cierre de sesión -->
    <a class="item logout" href="../logout.php">🚪 <span>Cerrar Sesión</span></a>
  </nav>

  <!-- Info inferior -->
  <div class="floating-card">
    <div class="fc-title">Corte de Caja</div>
    <div class="fc-sub">Sistema Activo en línea</div>
    <div class="fc-small">© <?= date('Y') ?> LUMISPACE</div>
  </div>
</aside>

<style>
/* === Sidebar base === */
.sidebar {
  width: 250px;
  background: linear-gradient(180deg, #6a5745, #8b6a52, #b38158);
  color: #fff;
  position: fixed; top: 0; left: 0; bottom: 0;
  display: flex; flex-direction: column; justify-content: space-between;
  padding: 1rem;
  box-shadow: 0 8px 22px rgba(0,0,0,.2);
  transition: background 0.3s, color 0.3s, transform 0.3s;
  z-index: 1000;
}
.sidebar.hidden { transform: translateX(-100%); }

/* Marca */
.sidebar .brand {
  display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
}
.sidebar .logo {
  background: #fff;
  color: var(--act1, #b38158);
  font-weight: bold; font-size: 1.2rem;
  display: flex; align-items: center; justify-content: center;
  width: 40px; height: 40px; border-radius: 10px;
}
.sidebar .brand-meta small { color: rgba(255,255,255,.7); }

/* Menú */
.sidebar .menu {
  flex: 1; display: flex; flex-direction: column; gap: 8px;
}
.sidebar .menu .item {
  display: flex; align-items: center; gap: 10px;
  padding: 10px; border-radius: 8px;
  color: #fff; text-decoration: none; font-weight: 500;
  transition: all 0.3s ease;
}
.sidebar .menu .item:hover {
  background: linear-gradient(90deg, rgba(255,255,255,0.15), rgba(255,255,255,0.05));
  transform: translateX(6px);
}
.sidebar .menu .item.active {
  background: linear-gradient(90deg, var(--act1,#b38158), var(--act2,#9a6c52));
  font-weight: 600;
  box-shadow: inset 4px 0 var(--act3,#7a5a43);
}
.sidebar .menu .logout {
  margin-top: auto; background: var(--danger,#e74c3c); color: #fff; text-align: center;
}
.sidebar .menu .logout:hover { background: #c0392b; }
.sidebar .menu .toggle-theme {
  cursor: pointer; background: transparent; border: none; text-align: left;
  font-size: 1rem; color: inherit;
}

/* Floating card */
.sidebar .floating-card {
  margin-top: 20px; padding: 12px;
  background: rgba(255,255,255,0.15); border-radius: 10px;
  font-size: .85rem; text-align: center;
  line-height: 1.3;
}

/* Dark mode */
body.dark .sidebar {
  background: linear-gradient(180deg, #2c2723, #3b3129, #9b704c);
  color: #eee;
}
body.dark .sidebar .menu .item { color: #eee; }
body.dark .sidebar .menu .item.active {
  background: linear-gradient(90deg,#d6a374,#c28a61);
  box-shadow: inset 4px 0 #9b704c;
}
body.dark .sidebar .floating-card { background: rgba(255,255,255,0.08); }

/* Responsive */
@media(max-width:840px){
  .sidebar { transform: translateX(-100%); }
  .sidebar.show { transform: translateX(0); }
}
</style>

<script>
// 🌙 Dark Mode con localStorage
const body = document.body;
const darkToggle = document.getElementById("darkToggle");

// Cargar preferencia
if (localStorage.getItem("theme") === "dark") {
  body.classList.add("dark");
  darkToggle.textContent = "☀️ Modo Claro";
}

// Alternar tema
darkToggle?.addEventListener("click", () => {
  body.classList.toggle("dark");
  const dark = body.classList.contains("dark");
  localStorage.setItem("theme", dark ? "dark" : "light");
  darkToggle.textContent = dark ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
});
</script>
