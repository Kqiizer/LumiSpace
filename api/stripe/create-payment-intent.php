<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/stripe.php";

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Validar carrito
    $carrito = carritoObtener();
    if (empty($carrito)) {
        http_response_code(400);
        echo json_encode(['error' => 'El carrito está vacío']);
        exit;
    }

    // Obtener datos del cliente
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

    if (empty($nombre) || empty($correo) || empty($direccion)) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos del cliente']);
        exit;
    }

    // Calcular totales
    $subtotal = 0;
    foreach ($carrito as $item) {
        $subtotal += (float)$item['precio'] * (int)$item['cantidad'];
    }
    $iva = $subtotal * 0.16;
    $envio = $subtotal > 1000 ? 0 : 150;
    $total = $subtotal + $iva + $envio;

    // Convertir a centavos (Stripe usa la moneda más pequeña)
    $amountInCents = (int)round($total * 100);

    if ($amountInCents < 50) { // Mínimo $0.50 MXN
        http_response_code(400);
        echo json_encode(['error' => 'El monto mínimo es $0.50 MXN']);
        exit;
    }

    // Crear cliente en Stripe (opcional, para historial)
    $stripe = stripeClient();
    $config = stripeConfig();

    // Crear o recuperar cliente de Stripe
    $stripeCustomer = null;
    if ($usuario_id > 0) {
        // Buscar si ya existe un customer_id para este usuario
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT stripe_customer_id FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && !empty($user['stripe_customer_id'])) {
            try {
                $stripeCustomer = $stripe->customers->retrieve($user['stripe_customer_id']);
            } catch (\Exception $e) {
                // Si el customer no existe en Stripe, crear uno nuevo
                $stripeCustomer = null;
            }
        }

        if (!$stripeCustomer) {
            $stripeCustomer = $stripe->customers->create([
                'email' => $correo,
                'name' => $nombre,
                'metadata' => [
                    'usuario_id' => (string)$usuario_id,
                    'direccion' => $direccion
                ]
            ]);

            // Guardar customer_id en la BD
            $stmt = $conn->prepare("UPDATE usuarios SET stripe_customer_id = ? WHERE id = ?");
            $stmt->bind_param("si", $stripeCustomer->id, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Cliente invitado - crear customer temporal
        $stripeCustomer = $stripe->customers->create([
            'email' => $correo,
            'name' => $nombre,
            'metadata' => [
                'tipo' => 'invitado',
                'direccion' => $direccion
            ]
        ]);
    }

    // Preparar items para metadata
    $itemsMetadata = [];
    foreach ($carrito as $item) {
        $itemsMetadata[] = $item['producto_id'] . ':' . $item['cantidad'];
    }

    // Crear Payment Intent
    $paymentIntent = $stripe->paymentIntents->create([
        'amount' => $amountInCents,
        'currency' => strtolower($config['currency'] ?? 'mxn'),
        'customer' => $stripeCustomer->id,
        'payment_method_types' => ['card'],
        'metadata' => [
            'usuario_id' => (string)$usuario_id,
            'nombre' => $nombre,
            'correo' => $correo,
            'direccion' => $direccion,
            'items' => implode(',', $itemsMetadata),
            'subtotal' => (string)$subtotal,
            'iva' => (string)$iva,
            'envio' => (string)$envio,
            'total' => (string)$total
        ],
        'description' => 'Compra en LumiSpace - ' . count($carrito) . ' producto(s)',
        'receipt_email' => $correo,
    ]);

    // Guardar Payment Intent ID en sesión para referencia
    $_SESSION['stripe_payment_intent_id'] = $paymentIntent->id;
    $_SESSION['checkout_data'] = [
        'nombre' => $nombre,
        'correo' => $correo,
        'direccion' => $direccion,
        'total' => $total,
        'carrito' => $carrito
    ];

    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
        'paymentIntentId' => $paymentIntent->id
    ]);

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log("Stripe API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al procesar el pago: ' . $e->getMessage()]);
} catch (\Exception $e) {
    error_log("Error en create-payment-intent: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}

