<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../config/functions.php";

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if (!$usuario_id) {
  http_response_code(401);
  echo json_encode(["ok"=>false, "msg"=>"No autenticado"]);
  exit;
}

$payload = json_decode(file_get_contents("php://input"), true) ?? [];
$producto_id = (int)($payload['producto_id'] ?? 0);

if ($producto_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "msg"=>"Producto inválido"]);
  exit;
}

$conn = getDBConnection();

// Asegurar índice único en (usuario_id, producto_id) en tu tabla favoritos
// CREATE TABLE IF NOT EXISTS favoritos (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   usuario_id INT NOT NULL,
//   producto_id INT NOT NULL,
//   creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
//   UNIQUE KEY uq_user_prod (usuario_id, producto_id)
// );

try {
  // ¿Existe?
  $stmt = $conn->prepare("SELECT 1 FROM favoritos WHERE usuario_id=? AND producto_id=?");
  $stmt->bind_param("ii", $usuario_id, $producto_id);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_row() ? true : false;

  if ($exists) {
    // Borrar
    $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?");
    $del->bind_param("ii", $usuario_id, $producto_id);
    $del->execute();
    echo json_encode(["ok"=>true, "in_wishlist"=>false]);
  } else {
    // Insertar
    $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)");
    $ins->bind_param("ii", $usuario_id, $producto_id);
    $ins->execute();
    echo json_encode(["ok"=>true, "in_wishlist"=>true]);
  }
} catch(Exception $e) {
  error_log("wishlist toggle error: ".$e->getMessage());
  http_response_code(500);
  echo json_encode(["ok"=>false, "msg"=>"Error de servidor"]);
}
