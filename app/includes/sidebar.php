<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <button id="theme-toggle" class="theme-btn">Modo Oscuro</button>
  <a href="index.php">Categorías</a>
  <a href="#">Marcas</a>
  <a href="producto.php">Proyectos</a>
  <a href="#">Servicios</a>
  
  <hr>
  
  <?php
  session_start();
  if (isset($_SESSION['usuario_id'])): ?>
      <!-- Si ya inició sesión -->
      <p style="margin:10px 0; font-weight:bold;">👋 Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></p>
      <a href="logout.php">🚪 Cerrar Sesión</a>
  <?php else: ?>
      <!-- Si NO ha iniciado sesión -->
      <a href="views/login.php">🔑 Iniciar Sesión</a>
      <a href="views/register.php">📝 Registrarse</a>
  <?php endif; ?>
</aside>
