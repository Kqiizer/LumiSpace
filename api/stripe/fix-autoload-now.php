<?php
/**
 * Script para intentar solucionar el problema del autoload de Stripe
 * Accede desde: /api/stripe/fix-autoload-now.php
 */

header("Content-Type: application/json; charset=UTF-8");

$result = [
    'success' => false,
    'message' => '',
    'actions' => []
];

try {
    $baseDir = __DIR__ . '/../../';
    $composerJson = $baseDir . 'composer.json';
    $vendorDir = $baseDir . 'vendor';
    $autoloadPath = $vendorDir . '/autoload.php';
    
    // Verificar que composer.json existe
    if (!file_exists($composerJson)) {
        throw new RuntimeException('composer.json no encontrado');
    }
    $result['actions'][] = '✓ composer.json encontrado';
    
    // Verificar que vendor existe
    if (!is_dir($vendorDir)) {
        throw new RuntimeException('Carpeta vendor/ no encontrada. Ejecuta: composer install');
    }
    $result['actions'][] = '✓ Carpeta vendor/ encontrada';
    
    // Verificar que Stripe está instalado
    $stripeDir = $vendorDir . '/stripe/stripe-php';
    if (!is_dir($stripeDir)) {
        throw new RuntimeException('Stripe no está instalado. Ejecuta: composer require stripe/stripe-php');
    }
    $result['actions'][] = '✓ Stripe está instalado';
    
    // Verificar StripeClient.php
    $stripeClientPath = $stripeDir . '/lib/StripeClient.php';
    if (!file_exists($stripeClientPath)) {
        throw new RuntimeException('StripeClient.php no encontrado');
    }
    $result['actions'][] = '✓ StripeClient.php encontrado';
    
    // Intentar cargar el autoload
    if (!file_exists($autoloadPath)) {
        throw new RuntimeException('autoload.php no encontrado. Ejecuta: composer install');
    }
    
    require_once $autoloadPath;
    $result['actions'][] = '✓ autoload.php cargado';
    
    // Registrar autoloader personalizado
    $stripeLibPath = $stripeDir . '/lib';
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
    
    $result['actions'][] = '✓ Autoloader personalizado registrado';
    
    // Intentar cargar StripeClient
    if (!class_exists('\Stripe\StripeClient', false)) {
        // Cargar dependencias manualmente
        $deps = [
            'Util/Set.php',
            'Util/Util.php',
            'ErrorObject.php',
            'StripeObject.php',
            'ApiResource.php',
            'Collection.php',
            'ApiOperations/Request.php',
            'ApiOperations/Retrieve.php',
            'ApiOperations/Create.php',
            'ApiOperations/Update.php',
            'ApiOperations/Delete.php',
            'ApiOperations/All.php',
        ];
        
        foreach ($deps as $dep) {
            $depPath = $stripeLibPath . '/' . $dep;
            if (file_exists($depPath)) {
                @require_once $depPath;
            }
        }
        
        @require_once $stripeClientPath;
        $result['actions'][] = '✓ Dependencias cargadas manualmente';
    }
    
    // Verificar si ahora funciona
    if (class_exists('\Stripe\StripeClient')) {
        $result['success'] = true;
        $result['message'] = '✅ Stripe se cargó correctamente usando el autoloader personalizado';
        $result['actions'][] = '✓ StripeClient está disponible';
        
        // Intentar crear una instancia de prueba
        try {
            require_once __DIR__ . '/../../config/stripe.php';
            $config = stripeConfig();
            if (!empty($config['secret_key'])) {
                $client = stripeClient();
                $result['actions'][] = '✓ Cliente de Stripe creado exitosamente';
            } else {
                $result['actions'][] = '⚠️ Stripe funciona pero falta STRIPE_SECRET_KEY en .env';
            }
        } catch (\Throwable $e) {
            $result['actions'][] = '⚠️ Stripe se cargó pero hay un error: ' . $e->getMessage();
        }
    } else {
        $result['message'] = '❌ Stripe no se pudo cargar incluso con el autoloader personalizado';
        $result['actions'][] = '✗ StripeClient no está disponible';
        $result['solution'] = [
            'Ejecuta en el servidor (SSH):',
            '  cd public_html',
            '  composer dump-autoload',
            '',
            'Si no tienes SSH, contacta al soporte de Hostinger'
        ];
    }
    
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['error'] = [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];
    $result['solution'] = [
        'SOLUCIÓN OBLIGATORIA:',
        'Ejecuta en el servidor (SSH):',
        '  cd public_html',
        '  composer dump-autoload',
        '',
        'Si no tienes SSH, contacta al soporte de Hostinger'
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

