<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
  header("Location: ../login.php?error=unauthorized"); exit();
}
$inventario = getInventario();
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Inventario</title>
<link rel="stylesheet" href="../../css/dashboard.css"></head>
<body>
<?php include __DIR__."/../partials/header.php"; ?>
<main class="content">
  <h1>Inventario</h1>
  <a href="inventario-agregar.php" class="btn btn-primary">+ Agregar stock</a>
  <a href="movimientos-listar.php" class="btn">📜 Ver movimientos</a>
  <table class="table">
    <thead><tr><th>Producto</th><th>Sucursal</th><th>Cantidad</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach($inventario as $i): ?>
      <tr>
        <td><?= htmlspecialchars($i['producto']) ?></td>
        <td><?= $i['sucursal'] ?></td>
        <td><?= $i['cantidad'] ?></td>
        <td>
          <a href="inventario-editar.php?id=<?= $i['id'] ?>" class="btn btn-sm">✏️</a>
          <a href="inventario-eliminar.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar stock?')">🗑</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
