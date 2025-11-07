<?php
/**
 * ============================================================
 * 👥 MÓDULO: SISTEMA DE ROLES Y PERMISOS
 * ============================================================
 * 
 * @package    LumiSpace
 * @subpackage Modules
 * @version    2.0.0
 */

// Prevenir acceso directo
if (!function_exists('getDBConnection')) {
    die('Acceso denegado');
}

/**
 * Obtener todos los roles con estadísticas completas
 * 
 * @return array Lista de roles con contadores
 */
function getRoles(): array {
    try {
        $conn = getDBConnection();

        $sql = "
            SELECT 
                r.id,
                r.nombre,
                r.descripcion,
                r.fecha_creacion AS creado_en,
                NULL AS actualizado_en,
                IFNULL(COUNT(DISTINCT u.id), 0) AS usuarios_count,
                IFNULL(COUNT(DISTINCT rp.permiso_id), 0) AS permisos_count,
                IFNULL(GROUP_CONCAT(DISTINCT p.nombre ORDER BY p.nombre SEPARATOR ', '), '') AS permisos
            FROM roles r
            LEFT JOIN usuarios u ON LOWER(u.rol) = LOWER(r.nombre)
            LEFT JOIN rol_permisos rp ON rp.rol_id = r.id
            LEFT JOIN permisos p ON p.id = rp.permiso_id
            GROUP BY r.id, r.nombre, r.descripcion, r.fecha_creacion
            ORDER BY r.id ASC
        ";

        $result = $conn->query($sql);
        if (!$result) {
            error_log("❌ Error SQL getRoles(): " . $conn->error);
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC) ?: [];

    } catch (Throwable $e) {
        error_log("⚠️ Excepción en getRoles(): " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener un rol por ID con información completa
 * 
 * @param int $id ID del rol
 * @return array|null Datos del rol o null si no existe
 */
function getRolById(int $id): ?array {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            SELECT 
                r.id, 
                r.nombre, 
                r.descripcion, 
                r.creado_en, 
                r.actualizado_en,
                COUNT(DISTINCT u.id) AS usuarios_count
            FROM roles r
            LEFT JOIN usuarios u ON u.rol_id = r.id
            WHERE r.id = ?
            GROUP BY r.id, r.nombre, r.descripcion, r.creado_en, r.actualizado_en
        ");
        
        if (!$stmt) {
            error_log("Error en getRolById prepare: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $rol = $result->fetch_assoc();
        
        if (!$rol) {
            error_log("Rol con ID $id no encontrado");
            return null;
        }

        return $rol;

    } catch (Exception $e) {
        error_log("Error en getRolById($id): " . $e->getMessage());
        return null;
    }
}

/**
 * Crear un nuevo rol
 * 
 * @param string $nombre Nombre del rol
 * @param string $descripcion Descripción opcional
 * @return int|false ID del rol creado o false si falla
 */
function createRol(string $nombre, string $descripcion = ''): int|false {
    try {
        $conn = getDBConnection();

        if (!$conn || $conn->connect_error) {
            error_log("Error de conexión en createRol");
            return false;
        }

        $nombre = trim($nombre);
        $descripcion = trim($descripcion);

        // Validar nombre
        $validation = validateRolName($nombre);
        if (!$validation['valid']) {
            error_log("Validación fallida: {$validation['message']}");
            return false;
        }

        // Verificar duplicado
        $stmt = $conn->prepare("SELECT id FROM roles WHERE LOWER(nombre) = LOWER(?)");
        
        if (!$stmt) {
            error_log("Error en prepare: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            error_log("Rol duplicado: $nombre");
            return false;
        }

        // Insertar
        $stmt = $conn->prepare("INSERT INTO roles (nombre, descripcion, creado_en) VALUES (?, ?, NOW())");
        
        if (!$stmt) {
            error_log("Error en prepare insert: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("ss", $nombre, $descripcion);

        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            error_log("✅ Rol creado: ID=$newId, Nombre=$nombre");
            return $newId;
        }

        return false;

    } catch (Exception $e) {
        error_log("Excepción en createRol: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualizar un rol existente
 */
function updateRol(int $id, string $nombre, string $descripcion = ''): bool {
    try {
        $conn = getDBConnection();

        $nombre = trim($nombre);
        $descripcion = trim($descripcion);

        $validation = validateRolName($nombre);
        if (!$validation['valid']) {
            return false;
        }

        // Evitar duplicados
        $stmt = $conn->prepare("SELECT id FROM roles WHERE LOWER(nombre) = LOWER(?) AND id != ?");
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return false;
        }

        // Actualizar
        $stmt = $conn->prepare("UPDATE roles SET nombre = ?, descripcion = ?, actualizado_en = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);

        return $stmt->execute();

    } catch (Exception $e) {
        error_log("Error en updateRol: " . $e->getMessage());
        return false;
    }
}

/**
 * Eliminar un rol con validaciones
 */
function deleteRol(int $id): bool {
    try {
        $conn = getDBConnection();

        $rol = getRolById($id);
        if (!$rol) return false;

        // Roles protegidos
        $protegidos = ['Administrador', 'Admin', 'administrador', 'admin'];
        if (in_array($rol['nombre'], $protegidos)) {
            error_log("Intento de eliminar rol protegido: {$rol['nombre']}");
            return false;
        }

        // Verificar usuarios
        if ($rol['usuarios_count'] > 0) {
            error_log("Rol tiene usuarios asignados");
            return false;
        }

        $conn->begin_transaction();

        try {
            // Eliminar permisos
            $stmt = $conn->prepare("DELETE FROM rol_permisos WHERE rol_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Eliminar rol
            $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $conn->commit();
            error_log("✅ Rol eliminado: ID=$id");
            return true;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error al eliminar rol: " . $e->getMessage());
            return false;
        }

    } catch (Exception $e) {
        error_log("Excepción en deleteRol: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas de roles
 */
function getRolesStats(): array {
    try {
        $conn = getDBConnection();

        $sql = "
            SELECT 
                LOWER(r.nombre) as nombre_lower,
                r.nombre,
                COUNT(DISTINCT u.id) as count
            FROM roles r
            LEFT JOIN usuarios u ON u.rol_id = r.id
            GROUP BY r.id, r.nombre
        ";
        
        $result = $conn->query($sql);
        
        if (!$result) {
            return [];
        }

        $stats = [
            'admin' => 0,
            'administrador' => 0,
            'supervisor' => 0,
            'vendedor' => 0,
            'cajero' => 0,
            'inventario' => 0
        ];

        while ($row = $result->fetch_assoc()) {
            $key = strtolower($row['nombre']);
            $stats[$key] = (int)$row['count'];
        }

        return $stats;

    } catch (Exception $e) {
        error_log("Error en getRolesStats: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener permisos de un rol
 */
function getRolPermisos(int $rolId): array {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            SELECT p.id, p.nombre, p.descripcion, p.modulo
            FROM permisos p
            INNER JOIN rol_permisos rp ON rp.permiso_id = p.id
            WHERE rp.rol_id = ?
            ORDER BY p.modulo, p.nombre
        ");
        
        $stmt->bind_param("i", $rolId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    } catch (Exception $e) {
        error_log("Error en getRolPermisos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todos los permisos agrupados por módulo
 */
function getAllPermisos(): array {
    try {
        $conn = getDBConnection();
        
        $sql = "SELECT id, nombre, descripcion, modulo FROM permisos ORDER BY modulo, nombre";
        $result = $conn->query($sql);

        if (!$result) return [];

        $permisos = [];
        while ($row = $result->fetch_assoc()) {
            $modulo = $row['modulo'] ?: 'General';
            if (!isset($permisos[$modulo])) {
                $permisos[$modulo] = [];
            }
            $permisos[$modulo][] = $row;
        }

        return $permisos;

    } catch (Exception $e) {
        error_log("Error en getAllPermisos: " . $e->getMessage());
        return [];
    }
}

/**
 * Asignar permiso a rol
 */
function assignPermisoToRol(int $rolId, int $permisoId): bool {
    try {
        $conn = getDBConnection();

        // Verificar duplicado
        $stmt = $conn->prepare("SELECT id FROM rol_permisos WHERE rol_id = ? AND permiso_id = ?");
        $stmt->bind_param("ii", $rolId, $permisoId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return true;
        }

        // Insertar
        $stmt = $conn->prepare("INSERT INTO rol_permisos (rol_id, permiso_id, creado_en) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $rolId, $permisoId);

        return $stmt->execute();

    } catch (Exception $e) {
        error_log("Error en assignPermisoToRol: " . $e->getMessage());
        return false;
    }
}

/**
 * Remover permiso de rol
 */
function removePermisoFromRol(int $rolId, int $permisoId): bool {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("DELETE FROM rol_permisos WHERE rol_id = ? AND permiso_id = ?");
        $stmt->bind_param("ii", $rolId, $permisoId);

        return $stmt->execute();

    } catch (Exception $e) {
        error_log("Error en removePermisoFromRol: " . $e->getMessage());
        return false;
    }
}

/**
 * Verificar si usuario tiene permiso
 */
function usuarioTienePermiso(int $usuarioId, string $permisoNombre): bool {
    try {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            INNER JOIN rol_permisos rp ON rp.rol_id = r.id
            INNER JOIN permisos p ON p.id = rp.permiso_id
            WHERE u.id = ? AND p.nombre = ?
        ");
        
        $stmt->bind_param("is", $usuarioId, $permisoNombre);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        return ($res['total'] ?? 0) > 0;

    } catch (Exception $e) {
        error_log("Error en usuarioTienePermiso: " . $e->getMessage());
        return false;
    }
}

/**
 * Validar nombre de rol
 */
function validateRolName(string $nombre): array {
    $nombre = trim($nombre);

    if (empty($nombre)) {
        return ['valid' => false, 'message' => 'El nombre es obligatorio'];
    }

    if (strlen($nombre) < 3) {
        return ['valid' => false, 'message' => 'Debe tener al menos 3 caracteres'];
    }

    if (strlen($nombre) > 50) {
        return ['valid' => false, 'message' => 'No puede exceder 50 caracteres'];
    }

    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
        return ['valid' => false, 'message' => 'Solo letras y espacios'];
    }

    return ['valid' => true, 'message' => 'OK'];
}