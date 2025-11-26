<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE)
  session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../../config/functions.php";

$carrito = carritoObtener();
$count = 0;

foreach ($carrito as $item) {
  $count += (int)($item['cantidad'] ?? 1);
}

echo json_encode(['count' => $count]);

