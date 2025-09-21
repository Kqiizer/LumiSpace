<?php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__."/../../config/functions.php";
if (!isset($_SESSION['usuario_id'])||!in_array($_SESSION['usuario_rol'],['admin','gestor'])) {
  header("Location: ../login.php?error=unauthorized"); exit();
}
$id=(int)($_GET['id']??0);
if($id>0 && eliminarInventario($id)){
  header("Location: inventario-listar.php?deleted=1"); exit();
}
header("Location: inventario-listar.php?error=1");
