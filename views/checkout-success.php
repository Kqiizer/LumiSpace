<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/stripe.php';

$sessionId = $_GET['session_id'] ?? null;
$ventaId = null;
$invoiceUrl = null;
$monto = 0;
$currency = 'mxn';
$estadoPago = 'pendiente';
$mensaje = '';

if (!$sessionId) {
    http_response_code(400);
    $mensaje = 'Falta el identificador de la sesión de pago.';
} else {
    try {
        $client  = stripeClient();
        $session = $client->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent.charges'],
        ]);

        $estadoPago = $session->payment_status;
        $currency   = strtoupper($session->currency);
        $monto      = ($session->amount_total ?? 0) / 100;

        $charge = $session->payment_intent->charges->data[0] ?? null;
        if ($charge && isset($charge->receipt_url)) {
            $invoiceUrl = $charge->receipt_url;
        }

        $registroLocal = $_SESSION['stripe_checkout'][$sessionId] ?? null;
        $ventaRegistrada = $registroLocal['venta_id'] ?? null;

        if ($estadoPago === 'paid' && $registroLocal && !$ventaRegistrada) {
            $ventaId = registrarVenta(
                null,
                $_SESSION['usuario_id'] ?? null,
                $registroLocal['items'],
                $registroLocal['metodo'] ?? 'Stripe',
                (float)($registroLocal['totals']['total'] ?? $monto)
            );

            if ($ventaId) {
                $_SESSION['stripe_checkout'][$sessionId]['venta_id'] = $ventaId;
                $_SESSION['carrito'] = [];
                unset($_SESSION['stripe_checkout'][$sessionId]);
            }
        } elseif ($ventaRegistrada) {
            $ventaId = $ventaRegistrada;
            unset($_SESSION['stripe_checkout'][$sessionId]);
        }
    } catch (Throwable $e) {
        $mensaje = 'No pudimos recuperar la sesión de Stripe: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago completado - LumiSpace</title>
    <link rel="stylesheet" href="../css/carrito.css">
    <style>
        body {
            background: #f9f5f0;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }
        .checkout-result {
            max-width: 720px;
            margin: 40px auto;
            background: #fff;
            border-radius: 14px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
            text-align: center;
        }
        .checkout-result h1 {
            font-size: 32px;
            margin-bottom: 12px;
            color: #2e251d;
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 999px;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .status-paid {
            background: rgba(46, 213, 115, 0.15);
            color: #0f8a4b;
        }
        .status-pending {
            background: rgba(255, 193, 7, .2);
            color: #9c6b00;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
            gap: 18px;
            margin: 30px 0;
        }
        .info-card {
            border: 1px solid #f0e6da;
            border-radius: 12px;
            padding: 18px;
            text-align: left;
        }
        .info-card span {
            display: block;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8a7360;
            margin-bottom: 4px;
        }
        .info-card strong {
            font-size: 20px;
            color: #2e251d;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            justify-content: center;
            margin-top: 24px;
        }
        .btn-primary {
            background: linear-gradient(120deg,#a1683a,#8f5e4b);
            color: #fff;
            border: none;
            padding: 12px 22px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-outline {
            border: 2px solid #a1683a;
            color: #a1683a;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
        }
        .alert {
            border-radius: 8px;
            padding: 14px;
            background: #fff6d4;
            color: #7a5a00;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="checkout-result">
        <?php if ($mensaje): ?>
            <div class="alert"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <div class="status-chip <?= $estadoPago === 'paid' ? 'status-paid' : 'status-pending' ?>">
            <?= $estadoPago === 'paid' ? 'Pago confirmado ✅' : 'Pago pendiente' ?>
        </div>
        <h1>¡Gracias por tu compra!</h1>
        <p>Hemos recibido tu pago y estamos preparando tu pedido.</p>

        <div class="info-grid">
            <div class="info-card">
                <span>Total pagado</span>
                <strong><?= number_format($monto, 2) ?> <?= htmlspecialchars($currency) ?></strong>
            </div>
            <div class="info-card">
                <span>ID de pago</span>
                <strong><?= htmlspecialchars($sessionId ?? 'N/D') ?></strong>
            </div>
            <?php if ($ventaId): ?>
            <div class="info-card">
                <span>Venta registrada</span>
                <strong>#<?= htmlspecialchars((string)$ventaId) ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <?php if ($invoiceUrl): ?>
                <a class="btn-primary" href="<?= htmlspecialchars($invoiceUrl) ?>" target="_blank" rel="noopener">
                    Ver recibo oficial
                </a>
            <?php endif; ?>
            <a class="btn-outline" href="../index.php">Volver al inicio</a>
        </div>
    </div>
</body>
</html>

