<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/stripe.php';

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

http_response_code(200);
header('Content-Type: application/json');

try {
    $config = stripeConfig();
    $webhookSecret = $config['webhook_secret'] ?? '';

    if ($webhookSecret === '') {
        throw new RuntimeException('No hay STRIPE_WEBHOOK_SECRET configurado.');
    }

    $event = \Stripe\Webhook::constructEvent(
        $payload ?: '',
        $signature,
        $webhookSecret
    );

    if ($event->type === 'checkout.session.completed') {
        $session = $event->data->object;
        error_log("Stripe checkout completado: {$session->id}");
        // Aquí podrías reconciliar la venta consultando tu base de datos.
    }

    echo json_encode(['received' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

