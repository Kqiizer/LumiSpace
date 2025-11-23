<?php
/**
 * Endpoint para verificar si existe una sesión activa
 * Retorna JSON con el estado de la sesión
 */
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si hay sesión activa
$hasSession = isset($_SESSION['usuario_id'], $_SESSION['usuario_rol']);

echo json_encode([
    'hasSession' => $hasSession,
    'usuario_id' => $_SESSION['usuario_id'] ?? null,
    'usuario_rol' => $_SESSION['usuario_rol'] ?? null
]);

