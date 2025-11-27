<?php
/**
 * Script de debug para ver los últimos errores del payment intent
 * Accede desde: /api/stripe/debug-payment.php
 */

header("Content-Type: application/json; charset=UTF-8");

$result = [
    'success' => false,
    'message' => '',
    'errors' => []
];

try {
    // Intentar cargar la configuración
    require_once __DIR__ . '/../../config/functions.php';
    require_once __DIR__ . '/../../config/stripe.php';
    
    $result['success'] = true;
    $result['message'] = 'Configuración cargada correctamente';
    
    // Verificar configuración
    $config = stripeConfig();
    $result['config'] = [
        'has_secret_key' => !empty($config['secret_key']),
        'has_publishable_key' => !empty($config['publishable_key']),
        'currency' => $config['currency'] ?? 'mxn'
    ];
    
    // Verificar carrito
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $carrito = carritoObtener();
    $result['carrito'] = [
        'count' => count($carrito),
        'items' => $carrito
    ];
    
    // Intentar crear cliente de Stripe
    try {
        $stripe = stripeClient();
        $result['stripe_client'] = 'OK';
    } catch (\Throwable $e) {
        $result['stripe_client'] = 'ERROR: ' . $e->getMessage();
        $result['errors'][] = 'Error al crear cliente Stripe: ' . $e->getMessage();
    }
    
    // Si hay carrito, intentar crear un Payment Intent de prueba
    if (!empty($carrito) && isset($result['stripe_client']) && $result['stripe_client'] === 'OK') {
        $subtotal = 0;
        foreach ($carrito as $item) {
            $precio = (float)($item['precio'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 1);
            if ($precio > 0 && $cantidad > 0) {
                $subtotal += $precio * $cantidad;
            }
        }
        $total = $subtotal;
        $amountInCents = (int)round($total * 100);
        
        if ($amountInCents >= 50) {
            try {
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => $amountInCents,
                    'currency' => strtolower($config['currency'] ?? 'mxn'),
                    'payment_method_types' => ['card'],
                    'description' => 'Test Payment Intent',
                ]);
                
                $result['payment_intent_test'] = [
                    'success' => true,
                    'id' => $paymentIntent->id,
                    'amount' => $amountInCents,
                    'currency' => $paymentIntent->currency
                ];
                
                // Cancelar el Payment Intent de prueba
                try {
                    $stripe->paymentIntents->cancel($paymentIntent->id);
                    $result['payment_intent_test']['cancelled'] = true;
                } catch (\Exception $e) {
                    $result['payment_intent_test']['cancelled'] = false;
                    $result['payment_intent_test']['cancel_error'] = $e->getMessage();
                }
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $result['payment_intent_test'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'stripe_code' => $e->getStripeCode(),
                    'type' => get_class($e)
                ];
                $result['errors'][] = 'Error al crear Payment Intent: ' . $e->getMessage();
            } catch (\Throwable $e) {
                $result['payment_intent_test'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'type' => get_class($e)
                ];
                $result['errors'][] = 'Error inesperado: ' . $e->getMessage();
            }
        } else {
            $result['payment_intent_test'] = [
                'skipped' => true,
                'reason' => 'Monto muy bajo: $' . number_format($total, 2)
            ];
        }
    } else {
        $result['payment_intent_test'] = [
            'skipped' => true,
            'reason' => empty($carrito) ? 'Carrito vacío' : 'Cliente Stripe no disponible'
        ];
    }
    
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['errors'][] = [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'type' => get_class($e)
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

