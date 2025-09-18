<?php
// config/auth-redirect.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    $rol = strtolower($_SESSION['usuario_rol']);

    // 🚫 Admins y Gestores no pueden entrar al index
    if (basename($_SERVER['PHP_SELF']) === 'index.php') {
        if ($rol === 'admin') {
            header("Location: views/dashboard-admin.php");
            exit();
        }
        if ($rol === 'gestor') {
            header("Location: views/dashboard-gestor.php");
            exit();
        }
    }
}
