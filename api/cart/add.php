<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
  exit;
}

/** --------- Leer payload de forma eficiente --------- */
$payload = [];
$ct = $_SERVER['CONTENT_TYPE'] ?? '';

if (stripos($ct, 'application/json') !== false) {
  $raw = file_get_contents('php://input');
  $tmp = json_decode($raw, true);
  $payload = is_array($tmp) ? $tmp : [];
} else {
  // form-data o x-www-form-urlencoded
  $payload = $_POST ?: [];
}

/** --------- Aliases aceptados --------- */
$id = 0;
foreach (['producto_id','product_id','id'] as $k) {
  if (isset($payload[$k])) { $id = (int)$payload[$k]; break; }
}

$qty = 1;
foreach (['cantidad','qty','quantity'] as $k) {
  if (isset($payload[$k])) { $qty = (int)$payload[$k]; break; }
}
$qty = max(1, $qty);

if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Producto inválido']);
  exit;
}

/** --------- Inicializar carrito si no existe --------- */
$_SESSION['carrito'] = $_SESSION['carrito'] ?? [];

// Inicializa contadores O(1) si no existen todavía (compat con carritos previos)
if (!isset($_SESSION['carrito_items']) || !isset($_SESSION['carrito_qty'])) {
  $_SESSION['carrito_items'] = count($_SESSION['carrito']);
  $_SESSION['carrito_qty']   = 0;
  foreach ($_SESSION['carrito'] as $row) {
    $_SESSION['carrito_qty'] += (int)($row['qty'] ?? 0);
  }
}

/** --------- Agregar/Acumular --------- */
if (isset($_SESSION['carrito'][$id])) {
  $_SESSION['carrito'][$id]['qty'] += $qty;
} else {
  $_SESSION['carrito'][$id] = ['id' => $id, 'qty' => $qty];
  $_SESSION['carrito_items']++; // nuevo ítem distinto
}
$_SESSION['carrito_qty'] += $qty;

/** --------- Respuesta --------- */
$response = [
  'ok'         => true,
  'id'         => $id,
  'added_qty'  => $qty,
  'items'      => (int)$_SESSION['carrito_items'], // # de productos distintos
  'qty'        => (int)$_SESSION['carrito_qty'],   // unidades totales en carrito
];

// Cerrar la sesión cuanto antes para liberar el lock y acelerar concurrencia
session_write_close();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
