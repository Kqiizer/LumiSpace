<?php
require_once __DIR__ . "/../config/functions.php";

/**
 * Devuelve los productos más vendidos con su total de unidades.
 */
function getProductosDestacados(int $limit = 4): array {
    $db = getDBConnection();
    $sql = "
        SELECT p.id, p.nombre, COALESCE(SUM(dv.cantidad),0) AS vendidos
        FROM productos p
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre
        ORDER BY vendidos DESC
        LIMIT ?
    ";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("Error prepare: " . $db->error);
        return [];
    }
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
