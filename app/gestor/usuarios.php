<?php
require_once __DIR__ . "/../config/functions.php";

/** Helper: verifica si una columna existe en una tabla del esquema actual (DATABASE()) */
function _colExists(mysqli $db, string $table, string $col): bool {
    $sql = "SELECT 1
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = ?
              AND COLUMN_NAME  = ?
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $table, $col);
    $stmt->execute();
    $res = $stmt->get_result();
    return ($res && $res->num_rows > 0);
}

/** Último acceso del usuario: usa la columna que exista (ultimo_acceso | ultima_sesion) */
function getUltimoAcceso(int $usuarioId): ?string {
    $db = getDBConnection();

    $col = null;
    if (_colExists($db, 'usuarios', 'ultimo_acceso')) {
        $col = 'ultimo_acceso';
    } elseif (_colExists($db, 'usuarios', 'ultima_sesion')) {
        $col = 'ultima_sesion';
    } else {
        return null; // ninguna de las dos existe
    }

    // Insertamos el nombre de columna elegido (whitelist) directamente en el SQL
    $sql = "SELECT {$col} AS v FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['v'] ?? null;
}

/** Usuarios registrados hoy (usa created_at si existe, si no, fecha_registro) */
function getUsuariosRegistradosHoy(): int {
    $db = getDBConnection();

    // Elegir columna de fecha válida
    $dateCol = _colExists($db, 'usuarios', 'created_at') ? 'created_at'
            : (_colExists($db, 'usuarios', 'fecha_registro') ? 'fecha_registro' : null);

    if ($dateCol === null) return 0;

    $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE DATE({$dateCol}) = CURDATE()";
    $res = $db->query($sql)->fetch_assoc();
    return (int)($res['total'] ?? 0);
}

/**
 * Últimos usuarios registrados.
 * Devuelve la fecha como alias 'fecha_registro' para no romper el dashboard.
 */
function getUsuariosRecientes(int $limit = 5): array {
    $db = getDBConnection();

    // Elegir columna de orden válido
    $dateCol = _colExists($db, 'usuarios', 'created_at') ? 'created_at'
            : (_colExists($db, 'usuarios', 'fecha_registro') ? 'fecha_registro' : null);

    if ($dateCol === null) {
        // Sin columna de fecha, devolvemos lo que se pueda
        $sql = "SELECT id, nombre, email, NULL AS fecha_registro
                FROM usuarios
                ORDER BY id DESC
                LIMIT ?";
    } else {
        $sql = "SELECT id, nombre, email, {$dateCol} AS fecha_registro
                FROM usuarios
                ORDER BY {$dateCol} DESC
                LIMIT ?";
    }

    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}
