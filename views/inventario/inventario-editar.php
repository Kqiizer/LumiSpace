<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__."/../../config/functions.php";
if (!isset($_SESSION['usuario_id'])||!in_array($_SESSION['usuario_rol'],['admin','gestor'])) {
  header("Location: ../login.php?error=unauthorized"); exit();
}
$id=(int)($_GET['id']??0);
$inv=getInventarioById($id);
if(!$inv) die("No encontrado.");
if($_SERVER['REQUEST_METHOD']==='POST'){
  $cantidad=(int)$_POST['cantidad'];
  $sucursal=$_POST['sucursal'];
  if(actualizarInventario($id,$cantidad,$sucursal)){
    header("Location: inventario-listar.php?updated=1"); exit();
  }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Editar stock</title>
<link rel="stylesheet" href="../../css/dashboard.css"></head>
<body>
<?php include __DIR__."/../partials/header.php"; ?>
<main class="content">
<h1>Editar stock</h1>
<form method="POST">
  <label>Sucursal</label>
  <input type="text" name="sucursal" value="<?= $inv['sucursal'] ?>">
  <label>Cantidad</label>
  <input type="number" name="cantidad" value="<?= $inv['cantidad'] ?>" min="0" required>
  <button class="btn btn-primary">Actualizar</button>
  <a href="inventario-listar.php" class="btn">Cancelar</a>
</form>
</main></body></html>
