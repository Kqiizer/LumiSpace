<?php
require_once __DIR__ . "/../config/db.php";

/**
 * Reporte de ventas entre fechas
 */
function getReporteVentas(string $inicio, string $fin): array {
    $db = getDBConnection();
    $sql = "
        SELECT v.id, v.fecha, v.total, v.metodo_pago,
               p.nombre AS producto, c.nombre AS categoria,
               u.nombre AS cliente
        FROM ventas v
        JOIN productos p ON v.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        WHERE DATE(v.fecha) BETWEEN ? AND ?
        ORDER BY v.fecha DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $inicio, $fin);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Reporte de productos más vendidos
 */
function getReporteProductos(int $limit = 10): array {
    $db = getDBConnection();
    $sql = "
        SELECT p.id, p.nombre, c.nombre AS categoria,
               COALESCE(SUM(v.cantidad), 0) AS vendidos,
               COALESCE(SUM(v.total), 0) AS ingresos
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
