<?php
session_start();

// Usar la ruta correcta del autoload (porque no tienes vendor/)
require_once __DIR__ . '/../google-api-php-client/vendor/autoload.php';
include_once __DIR__ . '/../config/functions.php';

// Configuración del cliente
$client = new Google_Client();
$client->setClientId('TU_CLIENT_ID');      // 👉 pon tu Client ID
$client->setClientSecret('TU_CLIENT_SECRET');  // 👉 pon tu Client Secret
$client->setRedirectUri('http://localhost/LumiSpace/oauth/google_callback.php');

if (!isset($_GET['code'])) {
    header('Location: ../views/login.php?error=google');
    exit();
}

// Obtener el token con el código de Google
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    header('Location: ../views/login.php?error=google_token');
    exit();
}

$client->setAccessToken($token);

// Obtener la info del usuario
$oauth2 = new Google_Service_Oauth2($client);
$googleUser = $oauth2->userinfo->get();

$email  = $googleUser->email ?? null;
$nombre = $googleUser->name ?? null;

if (!$email) {
    header('Location: ../views/login.php?error=google_no_email');
    exit();
}

// Buscar usuario en la BD
$user = obtenerUsuarioPorEmail($email);

// Si no existe, registrarlo como usuario (password NULL)
if (!$user) {
    registrarUsuario($nombre ?: 'Usuario Google', $email, null, 'usuario');
    $user = obtenerUsuarioPorEmail($email);
}

// Guardar datos en la sesión
$_SESSION['usuario_id']     = $user['id'];
$_SESSION['usuario_nombre'] = $user['nombre'];
$_SESSION['usuario_rol']    = $user['rol'] ?? 'usuario';

// Redirigir según rol
$rutas = [
    'admin'   => '../views/dashboard-admin.php',
    'usuario' => '../views/dashboard-usuario.php',
    'dueno'   => '../views/dashboard-dueno.php'
];

header('Location: ' . ($rutas[$_SESSION['usuario_rol']] ?? '../views/dashboard-usuario.php'));
exit();
