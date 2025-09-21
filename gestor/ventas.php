<?php
require_once __DIR__ . "/../config/functions.php";

/**
 * Total de ventas del día (monto).
 */
function getVentasHoy(): float {
    $conn = getDBConnection();
    $sql  = "SELECT IFNULL(SUM(total),0) AS total FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res  = $conn->query($sql);
    if (!$res) return 0.0;
    $row  = $res->fetch_assoc();
    return (float)$row['total'];
}

/**
 * Ventas recientes (una fila por venta).
 */
function getVentasRecientes(int $limit = 6): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            v.id,
            u.nombre AS nombre,
            v.total,
            v.fecha,
            (
                SELECT CONCAT(p.nombre, 
                    CASE 
                        WHEN COUNT(*) > 1 THEN CONCAT(' +', COUNT(*)-1, ' más') 
                        ELSE '' 
                    END
                )
                FROM detalle_ventas dv
                INNER JOIN productos p ON p.id = dv.producto_id
                WHERE dv.venta_id = v.id
                GROUP BY dv.venta_id
                LIMIT 1
            ) AS productos
        FROM ventas v
        LEFT JOIN usuarios u ON u.id = v.usuario_id
        ORDER BY v.fecha DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res) return [];
    return $res->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ventas por categoría (monto).
 */
function getVentasPorCategoria(): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            COALESCE(cat.nombre, 'Sin categoría') AS categoria,
            IFNULL(SUM(dv.cantidad * dv.precio), 0) AS total
        FROM productos p
        LEFT JOIN categorias cat ON cat.id = p.categoria_id
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY COALESCE(cat.nombre, 'Sin categoría')
        ORDER BY total DESC
    ";
    $res = $conn->query($sql);
    if ($res) {
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    // Fallback
    $sql2 = "
        SELECT 
            COALESCE(p.categoria, 'Sin categoría') AS categoria,
            IFNULL(SUM(dv.cantidad * dv.precio), 0) AS total
        FROM productos p
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY COALESCE(p.categoria, 'Sin categoría')
        ORDER BY total DESC
    ";
    $res2 = $conn->query($sql2);
    return $res2 ? $res2->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Corte de caja del día de hoy agrupado por método de pago.
 */
function getCorteCajaHoy(): array {
    $conn = getDBConnection();
    $sql = "
        SELECT metodo_pago, IFNULL(SUM(total),0) AS total
        FROM ventas
        WHERE DATE(fecha)=CURDATE()
        GROUP BY metodo_pago
    ";
    $res = $conn->query($sql);
    if (!$res) return [];
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[$row['metodo_pago']] = (float)$row['total'];
    }
    return $out;
}

/**
 * Número de clientes únicos que compraron hoy.
 */
function getClientesUnicosHoy(): int {
    $conn = getDBConnection();
    $sql  = "SELECT COUNT(DISTINCT usuario_id) AS c FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res  = $conn->query($sql);
    if (!$res) return 0;
    $row  = $res->fetch_assoc();
    return (int)$row['c'];
}

/**
 * Resumen del día: total vendido, número de ventas, productos vendidos.
 */
function getResumenHoy(): array {
    $conn = getDBConnection();
    $sql = "
        SELECT 
            IFNULL(SUM(v.total),0) AS total,
            COUNT(DISTINCT v.id) AS transacciones,
            IFNULL(SUM(dv.cantidad),0) AS productos
        FROM ventas v
        LEFT JOIN detalle_ventas dv ON dv.venta_id = v.id
        WHERE DATE(v.fecha) = CURDATE()
    ";
    $res = $conn->query($sql);
    if (!$res) return ['total'=>0.0,'transacciones'=>0,'productos'=>0];
    $row = $res->fetch_assoc();
    return [
        'total'         => (float)$row['total'],
        'transacciones' => (int)$row['transacciones'],
        'productos'     => (int)$row['productos']
    ];
}
