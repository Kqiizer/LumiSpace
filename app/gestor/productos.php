<?php
require_once __DIR__ . "/../config/functions.php";
require_once __DIR__ . "/../config/db.php";
/**
 * Obtiene los productos más vendidos.
 * 
 * @param int $limit Número de productos a devolver (default: 4)
 * @return array Lista de productos con ['id','nombre','vendidos']
 */
function getProductosDestacados(int $limit = 4): array {
    $db = getDBConnection();

    $sql = "
        SELECT 
            p.id, 
            p.nombre, 
            COALESCE(SUM(dv.cantidad),0) AS vendidos
        FROM productos p
        LEFT JOIN detalle_ventas dv ON dv.producto_id = p.id
        GROUP BY p.id, p.nombre
        ORDER BY vendidos DESC
        LIMIT ?
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare getProductosDestacados: " . $db->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $db->close();

    return $rows;
}
