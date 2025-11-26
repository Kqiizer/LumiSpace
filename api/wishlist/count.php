<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/functions.php';

$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
$count = 0;

if ($usuarioId > 0 && favoritosAvailable()) {
    $count = getFavoritosCount($usuarioId);
} else {
    $count = isset($_SESSION['favoritos']) ? count((array)$_SESSION['favoritos']) : 0;
}

echo json_encode([
    'ok' => true,
    'count' => $count,
]);

