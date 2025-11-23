// 🔹 BASE CORREGIDA
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🔹 BASE DEFINIDA CORRECTAMENTE (NO CAMBIA SEGÚN LA CARPETA)
$projectFolder = '/'; 
$BASE = rtrim($projectFolder, '/') . '/';

// Detecta página activa automáticamente
$currentPage = basename($_SERVER['PHP_SELF']);
function activeClass(string $page, string $current): string {
  return $page === $current ? 'active' : '';
}
function isGroupActive(array $pages, string $current): string {
  return in_array($current, $pages) ? 'show group-active' : '';
}
?>


<!-- ⚡ FONT AWESOME - CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Toggle para móvil -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Abrir menú">
  <i class="fas fa-bars"></i>
</button>

<!-- Overlay para móvil -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <!-- Header con logo y usuario -->
  <div class="sidebar-header">
    <div class="brand">
      <div class="logo">
        <i class="fas fa-store"></i>
      </div>
      <div class="brand-info">
        <h3 class="brand-title">LumiSpace</h3>
        <span class="brand-subtitle">Panel Admin</span>
      </div>
    </div>
    <button class="sidebar-close" id="sidebarClose">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <!-- Menú de navegación -->
  <nav class="menu" aria-label="Menú Principal">

    <!-- Dashboard -->
    <a class="menu-item <?= activeClass('dashboard-admin.php', $currentPage) ?>" 
       href="<?= $BASE ?>views/dashboard-admin.php">
      <i class="fas fa-home"></i>
      <span>Dashboard</span>
    </a>

    <!-- Usuarios -->
    <div class="menu-group">
      <button class="menu-item has-submenu <?= isGroupActive(['usuarios.php','usuario-agregar.php','usuario-editar.php','usuarios-roles.php'], $currentPage) ?>">
        <i class="fas fa-users"></i>
        <span>Empleados</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="submenu <?= isGroupActive(['usuarios.php','usuario-agregar.php','usuario-editar.php','usuarios-roles.php'], $currentPage) ?>">
        <a class="submenu-item <?= activeClass('usuarios.php', $currentPage) ?>" 
           href="<?= $BASE ?>admin/usuarios/usuarios.php">
          <i class="fas fa-list"></i>
          <span>Lista de Empleados</span>
        </a>
        <a class="submenu-item <?= activeClass('usuario-agregar.php', $currentPage) ?>" 
           href="<?= $BASE ?>admin/usuarios/usuario-agregar.php">
          <i class="fas fa-user-plus"></i>
          <span>Agregar Empleado</span>
        </a>
        <a class="submenu-item <?= activeClass('roles.php', $currentPage) ?>" 
           href="<?= $BASE ?>admin/roles/roles.php">
          <i class="fas fa-user-tag"></i>
          <span>Gestión de Roles</span>
        </a>
      </div>
    </div>

    <!-- Catálogo -->
    <div class="menu-group">
      <button class="menu-item has-submenu <?= isGroupActive(['productos.php','inventario.php','proveedores.php','categorias.php'], $currentPage) ?>">
        <i class="fas fa-box-open"></i>
        <span>Catálogo</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="submenu <?= isGroupActive(['productos.php','inventario.php','proveedores.php','categorias.php'], $currentPage) ?>">
        <a class="submenu-item <?= activeClass('productos.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/catalogo/productos.php">
          <i class="fas fa-cube"></i>
          <span>Productos</span>
        </a>
        <a class="submenu-item <?= activeClass('inventario.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/inventario/inventario.php">
          <i class="fas fa-warehouse"></i>
          <span>Inventario</span>
        </a>
        <a class="submenu-item <?= activeClass('proveedores.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/proveedores/proveedores.php">
          <i class="fas fa-truck-loading"></i>
          <span>Proveedores</span>
        </a>
        <a class="submenu-item <?= activeClass('categorias.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/categorias/categorias.php">
          <i class="fas fa-tags"></i>
          <span>Categorías</span>
        </a>
      </div>
    </div>

    <!-- Ventas -->
    <div class="menu-group">
      <button class="menu-item has-submenu <?= isGroupActive(['ventas.php','punto-venta.php','ordenes.php'], $currentPage) ?>">
        <i class="fas fa-cash-register"></i>
        <span>Ventas</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="submenu <?= isGroupActive(['ventas.php','punto-venta.php','ordenes.php'], $currentPage) ?>">
        <a class="submenu-item <?= activeClass('punto-venta.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/ventas/punto-venta.php">
          <i class="fas fa-shopping-cart"></i>
          <span>Punto de Venta</span>
        </a>
        <a class="submenu-item <?= activeClass('ventas.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/ventas/ventas.php">
          <i class="fas fa-receipt"></i>
          <span>Historial de Ventas</span>
        </a>
        <a class="submenu-item <?= activeClass('ordenes.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/ventas/ordenes.php">
          <i class="fas fa-clipboard-list"></i>
          <span>Órdenes Online</span>
        </a>
      </div>
    </div>

    <!-- Reportes -->
    <div class="menu-group">
      <button class="menu-item has-submenu <?= isGroupActive(['reportes.php','reportes-ventas.php','reportes-inventario.php'], $currentPage) ?>">
        <i class="fas fa-chart-line"></i>
        <span>Reportes</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="submenu <?= isGroupActive(['reportes.php','reportes-ventas.php','reportes-inventario.php'], $currentPage) ?>">
        <a class="submenu-item <?= activeClass('reportes.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/reportes/reportes.php">
          <i class="fas fa-file-chart-line"></i>
          <span>Reportes Generales</span>
        </a>
        <a class="submenu-item <?= activeClass('reportes-ventas.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/reportes/reportes-ventas.php">
          <i class="fas fa-chart-bar"></i>
          <span>Análisis de Ventas</span>
        </a>
        <a class="submenu-item <?= activeClass('reportes-inventario.php', $currentPage) ?>" 
           href="<?= $BASE ?>views/reportes/reportes-inventario.php">
          <i class="fas fa-boxes"></i>
          <span>Stock y Movimientos</span>
        </a>
      </div>
    </div>

    <!-- Configuración -->
    <a class="menu-item <?= activeClass('configuracion.php', $currentPage) ?>" 
       href="<?= $BASE ?>views/configuracion.php">
      <i class="fas fa-cog"></i>
      <span>Configuración</span>
    </a>

  </nav>

  <!-- Footer del sidebar -->
  <div class="sidebar-footer">
    <button class="theme-toggle" id="themeToggle">
      <i class="fas fa-moon"></i>
      <span>Modo Oscuro</span>
    </button>
    
    <div class="footer-info">
      <div class="footer-logo">
        <i class="fas fa-store"></i>
      </div>
      <div class="footer-text">
        <strong>LumiSpace</strong>
        <small>© <?= date('Y') ?> Todos los derechos reservados</small>
      </div>
    </div>

    <a href="<?= $BASE ?>logout.php"class="logout-btn">
      <i class="fas fa-sign-out-alt"></i>
      <span>Cerrar Sesión</span>
    </a>
  </div>
</aside>

<style>
:root {
  --sidebar-width: 280px;
  --primary: #a1683a;
  --primary-dark: #8f5e4b;
  --sidebar-bg: linear-gradient(180deg, #2d2520 0%, #1a1612 100%);
  --sidebar-text: #f5f3f0;
  --sidebar-hover: rgba(161, 104, 58, 0.15);
  --sidebar-active: linear-gradient(135deg, #a1683a, #8f5e4b);
  --submenu-bg: rgba(0, 0, 0, 0.2);
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.1);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.2);
  --radius-sm: 8px;
  --radius-md: 12px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Sidebar principal */
.sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  color: var(--sidebar-text);
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 20px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  overflow-y: auto;
  overflow-x: hidden;
  transition: var(--transition);
}

.sidebar::-webkit-scrollbar {
  width: 6px;
}

.sidebar::-webkit-scrollbar-thumb {
  background: rgba(161, 104, 58, 0.3);
  border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
  background: rgba(161, 104, 58, 0.5);
}

/* Header del sidebar */
.sidebar-header {
  padding: 24px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
}

.logo i {
  display: inline-block;
  font-style: normal;
  font-variant: normal;
  text-rendering: auto;
  line-height: 1;
}

.brand-info {
  display: flex;
  flex-direction: column;
}

.brand-title {
  font-size: 1.3rem;
  font-weight: 700;
  margin: 0;
  color: #fff;
  letter-spacing: 0.5px;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  letter-spacing: 1px;
}

.sidebar-close {
  display: none;
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition);
}

.sidebar-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Menú de navegación */
.menu {
  flex: 1;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.menu-group {
  margin-bottom: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  color: var(--sidebar-text);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: var(--transition);
  cursor: pointer;
  background: transparent;
  border: none;
  width: 100%;
  position: relative;
}

/* 🎯 ESTILOS CRÍTICOS PARA ICONOS */
.menu-item i {
  font-style: normal !important;
  font-variant: normal !important;
  text-rendering: auto !important;
  line-height: 1 !important;
  -webkit-font-smoothing: antialiased !important;
  -moz-osx-font-smoothing: grayscale !important;
}

.menu-item i:first-child {
  width: 20px;
  text-align: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.menu-item span {
  flex: 1;
  text-align: left;
}

.menu-item .arrow {
  width: auto;
  font-size: 0.75rem;
  transition: var(--transition);
  opacity: 0.6;
}

.menu-item.has-submenu.group-active .arrow {
  transform: rotate(180deg);
}

.menu-item:hover {
  background: var(--sidebar-hover);
  color: #fff;
  transform: translateX(4px);
}

.menu-item.active,
.menu-item.group-active {
  background: var(--sidebar-active);
  color: #fff;
  box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
}

.menu-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 60%;
  background: #fff;
  border-radius: 0 4px 4px 0;
}

/* Submenú */
.submenu {
  display: none;
  flex-direction: column;
  gap: 2px;
  padding: 8px 0 8px 20px;
  margin-left: 16px;
  border-left: 2px solid rgba(161, 104, 58, 0.3);
  animation: slideDown 0.3s ease;
}

.submenu.show {
  display: flex;
}

.submenu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  font-size: 0.9rem;
  transition: var(--transition);
}

/* 🎯 Iconos en submenú */
.submenu-item i {
  width: 18px;
  text-align: center;
  font-size: 0.9rem;
  flex-shrink: 0;
  font-style: normal !important;
}

.submenu-item:hover {
  background: rgba(161, 104, 58, 0.1);
  color: #fff;
  transform: translateX(4px);
}

.submenu-item.active {
  background: rgba(161, 104, 58, 0.2);
  color: var(--primary);
  font-weight: 600;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Footer del sidebar */
.sidebar-footer {
  padding: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.theme-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  background: rgba(255, 255, 255, 0.05);
  color: var(--sidebar-text);
  border: none;
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.9rem;
  font-weight: 500;
}

.theme-toggle i {
  font-size: 1rem;
  flex-shrink: 0;
}

.theme-toggle:hover {
  background: rgba(255, 255, 255, 0.1);
}

.footer-info {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: var(--radius-sm);
}

.footer-logo {
  width: 32px;
  height: 32px;
  background: rgba(161, 104, 58, 0.2);
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary);
  font-size: 1rem;
}

.footer-logo i {
  font-style: normal !important;
}

.footer-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.footer-text strong {
  font-size: 0.9rem;
  color: #fff;
}

.footer-text small {
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.5);
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  background: rgba(220, 53, 69, 0.1);
  color: #ff6b6b;
  text-decoration: none;
  font-weight: 500;
  transition: var(--transition);
  font-size: 0.9rem;
}

.logout-btn i {
  font-size: 1rem;
  flex-shrink: 0;
  font-style: normal !important;
}

.logout-btn:hover {
  background: rgba(220, 53, 69, 0.2);
  transform: translateX(4px);
}

/* Toggle móvil */
.sidebar-toggle {
  display: none;
  position: fixed;
  top: 20px;
  left: 20px;
  width: 44px;
  height: 44px;
  border: none;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #fff;
  border-radius: var(--radius-md);
  cursor: pointer;
  z-index: 1001;
  box-shadow: var(--shadow-md);
  transition: var(--transition);
}

.sidebar-toggle:hover {
  transform: scale(1.05);
}

.sidebar-toggle i {
  font-size: 1.2rem;
  font-style: normal !important;
}

.sidebar-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  backdrop-filter: blur(2px);
}

/* Dark mode */
body.dark .sidebar {
  --sidebar-bg: linear-gradient(180deg, #0f0d0b 0%, #1a1612 100%);
}

/* Responsive */
@media (max-width: 1024px) {
  .sidebar {
    transform: translateX(-100%);
  }

  .sidebar.active {
    transform: translateX(0);
  }

  .sidebar-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .sidebar-overlay.active {
    display: block;
  }

  .sidebar-close {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
</style>

<script>
// ====== TEMA OSCURO/CLARO ======
const themeToggle = document.getElementById('themeToggle');
const body = document.body;

function updateThemeButton() {
  const isDark = body.classList.contains('dark');
  const icon = themeToggle.querySelector('i');
  const span = themeToggle.querySelector('span');
  
  if (isDark) {
    icon.className = 'fas fa-sun';
    span.textContent = 'Modo Claro';
  } else {
    icon.className = 'fas fa-moon';
    span.textContent = 'Modo Oscuro';
  }
}

// Cargar tema guardado
if (localStorage.getItem('theme') === 'dark') {
  body.classList.add('dark');
}
updateThemeButton();

// Toggle tema
themeToggle?.addEventListener('click', () => {
  body.classList.toggle('dark');
  localStorage.setItem('theme', body.classList.contains('dark') ? 'dark' : 'light');
  updateThemeButton();
});

// ====== SUBMENÚS ======
document.querySelectorAll('.menu-item.has-submenu').forEach(button => {
  button.addEventListener('click', (e) => {
    e.preventDefault();
    const submenu = button.nextElementSibling;
    const isOpen = submenu.classList.contains('show');
    
    // Cerrar otros submenús
    document.querySelectorAll('.submenu').forEach(s => s.classList.remove('show'));
    document.querySelectorAll('.menu-item.has-submenu').forEach(b => b.classList.remove('group-active'));
    
    // Toggle actual
    if (!isOpen) {
      submenu.classList.add('show');
      button.classList.add('group-active');
    }
  });
});

// ====== SIDEBAR MÓVIL ======
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebarClose = document.getElementById('sidebarClose');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
  sidebar?.classList.add('active');
  sidebarOverlay?.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  sidebar?.classList.remove('active');
  sidebarOverlay?.classList.remove('active');
  document.body.style.overflow = '';
}

sidebarToggle?.addEventListener('click', openSidebar);
sidebarClose?.addEventListener('click', closeSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);

// Cerrar sidebar al hacer clic en un link (móvil)
if (window.innerWidth <= 1024) {
  document.querySelectorAll('.menu-item:not(.has-submenu), .submenu-item').forEach(link => {
    link.addEventListener('click', closeSidebar);
  });
}

// ====== LOG DE VERIFICACIÓN ======
console.log('✅ Sidebar ADMIN cargado correctamente');
console.log('🎨 Font Awesome:', document.querySelector('link[href*="font-awesome"]') ? 'Cargado' : 'No encontrado');
</script>