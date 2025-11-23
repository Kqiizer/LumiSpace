<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$loggedIn = isset($_SESSION['usuario_id']);

$response = [
    'ok' => true,
    'loggedIn' => $loggedIn,
    'user' => $loggedIn ? [
        'id' => (int)($_SESSION['usuario_id'] ?? 0),
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
        'rol' => $_SESSION['usuario_rol'] ?? ''
    ] : null
];

echo json_encode($response);
exit;


