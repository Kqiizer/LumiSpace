<?php
declare(strict_types=1);
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/carritos-funciones.php";

if (!isset($_SESSION['usuario_id'])) {
  echo json_encode(['ok' => false, 'msg' => 'Usuario no autenticado']);
  exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$producto_id = (int)($data['producto_id'] ?? 0);
$usuario_id = (int)$_SESSION['usuario_id'];

if ($producto_id <= 0) {
  echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
  exit;
}

if (function_exists('eliminarDelCarrito')) {
  $ok = eliminarDelCarrito($usuario_id, $producto_id);
} else {
  $ok = false;
}

echo json_encode(['ok' => $ok, 'msg' => $ok ? 'Producto eliminado' : 'Error al eliminar']);
