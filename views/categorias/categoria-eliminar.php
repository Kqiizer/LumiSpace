<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Primero validamos que exista la categoría
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM categorias WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $categoria = $res->fetch_assoc();

    if ($categoria) {
        // Eliminamos categoría
        if (eliminarCategoria($id)) {
            header("Location: categorias.php?msg=" . urlencode("✅ Categoría eliminada correctamente."));
            exit();
        } else {
            $error = "❌ Error al eliminar la categoría en la base de datos.";
        }
    } else {
        $error = "⚠️ Categoría no encontrada.";
    }
} else {
    $error = "⚠️ ID inválido.";
}

header("Location: categorias.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
