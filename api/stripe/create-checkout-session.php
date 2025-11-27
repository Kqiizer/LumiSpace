<?php
declare(strict_types=1);

// Desactivar visualización de errores para evitar HTML en la respuesta JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Iniciar buffer de salida para capturar cualquier output inesperado
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/stripe.php';

// Limpiar cualquier output previo después de incluir archivos
ob_clean();

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

    $total = $subtotal;

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
            'total'    => round($total, 2),
        ],
        'metodo' => $metodo,
    ];

    // Limpiar buffer antes de enviar JSON
    ob_clean();
    echo json_encode([
        'sessionId' => $session->id,
        'url'       => $session->url,
        'publishableKey' => $config['publishable_key'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} catch (Throwable $e) {
    error_log("Error en create-checkout-session: " . $e->getMessage());
    ob_clean();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

