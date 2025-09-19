<?php
session_start();
if ($_SESSION['usuario_rol'] !== 'admin') {
  header("Location: ../views/login.php"); exit;
}
require_once __DIR__ . "/../config/functions.php";

$conn = getDBConnection();
$res = $conn->query("SELECT * FROM categorias ORDER BY created_at DESC");
$categorias = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías - Admin</title>
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<?php include("../includes/sidebar-admin.php"); ?>
<main class="main">
  <h1>Categorías</h1>
  <a href="categorias-form.php" class="btn primary">➕ Nueva categoría</a>
  <table class="table mt-16">
    <thead>
      <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Acciones</th></tr>
    </thead>
    <tbody>
      <?php foreach($categorias as $c): ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><?= htmlspecialchars($c['nombre']) ?></td>
        <td><?= htmlspecialchars($c['descripcion'] ?? '') ?></td>
        <td>
          <a href="categorias-form.php?id=<?= $c['id'] ?>">✏️ Editar</a>
          <a href="categorias-delete.php?id=<?= $c['id'] ?>" onclick="return confirm('¿Eliminar categoría?')">🗑️ Eliminar</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
