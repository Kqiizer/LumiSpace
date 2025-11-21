<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Validar permisos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // 🔹 Obtenemos inventario antes de eliminar
    $inv = getInventarioById($id);

    if ($inv) {
        if (eliminarInventario($id)) {
            // 🔹 Registrar movimiento de salida solo si hay stock
            if ((int)$inv['cantidad'] > 0) {
                registrarMovimiento(
                    (int)$inv['producto_id'],
                    (int)$_SESSION['usuario_id'],
                    'salida',
                    (int)$inv['cantidad'],
                    "Eliminación de inventario (Sucursal: {$inv['sucursal']})"
                );
            }

            header("Location: inventario-listar.php?msg=" . urlencode("✅ Registro de inventario eliminado correctamente."));
            exit();
        } else {
            header("Location: inventario-listar.php?error=" . urlencode("❌ No se pudo eliminar el registro de inventario."));
            exit();
        }
    } else {
        header("Location: inventario-listar.php?error=" . urlencode("⚠️ El registro no existe o ya fue eliminado."));
        exit();
    }
} else {
    header("Location: inventario-listar.php?error=" . urlencode("⚠️ ID de inventario inválido."));
    exit();
}
