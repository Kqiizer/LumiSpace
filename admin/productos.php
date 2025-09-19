<?php
require_once __DIR__ . "/../config/functions.php";

/**
 * Crear un nuevo producto
 */
function crearProducto(string $nombre, float $precio, int $stock, int $categoria_id, string $img = null): bool {
    $db = getDBConnection();
    $sql = "INSERT INTO productos (nombre, precio, stock, categoria_id, img) VALUES (?,?,?,?,?)";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error crearProducto: " . $db->error);
        return false;
    }
    $stmt->bind_param("sdiis", $nombre, $precio, $stock, $categoria_id, $img);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Obtener todos los productos
 */
function getProductos(): array {
    $db = getDBConnection();
    $sql = "SELECT id, nombre, precio, stock, categoria_id, img FROM productos ORDER BY id DESC";
    $res = $db->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtener un producto por ID
 */
function getProducto(int $id): ?array {
    $db = getDBConnection();
    $sql = "SELECT id, nombre, precio, stock, categoria_id, img FROM productos WHERE id = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Actualizar un producto
 */
function actualizarProducto(int $id, string $nombre, float $precio, int $stock, int $categoria_id, string $img = null): bool {
    $db = getDBConnection();
    $sql = "UPDATE productos SET nombre=?, precio=?, stock=?, categoria_id=?, img=? WHERE id=?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error actualizarProducto: " . $db->error);
        return false;
    }
    $stmt->bind_param("sdiisi", $nombre, $precio, $stock, $categoria_id, $img, $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Eliminar un producto
 */
function eliminarProducto(int $id): bool {
    $db = getDBConnection();
    $sql = "DELETE FROM productos WHERE id=?";
    $stmt = $db->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Productos destacados (más vendidos)
 */
function getProductosDestacados(int $limit = 4): array {
    $db = getDBConnection();
    $sql = "
        SELECT 
            p.id, 
            p.nombre, 
            COALESCE(SUM(dv.cantidad),0) AS vendidos
        FROM productos p
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre
        ORDER BY vendidos DESC
        LIMIT ?
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error en getProductosDestacados: " . $db->error);
        return [];
    }
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
