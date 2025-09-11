<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/ventas.php"; // aquí ya tienes getVentasRecientes()

// 🚀 Obtenemos las últimas 5 ventas
$ventas = getVentasRecientes(5);

$notifs = [];
foreach ($ventas as $venta) {
    $notifs[] = [
        "mensaje" => "💡 {$venta['nombre']} compró {$venta['producto']} x{$venta['cantidad']}",
        "fecha"   => $venta['fecha'],
        "total"   => $venta['total']
    ];
}

echo json_encode($notifs);
