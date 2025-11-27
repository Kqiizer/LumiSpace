<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Cancelado - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/carrito.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">
<div class="checkout-container">
    <div class="cancel-container">
        <div class="cancel-icon">
            <div class="cancel-circle">
                <i class="fas fa-times"></i>
            </div>
        </div>
        
        <h1 class="cancel-title">Pago Cancelado</h1>
        <p class="cancel-message">
            Tu pago fue cancelado. No se realizó ningún cargo. Puedes intentar nuevamente cuando estés listo.
        </p>

        <div class="cancel-actions">
            <a href="<?= $BASE ?>includes/carrito.php" class="btn-primary">
                <i class="fas fa-shopping-cart"></i> Volver al Carrito
            </a>
            <a href="<?= $BASE ?>includes/checkout.php" class="btn-secondary">
                <i class="fas fa-redo"></i> Intentar Nuevamente
            </a>
        </div>

        <div class="cancel-info">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>¿Necesitas ayuda?</strong>
                    <p>Si tienes problemas con el pago, contáctanos y te ayudaremos</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cancel-container {
    max-width: 600px;
    margin: 4rem auto;
    text-align: center;
    padding: 3rem 2rem;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

body.dark .cancel-container {
    background: rgba(26, 19, 15, 0.95);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.cancel-icon {
    margin-bottom: 2rem;
}

.cancel-circle {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    animation: scaleIn 0.5s ease;
}

.cancel-circle i {
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

.cancel-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.5rem;
    color: var(--cart-primary, #8b7355);
    margin-bottom: 1rem;
}

body.dark .cancel-title {
    color: #ffe6c6;
}

.cancel-message {
    font-size: 1.1rem;
    color: var(--cart-text, #555);
    margin-bottom: 2rem;
    line-height: 1.6;
}

body.dark .cancel-message {
    color: rgba(255, 255, 255, 0.8);
}

.cancel-actions {
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

.cancel-info {
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
    .cancel-container {
        margin: 2rem auto;
        padding: 2rem 1.5rem;
    }

    .cancel-title {
        font-size: 2rem;
    }

    .cancel-actions {
        flex-direction: column;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
</body>
</html>
