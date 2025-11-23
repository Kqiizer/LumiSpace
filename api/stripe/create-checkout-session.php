<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/stripe.php';

try {
    $config = stripeConfig();
    if (empty($config['publishable_key'])) {
        throw new RuntimeException('Configura tus claves de Stripe en el archivo .env');
    }

    $carrito = $_SESSION['carrito'] ?? [];
    if (empty($carrito)) {
        throw new RuntimeException('Tu carrito está vacío.');
    }

    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $metodo    = 'Stripe - Tarjeta';

    if ($nombre === '' || $correo === '' || $direccion === '') {
        throw new RuntimeException('Completa nombre, correo y dirección.');
    }
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('El correo no es válido.');
    }

    $lineItems = [];
    $subtotal  = 0.0;
    foreach ($carrito as $item) {
        $precio = (float)($item['precio'] ?? 0);
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $subtotal += $precio * $cantidad;

        $nombreProducto = substr($item['nombre'] ?? 'Producto', 0, 80);

        $lineItems[] = [
            'price_data' => [
                'currency' => $config['currency'],
                'unit_amount' => (int) round($precio * 100),
                'product_data' => [
                    'name' => $nombreProducto,
                ],
            ],
            'quantity' => $cantidad,
        ];
    }

    $iva   = $subtotal * 0.16;
    $envio = $subtotal > 0 ? 50.0 : 0.0;
    $total = $subtotal + $iva + $envio;

    if ($iva > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => $config['currency'],
                'unit_amount' => (int) round($iva * 100),
                'product_data' => [
                    'name' => 'IVA (16%)',
                ],
            ],
            'quantity' => 1,
        ];
    }

    if ($envio > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => $config['currency'],
                'unit_amount' => (int) round($envio * 100),
                'product_data' => [
                    'name' => 'Envío',
                ],
            ],
            'quantity' => 1,
        ];
    }

    $client = stripeClient();
    $session = $client->checkout->sessions->create([
        'mode' => 'payment',
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'customer_email' => $correo,
        'metadata' => [
            'customer_name' => $nombre,
            'direccion' => $direccion,
            'metodo' => $metodo,
        ],
        'success_url' => $config['success_url'],
        'cancel_url' => $config['cancel_url'],
        'billing_address_collection' => 'auto',
        'shipping_address_collection' => [
            'allowed_countries' => ['MX', 'US', 'CA', 'ES'],
        ],
    ]);

    $_SESSION['stripe_checkout'] = $_SESSION['stripe_checkout'] ?? [];
    $_SESSION['stripe_checkout'][$session->id] = [
        'customer' => [
            'nombre' => $nombre,
            'correo' => $correo,
            'direccion' => $direccion,
        ],
        'items' => $carrito,
        'totals' => [
            'subtotal' => round($subtotal, 2),
            'iva'      => round($iva, 2),
            'envio'    => round($envio, 2),
            'total'    => round($total, 2),
        ],
        'metodo' => $metodo,
    ];

    echo json_encode([
        'sessionId' => $session->id,
        'url'       => $session->url,
        'publishableKey' => $config['publishable_key'],
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

