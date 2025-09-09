<header class="navbar">
  <div class="logo">
    <!-- Logo (activo en inicio) -->
    <a href="/LumiSpace/index.php" 
       class="logo-link <?php echo ($activePage === 'home') ? 'active' : ''; ?>">
      Lumi<span>Space</span>
    </a>
  </div>

  <nav class="menu">
    <a href="/LumiSpace/views/categorias.php" 
       class="<?php echo ($activePage === 'categorias') ? 'active' : ''; ?>">
       CATEGORÍAS
    </a>
    <a href="/LumiSpace/views/marcas.php" 
       class="<?php echo ($activePage === 'marcas') ? 'active' : ''; ?>">
       MARCAS
    </a>
    <a href="/LumiSpace/views/proyectos.php" 
       class="<?php echo ($activePage === 'proyectos') ? 'active' : ''; ?>">
       PROYECTOS
    </a>
    <a href="/LumiSpace/views/servicios.php" 
       class="<?php echo ($activePage === 'servicios') ? 'active' : ''; ?>">
       SERVICIOS
    </a>
  </nav>

  <div class="header-actions">
    <button class="menu-toggle" id="menu-btn" aria-label="Abrir configuración">
      <svg class="hamburger" viewBox="0 0 100 80" width="28" height="28">
        <rect class="line top" width="100" height="10"></rect>
        <rect class="line middle" y="30" width="100" height="10"></rect>
        <rect class="line bottom" y="60" width="100" height="10"></rect>
      </svg>
    </button>
  </div>
</header>
