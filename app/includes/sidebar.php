<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <button id="theme-toggle" class="theme-btn">🌙 Modo Oscuro</button>
  <a href="categorias.php">Categorías</a>
  <a href="marcas.php">Marcas</a>
  <a href="proyectos.php">Proyectos</a>
  <a href="servicios.php">Servicios</a>
  <hr>
  <?php if (isset($_SESSION['usuario_id'])): ?>
    <p style="margin:10px 0; font-weight:bold;">
      👋 Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?>
    </p>
    <a href="logout.php">🚪 Cerrar Sesión</a>
  <?php else: ?>
    <a href="views/login.php">🔑 Iniciar Sesión</a>
    <a href="views/register.php">📝 Registrarse</a>
  <?php endif; ?>
</aside>
