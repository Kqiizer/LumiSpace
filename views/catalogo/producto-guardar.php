<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre        = trim($_POST['nombre'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $precio        = (float)($_POST['precio'] ?? 0);
    $stockInicial  = (int)($_POST['stock_inicial'] ?? 0);
    $categoria_id  = (int)($_POST['categoria_id'] ?? 0);
    $proveedor_id  = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;

    $imagenNombre = null;

    // 📂 Subida de imagen opcional
    if (!empty($_FILES["imagen"]["name"])) {
        $allowedExt = ["jpg","jpeg","png","gif","webp"];
        $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            header("Location: productos.php?error=" . urlencode("⚠️ Solo imágenes válidas (jpg, jpeg, png, gif, webp)."));
            exit();
        }

        $uploadDir = __DIR__ . "/../../images/productos/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename   = uniqid("prod_") . "." . $ext;
        $targetFile = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES["imagen"]["tmp_name"], $targetFile)) {
            header("Location: productos.php?error=" . urlencode("❌ Error al subir la imagen."));
            exit();
        }
        $imagenNombre = $filename;
    }

    // ✅ Validación
    if (!$nombre || $precio <= 0 || $categoria_id <= 0) {
        header("Location: productos.php?error=" . urlencode("⚠️ Nombre, precio y categoría son obligatorios."));
        exit();
    }

    $conn = getDBConnection();

    // 📝 Insertar producto (sin stock en tabla productos)
    if ($proveedor_id === null) {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria_id, proveedor_id, imagen, creado_en)
                VALUES (?, ?, ?, ?, NULL, ?, NOW())";
        $stmt = $conn->prepare($sql);
        // 5 parámetros: s (nombre), s (desc), d (precio), i (categoria), s (imagen)
        $stmt->bind_param("ssdis", $nombre, $descripcion, $precio, $categoria_id, $imagenNombre);
    } else {
        $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria_id, proveedor_id, imagen, creado_en)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        // 6 parámetros: s (nombre), s (desc), d (precio), i (categoria), i (proveedor), s (imagen)
        $stmt->bind_param("ssdiss", $nombre, $descripcion, $precio, $categoria_id, $proveedor_id, $imagenNombre);
    }

    if (!$stmt->execute()) {
        header("Location: productos.php?error=" . urlencode("❌ Error: " . $stmt->error));
        exit();
    }

    $productoId = $stmt->insert_id;

    // ✅ Registrar stock inicial SOLO con movimientos (no duplicar en productos)
    if ($stockInicial > 0) {
        registrarMovimiento(
            $productoId,
            (int)$_SESSION['usuario_id'],
            'entrada',
            $stockInicial,
            "Stock inicial en sucursal Principal",
            'Principal'
        );
    }

    header("Location: productos.php?msg=" . urlencode("✅ Producto creado con éxito."));
    exit();
}

// 🚨 Acceso directo no permitido
header("Location: productos.php?error=" . urlencode("Acceso inválido."));
exit();
