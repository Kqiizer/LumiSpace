<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    if (eliminarProducto($id)) {
        header("Location: productos.php?msg=eliminado");
        exit();
    } else {
        $error = "❌ No se pudo eliminar producto.";
    }
} else {
    $error = "⚠️ ID inválido.";
}

header("Location: productos.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
