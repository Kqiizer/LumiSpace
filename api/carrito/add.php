<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
  session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../../config/functions.php";

$data = json_decode(file_get_contents("php://input"), true);
$producto_id = (int) ($data['producto_id'] ?? $data['product_id'] ?? 0);
$cantidad = max(1, (int) ($data['cantidad'] ?? $data['qty'] ?? 1));

if ($producto_id <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'Producto inválido']);
  exit;
}

// Obtener información del producto
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id, nombre, precio, imagen FROM productos WHERE id=?");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
$stmt->close();

if (!$producto) {
  echo json_encode(['ok' => false, 'msg' => 'El producto no existe']);
  exit;
}

// Agregar al carrito usando la función del sistema
// Esta función maneja tanto usuarios logueados (BD) como invitados (sesión)
carritoAgregar($producto_id, $cantidad);

// Preparar respuesta
echo json_encode([
  'ok' => true,
  'msg' => '🛒 Producto agregado al carrito',
  'producto' => [
    'id' => (int) $producto['id'],
    'nombre' => $producto['nombre'],
    'precio' => (float) $producto['precio'],
    'imagen' => publicImageUrl($producto['imagen'] ?? 'images/default.png'),
    'cantidad' => $cantidad
  ]
]);
