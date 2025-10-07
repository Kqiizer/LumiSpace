<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/functions.php';

$uid = $_SESSION['usuario_id'] ?? 0;
$pid = (int)($_POST['producto_id'] ?? 0);
if ($pid <= 0) { echo json_encode(['ok'=>false,'msg'=>'Producto inválido']); exit; }

$ok = toggleFavorito((int)$uid, $pid);
echo json_encode(['ok'=>$ok, 'count'=>getFavoritosCount((int)$uid)]);
