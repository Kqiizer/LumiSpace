<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../config/functions.php";

$usuario_id = $_SESSION['usuario_id'] ?? 0;
if (!$usuario_id) {
  http_response_code(401);
  echo json_encode(["ok"=>false, "msg"=>"No autenticado", "count"=>0]);
  exit;
}

$payload = json_decode(file_get_contents("php://input"), true) ?? [];
$producto_id = (int)($payload['producto_id'] ?? 0);

if ($producto_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false, "msg"=>"Producto inválido", "count"=>getFavoritosCount((int)$usuario_id)]);
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
  // Verificar si la tabla existe
  $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
  if (!$chk || $chk->num_rows === 0) {
    http_response_code(500);
    echo json_encode(["ok"=>false, "msg"=>"La tabla favoritos no existe"]);
    exit;
  }

  // ¿Existe?
  $stmt = $conn->prepare("SELECT 1 FROM favoritos WHERE usuario_id=? AND producto_id=?");
  if (!$stmt) {
    throw new Exception("Error preparando consulta: " . $conn->error);
  }
  $stmt->bind_param("ii", $usuario_id, $producto_id);
  $stmt->execute();
  $exists = $stmt->get_result()->fetch_row() ? true : false;
  $stmt->close();

  if ($exists) {
    // Borrar
    $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?");
    if (!$del) {
      throw new Exception("Error preparando DELETE: " . $conn->error);
    }
    $del->bind_param("ii", $usuario_id, $producto_id);
    $del->execute();
    $del->close();
    
    // Obtener conteo actualizado
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM favoritos WHERE usuario_id=?");
    $countStmt->bind_param("i", $usuario_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $count = (int)($countResult['count'] ?? 0);
    $countStmt->close();
    
    echo json_encode(["ok"=>true, "in_wishlist"=>false, "count"=>$count]);
  } else {
    // Insertar - verificar si la columna creado_en existe
    $colCheck = $conn->query("SHOW COLUMNS FROM favoritos LIKE 'creado_en'");
    $hasCreatedAt = $colCheck && $colCheck->num_rows > 0;
    
    if ($hasCreatedAt) {
      $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id, creado_en) VALUES (?, ?, NOW())");
    } else {
      $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id) VALUES (?, ?)");
    }
    
    if (!$ins) {
      throw new Exception("Error preparando INSERT: " . $conn->error);
    }
    $ins->bind_param("ii", $usuario_id, $producto_id);
    $ins->execute();
    $ins->close();
    
    // Obtener conteo actualizado
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM favoritos WHERE usuario_id=?");
    $countStmt->bind_param("i", $usuario_id);
    $countStmt->execute();
    $countResult = $countStmt->get_result()->fetch_assoc();
    $count = (int)($countResult['count'] ?? 0);
    $countStmt->close();
    
    echo json_encode(["ok"=>true, "in_wishlist"=>true, "count"=>$count]);
  }
} catch(Exception $e) {
  error_log("wishlist toggle error: ".$e->getMessage());
  http_response_code(500);
  echo json_encode(["ok"=>false, "msg"=>"Error de servidor: " . $e->getMessage()]);
}
