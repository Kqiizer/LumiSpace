<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'gestor') {
    header("Location: login.php?error=unauthorized"); exit;
}
require_once __DIR__ . "/../gestor/productos.php";
$productos = getProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Productos</title>
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <?php include("../includes/sidebar-gestor.php"); ?>
  <main class="main">
    <?php include("../includes/header-gestor.php"); ?>
    <section class="content">
      <h2>📦 Productos</h2>
      <a href="producto-nuevo.php" class="btn primary">➕ Nuevo Producto</a>
      <table class="table mt-16">
        <thead>
          <tr><th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Categoría</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach($productos as $p): ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['nombre']) ?></td>
              <td>$<?= number_format($p['precio'],2) ?></td>
              <td><?= $p['stock'] ?></td>
              <td><?= $p['categoria'] ?? 'N/A' ?></td>
              <td>
                <a href="producto-editar.php?id=<?= $p['id'] ?>" class="btn">✏️ Editar</a>
                <a href="producto-eliminar.php?id=<?= $p['id'] ?>" class="btn danger" onclick="return confirm('¿Eliminar producto?')">🗑 Eliminar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
</body>
</html>
