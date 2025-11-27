<?php
/**
 * Script para regenerar el autoload de Composer
 * Útil cuando Stripe no se carga correctamente
 */

header("Content-Type: application/json; charset=UTF-8");

$result = [
    'success' => false,
    'message' => '',
    'actions_taken' => []
];

try {
    // 1. Verificar que composer.json existe
    $composerJson = __DIR__ . '/../../composer.json';
    if (!file_exists($composerJson)) {
        throw new RuntimeException('composer.json no encontrado');
    }
    $result['actions_taken'][] = '✓ composer.json encontrado';

    // 2. Verificar que vendor/autoload.php existe
    $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
    if (!file_exists($autoloadPath)) {
        throw new RuntimeException('vendor/autoload.php no encontrado. Ejecuta: composer install');
    }
    $result['actions_taken'][] = '✓ vendor/autoload.php encontrado';

    // 3. Cargar autoload
    require_once $autoloadPath;
    $result['actions_taken'][] = '✓ Autoload cargado';

    // 4. Verificar Stripe en composer.json
    $composerData = json_decode(file_get_contents($composerJson), true);
    $hasStripe = isset($composerData['require']['stripe/stripe-php']);
    
    if (!$hasStripe) {
        $result['message'] = 'Stripe no está en composer.json. Agrega: "stripe/stripe-php": "^13.0"';
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    $result['actions_taken'][] = '✓ Stripe encontrado en composer.json';

    // 5. Verificar que la librería existe físicamente
    $stripeLibPath = __DIR__ . '/../../vendor/stripe/stripe-php/lib';
    if (!is_dir($stripeLibPath)) {
        throw new RuntimeException('La carpeta vendor/stripe/stripe-php/lib no existe. Ejecuta: composer install');
    }
    $result['actions_taken'][] = '✓ Carpeta de Stripe encontrada';

    // 6. Verificar StripeClient.php
    $stripeClientPath = $stripeLibPath . '/StripeClient.php';
    if (!file_exists($stripeClientPath)) {
        throw new RuntimeException('StripeClient.php no encontrado. Ejecuta: composer install');
    }
    $result['actions_taken'][] = '✓ StripeClient.php encontrado';

    // 7. Intentar cargar manualmente
    require_once $stripeClientPath;
    $result['actions_taken'][] = '✓ StripeClient.php cargado manualmente';

    // 8. Verificar que la clase existe
    if (class_exists('\Stripe\StripeClient')) {
        $result['success'] = true;
        $result['message'] = '✅ Stripe se cargó correctamente. El problema puede ser que necesitas ejecutar: composer dump-autoload en el servidor';
        $result['actions_taken'][] = '✓ Clase StripeClient disponible';
    } else {
        throw new RuntimeException('La clase StripeClient no se pudo cargar');
    }

    // 9. Recomendaciones
    $result['recommendations'] = [
        'Si estás en Hostinger, ejecuta en SSH:',
        '  cd public_html/LumiSpace (o la ruta de tu proyecto)',
        '  composer dump-autoload',
        '',
        'Si no tienes acceso SSH, contacta al soporte de Hostinger para que ejecuten:',
        '  composer dump-autoload',
        '',
        'Alternativamente, puedes regenerar el autoload localmente y subir la carpeta vendor/'
    ];

} catch (\Throwable $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['error'] = $e->getMessage();
    $result['file'] = $e->getFile();
    $result['line'] = $e->getLine();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

