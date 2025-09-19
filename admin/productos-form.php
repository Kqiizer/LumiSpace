<?php
session_start();
if ($_SESSION['usuario_rol'] !== 'admin') { header("Location: ../views/login.php"); exit; }
require_once __DIR__ . "/../config/functions.php";

$conn = getDBConnection();
$id = $_GET['id'] ?? null;
$producto = ['nombre'=>'','descripcion'=>'','precio'=>0,'stock'=>0,'categoria_id'=>null,'img'=>''];

if ($id) {
  $stmt = $conn->prepare("SELECT * FROM productos WHERE id=? LIMIT 1");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $producto = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $nombre=$_POST['nombre']; $desc=$_POST['descripcion']; 
  $precio=$_POST['precio']; $stock=$_POST['stock']; $cat=$_POST['categoria_id'];
  $img=$_POST['img'] ?? null;

  if ($id) {
    $stmt=$conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=?,img=? WHERE id=?");
    $stmt->bind_param("ssdiisi",$nombre,$desc,$precio,$stock,$cat,$img,$id);
  } else {
    $stmt=$conn->prepare("INSERT INTO productos (nombre,descripcion,precio,stock,categoria_id,img) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("ssdiis",$nombre,$desc,$precio,$stock,$cat,$img);
  }
  $stmt->execute();
  header("Location: productos.php"); exit;
}

$categorias = $conn->query("SELECT * FROM categorias")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Producto</title></head>
<body>
<form method="post">
  <label>Nombre: <input type="text" name="nombre" value="<?=htmlspecialchars($producto['nombre'])?>"></label><br>
  <label>Descripción: <textarea name="descripcion"><?=htmlspecialchars($producto['descripcion'])?></textarea></label><br>
  <label>Precio: <input type="number" step="0.01" name="precio" value="<?=$producto['precio']?>"></label><br>
  <label>Stock: <input type="number" name="stock" value="<?=$producto['stock']?>"></label><br>
  <label>Categoría: 
    <select name="categoria_id">
      <option value="">-- Selecciona --</option>
      <?php foreach($categorias as $c): ?>
        <option value="<?=$c['id']?>" <?=($producto['categoria_id']==$c['id'])?'selected':''?>><?=$c['nombre']?></option>
      <?php endforeach; ?>
    </select>
  </label><br>
  <label>Imagen (URL): <input type="text" name="img" value="<?=$producto['img']?>"></label><br>
  <button type="submit">Guardar</button>
</form>
</body></html>
