<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = (float)($_POST['precio'] ?? 0);
    $stock        = (int)($_POST['stock'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;

    $imagenNombre = null;

    // 📂 Subida de imagen opcional
    if (!empty($_FILES["imagen"]["name"])) {
        $allowedExt = ["jpg","jpeg","png","gif","webp"];
        $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowedExt)) {
            $uploadDir = __DIR__ . "/../../images/productos/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename   = uniqid("prod_") . "." . $ext;
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $targetFile)) {
                // ✅ Guardamos solo el nombre
                $imagenNombre = $filename;
            } else {
                header("Location: productos.php?error=" . urlencode("❌ Error al subir la imagen."));
                exit();
            }
        } else {
            header("Location: productos.php?error=" . urlencode("⚠️ Solo se permiten imágenes (jpg, jpeg, png, gif, webp)."));
            exit();
        }
    }

    // 📌 Validación básica
    if ($nombre && $precio > 0 && $categoria_id > 0) {
        $ok = insertarProducto($nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id, $imagenNombre);

        if ($ok) {
            header("Location: productos.php?msg=" . urlencode("Producto creado con éxito."));
            exit();
        } else {
            header("Location: productos.php?error=" . urlencode("❌ Error al guardar el producto en la base de datos."));
            exit();
        }
    } else {
        header("Location: productos.php?error=" . urlencode("⚠️ Nombre, precio y categoría son obligatorios."));
        exit();
    }
}

// 🚨 Fallback si acceden directo
header("Location: productos.php?error=" . urlencode("Acceso inválido."));
exit();
