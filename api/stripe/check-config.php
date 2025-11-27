<?php
/**
 * Script de diagnóstico para verificar la configuración de Stripe
 * Accede desde: /api/stripe/check-config.php
 */

header("Content-Type: application/json; charset=UTF-8");

$checks = [
    'composer_installed' => false,
    'stripe_php_installed' => false,
    'env_file_exists' => false,
    'stripe_secret_key' => false,
    'stripe_publishable_key' => false,
    'functions_loaded' => false,
    'stripe_config_loaded' => false,
    'stripe_client_created' => false,
];

$errors = [];
$warnings = [];

// 1. Verificar Composer
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    $checks['composer_installed'] = true;
    require_once $autoloadPath;
    
    // 2. Verificar Stripe PHP
    if (class_exists('\Stripe\StripeClient')) {
        $checks['stripe_php_installed'] = true;
    } else {
        $errors[] = 'Stripe PHP SDK no está instalado. Ejecuta: composer require stripe/stripe-php';
    }
} else {
    $errors[] = 'Composer no está instalado. Ejecuta: composer install';
}

// 3. Verificar archivo .env
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $checks['env_file_exists'] = true;
} else {
    $warnings[] = 'Archivo .env no encontrado. Crea uno basado en .env.example';
}

// 4. Verificar funciones
try {
    require_once __DIR__ . '/../../config/functions.php';
    $checks['functions_loaded'] = true;
} catch (\Throwable $e) {
    $errors[] = 'Error cargando functions.php: ' . $e->getMessage();
}

// 5. Verificar configuración de Stripe
try {
    require_once __DIR__ . '/../../config/stripe.php';
    $checks['stripe_config_loaded'] = true;
    
    $config = stripeConfig();
    
    if (!empty($config['secret_key'])) {
        $checks['stripe_secret_key'] = true;
    } else {
        $errors[] = 'STRIPE_SECRET_KEY no está configurado en .env';
    }
    
    if (!empty($config['publishable_key'])) {
        $checks['stripe_publishable_key'] = true;
    } else {
        $errors[] = 'STRIPE_PUBLISHABLE_KEY no está configurado en .env';
    }
    
    // 6. Intentar crear cliente de Stripe
    if ($checks['stripe_secret_key']) {
        try {
            $client = stripeClient();
            $checks['stripe_client_created'] = true;
        } catch (\Throwable $e) {
            $errors[] = 'Error al crear cliente de Stripe: ' . $e->getMessage();
        }
    }
    
} catch (\Throwable $e) {
    $errors[] = 'Error cargando stripe.php: ' . $e->getMessage();
}

$allPassed = !empty($errors) ? false : (count(array_filter($checks)) === count($checks));

echo json_encode([
    'status' => $allPassed ? 'ok' : 'error',
    'checks' => $checks,
    'errors' => $errors,
    'warnings' => $warnings,
    'message' => $allPassed 
        ? '✅ Stripe está correctamente configurado'
        : '❌ Hay problemas con la configuración de Stripe'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

