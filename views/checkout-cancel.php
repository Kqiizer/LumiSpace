<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago cancelado - LumiSpace</title>
    <link rel="stylesheet" href="../css/carrito.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            background: #f7f3ef;
        }
        .cancel-wrapper {
            max-width: 540px;
            margin: 60px auto;
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 15px 45px rgba(0,0,0,.08);
        }
        h1 {
            color: #8f5e4b;
            margin-bottom: 12px;
        }
        p {
            color: #594737;
            margin-bottom: 28px;
        }
        a.btn-primary {
            display: inline-block;
            background: #a1683a;
            color: #fff;
            padding: 12px 22px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
        }
        a.btn-outline {
            display: inline-block;
            border: 2px solid #a1683a;
            color: #a1683a;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin-left: 12px;
        }
    </style>
</head>
<body>
    <div class="cancel-wrapper">
        <h1>Pago cancelado</h1>
        <p>Tu transacción fue cancelada. Puedes intentarlo nuevamente cuando estés listo.</p>
        <div>
            <a class="btn-primary" href="../includes/checkout.php">Volver al checkout</a>
            <a class="btn-outline" href="../index.php">Seguir explorando</a>
        </div>
    </div>
</body>
</html>

