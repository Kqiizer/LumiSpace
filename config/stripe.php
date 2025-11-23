<?php
declare(strict_types=1);

use Stripe\StripeClient;

require_once __DIR__ . '/../vendor/autoload.php';

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

