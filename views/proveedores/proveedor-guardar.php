<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre    = trim($_POST['nombre'] ?? '');
    $contacto  = trim($_POST['contacto'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre !== '') {
        $ok = insertarProveedor($nombre, $contacto, $telefono, $email, $direccion);

        if ($ok) {
            header("Location: proveedores.php?msg=" . urlencode("✅ Proveedor creado con éxito."));
            exit();
        } else {
            $error = "❌ Error al guardar proveedor en la base de datos.";
        }
    } else {
        $error = "⚠️ El nombre es obligatorio.";
    }
}

// 🚨 Si hubo error o acceso incorrecto
header("Location: proveedores.php?error=" . urlencode($error ?? "Acceso inválido."));
exit();
