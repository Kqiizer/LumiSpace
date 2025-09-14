<header class="navbar">
  <!-- Logo -->
  <div class="logo">
    <a href="index.php" class="logo-link <?= ($activePage ?? '')=='home' ? 'active' : '' ?>">
      <img src="images/logo.png" alt="Lumi" class="logo-img" style="height:22px;">
      <span><span>Space</span></span>
    </a>
  </div>

  <!-- Menú centrado (visible en desktop, oculto en mobile) -->
  <nav class="menu">
    <a href="categorias.php" class="<?= ($activePage ?? '')=='categorias' ? 'active' : '' ?>">Categorías</a>
    <a href="marcas.php" class="<?= ($activePage ?? '')=='marcas' ? 'active' : '' ?>">Marcas</a>
    <a href="proyectos.php" class="<?= ($activePage ?? '')=='proyectos' ? 'active' : '' ?>">Proyectos</a>
    <a href="servicios.php" class="<?= ($activePage ?? '')=='servicios' ? 'active' : '' ?>">Servicios</a>
  </nav>

  <!-- Botón hamburguesa SIEMPRE visible -->
  <button class="menu-toggle" id="menu-btn" aria-label="Abrir menú">
    <span class="top"></span>
    <span class="middle"></span>
    <span class="bottom"></span>
  </button>
</header>
