<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/functions.php';

/* BASE para rutas absolutas/relativas */
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/').'/' : '/';

/* Página actual */
$currentPage = basename($_SERVER['PHP_SELF']);

/* Contadores */
$carritoCount = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;

$favoritosCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $conn = getDBConnection();
    if ($stmt = $conn->prepare("SELECT COUNT(*) as c FROM favoritos WHERE usuario_id=?")) {
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->bind_result($c);
        if ($stmt->fetch()) $favoritosCount = (int)$c;
        $stmt->close();
    }
} else {
    // fallback si aún usas favoritos en sesión cuando no hay login
    $favoritosCount = isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>LumiSpace</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Estilos (rutas limpias con BASE) -->
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/header.css" />
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css" />
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">

<!-- Top Bar -->
<div class="top-bar">
  <div class="container">
    <span>📞 +52 123 456 7890 | ✉ contacto@lumispace.com</span>
    <div class="social-icons">
      <a href="#"><i class="fab fa-facebook-f"></i></a>
      <a href="#"><i class="fab fa-twitter"></i></a>
      <a href="#"><i class="fab fa-instagram"></i></a>
    </div>
  </div>
</div>

<!-- Header -->
<header class="header">
  <div class="container">
    <!-- Logo -->
    <a href="<?= $BASE ?>index.php" class="logo">
      <div class="logo-icon"><i class="fas fa-couch"></i></div>
      <span>LumiSpace</span>
    </a>

    <!-- Menú Desktop -->
    <ul class="nav-menu">
      <li><a href="<?= $BASE ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Inicio</a></li>
      <li><a href="<?= $BASE ?>views/categorias.php" class="<?= $currentPage === 'categorias.php' ? 'active' : '' ?>">Categorías</a></li>
      <li><a href="<?= $BASE ?>views/marcas.php" class="<?= $currentPage === 'marcas.php' ? 'active' : '' ?>">Marcas</a></li>
      <li><a href="<?= $BASE ?>views/catalogo.php" class="<?= $currentPage === 'catalogo.php' ? 'active' : '' ?>">Catálogo</a></li>
      <li><a href="<?= $BASE ?>views/contacto.php" class="<?= $currentPage === 'contacto.php' ? 'active' : '' ?>">Contacto</a></li>
    </ul>

    <!-- Íconos -->
    <div class="header-icons">
      <!-- Buscar -->
      <a href="<?= $BASE ?>views/search.php" class="icon-btn <?= $currentPage === 'search.php' ? 'active' : '' ?>">
        <i class="fas fa-search"></i>
      </a>

      <!-- Favoritos -->
      <a href="<?= $BASE ?>views/favoritos.php" class="icon-btn <?= $currentPage === 'favoritos.php' ? 'active' : '' ?>">
        <i class="fas fa-heart"></i>
        <span class="badge" id="fav-badge" style="<?= $favoritosCount ? '' : 'display:none;' ?>"><?= $favoritosCount ?></span>
      </a>

      <!-- Carrito -->
      <a href="<?= $BASE ?>includes/carrito.php" class="icon-btn <?= $currentPage === 'carrito.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-cart"></i>
        <span class="badge" id="cart-badge" style="<?= $carritoCount ? '' : 'display:none;' ?>"><?= $carritoCount ?></span>
      </a>

      <!-- Hamburguesa -->
      <button class="menu-toggle" id="menu-btn" aria-label="Abrir menú" aria-expanded="false">
        <span class="top"></span>
        <span class="middle"></span>
        <span class="bottom"></span>
      </button>
    </div>
  </div>
</header>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar (Móvil) -->
<aside class="sidebar" id="sidebar">
  <!-- Botón cambio de tema (si lo usas) -->
  <button id="theme-toggle" class="btn">🌙 Modo Oscuro</button>

  <!-- Menú móvil -->
  <a href="<?= $BASE ?>index.php" class="btn <?= $currentPage === 'index.php' ? 'active' : '' ?>">🏠 Inicio</a>
  <a href="<?= $BASE ?>views/categorias.php" class="btn <?= $currentPage === 'categorias.php' ? 'active' : '' ?>">📂 Categorías</a>
  <a href="<?= $BASE ?>views/marcas.php" class="btn <?= $currentPage === 'marcas.php' ? 'active' : '' ?>">🏷 Marcas</a>
  <a href="<?= $BASE ?>views/catalogo.php" class="btn <?= $currentPage === 'catalogo.php' ? 'active' : '' ?>">🛍 Catálogo</a>
  <a href="<?= $BASE ?>views/blog.php" class="btn <?= $currentPage === 'blog.php' ? 'active' : '' ?>">📰 Blog</a>
  <a href="<?= $BASE ?>views/contacto.php" class="btn <?= $currentPage === 'contacto.php' ? 'active' : '' ?>">📞 Contacto</a>

  <hr>

  <?php if (!empty($_SESSION['usuario_id'])): ?>
    <p style="margin:10px 0; font-weight:bold;">
      👋 Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
    </p>
    <a href="<?= $BASE ?>logout.php" class="btn">🚪 Cerrar Sesión</a>
  <?php else: ?>
    <a href="<?= $BASE ?>views/login.php" class="btn">🔑 Iniciar Sesión</a>
    <a href="<?= $BASE ?>views/register.php" class="btn">📝 Registrarse</a>
  <?php endif; ?>
</aside>

<!-- Scripts -->
<script src="<?= $BASE ?>js/header.js"></script>
</body>
</html>
