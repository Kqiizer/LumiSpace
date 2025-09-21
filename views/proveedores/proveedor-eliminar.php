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
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
    
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: proveedores.php?msg=" . urlencode("✅ Proveedor eliminado correctamente."));
            exit();
        } else {
            $error = "❌ No se pudo eliminar el proveedor.";
        }
    } else {
        $error = "❌ Error en la base de datos: " . $conn->error;
    }
} else {
    $error = "⚠️ ID inválido.";
}

header("Location: proveedores.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
