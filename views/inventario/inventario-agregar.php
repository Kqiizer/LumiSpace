<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__."/../../config/functions.php";
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
  header("Location: ../login.php?error=unauthorized"); exit();
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $producto_id=(int)$_POST['producto_id'];
  $cantidad=(int)$_POST['cantidad'];
  $sucursal=$_POST['sucursal'] ?: 'Principal';
  if (insertarInventario($producto_id,$cantidad,$sucursal)) {
    header("Location: inventario-listar.php?added=1"); exit();
  }
}
$productos=getProductos();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Agregar stock</title>
<link rel="stylesheet" href="../../css/dashboard.css"></head>
<body>
<?php include __DIR__."/../partials/header.php"; ?>
<main class="content">
<h1>Agregar stock</h1>
<form method="POST">
  <label>Producto</label>
  <select name="producto_id" required>
    <option value="">-- Selecciona --</option>
    <?php foreach($productos as $p): ?>
      <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <label>Sucursal</label>
  <input type="text" name="sucursal" value="Principal">
  <label>Cantidad inicial</label>
  <input type="number" name="cantidad" min="0" required>
  <button class="btn btn-primary">Guardar</button>
  <a href="inventario-listar.php" class="btn">Cancelar</a>
</form>
</main></body></html>
