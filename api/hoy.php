<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $conn = getDBConnection();

    // Total del día y cantidad de ventas
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

    // Meta diaria (puedes moverla a una tabla de configuración si quieres)
    $metaDiaria = 60000; 

    echo json_encode([
        "total"         => (float)$row["total"],
        "transacciones" => (int)$row["transacciones"],
        "productos"     => (int)$row["productos"],
        "meta"          => $metaDiaria,
        "pct"           => $metaDiaria > 0 ? round(($row["total"] / $metaDiaria) * 100) : 0
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
