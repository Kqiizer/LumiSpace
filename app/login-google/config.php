<?php
declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php"; 

/* ===========================
   Funciones para .env
   =========================== */
if (!function_exists('loadEnv')) {
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
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?? $default;
    }
}

/* ===========================
   Cargar variables de entorno
   =========================== */
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

/* ===========================
   Redirect URI (CALLBACK)
   =========================== */
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Ruta por defecto: si no está en .env, usa esta
$defaultCallbackPath = '/login-google/callback.php';

/* ===========================
   Variables desde .env
   =========================== */
$GOOGLE_CLIENT_ID     = env('GOOGLE_CLIENT_ID', '');
$GOOGLE_CLIENT_SECRET = env('GOOGLE_CLIENT_SECRET', '');
$GOOGLE_REDIRECT_URI  = env('GOOGLE_REDIRECT_URI', $proto . '://' . $host . $defaultCallbackPath);

// Validación obligatoria
if (!$GOOGLE_CLIENT_ID || !$GOOGLE_CLIENT_SECRET) {
    throw new RuntimeException("❌ Falta configurar GOOGLE_CLIENT_ID o GOOGLE_CLIENT_SECRET en el archivo .env");
}

/* ===========================
   Configuración del cliente
   =========================== */
$google_client = new Google_Client();
$google_client->setClientId($GOOGLE_CLIENT_ID);
$google_client->setClientSecret($GOOGLE_CLIENT_SECRET);
$google_client->setRedirectUri($GOOGLE_REDIRECT_URI);

// Scopes básicos
$google_client->addScope('email');
$google_client->addScope('profile');

// Opcionales
$google_client->setAccessType('online');        
$google_client->setIncludeGrantedScopes(true);
$google_client->setPrompt('select_account');    

/* ===========================
   Helper para obtener la URL
   =========================== */
if (!function_exists('getGoogleAuthUrl')) {
    function getGoogleAuthUrl(Google_Client $client): string {
        return $client->createAuthUrl();
    }
}
