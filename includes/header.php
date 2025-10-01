<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>LumiSpace</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- ✅ Estilos -->
  <link rel="stylesheet" href="css/styles/header.css">
  <link rel="stylesheet" href="css/styles/sidebar.css">
</head>
<body>

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
    <div class="logo">
      <div class="logo-icon">
        <i class="fas fa-couch"></i>
      </div>
      <span>LumiSpace</span>
    </div>

    <!-- Menú Desktop -->
    <ul class="nav-menu">
      <li><a href="index.php">Inicio</a></li>
      <li><a href="categorias.php">Categorías</a></li>
      <li><a href="marcas.php">Marcas</a></li>
      <li><a href="proyectos.php">Proyectos</a></li>
      <li><a href="servicios.php">Servicios</a></li>
      <li><a href="contacto.php">Contacto</a></li>
    </ul>

    <!-- Íconos + Hamburguesa -->
    <div class="header-icons">
      <i class="fas fa-search"></i>
      <i class="fas fa-heart"></i>
      <i class="fas fa-shopping-cart"></i>

      <!-- Botón hamburguesa -->
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

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <!-- Botón cambio de tema -->
  <button id="theme-toggle" class="btn">
    <span class="animation"></span> 🌙 Modo Oscuro <span class="animation"></span>
  </button>

  <!-- Menú móvil -->
  <a href="index.php" class="btn"><span class="animation"></span> 🏠 Home <span class="animation"></span></a>
  <a href="shop.php" class="btn"><span class="animation"></span> 🛍 Shop <span class="animation"></span></a>
  <a href="categorias.php" class="btn"><span class="animation"></span> 📂 Categorías <span class="animation"></span></a>
  <a href="marcas.php" class="btn"><span class="animation"></span> 🏷 Marcas <span class="animation"></span></a>
  <a href="proyectos.php" class="btn"><span class="animation"></span> 📐 Proyectos <span class="animation"></span></a>
  <a href="servicios.php" class="btn"><span class="animation"></span> ⚙ Servicios <span class="animation"></span></a>
  <a href="blog.php" class="btn"><span class="animation"></span> 📰 Blog <span class="animation"></span></a>

  <hr>

  <?php if (isset($_SESSION['usuario_id'])): ?>
    <p style="margin:10px 0; font-weight:bold;">
      👋 Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
    </p>
    <a href="logout.php" class="btn"><span class="animation"></span> 🚪 Cerrar Sesión <span class="animation"></span></a>
  <?php else: ?>
    <a href="views/login.php" class="btn"><span class="animation"></span> 🔑 Iniciar Sesión <span class="animation"></span></a>
    <a href="views/register.php" class="btn"><span class="animation"></span> 📝 Registrarse <span class="animation"></span></a>
  <?php endif; ?>
</aside>

<!-- ✅ Scripts -->
<include de sidebar.js y header.js -->
<script src="js/header.js"></script> <!-- ✅ control header + sidebar -->
</body>
</html>
