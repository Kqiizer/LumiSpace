<?php
/**
 * Script de prueba para diagnosticar errores en el proceso de pago
 * Accede desde: /api/stripe/test-payment.php
 */

header("Content-Type: application/json; charset=UTF-8");

$result = [
    'success' => false,
    'message' => '',
    'tests' => []
];

try {
    // Test 1: Sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $result['tests']['session'] = 'OK';
    
    // Test 2: Cargar funciones
    require_once __DIR__ . '/../../config/functions.php';
    $result['tests']['functions_loaded'] = 'OK';
    
    // Test 3: Cargar Stripe
    require_once __DIR__ . '/../../config/stripe.php';
    $result['tests']['stripe_loaded'] = 'OK';
    
    // Test 4: Configuración de Stripe
    $config = stripeConfig();
    if (empty($config['secret_key'])) {
        throw new RuntimeException('STRIPE_SECRET_KEY no configurado');
    }
    if (empty($config['publishable_key'])) {
        throw new RuntimeException('STRIPE_PUBLISHABLE_KEY no configurado');
    }
    $result['tests']['stripe_config'] = 'OK';
    
    // Test 5: Cliente de Stripe
    $stripe = stripeClient();
    $result['tests']['stripe_client'] = 'OK';
    
    // Test 6: Carrito
    $carrito = carritoObtener();
    if (empty($carrito)) {
        $result['tests']['carrito'] = 'VACÍO - Agrega productos al carrito primero';
    } else {
        $result['tests']['carrito'] = 'OK - ' . count($carrito) . ' productos';
        
        // Test 7: Estructura del carrito
        $firstItem = $carrito[0];
        $hasProductoId = isset($firstItem['producto_id']);
        $hasId = isset($firstItem['id']);
        $hasPrecio = isset($firstItem['precio']);
        $hasCantidad = isset($firstItem['cantidad']);
        
        $result['tests']['carrito_structure'] = [
            'producto_id' => $hasProductoId ? 'OK' : 'FALTA',
            'id' => $hasId ? 'OK' : 'FALTA',
            'precio' => $hasPrecio ? 'OK' : 'FALTA',
            'cantidad' => $hasCantidad ? 'OK' : 'FALTA',
            'sample_item' => $firstItem
        ];
    }
    
    // Test 8: Base de datos
    try {
        $conn = getDBConnection();
        if ($conn) {
            $result['tests']['database'] = 'OK';
        } else {
            $result['tests']['database'] = 'ERROR - No se pudo conectar';
        }
    } catch (\Throwable $e) {
        $result['tests']['database'] = 'ERROR - ' . $e->getMessage();
    }
    
    // Test 9: Intentar crear un Payment Intent de prueba (solo si hay carrito)
    if (!empty($carrito)) {
        try {
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
                // Crear Payment Intent de prueba
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => $amountInCents,
                    'currency' => strtolower($config['currency'] ?? 'mxn'),
                    'payment_method_types' => ['card'],
                    'description' => 'Test Payment Intent',
                ]);
                
                $result['tests']['payment_intent_creation'] = 'OK - ID: ' . $paymentIntent->id;
                
                // Cancelar el Payment Intent de prueba
                try {
                    $stripe->paymentIntents->cancel($paymentIntent->id);
                    $result['tests']['payment_intent_cancelled'] = 'OK';
                } catch (\Exception $e) {
                    $result['tests']['payment_intent_cancelled'] = 'No se pudo cancelar (no crítico)';
                }
            } else {
                $result['tests']['payment_intent_creation'] = 'SKIP - Monto muy bajo ($' . number_format($total, 2) . ')';
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $result['tests']['payment_intent_creation'] = 'ERROR - ' . $e->getMessage();
        } catch (\Throwable $e) {
            $result['tests']['payment_intent_creation'] = 'ERROR - ' . $e->getMessage();
        }
    } else {
        $result['tests']['payment_intent_creation'] = 'SKIP - Carrito vacío';
    }
    
    $result['success'] = true;
    $result['message'] = 'Todos los tests pasaron correctamente';
    
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
    $result['error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

