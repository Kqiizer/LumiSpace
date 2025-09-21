<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id          = (int)($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $imagenPath  = null;

    // ✅ Subida de imagen opcional
    if (!empty($_FILES["imagen"]["name"])) {
        $allowedExt = ["jpg","jpeg","png","gif","webp"];
        $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowedExt)) {
            $uploadDir = __DIR__ . "/../../images/categorias/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename = uniqid("cat_") . "." . $ext;
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $targetFile)) {
                $imagenPath = "images/categorias/" . $filename;
            }
        }
    }

    if ($id > 0 && $nombre) {
        if (actualizarCategoria($id, $nombre, $descripcion, $imagenPath)) {
            header("Location: categorias.php?msg=actualizada");
            exit();
        } else {
            $error = "No se pudo actualizar la categoría.";
        }
    } else {
        $error = "Datos inválidos.";
    }
}

header("Location: categorias.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
