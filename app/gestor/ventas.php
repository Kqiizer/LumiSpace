<?php
include_once(__DIR__ . "/../config/db.php");

/**
 * Obtiene las ventas del día actual.
 * Devuelve número (float). Si hay error, devuelve 0.
 */
function getVentasHoy(): float {
    $conn = getDBConnection();
    $sql  = "SELECT SUM(total) AS total FROM ventas WHERE DATE(fecha) = CURDATE()";

    $res = $conn->query($sql);
    if (!$res) {
        error_log("Error getVentasHoy: " . $conn->error);
        return 0;
    }

    $row = $res->fetch_assoc();
    return (float)($row['total'] ?? 0);
}

/**
 * Obtiene las últimas ventas con info de usuario y producto.
 * Devuelve array asociativo.
 */
function getVentasRecientes(int $limit = 10): array {
    $conn = getDBConnection();
    $sql  = "SELECT v.id, v.cantidad, v.total, v.fecha,
                    u.id AS usuario_id, u.nombre,
                    p.nombre AS producto
             FROM ventas v
             JOIN usuarios u ON v.usuario_id = u.id
             JOIN productos p ON v.producto_id = p.id
             ORDER BY v.fecha DESC
             LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error prepare getVentasRecientes: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    if (!$stmt->execute()) {
        error_log("Error execute getVentasRecientes: " . $stmt->error);
        return [];
    }

    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC) ?: [];
}
