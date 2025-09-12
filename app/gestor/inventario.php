<?php
require_once __DIR__ . "/../config/db.php";

/**
 * Listar inventario
 */
function getInventario(): array {
    $db = getDBConnection();
    $sql = "
        SELECT p.id, p.nombre, c.nombre AS categoria,
               i.stock, p.precio,
               (i.stock * p.precio) AS valor_total
        FROM inventario i
        JOIN productos p ON i.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.nombre ASC
    ";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Actualizar stock
 */
function actualizarStock(int $productoId, int $nuevoStock): bool {
    $db = getDBConnection();
    $sql = "UPDATE inventario SET stock = ?, actualizado_en = NOW() WHERE producto_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $nuevoStock, $productoId);
    return $stmt->execute();
}
