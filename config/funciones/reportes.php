<?php
/**
 * ==========================================================
 * 📊 FUNCIONES DE REPORTES DE VENTAS
 * ==========================================================
 */

require_once __DIR__ . '/functions.php';

/**
 * Obtener resumen general de ventas
 */
function getResumenVentas(): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            COUNT(*) AS total_ventas,
            COALESCE(SUM(total), 0) AS monto_total,
            COALESCE(AVG(total), 0) AS promedio_venta,
            COALESCE(SUM(CASE WHEN DATE(fecha) = CURDATE() THEN total END), 0) AS ventas_hoy,
            COALESCE(SUM(CASE WHEN WEEK(fecha) = WEEK(CURDATE()) THEN total END), 0) AS ventas_semana,
            COALESCE(SUM(CASE WHEN MONTH(fecha) = MONTH(CURDATE()) THEN total END), 0) AS ventas_mes
        FROM ventas
    ";
    $res = $conn->query($sql);
    return $res->fetch_assoc();
}

/**
 * Obtener ventas agrupadas por categoría
 */
function getVentasPorCategoria(): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            c.nombre AS categoria,
            COUNT(v.id) AS total_ventas,
            SUM(v.total) AS monto_total
        FROM ventas v
        LEFT JOIN productos p ON v.producto_id = p.id
        LEFT JOIN categorias c ON p.categoria_id = c.id
        GROUP BY c.id
        ORDER BY monto_total DESC
    ";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtener ventas agrupadas por usuario (para reportes de desempeño)
 */
function getVentasPorUsuario(): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            u.id AS usuario_id,
            CONCAT(u.nombre, ' ', u.apellido) AS usuario,
            r.nombre AS rol,
            COUNT(v.id) AS total_ventas,
            SUM(v.total) AS monto_total
        FROM ventas v
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        LEFT JOIN roles r ON u.rol_id = r.id
        GROUP BY u.id
        ORDER BY monto_total DESC
    ";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtener ventas por método de pago
 */
function getVentasPorMetodoPago(): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            metodo_pago,
            COUNT(*) AS cantidad,
            SUM(total) AS monto_total
        FROM ventas
        GROUP BY metodo_pago
    ";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Ventas por día (últimos 7 o 30 días)
 */
function getVentasPorDia(int $dias = 7): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            DATE(fecha) AS fecha,
            SUM(total) AS monto_total,
            COUNT(id) AS cantidad
        FROM ventas
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(fecha)
        ORDER BY fecha ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $dias);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
