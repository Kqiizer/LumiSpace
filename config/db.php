<?php
declare(strict_types=1);

/* ============================
   Configuración DB local (XAMPP)
   ============================ */
$DB_HOST = "127.0.0.1"; // o "localhost"
$DB_PORT = 3306;
$DB_USER = "root";      // usuario por defecto en XAMPP
$DB_PASS = "";          // contraseña (vacía por defecto en XAMPP)
$DB_NAME = "LumiSpace"; // nombre de tu base de datos

/**
 * Devuelve una conexión MySQLi.
 */
if (!function_exists('getDBConnection')) {
    function getDBConnection(): mysqli {
        global $DB_HOST, $DB_PORT, $DB_USER, $DB_PASS, $DB_NAME;

        $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

        if ($conn->connect_error) {
            die("❌ Error en la conexión a MySQL: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    }
}
