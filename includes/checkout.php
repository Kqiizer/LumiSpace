<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// ====================================
// 🔗 DEPENDENCIAS
// ====================================
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/carrito-funciones.php';
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
        $envio = $subtotal > 0 ? 50.0 : 0.0;
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
$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
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

// ====================================
// 📦 PROCESAR COMPRA
// ====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirmar_compra') {

    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $metodo   = trim($_POST['metodo_pago'] ?? 'Desconocido');
    $usuario_id = $_SESSION['usuario_id'] ?? null;

    if ($nombre && $correo && $direccion) {
        // Registrar venta en BD usando tus funciones LumiSpace
        $venta_id = registrarVenta(
            null,           // cliente_id (si manejas clientes aparte)
            $usuario_id,    // usuario_id actual
            $carrito,       // items
            $metodo,        // método de pago
            $total          // total
        );

        if ($venta_id) {
            // Limpiar carrito
            $_SESSION['carrito'] = [];

            // Redirigir a página de confirmación
            header("Location: confirmacion.php?id=" . $venta_id);
            exit;
        } else {
            $error = "Hubo un error al registrar la venta.";
        }
    } else {
        $error = "Por favor completa todos los campos requeridos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - LumiSpace</title>
    <link rel="stylesheet" href="../css/carrito.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-shopping-bag"></i> Finalizar Compra</h1>
        <p>Completa tu pedido y recibe tus productos en la comodidad de tu hogar</p>
    </div>

    <?php if (!empty($error)): ?>
        <div style="background:#ffefef; color:#b00020; padding:10px; border-radius:6px; margin-bottom:20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($stripeConfigError): ?>
        <div style="background:#fff4e5; color:#8a4c12; padding:12px 16px; border-radius:8px; margin-bottom:20px; border:1px solid #f7d7af;">
            ⚠️ <?= htmlspecialchars($stripeConfigError) ?>
        </div>
    <?php endif; ?>

    <form 
        id="checkout-form" 
        method="POST" 
        data-stripe-key="<?= htmlspecialchars($stripePublishable) ?>" 
        data-create-session="../api/stripe/create-checkout-session.php"
    >
        <input type="hidden" name="action" value="confirmar_compra">
        <input type="hidden" name="metodo_pago" value="Stripe - Tarjeta">

        <div class="checkout-grid">
            <!-- LEFT: Información del cliente y productos -->
            <div>
                <div class="card">
                    <h2 class="section-title"><i class="fas fa-user-circle"></i> Información del Cliente</h2>
                    <div class="info-grid">
                        <div class="info-field">
                            <label>Nombre completo *</label>
                            <input type="text" name="nombre" placeholder="Juan Pérez García" required>
                        </div>
                        <div class="info-field">
                            <label>Correo electrónico *</label>
                            <input type="email" name="correo" placeholder="correo@ejemplo.com" required>
                        </div>
                        <div class="info-field full-width">
                            <label>Dirección de entrega *</label>
                            <input type="text" name="direccion" placeholder="Calle, número, colonia, ciudad, C.P." required>
                        </div>
                        <div class="info-field">
                            <label>País</label>
                            <input type="text" value="México" readonly>
                        </div>
                        <div class="info-field">
                            <label>Fecha estimada de entrega</label>
                            <input type="date" value="<?= $fechaEntrega ?>" readonly>
                        </div>
                    </div>
                </div>

                <!-- 🛍 Productos -->
                <div class="card">
                    <h2 class="section-title"><i class="fas fa-box-open"></i> Productos en tu Pedido (<?= count($carrito) ?>)</h2>
                    <?php foreach ($carrito as $item): ?>
                        <div class="product-item">
                            <div class="product-image">
                                <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>">
                            </div>
                            <div class="product-details">
                                <div class="product-name"><?= htmlspecialchars($item['nombre']) ?></div>
                                <div class="product-type"><?= htmlspecialchars($item['detalles'] ?? '') ?></div>
                            </div>
                            <div class="product-actions">
                                <div class="quantity-control"><i class="fas fa-times"></i> <?= $item['cantidad'] ?></div>
                                <div class="product-price">
                                    <div class="unit-price">$<?= number_format($item['precio'], 2) ?> c/u</div>
                                    <div class="total-price">$<?= number_format($item['precio'] * $item['cantidad'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RIGHT: Resumen -->
            <div>
                <div class="card order-summary">
                    <h2 class="section-title"><i class="fas fa-receipt"></i> Resumen del Pedido</h2>
                    <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span></div>
                    <div class="summary-row"><span>IVA (16%)</span><span>$<?= number_format($iva, 2) ?></span></div>
                    <div class="summary-row"><span>Envío</span><span>$<?= number_format($envio, 2) ?></span></div>
                    <div class="summary-row total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>

                    <div class="info-field full-width" style="margin-top:15px;">
                        <label>Pago seguro</label>
                        <input type="text" value="Tarjeta (Stripe)" readonly>
                        <small style="display:block;color:#7a6a58;margin-top:6px;">
                            Se abrirá la ventana de Stripe para ingresar tus datos de tarjeta.
                        </small>
                    </div>

                    <div id="stripe-error" style="display:none;background:#ffefef;color:#8b2131;padding:10px;border-radius:8px;margin-top:12px;"></div>

                    <button 
                        class="pay-button" 
                        type="submit"
                        id="stripe-pay-button"
                        <?= $stripeReady ? '' : 'disabled' ?>
                    >
                        <i class="fas fa-lock"></i> Pagar con Stripe
                    </button>
                    <?php if (!$stripeReady): ?>
                        <small style="display:block;color:#a8322d;margin-top:6px;">
                            Configura tus claves de Stripe para habilitar este botón.
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>
<script src="../js/checkout-stripe.js"></script>
</body>
</html>
