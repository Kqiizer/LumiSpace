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

// Calcular fecha de entrega estimada (5 días hábiles)
$fechaEntrega = date('Y-m-d', strtotime('+5 weekdays'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra - LumiSpace</title>
    <link rel="stylesheet" href="../css/carrito.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #a1683a;
            --primary-dark: #8f5e4b;
            --success: #1abc9c;
            --danger: #e74c3c;
            --text: #2a1f15;
            --text-light: #7a6f65;
            --bg-light: #f8f6f3;
            --border: #d6d0c7;
            --shadow: 0 4px 16px rgba(0,0,0,0.1);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f5f7 0%, #ffffff 50%, #ececec 100%);
            color: var(--text);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeInDown 0.6s ease;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1.1rem;
        }

        /* Stepper Progress */
        .stepper {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .step.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
        }

        .step.completed {
            background: var(--success);
            color: white;
        }

        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            font-weight: 700;
        }

        /* Grid Layout */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            animation: fadeInUp 0.6s ease;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        /* Form Fields */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-field.full-width {
            grid-column: 1 / -1;
        }

        .info-field label {
            display: block;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .info-field input,
        .info-field select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .info-field input:focus,
        .info-field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(161, 104, 58, 0.1);
        }

        .info-field input.error {
            border-color: var(--danger);
        }

        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        /* Products List */
        .product-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: var(--bg-light);
            border-radius: var(--radius);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text);
            margin-bottom: 5px;
        }

        .product-type {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .product-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .quantity-control {
            background: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .product-price {
            text-align: right;
        }

        .unit-price {
            color: var(--text-light);
            font-size: 0.85rem;
        }

        .total-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Order Summary */
        .order-summary {
            position: sticky;
            top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid var(--border);
            font-size: 1rem;
        }

        .summary-row.total {
            border-top: 2px solid var(--primary);
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-top: 10px;
        }

        /* Buttons */
        .pay-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 14px rgba(161, 104, 58, 0.3);
        }

        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 104, 58, 0.4);
        }

        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
        }

        .close-modal {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--bg-light);
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: var(--danger);
            color: white;
        }

        .modal-body {
            padding: 30px;
        }

        /* Payment Methods */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .payment-method {
            padding: 20px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
            box-shadow: var(--shadow);
        }

        .payment-method.active {
            border-color: var(--primary);
            background: rgba(161, 104, 58, 0.1);
        }

        .payment-method img {
            margin-bottom: 10px;
        }

        .payment-method-name {
            font-weight: 600;
            color: var(--text);
            font-size: 0.9rem;
        }

        /* Payment Forms */
        .payment-form {
            display: none;
        }

        .payment-form.active {
            display: block;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(161, 104, 58, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }

        .confirm-payment-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--success), #16a085);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .confirm-payment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 188, 156, 0.4);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }

            .product-item {
                flex-direction: column;
            }

            .product-actions {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="page-header">
        <h1><i class="fas fa-shopping-bag"></i> Finalizar Compra</h1>
        <p>Completa tu pedido y recibe tus productos en la comodidad de tu hogar</p>
    </div>

    <!-- Progress Stepper -->
    <div class="stepper">
        <div class="step completed">
            <div class="step-icon"><i class="fas fa-check"></i></div>
            <span>Carrito</span>
        </div>
        <div class="step active">
            <div class="step-icon">2</div>
            <span>Información</span>
        </div>
        <div class="step">
            <div class="step-icon">3</div>
            <span>Pago</span>
        </div>
        <div class="step">
            <div class="step-icon">4</div>
            <span>Confirmación</span>
        </div>
    </div>

    <div class="checkout-grid">
        <!-- Left Column -->
        <div>
            <!-- Customer Info -->
            <div class="card">
                <h2 class="section-title">
                    <i class="fas fa-user-circle"></i>
                    Información del Cliente
                </h2>
                <form id="customerForm">
                    <div class="info-grid">
                        <div class="info-field">
                            <label for="customerName">Nombre completo *</label>
                            <input type="text" id="customerName" placeholder="Juan Pérez García" required>
                            <span class="error-message">Este campo es obligatorio</span>
                        </div>
                        <div class="info-field">
                            <label for="customerPhone">Teléfono *</label>
                            <div style="display: flex; gap: 8px;">
                                <input type="text" value="+52" style="width: 70px;" readonly>
                                <input type="tel" id="customerPhone" placeholder="477 123 4567" required>
                            </div>
                            <span class="error-message">Ingresa un teléfono válido</span>
                        </div>
                        <div class="info-field full-width">
                            <label for="customerEmail">Correo electrónico *</label>
                            <input type="email" id="customerEmail" placeholder="correo@ejemplo.com" required>
                            <span class="error-message">Ingresa un email válido</span>
                        </div>
                        <div class="info-field full-width">
                            <label for="customerAddress">Dirección de entrega *</label>
                            <input type="text" id="customerAddress" placeholder="Calle, número, colonia, ciudad, C.P." required>
                            <span class="error-message">La dirección es obligatoria</span>
                        </div>
                        <div class="info-field">
                            <label for="customerCountry">País</label>
                            <input type="text" id="customerCountry" value="México" readonly>
                        </div>
                        <div class="info-field">
                            <label for="deliveryDate">Fecha estimada de entrega</label>
                            <input type="date" id="deliveryDate" value="<?= $fechaEntrega ?>" readonly>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products -->
            <div class="card">
                <h2 class="section-title">
                    <i class="fas fa-box-open"></i>
                    Productos en tu Pedido (<?= count($carrito) ?>)
                </h2>
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
                                <i class="fas fa-times"></i> <?= $item['cantidad'] ?>
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

        <!-- Right Column - Summary -->
        <div>
            <div class="card order-summary">
                <h2 class="section-title">
                    <i class="fas fa-receipt"></i>
                    Resumen del Pedido
                </h2>
                <div class="summary-row">
                    <span>Subtotal (<?= count($carrito) ?> productos)</span>
                    <span id="subtotal">$<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span><i class="fas fa-truck"></i> Envío</span>
                    <span id="shipping">$<?= number_format($envio, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span><i class="fas fa-percent"></i> IVA (16%)</span>
                    <span id="tax">$<?= number_format($iva, 2) ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total a Pagar</span>
                    <span id="total">$<?= number_format($total, 2) ?></span>
                </div>
                <button class="pay-button" onclick="validateAndProceed()">
                    <i class="fas fa-lock"></i> Proceder al Pago Seguro
                </button>
                <div style="text-align: center; margin-top: 15px; color: var(--text-light); font-size: 0.85rem;">
                    <i class="fas fa-shield-alt"></i> Pago 100% seguro y encriptado
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal" id="paymentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-credit-card"></i> Método de Pago</h2>
            <button class="close-modal" onclick="closePaymentModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="payment-methods">
                <div class="payment-method active" onclick="selectPaymentMethod('card')">
                    <img src="imagenes/tarjeta.png" alt="Tarjeta" style="width:60px; height:60px;">
                    <div class="payment-method-name">Tarjeta</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                    <img src="imagenes/paypal.png" alt="PayPal" style="width:60px; height:60px;">
                    <div class="payment-method-name">PayPal</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('transfer')">
                    <img src="imagenes/transf.png" alt="Transferencia" style="width:60px; height:60px;">
                    <div class="payment-method-name">Transferencia</div>
                </div>
                <div class="payment-method" onclick="selectPaymentMethod('cash')">
                    <img src="imagenes/efectivo.png" alt="Efectivo" style="width:60px; height:60px;">
                    <div class="payment-method-name">Efectivo</div>
                </div>
            </div>

            <!-- Card Form -->
            <div class="payment-form active" id="cardForm">
                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Número de tarjeta</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre del titular</label>
                    <input type="text" id="cardHolder" placeholder="Como aparece en la tarjeta">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Expiración</label>
                        <input type="text" id="cardExpiry" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> CVV</label>
                        <input type="text" id="cardCVV" placeholder="123" maxlength="4">
                    </div>
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">
                    <i class="fas fa-check-circle"></i> Confirmar Pago
                </button>
            </div>

            <!-- PayPal Form -->
            <div class="payment-form" id="paypalForm">
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="fab fa-paypal" style="font-size: 4rem; color: #0070ba; margin-bottom: 20px;"></i>
                    <p style="margin-bottom: 20px;">Serás redirigido a PayPal para completar tu compra de forma segura.</p>
                </div>
                <button class="confirm-payment-btn" onclick="redirectToPayPal()">
                    <i class="fab fa-paypal"></i> Continuar con PayPal
                </button>
            </div>

            <!-- Transfer Form -->
            <div class="payment-form" id="transferForm">
                <div class="form-group">
                    <label><i class="fas fa-university"></i> Banco</label>
                    <select id="transferBank">
                        <option value="">Selecciona tu banco</option>
                        <option>BBVA</option>
                        <option>Santander</option>
                        <option>Banamex</option>
                        <option>Banorte</option>
                        <option>HSBC</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> CLABE interbancaria</label>
                    <input type="text" id="transferCLABE" placeholder="18 dígitos" maxlength="18">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nombre del titular</label>
                    <input type="text" id="transferHolder" placeholder="Como aparece en tu cuenta">
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">
                    <i class="fas fa-file-invoice"></i> Generar Orden de Transferencia
                </button>
            </div>

            <!-- Cash Form -->
            <div class="payment-form" id="cashForm">
                <div class="form-group">
                    <label><i class="fas fa-store"></i> Tienda de conveniencia</label>
                    <select id="cashMethod">
                        <option>Oxxo</option>
                        <option>7-Eleven</option>
                        <option>Farmacias del Ahorro</option>
                        <option>Walmart</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Correo para referencia</label>
                    <input type="email" id="cashEmail" placeholder="tucorreo@ejemplo.com">
                </div>
                <button class="confirm-payment-btn" onclick="confirmPayment()">
                    <i class="fas fa-barcode"></i> Generar Referencia de Pago
                </button>
            </div>
        </div>
    </div>
</div>

