<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json");

// Seguridad: solo gestor puede acceder
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'gestor') {
    echo json_encode([]);
    exit();
}

require_once __DIR__ . "/usuarios.php";

$usuarios = getUsuariosRecientes(5);
echo json_encode($usuarios);
