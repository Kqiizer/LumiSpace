<?php
require_once __DIR__ . "/../config/functions.php";

function getVentasHoy(): float {
    $db = getDBConnection();
    $sql = "SELECT SUM(total) as total FROM ventas WHERE DATE(fecha) = CURDATE()";
    $res = $db->query($sql)->fetch_assoc();
    return $res['total'] ?? 0;
}

function getVentasRecientes(int $limit = 5): array {
    $db = getDBConnection();
    $sql = "SELECT v.id, u.nombre, p.nombre as producto, v.cantidad, v.total, v.fecha, u.id as usuario_id
            FROM ventas v
            JOIN usuarios u ON u.id = v.usuario_id
            JOIN productos p ON p.id = v.producto_id
            ORDER BY v.fecha DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getVentasMensuales(): array {
    $db = getDBConnection();
    $sql = "SELECT MONTH(fecha) as mes, SUM(total) as total
            FROM ventas
            WHERE YEAR(fecha) = YEAR(CURDATE())
            GROUP BY MONTH(fecha)
            ORDER BY mes ASC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getVentasPorCategoria(): array {
    $db = getDBConnection();
    $sql = "SELECT c.nombre as categoria, SUM(v.total) as total
            FROM ventas v
            JOIN productos p ON p.id = v.producto_id
            JOIN categorias c ON c.id = p.categoria_id
            GROUP BY c.nombre";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function getCorteCajaHoy(): array {
    $db = getDBConnection();
    $sql = "SELECT metodo_pago, SUM(total) as total 
            FROM ventas 
            WHERE DATE(fecha) = CURDATE()
            GROUP BY metodo_pago";
    $res = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    $corte = [];
    foreach ($res as $r) {
        $corte[$r['metodo_pago']] = $r['total'];
    }
    return $corte;
}
 