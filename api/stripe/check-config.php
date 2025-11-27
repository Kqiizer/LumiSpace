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
    
    try {
        // Limpiar cualquier output previo
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        require_once $autoloadPath;
        
        // 2. Verificar Stripe PHP - verificar físicamente primero
        $stripeDir = __DIR__ . '/../../vendor/stripe/stripe-php';
        $stripeLibPath = $stripeDir . '/lib';
        $stripeClientPath = $stripeLibPath . '/StripeClient.php';
        
        // Verificar si la carpeta existe y tiene contenido
        $stripeDirExists = is_dir($stripeDir);
        $stripeDirHasContent = false;
        
        if ($stripeDirExists) {
            $dirContents = array_diff(scandir($stripeDir), ['.', '..']);
            $stripeDirHasContent = count($dirContents) > 0;
        }
        
        if (!$stripeDirExists || !$stripeDirHasContent) {
            $errors[] = 'Stripe PHP SDK NO está instalado físicamente en el servidor.';
            $errors[] = 'La carpeta vendor/stripe/stripe-php está vacía o no existe.';
            $errors[] = 'SOLUCIÓN: Ejecuta en Hostinger (SSH): composer install';
            $errors[] = 'Si no tienes SSH, contacta al soporte de Hostinger.';
        } elseif (!is_dir($stripeLibPath)) {
            $errors[] = 'La carpeta lib/ no existe dentro de vendor/stripe/stripe-php';
            $errors[] = 'SOLUCIÓN: Ejecuta: composer install --no-dev';
        } elseif (!file_exists($stripeClientPath)) {
            $errors[] = 'StripeClient.php no encontrado en: ' . $stripeClientPath;
            $errors[] = 'SOLUCIÓN: Ejecuta: composer dump-autoload';
        } else {
            // El archivo existe físicamente, intentar cargar
            if (!class_exists('\Stripe\StripeClient', false)) {
                try {
                    // Registrar autoloader personalizado
                    spl_autoload_register(function ($class) use ($stripeLibPath) {
                        if (strpos($class, 'Stripe\\') === 0) {
                            $classPath = str_replace('\\', '/', substr($class, 7));
                            $filePath = $stripeLibPath . '/' . $classPath . '.php';
                            if (file_exists($filePath)) {
                                require_once $filePath;
                                return true;
                            }
                        }
                        return false;
                    }, true, true);
                    
                    // Intentar cargar StripeClient
                    require_once $stripeClientPath;
                    
                    if (class_exists('\Stripe\StripeClient')) {
                        $checks['stripe_php_installed'] = true;
                    } else {
                        $errors[] = 'StripeClient.php existe pero la clase no se puede cargar.';
                        $errors[] = 'Posible problema con dependencias. Ejecuta: composer dump-autoload';
                    }
                } catch (\Throwable $e) {
                    $errors[] = 'Error al cargar StripeClient: ' . $e->getMessage();
                    $errors[] = 'Archivo: ' . basename($e->getFile()) . ':' . $e->getLine();
                }
            } else {
                $checks['stripe_php_installed'] = true;
            }
        }
    } catch (\Throwable $e) {
        $errors[] = 'Error al cargar autoload.php: ' . $e->getMessage();
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
        
        if (!empty($config['secret_key']) && $config['secret_key'] !== 'sk_test_tu_clave_secreta_aqui') {
            $checks['stripe_secret_key'] = true;
        } else {
            $errors[] = 'STRIPE_SECRET_KEY no está configurado en .env o aún tiene el valor por defecto';
            $warnings[] = 'Edita el archivo .env y reemplaza STRIPE_SECRET_KEY con tu clave real de Stripe';
        }
        
        if (!empty($config['publishable_key']) && $config['publishable_key'] !== 'pk_test_tu_clave_publica_aqui') {
            $checks['stripe_publishable_key'] = true;
        } else {
            $errors[] = 'STRIPE_PUBLISHABLE_KEY no está configurado en .env o aún tiene el valor por defecto';
            $warnings[] = 'Edita el archivo .env y reemplaza STRIPE_PUBLISHABLE_KEY con tu clave real de Stripe';
        }
        
        // 6. Intentar crear cliente de Stripe (solo si las claves están configuradas)
        if ($checks['stripe_secret_key'] && $checks['stripe_publishable_key']) {
            try {
                $client = stripeClient();
                $checks['stripe_client_created'] = true;
            } catch (\Throwable $e) {
                $errors[] = 'Error al crear cliente de Stripe: ' . $e->getMessage();
                $errors[] = 'Verifica que tu STRIPE_SECRET_KEY sea válida';
            }
        }
        
    } catch (\RuntimeException $e) {
        if (strpos($e->getMessage(), 'Composer no está instalado') !== false) {
            $errors[] = $e->getMessage();
        } else {
            $errors[] = 'Error cargando stripe.php: ' . $e->getMessage();
        }
    } catch (\Throwable $e) {
        $errors[] = 'Error crítico cargando stripe.php: ' . $e->getMessage();
    }

$allPassed = !empty($errors) ? false : (count(array_filter($checks)) === count($checks));

// Agregar instrucciones de solución
$solution = [];
if (!$checks['env_file_exists']) {
    $solution[] = '1. Crea el archivo .env visitando: /api/stripe/setup-env.php';
}
if (!$checks['stripe_secret_key'] || !$checks['stripe_publishable_key']) {
    $solution[] = '2. Edita el archivo .env y agrega tus claves de Stripe desde: https://dashboard.stripe.com/apikeys';
}
if (!$checks['stripe_php_installed']) {
    $solution[] = '3. Ejecuta: composer dump-autoload (o reinstala: composer require stripe/stripe-php)';
}

echo json_encode([
    'status' => $allPassed ? 'ok' : 'error',
    'checks' => $checks,
    'errors' => $errors,
    'warnings' => $warnings,
    'solution' => $solution,
    'message' => $allPassed 
        ? '✅ Stripe está correctamente configurado'
        : '❌ Hay problemas con la configuración de Stripe',
    'next_steps' => !empty($solution) ? implode("\n", $solution) : null
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

