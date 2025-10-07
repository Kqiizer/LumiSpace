<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si el carrito está vacío, regresamos
$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    header("Location: carrito.php");
    exit;
}

// Calcular totales
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}
$envio = 50;
$iva   = $subtotal * 0.16;
$total = $subtotal + $envio + $iva;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Compra</title>
    <link rel="stylesheet" href="../css/carrito.css">
</head>
<body>
<div class="container">
    <h1>Resumen de Compra</h1>

    <div class="checkout-grid">
        <!-- 🔹 Información del Cliente -->
        <div>
            <div class="customer-info">
                <h2 class="section-title">Información del Cliente</h2>
                <div class="info-grid">
                    <div class="info-field">
                        <label>Nombre completo</label>
                        <input type="text" id="customerName" placeholder="Ingresa tu nombre completo" required>
                    </div>
                    <div class="info-field">
                        <label for="customerPhone">Teléfono</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" value="+52" style="width: 80px; font-size: 15px; padding: 8px;" readonly>
                            <input type="tel" id="customerPhone" placeholder="000 000 0000" style="flex: 1;" required>
                        </div>
                    </div>
                    <div class="info-field">
                        <label for="customerGmail">Correo electrónico</label>
                        <input type="email" id="customerGmail" placeholder="tucorreo@gmail.com" required>
                    </div>
                    <div class="info-field">
                        <label for="customerCountry">País</label>
                        <input type="text" id="customerCountry" value="México" readonly>
                    </div>
                    <div class="info-field full-width">
                        <label>Dirección de entrega</label>
                        <input type="text" id="customerAddress" placeholder="Calle, número, colonia, ciudad" required>
                    </div>
                    <div class="info-field">
                        <label>Fecha de entrega</label>
                        <input type="date" id="deliveryDate" readonly>
                    </div>
                </div>
            </div>

            <!-- 🔹 Productos -->
            <div class="products-list">
                <h2 class="section-title">Productos en el Carrito</h2>
                <?php foreach ($carrito as $item): ?>
                    <div class="product-item">
                        <div class="product-image">
                            <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>">
                        </div>
                        <div class="product-details">
                            <div class="product-name"><?= htmlspecialchars($item['nombre']) ?></div>
                            <div class="product-type"><?= htmlspecialchars($item['detalles']) ?></div>
                        </div>
                        <div class="product-actions">
                            <div class="quantity-control">
                                <span class="qty-value"><?= $item['cantidad'] ?></span>
                            </div>
                            <div class="product-price">
                                <div class="unit-price">$<?= number_format($item['precio'], 2) ?> c/u</div>
                                <div class="total-price">$<?= number_format($item['precio'] * $item['cantidad'], 2) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 🔹 Resumen Pedido -->
        <div class="order-summary">
            <h2 class="section-title">Resumen del Pedido</h2>
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Envío</span>
                <span id="shipping">$<?= number_format($envio, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>IVA (16%)</span>
                <span id="tax">$<?= number_format($iva, 2) ?></span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="total">$<?= number_format($total, 2) ?></span>
            </div>
            <button class="pay-button" onclick="openPaymentModal()">Proceder al Pago</button>
        </div>
    </div>
</div>

<!-- 🔹 Modal Pago -->
<div class="modal" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Selecciona tu método de pago</h2>
            <button class="close-modal" onclick="closePaymentModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="payment-methods">
                <div class="payment-method active" onclick="selectPaymentMethod('card')">
                    <img src="imagenes/tarjeta.png" alt="Tarjeta" style="width:70px; height:70px;">
                    <div class="payment-method-name">Tarjeta</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                    <img src="imagenes/paypal.png" alt="PayPal" style="width:70px; height:70px;">
                    <div class="payment-method-name">PayPal</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('transfer')">
                    <img src="imagenes/transf.png" alt="Transferencia" style="width:70px; height:70px;">
                    <div class="payment-method-name">Transferencia</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('cash')">
                    <img src="imagenes/efectivo.png" alt="Efectivo" style="width:70px; height:70px;">
                    <div class="payment-method-name">Efectivo</div>
                </div>
            </div>

            <!-- Formulario de tarjeta -->
            <div class="payment-form active" id="cardForm">
                <div class="form-group">
                    <label>Número de tarjeta</label>
                    <input type="text" id="cardNumber" maxlength="19" required>
                </div>
                <div class="form-group">
                    <label>Nombre del titular</label>
                    <input type="text" id="cardHolder" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiración</label>
                        <input type="text" id="cardExpiry" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" id="cardCVV" maxlength="4" required>
                    </div>
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">Confirmar Pago</button>
            </div>

            <!-- PayPal -->
            <div class="payment-form" id="paypalForm">
                <p>Serás redirigido a PayPal para completar tu compra.</p>
                <button class="confirm-payment-btn" onclick="redirectToPayPal()">Continuar con PayPal</button>
            </div>

            <!-- Transferencia -->
            <div class="payment-form" id="transferForm">
                <div class="form-group">
                    <label>Banco</label>
                    <select id="transferBank" required>
                        <option value="">Selecciona tu banco</option>
                        <option>BBVA</option>
                        <option>Santander</option>
                        <option>Banamex</option>
                        <option>Banorte</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>CLABE interbancaria</label>
                    <input type="text" id="transferCLABE" maxlength="18" required>
                </div>
                <div class="form-group">
                    <label>Nombre del titular</label>
                    <input type="text" id="transferHolder" required>
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">Generar orden de transferencia</button>
            </div>

            <!-- Efectivo -->
            <div class="payment-form" id="cashForm">
                <div class="form-group">
                    <label>Método de pago en efectivo</label>
                    <select id="cashMethod" required>
                        <option>Oxxo</option>
                        <option>7-Eleven</option>
                        <option>Farmacias del Ahorro</option>
                        <option>Walmart</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Correo para referencia</label>
                    <input type="email" id="cashEmail" required>
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">Generar referencia de pago</button>
            </div>
        </div>
    </div>
</div>

<!-- 🔹 Scripts -->
<script src="../js/carrito.js"></script>
</body>
</html>
<?php exit(); ?>