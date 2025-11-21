<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id        = (int)($_POST['id'] ?? 0);
    $nombre    = trim($_POST['nombre'] ?? '');
    $contacto  = trim($_POST['contacto'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($id > 0 && $nombre !== '') {
        // 🔹 Validar que el nombre no contenga números
        if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u', $nombre)) {
            $error = "⚠️ El nombre no puede contener números ni caracteres inválidos.";
        } else {
            $conn = getDBConnection();

            $sql = "UPDATE proveedores 
                    SET nombre=?, contacto=?, telefono=?, email=?, direccion=? 
                    WHERE id=?";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssi", $nombre, $contacto, $telefono, $email, $direccion, $id);
                if ($stmt->execute()) {
                    header("Location: proveedores.php?msg=" . urlencode("✅ Proveedor actualizado correctamente."));
                    exit();
                } else {
                    $error = "❌ Error al actualizar proveedor: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "❌ Error en prepare(): " . $conn->error;
            }
        }
    } else {
        $error = "⚠️ Datos inválidos: el nombre es obligatorio.";
    }
}

// 🚨 Si falla, redirigir con error
header("Location: proveedores.php?error=" . urlencode($error ?? "Error desconocido"));
exit();
