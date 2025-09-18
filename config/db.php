<?php
declare(strict_types=1);

/* ============================
   Configuración DB local (XAMPP)
   ============================ */
$host = "127.0.0.1"; // o "localhost"
$port = 3306;
$user = "root";      // usuario por defecto en XAMPP
$pass = "";          // contraseña (vacía por defecto en XAMPP)
$db   = "LumiSpace"; // nombre de tu base de datos

/**
 * Devuelve SIEMPRE una conexión nueva a MySQL.
 */
function getDBConnection(): mysqli {
    global $host, $port, $user, $pass, $db;

    $conn = new mysqli($host, $user, $pass, $db, $port);

    if ($conn->connect_error) {
        die("❌ Error en la conexión a MySQL: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
