<?php
require_once __DIR__ . "/../config/db.php";
header("Content-Type: application/json");

/**
 * Notificaciones de ventas recientes
 */
function getNotificaciones(): array {
    $db = getDBConnection();
    $sql = "
        SELECT v.id, v.fecha, u.nombre AS usuario, p.nombre AS producto, v.total
        FROM ventas v
        JOIN productos p ON v.producto_id = p.id
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        ORDER BY v.fecha DESC
        LIMIT 5
    ";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Output JSON
echo json_encode(getNotificaciones());
