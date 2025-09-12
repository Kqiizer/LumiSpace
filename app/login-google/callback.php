<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config.php';              // $google_client cargado
require_once __DIR__ . '/../config/functions.php'; // funciones de BD

function go(string $path, string $flash = ''): never {
    if ($flash) $_SESSION['flash'] = $flash;
    header("Location: $path", true, 302);
    exit();
}

function normalizarRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    $map = [
        'admin'   => 'admin',
        'gestor'  => 'gestor', 'manager' => 'gestor',
        'dueno'   => 'dueno', 'dueño' => 'dueno', 'owner' => 'dueno',
        'usuario' => 'usuario', 'user' => 'usuario', 'cliente' => 'usuario',
    ];
    return $map[$rol] ?? 'usuario';
}

// Validaciones iniciales
if (!isset($_GET['code'])) go('../views/login.php', 'Error con Google');
if (!$google_client instanceof Google_Client) go('../views/login.php', 'Cliente Google no válido');

try {
    // 1) Token
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (!$token || isset($token['error'])) go('../views/login.php', 'Error con token de Google');

    $google_client->setAccessToken($token['access_token']);
    $oauth2 = new Google_Service_Oauth2($google_client);
    $info   = $oauth2->userinfo->get();

    // 2) Datos básicos
    $email    = strtolower(trim($info->email ?? ''));
    $nombre   = (string)($info->name ?? $email);
    $googleId = (string)($info->id ?? '');

    if ($email === '') go('../views/login.php', 'No se pudo obtener tu correo de Google');

    // 3) Buscar o registrar
    $user = obtenerUsuarioPorEmail($email);
    if (!$user) {
        if (!function_exists('registrarUsuarioSocial')) {
            go('../views/login.php', 'Falta función registrarUsuarioSocial');
        }

        $user = registrarUsuarioSocial($nombre, $email, $googleId, 'usuario', 'google');
        if (!$user) go('../views/login.php', 'No se pudo registrar tu cuenta de Google');
    }

    // 4) Sesión
    $rol = normalizarRol($user['rol'] ?? 'usuario');
    session_regenerate_id(true);
    $_SESSION['usuario_id']     = (int)$user['id'];
    $_SESSION['usuario_nombre'] = (string)$user['nombre'];
    $_SESSION['usuario_rol']    = $rol;

    // 5) Redirigir
    $rutas = [
        'admin'   => '../views/dashboard-admin.php',
        'gestor'  => '../views/dashboard-gestor.php',
        'dueno'   => '../views/dashboard-dueno.php',
        'usuario' => '../views/dashboard-usuario.php',
    ];

    go($rutas[$rol] ?? '../index.php');

} catch (Throwable $e) {
    go('../views/login.php', 'Error con Google: ' . $e->getMessage());
}
