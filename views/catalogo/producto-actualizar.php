<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id           = (int)($_POST['id'] ?? 0);
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = (float)($_POST['precio'] ?? 0);
    $stock        = (int)($_POST['stock'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;

    $imagenPath = null;

    // 🔎 Recuperar la imagen actual si no se sube una nueva
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $actual = $stmt->get_result()->fetch_assoc();
    $imagenActual = $actual['imagen'] ?? null;

    // 📂 Subida de nueva imagen
    if (!empty($_FILES["imagen"]["name"])) {
        $allowedExt = ["jpg","jpeg","png","gif","webp"];
        $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowedExt)) {
            $uploadDir = __DIR__ . "/../../images/productos/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $filename   = uniqid("prod_") . "." . $ext;
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $targetFile)) {
                $imagenPath = "images/productos/" . $filename;
            } else {
                $error = "❌ Error al subir la nueva imagen.";
                header("Location: productos.php?error=" . urlencode($error));
                exit();
            }
        } else {
            $error = "⚠️ Solo se permiten imágenes (jpg, png, gif, webp).";
            header("Location: productos.php?error=" . urlencode($error));
            exit();
        }
    } else {
        // Mantener la imagen anterior si no se subió una nueva
        $imagenPath = $imagenActual;
    }

    // ✅ Validar datos mínimos
    if ($id > 0 && $nombre && $precio > 0 && $categoria_id > 0) {
        if (actualizarProducto($id, $nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id, $imagenPath)) {
            header("Location: productos.php?msg=" . urlencode("Producto actualizado con éxito"));
            exit();
        } else {
            $error = "❌ Error al actualizar el producto.";
        }
    } else {
        $error = "⚠️ Datos inválidos (revisa nombre, precio y categoría).";
    }
}

header("Location: productos.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
