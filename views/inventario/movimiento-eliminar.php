<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $mov = getMovimientoById($id);

    if ($mov) {
        $conn = getDBConnection();

        // 🔹 Recuperar datos
        $productoId = (int)$mov['producto_id'];
        $tipo       = $mov['tipo'];
        $cantidad   = (int)$mov['cantidad'];
        $sucursal   = $mov['sucursal'] ?? 'Principal';

        // 🔹 Revertir efecto en inventario
        if ($tipo === 'entrada') {
            // Si fue entrada, restamos
            $stmt = $conn->prepare("UPDATE inventario SET cantidad = cantidad - ? WHERE producto_id=? AND sucursal=?");
            $stmt->bind_param("iis", $cantidad, $productoId, $sucursal);
            $stmt->execute();
            $stmt->close();
        } elseif ($tipo === 'salida') {
            // Si fue salida, sumamos
            $stmt = $conn->prepare("UPDATE inventario SET cantidad = cantidad + ? WHERE producto_id=? AND sucursal=?");
            $stmt->bind_param("iis", $cantidad, $productoId, $sucursal);
            $stmt->execute();
            $stmt->close();
        } elseif ($tipo === 'ajuste') {
            // Para ajustes se elimina sin revertir (depende del caso de negocio)
        }

        // 🔹 Eliminar movimiento
        if (eliminarMovimiento($id)) {
            header("Location: movimientos-listar.php?msg=" . urlencode("✅ Movimiento eliminado y stock revertido."));
            exit();
        } else {
            header("Location: movimientos-listar.php?error=" . urlencode("❌ No se pudo eliminar el movimiento."));
            exit();
        }
    } else {
        header("Location: movimientos-listar.php?error=" . urlencode("⚠️ Movimiento no encontrado."));
        exit();
    }
} else {
    header("Location: movimientos-listar.php?error=" . urlencode("⚠️ ID inválido."));
    exit();
}
