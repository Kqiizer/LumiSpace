<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/stripe.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$paymentIntentId = $_GET['payment_intent'] ?? '';

// Limpiar carrito si el pago fue exitoso
if (!empty($paymentIntentId)) {
    // Verificar que el payment intent existe y está completado
    try {
        $stripe = stripeClient();
        $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);
        
        if ($paymentIntent->status === 'succeeded') {
            // Limpiar carrito
            carritoVaciar();
            
            // Obtener información de la venta si existe
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, total, fecha FROM ventas WHERE stripe_payment_intent_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $paymentIntentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $venta = $result->fetch_assoc();
            $stmt->close();
        }
    } catch (\Exception $e) {
        error_log("Error verificando payment intent: " . $e->getMessage());
    }
}

$venta_id = $venta['id'] ?? null;
$total = $venta['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Pago Exitoso! - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/carrito.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">
<div class="checkout-container">
    <div class="success-container">
        <div class="success-icon">
            <div class="success-circle">
                <i class="fas fa-check"></i>
            </div>
        </div>
        
        <h1 class="success-title">¡Pago Exitoso!</h1>
        <p class="success-message">
            Tu pedido ha sido procesado correctamente. Recibirás un correo de confirmación en breve.
        </p>

        <?php if ($venta_id): ?>
            <div class="success-details">
                <div class="detail-item">
                    <span class="detail-label">Número de pedido:</span>
                    <span class="detail-value">#<?= $venta_id ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Total pagado:</span>
                    <span class="detail-value">$<?= number_format($total, 2) ?> MXN</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Método de pago:</span>
                    <span class="detail-value">Tarjeta (Stripe)</span>
                </div>
            </div>
        <?php endif; ?>

        <div class="success-actions">
            <a href="<?= $BASE ?>index.php" class="btn-primary">
                <i class="fas fa-home"></i> Volver al Inicio
            </a>
            <?php if ($venta_id): ?>
                <a href="<?= $BASE ?>views/mis-pedidos.php?id=<?= $venta_id ?>" class="btn-secondary">
                    <i class="fas fa-receipt"></i> Ver Detalles del Pedido
                </a>
            <?php endif; ?>
        </div>

        <div class="success-info">
            <div class="info-box">
                <i class="fas fa-truck"></i>
                <div>
                    <strong>Envío</strong>
                    <p>Tu pedido será enviado en 3-5 días hábiles</p>
                </div>
            </div>
            <div class="info-box">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Confirmación</strong>
                    <p>Recibirás un correo con los detalles de tu pedido</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.success-container {
    max-width: 600px;
    margin: 4rem auto;
    text-align: center;
    padding: 3rem 2rem;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

body.dark .success-container {
    background: rgba(26, 19, 15, 0.95);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.success-icon {
    margin-bottom: 2rem;
}

.success-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    animation: scaleIn 0.5s ease;
}

.success-circle i {
    font-size: 3rem;
    color: #fff;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.success-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: var(--cart-primary, #8b7355);
    margin-bottom: 1rem;
}

body.dark .success-title {
    color: #ffe6c6;
}

.success-message {
    font-size: 1.1rem;
    color: var(--cart-text, #555);
    margin-bottom: 2rem;
    line-height: 1.6;
}

body.dark .success-message {
    color: rgba(255, 255, 255, 0.8);
}

.success-details {
    background: #faf9f7;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    text-align: left;
}

body.dark .success-details {
    background: rgba(255, 255, 255, 0.05);
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(139, 115, 85, 0.1);
}

body.dark .detail-item {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    color: var(--cart-text, #555);
    font-weight: 500;
}

body.dark .detail-label {
    color: rgba(255, 255, 255, 0.7);
}

.detail-value {
    color: var(--cart-dark, #2c241b);
    font-weight: 700;
}

body.dark .detail-value {
    color: #ffe6c6;
}

.success-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
    padding: 14px 28px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #8b7355, #6d5a42);
    color: #fff;
}

body.dark .btn-primary {
    background: linear-gradient(135deg, #f6c995, #d28a4c);
    color: #1b120b;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(139, 115, 85, 0.3);
}

.btn-secondary {
    background: transparent;
    border: 2px solid var(--cart-primary, #8b7355);
    color: var(--cart-primary, #8b7355);
}

body.dark .btn-secondary {
    border-color: #f6c995;
    color: #f6c995;
}

.btn-secondary:hover {
    background: var(--cart-primary, #8b7355);
    color: #fff;
}

body.dark .btn-secondary:hover {
    background: #f6c995;
    color: #1b120b;
}

.success-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-top: 2rem;
}

.info-box {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #faf9f7;
    border-radius: 10px;
    text-align: left;
}

body.dark .info-box {
    background: rgba(255, 255, 255, 0.05);
}

.info-box i {
    font-size: 1.5rem;
    color: var(--cart-primary, #8b7355);
    margin-top: 0.25rem;
}

body.dark .info-box i {
    color: #f6c995;
}

.info-box strong {
    display: block;
    color: var(--cart-dark, #2c241b);
    margin-bottom: 0.25rem;
}

body.dark .info-box strong {
    color: #ffe6c6;
}

.info-box p {
    font-size: 0.9rem;
    color: var(--cart-text, #555);
    margin: 0;
}

body.dark .info-box p {
    color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 768px) {
    .success-container {
        margin: 2rem auto;
        padding: 2rem 1.5rem;
    }

    .success-title {
        font-size: 2rem;
    }

    .success-actions {
        flex-direction: column;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }

    .success-info {
        grid-template-columns: 1fr;
    }
}
</style>
</body>
</html>
