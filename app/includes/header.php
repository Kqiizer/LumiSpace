<header class="main-header">
  <div class="logo">
    <a href="index.php">
      <img src="images/logo.png" alt="LumiSpace" class="logo-img">
      <span class="logo-text">Lumi<span>Space</span></span>
    </a>
  </div>

  <!-- NAV -->
  <nav class="navbar">
    <ul>
      <li><a href="index.php" class="<?= ($activePage == 'home') ? 'active' : '' ?>">Inicio</a></li>
      <li><a href="categorias.php" class="<?= ($activePage == 'categorias') ? 'active' : '' ?>">Categorías</a></li>
      <li><a href="marcas.php" class="<?= ($activePage == 'marcas') ? 'active' : '' ?>">Marcas</a></li>
      <li><a href="proyectos.php" class="<?= ($activePage == 'proyectos') ? 'active' : '' ?>">Proyectos</a></li>
      <li><a href="servicios.php" class="<?= ($activePage == 'servicios') ? 'active' : '' ?>">Servicios</a></li>
    </ul>
  </nav>

  <!-- ACCIONES -->
  <div class="header-actions">
    <!-- Buscador -->
    <form action="buscar.php" method="GET" class="search-form">
      <input type="text" name="q" placeholder="Buscar..." required>
      <button type="submit">🔍</button>
    </form>

    <!-- Botón de login -->
    <a href="views/login.php" class="btn-login">Iniciar Sesión</a>

    <!-- Botón dark/light -->
    <button id="theme-toggle" class="theme-btn">🌙</button>

    <!-- Menú móvil -->
    <div class="menu-icon" id="menu-btn">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</header>
