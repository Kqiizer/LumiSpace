<?php
declare(strict_types=1);
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/carritos-funciones.php";

if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['ok' => false, 'msg' => 'Debes iniciar sesión para agregar al carrito']);
  exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];
$data = json_decode(file_get_contents("php://input"), true);
$producto_id = (int)($data['producto_id'] ?? 0);
$cantidad = max(1, (int)($data['cantidad'] ?? 1));

if ($producto_id <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'Producto inválido']);
  exit;
}

// 🔹 Obtener información del producto
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT id, nombre, precio, imagen, descripcion FROM productos WHERE id=? AND activo=1");
$stmt->bind_param("i", $producto_id);
$stmt->execute();
$res = $stmt->get_result();
$producto = $res->fetch_assoc();
$stmt->close();

if (!$producto) {
  echo json_encode(['ok' => false, 'msg' => 'El producto no existe o está inactivo']);
  exit;
}

// 🔹 Intentar guardar también en la BD (opcional, persistencia del carrito)
$ok = false;
if (function_exists('agregarProductoCarrito')) {
  $ok = agregarProductoCarrito($usuario_id, $producto_id, $cantidad);
} elseif (function_exists('addToCart')) {
  // addToCart() versión que usa localStorage en front, aquí la ignoramos
  $ok = true;
} else {
  $ok = true; // fallback para continuar en front
}

// 🔹 Preparar respuesta con datos completos para el frontend
if ($ok) {
  echo json_encode([
    'ok' => true,
    'msg' => '🛒 Producto agregado al carrito',
    'producto' => [
      'id' => (int)$producto['id'],
      'nombre' => $producto['nombre'],
      'precio' => (float)$producto['precio'],
      'imagen' => $producto['imagen'] ? (preg_match('#^https?://#', $producto['imagen']) ? $producto['imagen'] : BASE_URL . 'images/productos/' . $producto['imagen']) : BASE_URL . 'images/default.png',
      'cantidad' => $cantidad
    ]
  ]);
} else {
  echo json_encode(['ok' => false, 'msg' => 'Error al agregar el producto']);
}
