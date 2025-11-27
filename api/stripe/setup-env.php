<?php
/**
 * Script para crear el archivo .env automáticamente
 * Accede desde: /api/stripe/setup-env.php
 */

header("Content-Type: application/json; charset=UTF-8");

$envPath = __DIR__ . '/../../.env';
$envExamplePath = __DIR__ . '/../../.env.example';

$result = [
    'success' => false,
    'message' => '',
    'file_created' => false,
    'file_path' => $envPath
];

// Si ya existe .env, no sobrescribir
if (file_exists($envPath)) {
    $result['message'] = 'El archivo .env ya existe. No se sobrescribirá por seguridad.';
    $result['file_created'] = true;
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Contenido del archivo .env
$envContent = <<<'ENV'
# ========================================
# CONFIGURACIÓN DE LUMISPACE
# ========================================

# Base URL (ajusta según tu entorno)
BASE_URL=/LumiSpace/

# ========================================
# STRIPE - CONFIGURACIÓN DE PAGOS
# ========================================
# Obtén tus claves desde: https://dashboard.stripe.com/apikeys
# IMPORTANTE: Usa claves de TEST para desarrollo y LIVE para producción

# Clave secreta de Stripe (sk_test_... para desarrollo, sk_live_... para producción)
STRIPE_SECRET_KEY=sk_test_tu_clave_secreta_aqui

# Clave pública de Stripe (pk_test_... para desarrollo, pk_live_... para producción)
STRIPE_PUBLISHABLE_KEY=pk_test_tu_clave_publica_aqui

# Webhook Secret (obtén desde: https://dashboard.stripe.com/webhooks)
# Solo necesario si vas a usar webhooks
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui

# Moneda (mxn, usd, eur, etc.)
STRIPE_CURRENCY=mxn

# URL de la aplicación (opcional, se detecta automáticamente)
# STRIPE_APP_URL=https://tudominio.com

ENV;

try {
    // Intentar crear el archivo
    $written = @file_put_contents($envPath, $envContent);
    
    if ($written === false) {
        throw new RuntimeException('No se pudo crear el archivo .env. Verifica los permisos de escritura.');
    }
    
    // Establecer permisos (solo lectura para otros)
    @chmod($envPath, 0600);
    
    $result['success'] = true;
    $result['file_created'] = true;
    $result['message'] = '✅ Archivo .env creado exitosamente. Ahora edita el archivo y agrega tus claves de Stripe.';
    $result['instructions'] = [
        '1. Abre el archivo .env en la raíz del proyecto',
        '2. Reemplaza STRIPE_SECRET_KEY con tu clave secreta de Stripe',
        '3. Reemplaza STRIPE_PUBLISHABLE_KEY con tu clave pública de Stripe',
        '4. Obtén tus claves desde: https://dashboard.stripe.com/apikeys'
    ];
    
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['message'] = 'Error al crear .env: ' . $e->getMessage();
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

