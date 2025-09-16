<?php
require_once __DIR__ . '/../_db.php';
$q = $_GET['q'] ?? '';
try{
  $pdo = db();
  $stmt = $pdo->prepare("SELECT id,nombre,precio,stock,img,codigo_barra FROM productos WHERE codigo_barra = ? LIMIT 1");
  $stmt->execute([$q]);
  $row = $stmt->fetch();
  header('Content-Type: application/json');
  echo json_encode(['ok'=> (bool)$row, 'producto'=>$row]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'error']);
}
