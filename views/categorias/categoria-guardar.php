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

    // 🔹 Validaciones
    if ($nombre === '') {
        header("Location: categorias.php?error=" . urlencode("⚠️ El nombre es obligatorio."));
        exit();
    }
    if (!preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ0-9\s]+$/u', $nombre)) {
        header("Location: categorias.php?error=" . urlencode("⚠️ El nombre solo puede contener letras, números y espacios."));
        exit();
    }

    if ($id > 0) {
        // 🔹 EDITAR
        if (actualizarCategoria($id, $nombre, $descripcion)) {
            header("Location: categorias.php?msg=" . urlencode("✅ Categoría actualizada correctamente."));
            exit();
        } else {
            header("Location: categorias.php?error=" . urlencode("❌ No se pudo actualizar la categoría."));
            exit();
        }
    } else {
        // 🔹 CREAR
        if (insertarCategoria($nombre, $descripcion)) {
            header("Location: categorias.php?msg=" . urlencode("✅ Categoría creada correctamente."));
            exit();
        } else {
            header("Location: categorias.php?error=" . urlencode("❌ No se pudo crear la categoría."));
            exit();
        }
    }
}

// 🚨 Si alguien entra sin POST
header("Location: categorias.php?error=" . urlencode("Acceso inválido."));
exit();
