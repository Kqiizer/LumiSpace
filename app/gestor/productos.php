<?php
require_once __DIR__ . "/../config/db.php";

/**
 * Crear producto
 */
function crearProducto(string $nombre, float $precio, int $stock, int $categoriaId): bool {
    $db = getDBConnection();
    $sql = "INSERT INTO productos (nombre, precio, stock, categoria_id) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sdii", $nombre, $precio, $stock, $categoriaId);
    return $stmt->execute();
}

/**
 * Listar productos destacados (los más vendidos)
 */
function getProductosDestacados(int $limit = 5): array {
    $db = getDBConnection();

    $sql = "
        SELECT p.id, p.nombre,
               c.nombre AS categoria,
               COALESCE(SUM(v.cantidad), 0) AS vendidos
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN ventas v ON v.producto_id = p.id
        GROUP BY p.id, p.nombre, c.nombre
        ORDER BY vendidos DESC
        LIMIT ?
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
