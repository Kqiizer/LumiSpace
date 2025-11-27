<?php
declare(strict_types=1);

use Stripe\StripeClient;

// Verificar que Composer esté instalado
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    throw new RuntimeException(
        'Composer no está instalado. Ejecuta: composer require stripe/stripe-php'
    );
}

// Cargar autoload
require_once $autoloadPath;

// Verificar que Stripe esté disponible después de cargar autoload
if (!class_exists('\Stripe\StripeClient', false)) {
    // Registrar autoloader personalizado para Stripe como fallback
    spl_autoload_register(function ($class) {
        if (strpos($class, 'Stripe\\') === 0) {
            $stripeLibPath = __DIR__ . '/../vendor/stripe/stripe-php/lib';
            $classPath = str_replace('\\', '/', substr($class, 7)); // Remover 'Stripe\'
            $filePath = $stripeLibPath . '/' . $classPath . '.php';
            if (file_exists($filePath)) {
                require_once $filePath;
                return true;
            }
        }
        return false;
    }, true, true); // Prepend = true para que se ejecute primero
    
    // Verificar nuevamente después de registrar el autoloader
    if (!class_exists('\Stripe\StripeClient', false)) {
        $stripeLibPath = __DIR__ . '/../vendor/stripe/stripe-php/lib';
        $stripeClientPath = $stripeLibPath . '/StripeClient.php';
        $stripeDir = __DIR__ . '/../vendor/stripe/stripe-php';
        
        // Verificar si la carpeta existe y tiene contenido
        $stripeDirExists = is_dir($stripeDir);
        $stripeDirEmpty = $stripeDirExists && count(array_diff(scandir($stripeDir), ['.', '..'])) === 0;
        
        if (!$stripeDirExists || $stripeDirEmpty) {
            throw new RuntimeException(
                'Stripe PHP SDK no está instalado físicamente en el servidor. ' . PHP_EOL .
                'SOLUCIÓN PARA HOSTINGER:' . PHP_EOL .
                '1. Accede por SSH a tu servidor' . PHP_EOL .
                '2. Navega a: cd public_html/LumiSpace (o tu ruta)' . PHP_EOL .
                '3. Ejecuta: composer install' . PHP_EOL .
                '4. Si no tienes SSH, contacta al soporte de Hostinger' . PHP_EOL .
                '   y pídeles que ejecuten: composer install en tu proyecto'
            );
        } elseif (!file_exists($stripeClientPath)) {
            throw new RuntimeException(
                'StripeClient.php no encontrado. El autoload no está funcionando correctamente. ' . PHP_EOL .
                'SOLUCIÓN: Ejecuta en el servidor: composer dump-autoload'
            );
        } else {
            throw new RuntimeException(
                'Stripe PHP SDK está instalado pero no se puede cargar. ' . PHP_EOL .
                'Posibles causas:' . PHP_EOL .
                '1. El autoload no se regeneró después de instalar' . PHP_EOL .
                '2. Permisos de archivos incorrectos' . PHP_EOL .
                'SOLUCIÓN: Ejecuta: composer dump-autoload'
            );
        }
    }
}

// ============================================================
//  Cargar variables de entorno (.env)
// ============================================================
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

if (!function_exists('envOr')) {
    function envOr($keys, $default = null) {
        foreach ((array)$keys as $k) {
            $val = getenv($k);
            if ($val !== false && $val !== '') {
                return $val;
            }
        }
        return $default;
    }
}

if (!function_exists('stripeBaseUrl')) {
    function stripeBaseUrl(): string {
        $envUrl = envOr(['STRIPE_APP_URL', 'APP_URL', 'SITE_URL']);
        if ($envUrl) {
            return rtrim($envUrl, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        if (defined('BASE_URL')) {
            $basePath = rtrim(BASE_URL, '/');
        } else {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '') ?: '';
            $basePath  = rtrim($scriptDir, '/\\');
        }

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}

if (!function_exists('stripeConfig')) {
    function stripeConfig(): array {
        $baseUrl = stripeBaseUrl();

        return [
            'secret_key'      => envOr(['STRIPE_SECRET_KEY', 'STRIPE_SK']),
            'publishable_key' => envOr(['STRIPE_PUBLISHABLE_KEY', 'STRIPE_PK']),
            'webhook_secret'  => envOr(['STRIPE_WEBHOOK_SECRET', 'STRIPE_WH']),
            'currency'        => strtolower(envOr(['STRIPE_CURRENCY'], 'mxn')),
            'success_url'     => envOr(
                ['STRIPE_SUCCESS_URL'],
                $baseUrl . '/views/checkout-success.php?session_id={CHECKOUT_SESSION_ID}'
            ),
            'cancel_url'      => envOr(
                ['STRIPE_CANCEL_URL'],
                $baseUrl . '/views/checkout-cancel.php'
            ),
        ];
    }
}

if (!function_exists('stripeClient')) {
    function stripeClient(): StripeClient {
        static $client = null;
        if ($client === null) {
            $config = stripeConfig();
            if (empty($config['secret_key'])) {
                throw new RuntimeException('Falta STRIPE_SECRET_KEY en el entorno.');
            }
            $client = new StripeClient($config['secret_key']);
        }

        return $client;
    }
}

