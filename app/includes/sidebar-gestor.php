<?php
// Detecta página activa automáticamente
$currentPage = basename($_SERVER['PHP_SELF']);
function activeClass($page, $current) {
  return $page === $current ? 'active' : '';
}
?>
<aside class="sidebar">
  <div class="brand">
    <div class="logo">L</div>
    <div class="brand-meta">
      <strong>LUMISPACE</strong>
      <small>Iluminaria Premium</small>
    </div>
  </div>

  <nav class="menu">
    <a class="item <?= activeClass('dashboard-gestor.php', $currentPage) ?>" href="dashboard-gestor.php">🏠 <span>Dashboard</span></a>
    <a class="item <?= activeClass('punto-venta.php', $currentPage) ?>" href="punto-venta.php">🧾 <span>Punto de Venta</span></a>
    <a class="item <?= activeClass('productos.php', $currentPage) ?>" href="productos.php">📚 <span>Catálogo</span></a>
    <a class="item <?= activeClass('inventario.php', $currentPage) ?>" href="inventario.php">📦 <span>Inventario</span></a>
    <a class="item <?= activeClass('facturacion.php', $currentPage) ?>" href="facturacion.php">💳 <span>Facturación</span></a>
    <a class="item <?= activeClass('reportes.php', $currentPage) ?>" href="reportes.php">📊 <span>Estadísticas</span></a>
    <hr>
    <!-- 🔑 Cierre de sesión seguro -->
    <a class="item logout" href="../logout.php">🚪 <span>Cerrar Sesión</span></a>
  </nav>

  <div class="floating-card">
    <div class="fc-title">Corte de Caja</div>
    <div class="fc-sub">Sistema Activo en línea</div>
    <div class="fc-small">© <?= date('Y') ?> LUMISPACE</div>
  </div>
</aside>

<style>
/* Sidebar */
.sidebar {
  width: 250px;
  background: var(--bg-1, #fff);
  border-right: 1px solid rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
}
.sidebar .brand {
  display:flex; align-items:center; gap:10px; margin-bottom:20px;
}
.sidebar .logo {
  background: var(--act1, #8f5e4b);
  color:#fff; font-weight:bold; font-size:1.2rem;
  display:flex; align-items:center; justify-content:center;
  width:40px; height:40px; border-radius:10px;
}
.sidebar .brand-meta small { color: var(--muted, #666); }

/* Menu */
.sidebar .menu {
  flex:1; display:flex; flex-direction:column; gap:8px;
}
.sidebar .menu .item {
  display:flex; align-items:center; gap:10px;
  padding:10px; border-radius:8px;
  color: var(--text, #333); text-decoration:none; font-weight:500;
  transition: all 0.3s ease;
}
.sidebar .menu .item:hover {
  background: rgba(0,0,0,0.05); transform: translateX(4px);
}
.sidebar .menu .item.active {
  background: var(--act1, #8f5e4b);
  color:#fff; font-weight:600;
}
.sidebar .menu .logout {
  margin-top:auto; background: #e74c3c; color:#fff;
}
.sidebar .menu .logout:hover {
  background:#c0392b;
}

/* Floating card */
.sidebar .floating-card {
  margin-top:20px; padding:10px;
  background: rgba(0,0,0,0.05); border-radius:10px;
  font-size:.85rem; text-align:center;
}
</style>
