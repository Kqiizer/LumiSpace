<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config.php';              // $google_client cargado
require_once __DIR__ . '/../config/functions.php'; // funciones de BD

/* =========================
   Helpers
   ========================= */
function normalizarRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    $map = [
        'admin'   => 'admin',
        'gestor'  => 'gestor', 'manager' => 'gestor',
        'cajero'  => 'cajero', 'cashier' => 'cajero',
        'usuario' => 'usuario', 'user' => 'usuario', 'cliente' => 'usuario',
    ];
    return $map[$rol] ?? 'usuario';
}

function redirSegunRol(string $rol): never {
    switch ($rol) {
        case 'admin':
            header("Location: ../views/dashboard-admin.php"); 
            break;
        case 'gestor':
            header("Location: ../views/dashboard-gestor.php"); 
            break;
        case 'cajero':
            header("Location: ../views/pos.php"); 
            break;
        case 'usuario':
            header("Location: ../views/index.php"); 
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}

/* =========================
   🚨 Si ya hay sesión → directo
   ========================= */
if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    redirSegunRol(normalizarRol($_SESSION['usuario_rol']));
}

/* =========================
   Validaciones iniciales
   ========================= */
if (!isset($_GET['code']) || !$google_client instanceof Google_Client) {
    header("Location: ../views/login.php?error=google_invalid_request");
    exit();
}

// Validar CSRF state
if (!empty($_GET['state']) && isset($_SESSION['oauth2_state'])) {
    if (!hash_equals($_SESSION['oauth2_state'], $_GET['state'])) {
        unset($_SESSION['oauth2_state']);
        header("Location: ../views/login.php?error=google_state_invalid");
        exit();
    }
}

try {
    // 1) Intercambio code → token
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!$token || isset($token['error'])) {
        header("Location: ../views/login.php?error=google_token");
        exit();
    }

    $google_client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($google_client);
    $info   = $oauth2->userinfo->get();

    // 2) Datos básicos
    $email  = strtolower(trim($info->email ?? ''));
    $nombre = (string)($info->name ?? $email);

    if ($email === '') {
        header("Location: ../views/login.php?error=google_no_email");
        exit();
    }

    // 3) Buscar usuario en BD
    $user = obtenerUsuarioPorEmail($email);

    if (!$user) {
        // 🚨 No registrado → completar registro
        $url = "../views/register.php?google=1&email=" . urlencode($email) . "&nombre=" . urlencode($nombre);
        header("Location: $url");
        exit();
    }

    // 4) Crear sesión
    $rol = normalizarRol($user['rol'] ?? 'usuario');
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = (int)$user['id'];
    $_SESSION['usuario_nombre'] = (string)$user['nombre'];
    $_SESSION['usuario_rol']    = $rol;

    unset($_SESSION['oauth2_state']); // limpiar state

    // 5) Redirigir al dashboard según rol
    redirSegunRol($rol);

} catch (Throwable $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    header("Location: ../views/login.php?error=google_exception");
    exit();
}
