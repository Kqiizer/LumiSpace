<?php
declare(strict_types=1);

/* ============================
   Cargar variables del .env
   ============================ */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && is_readable($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

/* ============================
   Configuración DB desde .env
   ============================ */
$host = getenv("DB_HOST") ?: "db";          // servicio MySQL en docker-compose
$port = getenv("DB_PORT") ?: 3306;
$user = getenv("DB_USER") ?: "posuser";
$pass = getenv("DB_PASS") ?: "pospass";
$db   = getenv("DB_NAME") ?: "LumiSpace";

/* ============================
   Crear conexión
   ============================ */
$conn = new mysqli($host, $user, $pass, $db, (int)$port);

if ($conn->connect_error) {
    die("❌ Error en la conexión a MySQL: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/* ============================
   Función de acceso global
   ============================ */
function getDBConnection(): mysqli {
    global $conn;
    return $conn;
}
