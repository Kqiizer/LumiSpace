<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
  header("Location: ../login.php?error=unauthorized");
  exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id          = (int)($_POST['id'] ?? 0);
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombre) {
        if ($id > 0) {
            // 🔹 EDITAR
            if (actualizarCategoria($id, $nombre, $descripcion)) {
                header("Location: categorias.php?msg=actualizado");
                exit();
            } else {
                header("Location: categorias.php?error=update_failed");
                exit();
            }
        } else {
            // 🔹 CREAR
            if (insertarCategoria($nombre, $descripcion)) {
                header("Location: categorias.php?msg=creado");
                exit();
            } else {
                header("Location: categorias.php?error=create_failed");
                exit();
            }
        }
    } else {
        header("Location: categorias.php?error=missing_name");
        exit();
    }
}

// 🚨 Si alguien entra sin POST
header("Location: categorias.php?error=invalid_request");
exit();
