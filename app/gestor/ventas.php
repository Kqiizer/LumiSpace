<?php
require_once __DIR__ . "/../config/functions.php";

/**
 * Total de ventas del día (monto).
 */
function getVentasHoy(): float {
    $conn = getDBConnection();
    $sql  = "SELECT IFNULL(SUM(total),0) AS total FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res  = $conn->query($sql);
    $row  = $res->fetch_assoc();
    return (float)$row['total'];
}

/**
 * Ventas recientes (una fila por venta).
 * Devuelve: id venta, cliente, productos, total, fecha.
 */
function getVentasRecientes(int $limit = 6): array {
    $conn = getDBConnection();

    $sql = "
        SELECT 
            v.id,
            u.nombre AS cliente,
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
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res->fetch_all(MYSQLI_ASSOC);
}

/**
 * Tendencia de ventas del año actual por mes.
 * Devuelve 12 elementos: [ ['mes'=>1,'total'=>0.0], ... ]
 */
function getVentasMensuales(?int $year = null): array {
    $conn = getDBConnection();
    $year = $year ?? (int)date('Y');

    $sql = "
        SELECT MONTH(fecha) AS mes, IFNULL(SUM(total),0) AS total
        FROM ventas
        WHERE YEAR(fecha)=?
        GROUP BY MONTH(fecha)
        ORDER BY MONTH(fecha)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $year);
    $stmt->execute();
    $res = $stmt->get_result();

    // Inicializar meses 1..12 en 0
    $out = [];
    for ($m = 1; $m <= 12; $m++) $out[$m] = ['mes'=>$m, 'total'=>0.0];

    while ($row = $res->fetch_assoc()) {
        $m = (int)$row['mes'];
        $out[$m]['total'] = (float)$row['total'];
    }

    // Reindexar en orden 1..12
    return array_values($out);
}

/**
 * Ventas por categoría (monto).
 */
function getVentasPorCategoria(): array {
    $conn = getDBConnection();

    // Intento 1: con tabla categorias
    try {
        $sql = "
            SELECT 
                COALESCE(cat.nombre, 'Sin categoría') AS categoria,
                IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
            FROM productos p
            LEFT JOIN categorias cat ON cat.id = p.categoria_id
            LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
            GROUP BY COALESCE(cat.nombre, 'Sin categoría')
            ORDER BY total DESC
        ";
        $res = $conn->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);

    } catch (mysqli_sql_exception $e) {
        // Intento 2: usar columna categoria en productos
        try {
            $sql2 = "
                SELECT 
                    COALESCE(p.categoria, 'Sin categoría') AS categoria,
                    IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
                FROM productos p
                LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
                GROUP BY COALESCE(p.categoria, 'Sin categoría')
                ORDER BY total DESC
            ";
            $res2 = $conn->query($sql2);
            return $res2->fetch_all(MYSQLI_ASSOC);

        } catch (mysqli_sql_exception $e2) {
            // Intento 3: agrupar por categoria_id
            $sql3 = "
                SELECT 
                    CONCAT('Cat #', COALESCE(p.categoria_id, 0)) AS categoria,
                    IFNULL(SUM(dv.cantidad * dv.precio_unitario), 0) AS total
                FROM productos p
                LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
                GROUP BY COALESCE(p.categoria_id, 0)
                ORDER BY total DESC
            ";
            $res3 = $conn->query($sql3);
            return $res3->fetch_all(MYSQLI_ASSOC);
        }
    }
}

/**
 * Corte de caja del día de hoy agrupado por método de pago.
 * Devuelve: ['efectivo'=>123.45, 'tarjeta'=>89.00, ...]
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
    $row = $res->fetch_assoc();

    return [
        'total'         => (float)$row['total'],
        'transacciones' => (int)$row['transacciones'],
        'productos'     => (int)$row['productos']
    ];
}
