<?php
session_start();
if ($_SESSION['usuario_rol'] !== 'admin') {
  header("Location: ../views/login.php"); exit;
}
require_once __DIR__ . "/../config/functions.php";

$id = $_GET['id'] ?? null;
if ($id) {
  $conn = getDBConnection();
  // Primero asegurarse que no se rompan productos
  $stmt = $conn->prepare("UPDATE productos SET categoria_id=NULL WHERE categoria_id=?");
  $stmt->bind_param("i",$id);
  $stmt->execute();

  $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
  $stmt->bind_param("i",$id);
  $stmt->execute();
}
header("Location: categorias.php");
exit;
