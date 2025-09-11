<?php
declare(strict_types=1);
session_start();

/* Evitar cache */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/config.php';               // $google_client (cargado con .env)
require_once __DIR__ . '/../config/functions.php';  // obtenerUsuarioPorEmail

/* =========================
   Helpers
   ========================= */
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

/* =========================
   Validaciones iniciales
   ========================= */
if (!isset($_GET['code'])) go('../views/login.php', 'Error con Google');
if (!isset($google_client) || !$google_client instanceof Google_Client) {
  go('../views/login.php', 'Error de cliente Google');
}

/* Verificación CSRF */
if (isset($_SESSION['oauth2_state'])) {
  if (!hash_equals($_SESSION['oauth2_state'], (string)($_GET['state'] ?? ''))) {
    unset($_SESSION['oauth2_state']);
    go('../views/login.php', 'Error de seguridad');
  }
  unset($_SESSION['oauth2_state']);
}

try {
  // 1) Intercambio code -> token
  $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);
  if (!$token || isset($token['error'])) {
    go('../views/login.php', 'Error con el token de Google');
  }

  $google_client->setAccessToken($token['access_token']);
  $oauth2 = new Google_Service_Oauth2($google_client);
  $info   = $oauth2->userinfo->get();

  // 2) Obtener datos básicos
  $email = strtolower(trim($info->email ?? ''));
  if ($email === '') go('../views/login.php', 'No se pudo obtener tu correo de Google');

  $nombre = (string)($info->name ?? $email);

  // 3) Buscar en BD
  $user = obtenerUsuarioPorEmail($email);
  if (!$user) {
    go('../views/login.php', 'Tu cuenta de Google no está registrada. Regístrate primero.');
  }

  // 4) Crear sesión
  $rol = normalizarRol($user['rol'] ?? 'usuario');

  session_regenerate_id(true);
  $_SESSION['usuario_id']     = (int)$user['id'];
  $_SESSION['usuario_nombre'] = (string)$user['nombre'];
  $_SESSION['usuario_rol']    = $rol;

  // 5) Redirigir según rol
  switch ($rol) {
      case 'admin':
          go('../views/dashboard-admin.php');
      case 'gestor':
          go('../views/dashboard-gestor.php');
      case 'dueno':
      case 'dueño':
          go('../views/dashboard-dueno.php');
      default:
          go('../index.php'); // usuario normal → index
  }

} catch (Throwable $e) {
  go('../views/login.php', 'Ocurrió un error con Google');
}
