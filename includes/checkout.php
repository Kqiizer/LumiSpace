<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ====================================
// 🔗 DEPENDENCIAS
// ====================================
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/stripe.php';

if (!function_exists('computeTotals')) {
    function computeTotals(array $carrito): array {
        $subtotal = 0.0;
        foreach ($carrito as $item) {
            $precio = (float)($item['precio'] ?? 0);
            $cantidad = max(1, (int)($item['cantidad'] ?? 1));
            $subtotal += $precio * $cantidad;
        }
        $iva = $subtotal * 0.16;
        $envio = $subtotal > 1000 ? 0 : 150;
        $total = $subtotal + $iva + $envio;
        return [
            'subtotal' => round($subtotal, 2),
            'iva'      => round($iva, 2),
            'envio'    => round($envio, 2),
            'total'    => round($total, 2),
        ];
    }
}

// ====================================
// ⚙️ VALIDAR CARRITO
// ====================================
$carrito = carritoObtener();
if (empty($carrito)) {
    header("Location: " . (defined('BASE_URL') ? BASE_URL : '/') . "includes/carrito.php");
    exit;
}

// ====================================
// 💰 CALCULAR TOTALES
// ====================================
$totals = computeTotals($carrito);
$subtotal = $totals['subtotal'];
$iva      = $totals['iva'];
$envio    = $totals['envio'];
$total    = $totals['total'];

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$USER_ID = (int)($_SESSION['usuario_id'] ?? 0);

// Obtener datos del usuario si está logueado
$usuario = null;
if ($USER_ID > 0) {
    $usuario = getUsuarioPorId($USER_ID);
}

$stripePublishable = '';
$stripeConfigError = '';
try {
    $stripeSettings = stripeConfig();
    $stripePublishable = $stripeSettings['publishable_key'] ?? '';
    if ($stripePublishable === '') {
        $stripeConfigError = 'Configura STRIPE_PUBLISHABLE_KEY en tu archivo .env para habilitar los pagos.';
    }
} catch (Throwable $stripeException) {
    $stripeConfigError = $stripeException->getMessage();
}
$stripeReady = $stripePublishable !== '' && $stripeConfigError === '';

// ====================================
// 📅 FECHA DE ENTREGA (5 días hábiles)
// ====================================
$fechaEntrega = date('Y-m-d', strtotime('+5 weekdays'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/carrito.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $BASE ?>css/checkout.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">
<div class="checkout-container">
    <div class="checkout-header">
        <h1><i class="fas fa-shopping-bag"></i> Finalizar Compra</h1>
        <p>Completa tu pedido y recibe tus productos en la comodidad de tu hogar</p>
    </div>

    <?php if ($stripeConfigError): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($stripeConfigError) ?>
        </div>
    <?php endif; ?>

    <form 
        id="checkout-form" 
        method="POST" 
        data-stripe-key="<?= htmlspecialchars($stripePublishable) ?>"
    >
        <div class="checkout-grid">
            <!-- LEFT: Información del cliente y productos -->
            <div class="checkout-left">
                <!-- Información del Cliente -->
                <div class="checkout-card">
                    <h2 class="checkout-section-title">
                        <i class="fas fa-user-circle"></i> Información del Cliente
                    </h2>
                    <div class="checkout-form-grid">
                        <div class="form-field">
                            <label>Nombre completo *</label>
                            <input 
                                type="text" 
                                name="nombre" 
                                placeholder="Juan Pérez García" 
                                value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>"
                                required
                            >
                        </div>
                        <div class="form-field">
                            <label>Correo electrónico *</label>
                            <input 
                                type="email" 
                                name="correo" 
                                placeholder="correo@ejemplo.com" 
                                value="<?= htmlspecialchars($usuario['email'] ?? '') ?>"
                                required
                            >
                        </div>
                        <div class="form-field full-width">
                            <label>Dirección de entrega *</label>
                            <textarea 
                                name="direccion" 
                                placeholder="Calle, número, colonia, ciudad, C.P." 
                                rows="3"
                                required
                            ></textarea>
                        </div>
                        <div class="form-field">
                            <label>País</label>
                            <input type="text" value="México" readonly>
                        </div>
                        <div class="form-field">
                            <label>Fecha estimada de entrega</label>
                            <input type="date" value="<?= $fechaEntrega ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- Información de Pago -->
                <?php if ($stripeReady): ?>
                <div class="checkout-card">
                    <h2 class="checkout-section-title">
                        <i class="fas fa-credit-card"></i> Información de Pago
                    </h2>
                    <div class="stripe-card-container">
                        <div id="card-element">
                            <!-- Stripe Elements se montará aquí -->
                        </div>
                        <div id="card-errors" role="alert"></div>
                    </div>
                    <div class="payment-security">
                        <i class="fas fa-shield-alt"></i>
                        <span>Tu información de pago está protegida y encriptada</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Productos -->
                <div class="checkout-card">
                    <h2 class="checkout-section-title">
                        <i class="fas fa-box-open"></i> Productos en tu Pedido (<?= count($carrito) ?>)
                    </h2>
                    <div class="checkout-products-list">
                        <?php foreach ($carrito as $item): ?>
                            <div class="checkout-product-item">
                                <div class="checkout-product-image">
                                    <img 
                                        src="<?= htmlspecialchars($item['imagen']) ?>" 
                                        alt="<?= htmlspecialchars($item['nombre']) ?>"
                                        onerror="this.src='<?= $BASE ?>images/default.png'"
                                    >
                                </div>
                                <div class="checkout-product-details">
                                    <div class="checkout-product-name"><?= htmlspecialchars($item['nombre']) ?></div>
                                    <?php if (!empty($item['categoria'])): ?>
                                        <div class="checkout-product-category"><?= htmlspecialchars($item['categoria']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="checkout-product-price">
                                    <div class="checkout-product-qty">Cantidad: <?= $item['cantidad'] ?></div>
                                    <div class="checkout-product-total">
                                        $<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Resumen -->
            <div class="checkout-right">
                <div class="checkout-summary-card">
                    <h2 class="checkout-section-title">
                        <i class="fas fa-receipt"></i> Resumen del Pedido
                    </h2>
                    <div class="checkout-summary-details">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>$<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>IVA (16%)</span>
                            <span>$<?= number_format($iva, 2) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Envío</span>
                            <span><?= $envio > 0 ? '$' . number_format($envio, 2) : 'GRATIS' ?></span>
                        </div>
                        <?php if ($subtotal < 1000): ?>
                            <div class="shipping-notice">
                                <i class="fas fa-info-circle"></i>
                                <span>Envío gratis en compras mayores a $1,000</span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-divider"></div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>$<?= number_format($total, 2) ?></span>
                        </div>
                    </div>

                    <div id="stripe-error" class="stripe-error" style="display:none;"></div>

                    <button 
                        class="checkout-pay-button" 
                        type="submit"
                        id="stripe-pay-button"
                        <?= $stripeReady ? '' : 'disabled' ?>
                    >
                        <i class="fas fa-lock"></i> 
                        <span>Pagar $<?= number_format($total, 2) ?></span>
                    </button>
                    
                    <?php if (!$stripeReady): ?>
                        <small class="checkout-disabled-notice">
                            Configura tus claves de Stripe para habilitar este botón.
                        </small>
                    <?php endif; ?>

                    <div class="checkout-trust-badges">
                        <div class="trust-badge">
                            <i class="fas fa-shield-alt"></i>
                            <span>Pago Seguro</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-truck"></i>
                            <span>Envío Rápido</span>
                        </div>
                        <div class="trust-badge">
                            <i class="fas fa-undo"></i>
                            <span>Devoluciones</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="<?= $BASE ?>js/checkout-stripe.js"></script>
</body>
</html>
