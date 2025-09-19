<?php
session_start();
if ($_SESSION['usuario_rol'] !== 'admin') {
  header("Location: ../views/login.php"); exit;
}
require_once __DIR__ . "/../config/functions.php";

$conn = getDBConnection();
$id = $_GET['id'] ?? null;
$categoria = ['nombre'=>'','descripcion'=>''];

if ($id) {
  $stmt = $conn->prepare("SELECT * FROM categorias WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $categoria = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nombre = trim($_POST['nombre']);
  $desc   = trim($_POST['descripcion']);

  if ($id) {
    $stmt=$conn->prepare("UPDATE categorias SET nombre=?, descripcion=? WHERE id=?");
    $stmt->bind_param("ssi",$nombre,$desc,$id);
  } else {
    $stmt=$conn->prepare("INSERT INTO categorias (nombre,descripcion) VALUES (?,?)");
    $stmt->bind_param("ss",$nombre,$desc);
  }
  $stmt->execute();
  header("Location: categorias.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title><?= $id?'Editar':'Nueva' ?> Categoría</title></head>
<body>
<h1><?= $id?'Editar':'Nueva' ?> Categoría</h1>
<form method="post">
  <label>Nombre: <input type="text" name="nombre" required value="<?=htmlspecialchars($categoria['nombre'])?>"></label><br>
  <label>Descripción: <textarea name="descripcion"><?=htmlspecialchars($categoria['descripcion'])?></textarea></label><br>
  <button type="submit">Guardar</button>
</form>
</body>
</html>
