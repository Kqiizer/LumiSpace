<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../config/functions.php";

$data = json_decode(file_get_contents("php://input"), true);

$cliente_id  = $data['cliente_id'] ?? null;
$usuario_id  = $data['usuario_id'] ?? null;
$metodo_pago = $data['metodo_pago'] ?? 'efectivo';
$total       = $data['total'] ?? 0;
$items       = $data['items'] ?? [];

// Registrar venta en BD
$venta_id = registrarVenta($cliente_id, $usuario_id, $items, $metodo_pago, $total);

if ($venta_id) {
    echo json_encode(["success" => true, "venta_id" => $venta_id]);
} else {
    echo json_encode(["success" => false]);
}
exit();
?>