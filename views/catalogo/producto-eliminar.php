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
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();

    if ($producto) {
        // 🗑️ Eliminar registro en BD
        if (eliminarProducto($id)) {
            // 🗂️ Eliminar imagen física si existe
            if (!empty($producto['imagen'])) {
                $imgPath = __DIR__ . "/../../images/productos/" . $producto['imagen'];
                if (file_exists($imgPath)) {
                    unlink($imgPath);
                }
            }

            header("Location: productos.php?msg=" . urlencode("Producto eliminado correctamente."));
            exit();
        } else {
            $error = "❌ No se pudo eliminar el producto en la base de datos.";
        }
    } else {
        $error = "⚠️ Producto no encontrado.";
    }
} else {
    $error = "⚠️ ID inválido.";
}

header("Location: productos.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
