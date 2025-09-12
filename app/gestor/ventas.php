<?php
require_once __DIR__ . "/../config/db.php";

/**
 * Total de ventas del día actual
 */
function getVentasHoy(): float {
    $db = getDBConnection();
    $sql = "SELECT COALESCE(SUM(total),0) AS total
            FROM ventas
            WHERE DATE(fecha) = CURDATE()";
    $res = $db->query($sql);
    $row = $res->fetch_assoc();
    return (float)$row['total'];
}

/**
 * Ventas recientes con detalle
 */
function getVentasRecientes(int $limit = 5): array {
    $db = getDBConnection();
    $sql = "
        SELECT v.id, v.fecha, v.cantidad, v.total,
               u.id AS usuario_id, u.nombre,
               p.nombre AS producto
        FROM ventas v
        JOIN usuarios u ON v.usuario_id = u.id
        JOIN productos p ON v.producto_id = p.id
        ORDER BY v.fecha DESC
        LIMIT ?
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ventas mensuales (ya lo tenías)
 */
function getVentasMensuales(): array {
    $db = getDBConnection();
    $sql = "
        SELECT MONTH(fecha) AS mes, SUM(total) as total
        FROM ventas
        WHERE YEAR(fecha) = YEAR(CURDATE())
        GROUP BY mes ORDER BY mes
    ";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ventas por categoría (ya corregido)
 */
function getVentasPorCategoria(): array {
    $db = getDBConnection();
    $sql = "
        SELECT c.nombre AS categoria, SUM(v.total) as total
        FROM ventas v
        JOIN productos p ON v.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        GROUP BY c.nombre
        ORDER BY total DESC
    ";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
