<?php
declare(strict_types=1);

// Usamos tu conexión existente (mysqli)
require_once __DIR__ . '/../config/db.php'; // getDBConnection()

function db(): mysqli {
  return getDBConnection();
}

function money(float $v): string {
  return number_format($v, 2, '.', ',');
}

/**
 * Devuelve el turno abierto para una caja (o null si no hay).
 * Además incluye el nombre del cajero.
 */
function turno_actual(mysqli $db, string $cajaId): ?array {
  $sql = "SELECT t.*, u.nombre AS cajero_nombre
          FROM turnos_caja t
          LEFT JOIN usuarios u ON u.id = t.cajero_id
          WHERE t.caja_id = ?
            AND t.fecha_cierre IS NULL
          ORDER BY t.id DESC
          LIMIT 1";
  $stmt = $db->prepare($sql);
  $stmt->bind_param('s', $cajaId);
  $stmt->execute();
  $res = $stmt->get_result();
  return $res->fetch_assoc() ?: null;
}
