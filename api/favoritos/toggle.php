<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/../../config/functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Method Not Allowed']);
  exit;
}

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if (!$usuario_id) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
  exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
$producto_id = isset($payload['producto_id']) ? (int)$payload['producto_id'] : 0;

if ($producto_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Producto inválido']);
  exit;
}

$conn = getDBConnection();

// comprobar que exista la tabla
$chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
if (!$chk || $chk->num_rows === 0) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>"Falta la tabla 'favoritos'"]);
  exit;
}

// Revisar si ya existe el favorito
$exists = false;
if ($stmt = $conn->prepare("SELECT 1 FROM favoritos WHERE usuario_id=? AND producto_id=? LIMIT 1")) {
  $stmt->bind_param("ii", $usuario_id, $producto_id);
  $stmt->execute();
  $stmt->store_result();
  $exists = $stmt->num_rows > 0;
  $stmt->close();
} else {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error de servidor (prepare A)']);
  exit;
}

if ($exists) {
  // remover
  if ($del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=? LIMIT 1")) {
    $del->bind_param("ii", $usuario_id, $producto_id);
    $ok = $del->execute();
    $del->close();

    if ($ok) {
      // actualizar sesión (opcional)
      if (!empty($_SESSION['favoritos'][$producto_id])) {
        unset($_SESSION['favoritos'][$producto_id]);
      }
      echo json_encode(['ok'=>true, 'in_wishlist'=>false]);
      exit;
    }
  }
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'No se pudo eliminar de favoritos']);
  exit;
} else {
  // agregar
  if ($ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id, creado_en) VALUES (?, ?, NOW())")) {
    $ins->bind_param("ii", $usuario_id, $producto_id);
    $ok = $ins->execute();
    $ins->close();

    if ($ok) {
      // actualizar sesión (opcional)
      if (!isset($_SESSION['favoritos'])) $_SESSION['favoritos'] = [];
      $_SESSION['favoritos'][$producto_id] = true;

      echo json_encode(['ok'=>true, 'in_wishlist'=>true]);
      exit;
    }
  }
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'No se pudo agregar a favoritos']);
  exit;
}
