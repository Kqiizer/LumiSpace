<?php
declare(strict_types=1);
require_once __DIR__ . "/../../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

try {
    $conn = getDBConnection();

    // === Ventas de hoy ===
    $sqlHoy = "
        SELECT 
            IFNULL(SUM(v.total),0) AS total,
            COUNT(DISTINCT v.id) AS transacciones,
            IFNULL(SUM(dv.cantidad),0) AS productos
        FROM ventas v
        LEFT JOIN detalle_ventas dv ON dv.venta_id = v.id
        WHERE DATE(v.fecha) = CURDATE()
    ";
    $resHoy = $conn->query($sqlHoy);
    $hoy = $resHoy->fetch_assoc();

    $metaDiaria = 60000; // 👈 Puedes moverlo a BD si quieres dinámico

    // === Corte de caja ===
    $sqlCaja = "
        SELECT metodo_principal AS metodo, IFNULL(SUM(total),0) AS total
        FROM ventas
        WHERE DATE(fecha) = CURDATE()
        GROUP BY metodo_principal
    ";
    $resCaja = $conn->query($sqlCaja);
    $corteCaja = [];
    while ($row = $resCaja->fetch_assoc()) {
        $corteCaja[$row["metodo"]] = (float)$row["total"];
    }

    // === Ventas recientes (últimas 5) ===
    $sqlRec = "
        SELECT v.id, u.nombre AS cliente, v.total, v.fecha
        FROM ventas v
        LEFT JOIN usuarios u ON u.id = v.usuario_id
        ORDER BY v.fecha DESC
        LIMIT 5
    ";
    $resRec = $conn->query($sqlRec);
    $ventasRecientes = $resRec->fetch_all(MYSQLI_ASSOC);

    // === Productos más vendidos ===
    $sqlTop = "
        SELECT p.id, p.nombre, IFNULL(SUM(dv.cantidad),0) AS vendidos
        FROM productos p
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre
        ORDER BY vendidos DESC
        LIMIT 5
    ";
    $resTop = $conn->query($sqlTop);
    $productosTop = $resTop->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        "hoy" => [
            "total" => (float)$hoy["total"],
            "transacciones" => (int)$hoy["transacciones"],
            "productos" => (int)$hoy["productos"],
            "meta" => $metaDiaria,
            "pct" => $metaDiaria > 0 ? round(($hoy["total"] / $metaDiaria) * 100) : 0
        ],
        "corteCaja" => $corteCaja,
        "ventasRecientes" => $ventasRecientes,
        "productosTop" => $productosTop
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
