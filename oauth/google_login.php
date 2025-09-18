<?php
// Cargar la librería de Google API (asegúrate de que exista esta ruta)
require_once __DIR__ . '/../google-api-php-client/autoload.php';

// Crear cliente de Google
$client = new Google_Client();
$client->setClientId('AQUI_TU_CLIENT_ID');
$client->setClientSecret('AQUI_TU_CLIENT_SECRET');
$client->setRedirectUri('http://localhost/LumiSpace/oauth/google_callback.php');

// Scopes → los permisos que pedimos al usuario
$client->addScope("email");
$client->addScope("profile");

// Redirigir al usuario a la pantalla de login de Google
$loginUrl = $client->createAuthUrl();
header("Location: $loginUrl");
exit();
