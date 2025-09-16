<?php
require_once __DIR__ . "/../config/functions.php";

function getUltimoAcceso(int $usuarioId): ?string {
    $db = getDBConnection();
    $sql = "SELECT ultima_sesion FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['ultima_sesion'] ?? null;
}

function getClientesUnicosHoy(): int {
    $db = getDBConnection();
    $sql = "SELECT COUNT(DISTINCT usuario_id) as total FROM ventas WHERE DATE(fecha) = CURDATE()";
    $res = $db->query($sql)->fetch_assoc();
    return $res['total'] ?? 0;
}
