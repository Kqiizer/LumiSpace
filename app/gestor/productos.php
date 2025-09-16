<?php
require_once __DIR__ . "/../config/functions.php";

function getProductosDestacados(int $limit = 4): array {
    $db = getDBConnection();
    $sql = "SELECT p.id, p.nombre, COALESCE(SUM(v.cantidad),0) as vendidos
            FROM productos p
            LEFT JOIN ventas v ON v.producto_id = p.id
            GROUP BY p.id, p.nombre
            ORDER BY vendidos DESC
            LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
