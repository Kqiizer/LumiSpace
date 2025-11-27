<?php
/**
 * Script para verificar e instalar Stripe en Hostinger
 * Accede desde: /api/stripe/install-stripe.php
 * 
 * NOTA: Este script solo verifica. La instalación real debe hacerse por SSH.
 */

header("Content-Type: application/json; charset=UTF-8");

$result = [
    'success' => false,
    'message' => '',
    'stripe_installed' => false,
    'stripe_path' => '',
    'instructions' => []
];

$stripeDir = __DIR__ . '/../../vendor/stripe/stripe-php';
$stripeLibPath = $stripeDir . '/lib';
$stripeClientPath = $stripeLibPath . '/StripeClient.php';

$result['stripe_path'] = $stripeDir;

// Verificar si existe físicamente
if (is_dir($stripeDir)) {
    $dirContents = array_diff(scandir($stripeDir), ['.', '..']);
    $hasContent = count($dirContents) > 0;
    
    if ($hasContent) {
        if (file_exists($stripeClientPath)) {
            $result['stripe_installed'] = true;
            $result['success'] = true;
            $result['message'] = '✅ Stripe está instalado físicamente';
        } else {
            $result['message'] = '⚠️ La carpeta Stripe existe pero StripeClient.php no se encuentra';
            $result['instructions'] = [
                'Ejecuta en SSH: composer dump-autoload',
                'O reinstala: composer require stripe/stripe-php'
            ];
        }
    } else {
        $result['message'] = '❌ La carpeta vendor/stripe/stripe-php existe pero está VACÍA';
        $result['instructions'] = [
            'SOLUCIÓN OBLIGATORIA:',
            '1. Accede por SSH a tu servidor Hostinger',
            '2. Navega a tu proyecto: cd public_html/LumiSpace',
            '3. Ejecuta: composer install',
            '',
            'Si no tienes SSH:',
            '- Contacta al soporte de Hostinger',
            '- Pídeles que ejecuten: composer install',
            '- O que ejecuten: composer require stripe/stripe-php'
        ];
    }
} else {
    $result['message'] = '❌ Stripe NO está instalado. La carpeta no existe.';
    $result['instructions'] = [
        'SOLUCIÓN OBLIGATORIA:',
        '1. Accede por SSH a tu servidor Hostinger',
        '2. Navega a tu proyecto: cd public_html/LumiSpace (o tu ruta)',
        '3. Ejecuta: composer install',
        '',
        'Si no tienes SSH:',
        '- Contacta al soporte de Hostinger',
        '- Pídeles que ejecuten: composer install',
        '- O que ejecuten: composer require stripe/stripe-php',
        '',
        'ALTERNATIVA (sin SSH):',
        '- Descarga Stripe desde: https://github.com/stripe/stripe-php/releases',
        '- Extrae en: vendor/stripe/stripe-php/',
        '- O sube la carpeta vendor/stripe/ completa desde tu entorno local'
    ];
}

// Intentar cargar si existe
if ($result['stripe_installed'] && file_exists($stripeClientPath)) {
    try {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
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
        
        require_once $stripeClientPath;
        
        if (class_exists('\Stripe\StripeClient')) {
            $result['class_loaded'] = true;
            $result['message'] .= ' y la clase se puede cargar correctamente';
        } else {
            $result['class_loaded'] = false;
            $result['message'] .= ' pero la clase NO se puede cargar';
            $result['instructions'][] = 'Ejecuta: composer dump-autoload';
        }
    } catch (\Throwable $e) {
        $result['class_loaded'] = false;
        $result['error'] = $e->getMessage();
        $result['message'] .= ' pero hay un error al cargar: ' . $e->getMessage();
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

