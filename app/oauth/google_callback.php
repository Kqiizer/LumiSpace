<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/functions.php';

/* =========================
   Cargar .env
   ========================= */
function loadEnv(string $path): void {
    if (!is_readable($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}
function env(string $key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?? $default;
}
loadEnv(__DIR__ . '/../.env');

/* =========================
   Configuración Google
   ========================= */
$client = new Google_Client();
$client->setClientId(env('GOOGLE_CLIENT_ID', 'dummy-client-id'));
$client->setClientSecret(env('GOOGLE_CLIENT_SECRET', 'dummy-secret'));
$client->setRedirectUri(env('GOOGLE_REDIRECT_URI', 'http://localhost:8080/oauth/google_callback.php'));
$client->addScope(['email', 'profile']);

if (!isset($_GET['code'])) {
    header('Location: ../views/login.php?error=google');
    exit();
}

/* =========================
   Intercambio token
   ========================= */
$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (!$token || isset($token['error'])) {
    header('Location: ../views/login.php?error=google_token');
    exit();
}

$client->setAccessToken($token['access_token']);
$oauth2 = new Google_Service_Oauth2($client);
$googleUser = $oauth2->userinfo->get();

$email  = strtolower(trim($googleUser->email ?? ''));
$nombre = $googleUser->name ?? $email;
$gid    = $googleUser->id ?? null;

if (!$email) {
    header('Location: ../views/login.php?error=google_no_email');
    exit();
}

/* =========================
   Buscar o registrar usuario
   ========================= */
$user = obtenerUsuarioPorEmail($email);

if (!$user && function_exists('registrarUsuarioSocial')) {
    $user = registrarUsuarioSocial(
        $nombre ?: 'Usuario Google',
        $email,
        $gid,          // providerId correcto
        "usuario",     // rol
        "google"       // proveedor
    );
}

/* =========================
   Normalizar rol y sesión
   ========================= */
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

if (!$user) {
    header('Location: ../views/login.php?error=google_register_failed');
    exit();
}

$rol = normalizarRol($user['rol'] ?? 'usuario');

session_regenerate_id(true);
$_SESSION['usuario_id']     = (int)($user['id'] ?? 0);
$_SESSION['usuario_nombre'] = (string)($user['nombre'] ?? $nombre);
$_SESSION['usuario_rol']    = $rol;

/* =========================
   Redirigir según rol
   ========================= */
$rutas = [
    'admin'   => '../views/dashboard-admin.php',
    'usuario' => '../views/index.php',
    'gestor'  => '../views/dashboard-gestor.php',
    'dueno'   => '../views/pos.php'
];

header('Location: ' . ($rutas[$rol] ?? '../index.php'));
exit();
