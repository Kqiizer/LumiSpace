<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = getDBConnection();

    $id           = (int)($_POST['id'] ?? 0);
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $precio       = (float)($_POST['precio'] ?? 0);
    $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $proveedor_id = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;

    if ($id <= 0 || empty($nombre) || $precio <= 0) {
        header("Location: productos.php?error=Datos inválidos");
        exit();
    }

    // 📌 Obtener producto actual (para la imagen existente)
    $stmt = $conn->prepare("SELECT imagen FROM productos WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    $imagenActual = $prod['imagen'] ?? null;

    // 📂 Manejo de imagen nueva
    $imagenNombre = $imagenActual; // por defecto conservar
    if (!empty($_FILES['imagen']['name'])) {
        $dir = __DIR__ . "/../../images/productos/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $imagenNombre = uniqid("prod_") . "." . strtolower($ext);

        $destino = $dir . $imagenNombre;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
            // Borrar imagen anterior si existía
            if (!empty($imagenActual) && file_exists($dir . $imagenActual)) {
                unlink($dir . $imagenActual);
            }
        } else {
            header("Location: productos.php?error=Error al subir la nueva imagen");
            exit();
        }
    }

    // 📌 Actualizar producto (SIN tocar stock_inicial)
    $sql = "UPDATE productos 
            SET nombre=?, descripcion=?, precio=?, categoria_id=?, proveedor_id=?, imagen=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiisi", $nombre, $descripcion, $precio, $categoria_id, $proveedor_id, $imagenNombre, $id);

    if ($stmt->execute()) {
        header("Location: productos.php?msg=Producto actualizado correctamente");
        exit();
    } else {
        header("Location: productos.php?error=No se pudo actualizar el producto");
        exit();
    }
} else {
    header("Location: productos.php?error=invalid_request");
    exit();
}
