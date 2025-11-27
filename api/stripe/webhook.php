<?php
declare(strict_types=1);

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/stripe.php";

// Stripe webhook endpoint
// Configura este URL en tu dashboard de Stripe: https://dashboard.stripe.com/webhooks

header('Content-Type: application/json');

// Obtener el payload
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (empty($payload) || empty($sig_header)) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta payload o firma']);
    exit;
}

$config = stripeConfig();
$webhook_secret = $config['webhook_secret'] ?? '';

if (empty($webhook_secret)) {
    error_log("⚠️ STRIPE_WEBHOOK_SECRET no configurado");
    http_response_code(500);
    echo json_encode(['error' => 'Webhook secret no configurado']);
    exit;
}

$event = null;

try {
    $stripe = stripeClient();
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $webhook_secret
    );
} catch (\UnexpectedValueException $e) {
    // Payload inválido
    error_log("⚠️ Webhook payload inválido: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Payload inválido']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Firma inválida
    error_log("⚠️ Webhook firma inválida: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'Firma inválida']);
    exit;
}

// Procesar el evento
try {
    $eventType = $event->type;
    $eventData = $event->data->object;

    error_log("📥 Webhook recibido: " . $eventType);

    switch ($eventType) {
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($eventData);
            break;

        case 'payment_intent.payment_failed':
            handlePaymentIntentFailed($eventData);
            break;

        case 'payment_intent.canceled':
            handlePaymentIntentCanceled($eventData);
            break;

        case 'charge.succeeded':
            // El cargo fue exitoso
            error_log("✅ Cargo exitoso: " . $eventData->id);
            break;

        case 'charge.failed':
            // El cargo falló
            error_log("❌ Cargo fallido: " . $eventData->id);
            break;

        default:
            error_log("ℹ️ Evento no manejado: " . $eventType);
    }

    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (\Exception $e) {
    error_log("❌ Error procesando webhook: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error procesando webhook']);
}

/**
 * Manejar Payment Intent exitoso
 */
function handlePaymentIntentSucceeded($paymentIntent) {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        $paymentIntentId = $paymentIntent->id;
        $metadata = $paymentIntent->metadata ?? [];

        // Verificar si ya se procesó esta venta
        $stmt = $conn->prepare("SELECT id FROM ventas WHERE stripe_payment_intent_id = ?");
        $stmt->bind_param("s", $paymentIntentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            error_log("⚠️ Payment Intent ya procesado: " . $paymentIntentId);
            $conn->rollback();
            return;
        }
        $stmt->close();

        // Obtener datos del metadata
        $usuario_id = !empty($metadata['usuario_id']) ? (int)$metadata['usuario_id'] : null;
        $nombre = $metadata['nombre'] ?? 'Cliente';
        $correo = $metadata['correo'] ?? '';
        $direccion = $metadata['direccion'] ?? '';
        $total = (float)($metadata['total'] ?? 0);
        $itemsStr = $metadata['items'] ?? '';

        // Parsear items
        $items = [];
        if (!empty($itemsStr)) {
            $itemsArray = explode(',', $itemsStr);
            foreach ($itemsArray as $itemStr) {
                $parts = explode(':', $itemStr);
                if (count($parts) === 2) {
                    $producto_id = (int)$parts[0];
                    $cantidad = (int)$parts[1];
                    
                    // Obtener datos del producto
                    $producto = getProductoById($producto_id);
                    if ($producto) {
                        $items[] = [
                            'producto_id' => $producto_id,
                            'id' => $producto_id,
                            'nombre' => $producto['nombre'],
                            'precio' => (float)$producto['precio'],
                            'cantidad' => $cantidad,
                            'imagen' => $producto['imagen'] ?? 'images/default.png'
                        ];
                    }
                }
            }
        }

        if (empty($items)) {
            throw new Exception("No se pudieron parsear los items del pedido");
        }

        // Registrar la venta
        $venta_id = registrarVenta(
            null, // cliente_id
            $usuario_id,
            $items,
            'Stripe - Tarjeta',
            $total
        );

        if (!$venta_id) {
            throw new Exception("Error al registrar la venta");
        }

        // Guardar información de Stripe en la venta
        $stmt = $conn->prepare("
            UPDATE ventas 
            SET stripe_payment_intent_id = ?, 
                stripe_charge_id = ?,
                estado_pago = 'completado'
            WHERE id = ?
        ");
        $chargeId = $paymentIntent->charges->data[0]->id ?? null;
        $stmt->bind_param("ssi", $paymentIntentId, $chargeId, $venta_id);
        $stmt->execute();
        $stmt->close();

        // Guardar información de envío si existe
        if (!empty($direccion)) {
            $stmt = $conn->prepare("
                INSERT INTO direcciones_envio (venta_id, direccion, nombre_cliente, correo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $venta_id, $direccion, $nombre, $correo);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        error_log("✅ Venta registrada exitosamente: ID $venta_id para Payment Intent $paymentIntentId");

        // Enviar email de confirmación (opcional)
        if (!empty($correo) && function_exists('enviarCorreo')) {
            $subject = "Confirmación de compra - LumiSpace";
            $body = "
                <h2>¡Gracias por tu compra, $nombre!</h2>
                <p>Tu pedido #$venta_id ha sido confirmado.</p>
                <p><strong>Total:</strong> $" . number_format($total, 2) . " MXN</p>
                <p><strong>Método de pago:</strong> Tarjeta (Stripe)</p>
                <p>Recibirás un correo con los detalles de envío próximamente.</p>
            ";
            enviarCorreo($correo, $subject, $body);
        }

    } catch (\Exception $e) {
        $conn->rollback();
        error_log("❌ Error en handlePaymentIntentSucceeded: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Manejar Payment Intent fallido
 */
function handlePaymentIntentFailed($paymentIntent) {
    $paymentIntentId = $paymentIntent->id;
    $error = $paymentIntent->last_payment_error ?? null;
    
    error_log("❌ Payment Intent fallido: $paymentIntentId");
    if ($error) {
        error_log("   Error: " . ($error->message ?? 'Desconocido'));
    }

    // Aquí podrías actualizar el estado en la BD si guardas intentos de pago
}

/**
 * Manejar Payment Intent cancelado
 */
function handlePaymentIntentCanceled($paymentIntent) {
    $paymentIntentId = $paymentIntent->id;
    error_log("⚠️ Payment Intent cancelado: $paymentIntentId");
}
