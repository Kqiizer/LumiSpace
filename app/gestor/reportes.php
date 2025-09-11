<?php
require_once __DIR__ . "/../config/db.php";

// Ventas mensuales para gráfico
function getVentasMensuales(): array {
    $db = getDBConnection();
    $sql = "SELECT MONTH(fecha) AS mes, SUM(total) as total
            FROM ventas
            WHERE YEAR(fecha) = YEAR(CURDATE())
            GROUP BY mes ORDER BY mes";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Ventas por categoría
function getVentasPorCategoria(): array {
    $db = getDBConnection();
    $sql = "SELECT categoria, SUM(total) as total
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY categoria";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
