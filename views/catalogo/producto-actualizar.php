<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id          = (int)($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = (float)($_POST['precio'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $categoriaId = (int)($_POST['categoria_id'] ?? 0);
    $proveedorId = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;

    // 📌 Buscar imagen actual
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $imagenActual = $res['imagen'] ?? null;

    $nuevaImagen = $imagenActual;

    // 📂 Subida de nueva imagen
    if (!empty($_FILES['imagen']['name'])) {
        $uploadDir = __DIR__ . "/../../images/productos/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            header("Location: productos.php?error=" . urlencode("⚠️ Formato de imagen no permitido."));
            exit();
        }

        $fileName = uniqid("prod_") . "." . $ext;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $filePath)) {
            // 🗑️ Eliminar imagen vieja si existe
            if ($imagenActual && file_exists($uploadDir . $imagenActual)) {
                unlink($uploadDir . $imagenActual);
            }
            $nuevaImagen = $fileName;
        } else {
            header("Location: productos.php?error=" . urlencode("❌ Error al subir la nueva imagen."));
            exit();
        }
    }

    // 📌 Actualizar producto
    $stmt = $conn->prepare("UPDATE productos 
                            SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, proveedor_id=?, imagen=? 
                            WHERE id=?");
    $stmt->bind_param("ssdiissi", $nombre, $descripcion, $precio, $stock, $categoriaId, $proveedorId, $nuevaImagen, $id);

    if ($stmt->execute()) {
        header("Location: productos.php?msg=" . urlencode("Producto actualizado correctamente."));
    } else {
        header("Location: productos.php?error=" . urlencode("❌ Error al actualizar el producto."));
    }
    exit();
}

// 🚨 Si entran directo
header("Location: productos.php?error=" . urlencode("Acceso inválido."));
exit();
