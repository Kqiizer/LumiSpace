<?php
/**
 * ============================================================
 * 🔌 CONFIGURACIÓN DE BASE DE DATOS
 * ============================================================
 * 
 * @package    LumiSpace
 * @subpackage Config
 * @version    2.0.1
 */

// Prevenir acceso directo
if (!defined('BASE_URL')) {
    $envBase = getenv('BASE_URL');
    define('BASE_URL', $envBase !== false ? $envBase : '/LumiSpace/');
}

/**
 * Obtener conexión a la base de datos (Singleton)
 * 
 * @return mysqli Conexión activa a MySQL
 * @throws Exception Si falla la conexión
 */
function getDBConnection(): mysqli
{
    static $conn = null;

    // Reutilizar conexión existente
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }

    // Configuración de la base de datos
    $config = [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'user' => getenv('DB_USER') ?: 'u496299715_LumiSpace',
        'password' => getenv('DB_PASS') ?: 'LumiSpace1',
        'database' => getenv('DB_NAME') ?: 'u496299715_LumiSpace',
        'charset' => 'utf8mb4',
        'timezone' => '-06:00' // Ajusta según tu zona horaria
    ];

    try {
        // Crear nueva conexión
        $conn = new mysqli(
            $config['host'],
            $config['user'],
            $config['password'],
            $config['database']
        );

        // Verificar errores de conexión
        if ($conn->connect_error) {
            error_log("❌ Error de conexión MySQL: " . $conn->connect_error);
            throw new Exception("Error de conexión a la base de datos");
        }

        // Configurar charset
        if (!$conn->set_charset($config['charset'])) {
            error_log("⚠ Error al establecer charset: " . $conn->error);
        }

        // Configurar zona horaria
        $conn->query("SET time_zone = '{$config['timezone']}'");

        // Modo estricto SQL (recomendado)
        $conn->query("SET sql_mode = 'STRICT_ALL_TABLES'");

        return $conn;

    } catch (Exception $e) {
        error_log("❌ Excepción al conectar a BD: " . $e->getMessage());

        // En producción, no mostrar detalles técnicos
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            die("Error de conexión a la base de datos. Contacte al administrador.");
        } else {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}

/**
 * Verificar si una tabla existe
 * 
 * @param mysqli $conn Conexión activa
 * @param string $table Nombre de la tabla
 * @return bool True si existe
 */
function tableExists(mysqli $conn, string $table): bool
{
    // Sanitizar nombre de tabla
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        error_log("⚠ Nombre de tabla inválido: $table");
        return false;
    }

    // Usar query directa en lugar de prepared statement
    $database = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    $table = $conn->real_escape_string($table);

    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM information_schema.tables 
        WHERE table_schema = '$database' 
        AND table_name = '$table'
    ");

    if (!$result) {
        error_log("⚠ Error al verificar tabla: " . $conn->error);
        return false;
    }

    $row = $result->fetch_assoc();
    return $row && $row['count'] > 0;
}

/**
 * Verificar si una columna existe en una tabla
 * 
 * @param mysqli $conn Conexión activa
 * @param string $table Nombre de la tabla
 * @param string $column Nombre de la columna
 * @return bool True si existe
 */
function columnExists(mysqli $conn, string $table, string $column): bool
{
    // Sanitizar nombres
<<<<<<< HEAD
    if (
        !preg_match('/^[a-zA-Z0-9_]+$/', $table) ||
        !preg_match('/^[a-zA-Z0-9_]+$/', $column)
    ) {
        error_log("⚠️ Nombre inválido - Tabla: $table, Columna: $column");
=======
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || 
        !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        error_log("⚠ Nombre inválido - Tabla: $table, Columna: $column");
>>>>>>> bea4243d (ajustes, marcas, categorias, buscador)
        return false;
    }

    $database = $conn->query("SELECT DATABASE()")->fetch_row()[0];
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);

    $result = $conn->query("
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_schema = '$database' 
        AND table_name = '$table' 
        AND column_name = '$column'
    ");

    if (!$result) {
        error_log("⚠ Error al verificar columna: " . $conn->error);
        return false;
    }

    $row = $result->fetch_assoc();
    return $row && $row['count'] > 0;
}

/**
 * Cerrar conexión a la base de datos
 * 
 * @return void
 */
function closeDBConnection(): void
{
    static $conn = null;

    if ($conn !== null && $conn instanceof mysqli) {
        $conn->close();
        $conn = null;
    }
}

/**
 * Ejecutar query con manejo de errores
 * 
 * @param string $sql Query SQL
 * @param array $params Parámetros para bind
 * @param string $types Tipos de parámetros (i, s, d, b)
 * @return mysqli_result|bool Resultado de la query
 */
function executeQuery(string $sql, array $params = [], string $types = ''): mysqli_result|bool
{
    $conn = getDBConnection();

    try {
        if (empty($params)) {
            $result = $conn->query($sql);

            if (!$result) {
                error_log("❌ Error en query: " . $conn->error . " | SQL: $sql");
                return false;
            }

            return $result;
        }

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            error_log("❌ Error en prepare: " . $conn->error . " | SQL: $sql");
            return false;
        }

        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("❌ Error en execute: " . $stmt->error);
            return false;
        }

        $result = $stmt->get_result();
        $stmt->close();

        return $result;

    } catch (Exception $e) {
        error_log("❌ Excepción en executeQuery: " . $e->getMessage());
        return false;
    }
}

/**
 * Iniciar transacción
 * 
 * @return bool True si se inició correctamente
 */
function beginTransaction(): bool
{
    $conn = getDBConnection();
    return $conn->begin_transaction();
}

/**
 * Confirmar transacción
 * 
 * @return bool True si se confirmó correctamente
 */
function commitTransaction(): bool
{
    $conn = getDBConnection();
    return $conn->commit();
}

/**
 * Revertir transacción
 * 
 * @return bool True si se revirtió correctamente
 */
function rollbackTransaction(): bool
{
    $conn = getDBConnection();
    return $conn->rollback();
}

/**
 * Obtener lista de tablas en la base de datos
 * 
 * @return array Lista de nombres de tablas
 */
function getTables(): array
{
    $conn = getDBConnection();
    $tables = [];

    $result = $conn->query("SHOW TABLES");

    if ($result) {
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
    }

    return $tables;
}

/**
 * Crear tabla roles si no existe
 * 
 * @return bool True si se creó o ya existe
 */
function createRolesTable(): bool
{
    $conn = getDBConnection();

    $sql = "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE,
        descripcion TEXT,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        activo TINYINT(1) DEFAULT 1,
        INDEX idx_nombre (nombre)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        error_log("✅ Tabla 'roles' creada o ya existe");

        // Insertar roles por defecto si la tabla está vacía
        $count = $conn->query("SELECT COUNT(*) as count FROM roles")->fetch_assoc()['count'];

        if ($count == 0) {
            $conn->query("INSERT INTO roles (nombre, descripcion) VALUES 
                ('admin', 'Administrador del sistema con acceso total'),
                ('gestor', 'Gestor de ventas y productos'),
                ('usuario', 'Usuario estándar del sistema')
            ");
            error_log("✅ Roles por defecto insertados");
        }

        return true;
    } else {
        error_log("❌ Error al crear tabla roles: " . $conn->error);
        return false;
    }
}

/**
 * Crear tabla permisos si no existe
 * 
 * @return bool True si se creó o ya existe
 */
function createPermisosTable(): bool
{
    $conn = getDBConnection();

    $sql = "CREATE TABLE IF NOT EXISTS permisos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        modulo VARCHAR(50) DEFAULT 'General',
        descripcion TEXT,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_permiso (nombre, modulo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql)) {
        error_log("✅ Tabla 'permisos' creada o ya existe");
        return true;
    } else {
        error_log("❌ Error al crear tabla permisos: " . $conn->error);
        return false;
    }
}

// Cerrar conexión al finalizar el script
register_shutdown_function('closeDBConnection');