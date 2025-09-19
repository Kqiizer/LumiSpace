<?php
session_start();
if ($_SESSION['usuario_rol'] !== 'gestor') { header("Location: ../views/login.php"); exit; }
require_once __DIR__ . "/../config/functions.php";

$conn = getDBConnection();
$res = $conn->query("SELECT p.id,p.nombre,p.stock,c.nombre AS categoria FROM productos p 
                     LEFT JOIN categorias c ON p.categoria_id=c.id ORDER BY p.nombre");
$productos=$res->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $id=$_POST['id']; $stock=$_POST['stock'];
  $stmt=$conn->prepare("UPDATE productos SET stock=? WHERE id=?");
  $stmt->bind_param("ii",$stock,$id);
  $stmt->execute();
  header("Location: inventario.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>Inventario</title></head>
<body>
<h1>Gestión de Inventario</h1>
<table border="1">
<thead><tr><th>Producto</th><th>Categoría</th><th>Stock</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach($productos as $p): ?>
<tr>
  <td><?=$p['nombre']?></td>
  <td><?=$p['categoria']?></td>
  <td>
    <form method="post">
      <input type="hidden" name="id" value="<?=$p['id']?>">
      <input type="number" name="stock" value="<?=$p['stock']?>">
      <button type="submit">Actualizar</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
