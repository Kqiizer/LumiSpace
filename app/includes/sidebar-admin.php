<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$usuarioNombre = $_SESSION['usuario_nombre'] ?? "Admin Invitado";
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <h2>⚙️ Admin</h2>
    <p><?= htmlspecialchars($usuarioNombre) ?></p>
  </div>

  <nav class="sidebar-nav">
    <a href="../views/dashboard-admin.php">🏠 Dashboard</a>
    <a href="../admin/usuarios.php">👥 Gestionar Usuarios</a>
    <a href="../admin/productos.php">📦 Gestionar Productos</a>
    <a href="../admin/reportes.php">📈 Reportes Globales</a>
    <a href="../admin/configuracion.php">⚙️ Configuración</a>
  </nav>

  <div class="sidebar-footer">
    <a href="../logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
  </div>
</aside>

<style>
/* Sidebar */
.sidebar {
  width:240px; height:100vh; position:fixed; top:0; left:0;
  background:var(--bg-2,#f8f8f8); border-right:1px solid rgba(0,0,0,.1);
  display:flex; flex-direction:column; justify-content:space-between;
  padding:1rem; box-shadow:2px 0 6px rgba(0,0,0,.05);
}

.sidebar-header {
  text-align:center; margin-bottom:2rem;
}
.sidebar-header h2 {
  margin:0; font-size:1.2rem; color:var(--act1,#8f5e4b);
}
.sidebar-header p {
  margin:0; font-size:.9rem; color:var(--muted,#666);
}

.sidebar-nav {
  display:flex; flex-direction:column; gap:.8rem;
}
.sidebar-nav a {
  text-decoration:none; color:var(--text,#333);
  font-size:.95rem; padding:.6rem 1rem;
  border-radius:6px; display:flex; align-items:center; gap:.5rem;
  transition:background .2s;
}
.sidebar-nav a:hover {
  background:rgba(0,0,0,0.05);
}

.sidebar-footer {
  margin-top:2rem; text-align:center;
}
.btn-logout {
  display:inline-block; text-decoration:none;
  background:#d9534f; color:#fff; font-size:.9rem;
  padding:.5rem 1rem; border-radius:6px;
  transition:background .2s;
}
.btn-logout:hover {
  background:#c9302c;
}
</style>
