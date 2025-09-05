<?php
// === CONFIGURACIÓN GLOBAL DE LA BASE DE DATOS === //
$host = "localhost";    // Servidor (puede ser una IP o dominio)
$user = "root";         // Usuario de MySQL
$pass = "";             // Contraseña de MySQL
$db   = "LumiSpace";   // Nombre de la base de datos

// Crear conexión global
$conn = new mysqli($host, $user, $pass, $db);

// Verificar si hay error
if ($conn->connect_error) {
    die("❌ Error en la conexión a la base de datos: " . $conn->connect_error);
}

// Función para acceder a la conexión global
function getDBConnection() {
    global $conn;
    return $conn;
}
?>
