<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

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

    $ventas = [];
    while ($row = $res->fetch_assoc()) {
        $ventas[] = [
            "id"        => (int)$row["id"],
            "cliente"   => $row["cliente"] ?? "N/D",
            "productos" => $row["productos"] ?? "—",
            "total"     => number_format((float)$row["total"], 2),
            "fecha"     => date("d/m/Y H:i", strtotime($row["fecha"]))
        ];
    }

    echo json_encode($ventas, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
