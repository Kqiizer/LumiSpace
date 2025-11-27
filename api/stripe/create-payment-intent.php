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

header("Content-Type: application/json; charset=UTF-8");

try {
    require_once __DIR__ . "/../../config/functions.php";
} catch (\Throwable $e) {
    ob_clean();
    error_log("Error cargando functions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar configuración del sistema'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . "/../../config/stripe.php";
} catch (\RuntimeException $e) {
    ob_clean();
    error_log("Error cargando stripe.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Throwable $e) {
    ob_clean();
    error_log("Error crítico cargando stripe.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al inicializar Stripe. Verifica la instalación de Composer.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpiar cualquier output previo después de incluir archivos
ob_clean();

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Validar que Stripe esté configurado
    $config = stripeConfig();
    if (empty($config['secret_key'])) {
        throw new RuntimeException('Stripe no está configurado. Verifica STRIPE_SECRET_KEY en tu archivo .env');
    }
    if (empty($config['publishable_key'])) {
        throw new RuntimeException('Stripe no está configurado. Verifica STRIPE_PUBLISHABLE_KEY en tu archivo .env');
    }

    // Validar carrito
    $carrito = carritoObtener();
    if (empty($carrito)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'El carrito está vacío'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Obtener datos del cliente
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $usuario_id = (int)($_SESSION['usuario_id'] ?? 0);

    if (empty($nombre) || empty($correo) || empty($direccion)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'Faltan datos del cliente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Validar formato de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'El correo electrónico no es válido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Calcular totales
    $subtotal = 0;
    foreach ($carrito as $item) {
        $precio = (float)($item['precio'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 1);
        if ($precio > 0 && $cantidad > 0) {
            $subtotal += $precio * $cantidad;
        }
    }
    $total = $subtotal;

    // Convertir a centavos (Stripe usa la moneda más pequeña)
    $amountInCents = (int)round($total * 100);

    if ($amountInCents < 50) { // Mínimo $0.50 MXN
        ob_clean();
        http_response_code(400);
        echo json_encode(['error' => 'El monto mínimo es $0.50 MXN'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Crear cliente de Stripe
    try {
        $stripe = stripeClient();
    } catch (\Throwable $e) {
        error_log("Error al crear cliente Stripe: " . $e->getMessage());
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Error al inicializar Stripe. Verifica la configuración.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Crear o recuperar cliente de Stripe
    $stripeCustomer = null;
            try {
                if ($usuario_id > 0) {
                    // Buscar si ya existe un customer_id para este usuario
                    try {
                        $conn = getDBConnection();
                        if (!$conn) {
                            throw new RuntimeException('No se pudo conectar a la base de datos');
                        }
                $stmt = $conn->prepare("SELECT stripe_customer_id FROM usuarios WHERE id = ?");
                if (!$stmt) {
                    throw new RuntimeException('Error al preparar consulta: ' . $conn->error);
                }
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();

                if ($user && !empty($user['stripe_customer_id'])) {
                    try {
                        $stripeCustomer = $stripe->customers->retrieve($user['stripe_customer_id']);
                    } catch (\Stripe\Exception\InvalidRequestException $e) {
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

                    // Guardar customer_id en la BD (opcional, no crítico si falla)
                    try {
                        $stmt = $conn->prepare("UPDATE usuarios SET stripe_customer_id = ? WHERE id = ?");
                        if ($stmt) {
                            $stmt->bind_param("si", $stripeCustomer->id, $usuario_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    } catch (\Exception $e) {
                        error_log("No se pudo guardar stripe_customer_id: " . $e->getMessage());
                        // Continuar aunque falle guardar el customer_id
                    }
                }
            } catch (\Exception $e) {
                error_log("Error al consultar BD para customer: " . $e->getMessage());
                // Si falla la BD, crear customer sin guardar en BD
                $stripeCustomer = $stripe->customers->create([
                    'email' => $correo,
                    'name' => $nombre,
                    'metadata' => [
                        'usuario_id' => (string)$usuario_id,
                        'direccion' => $direccion
                    ]
                ]);
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
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Stripe Customer Error: " . $e->getMessage());
        throw new RuntimeException('Error al crear cliente en Stripe: ' . $e->getMessage());
    }

    // Preparar items para metadata
    $itemsMetadata = [];
    foreach ($carrito as $item) {
        $productoId = $item['producto_id'] ?? $item['id'] ?? 0;
        $cantidad = $item['cantidad'] ?? 1;
        if ($productoId > 0) {
            $itemsMetadata[] = $productoId . ':' . $cantidad;
        }
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

    // Limpiar buffer antes de enviar JSON
    ob_clean();
    echo json_encode([
        'clientSecret' => $paymentIntent->client_secret,
        'paymentIntentId' => $paymentIntent->id
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (\Stripe\Exception\ApiErrorException $e) {
    $errorMsg = $e->getMessage();
    error_log("Stripe API Error: " . $errorMsg . " | Code: " . $e->getStripeCode() . " | File: " . $e->getFile() . ":" . $e->getLine());
    ob_clean();
    http_response_code(500);
    
    // Mensaje más amigable para el usuario
    $userMessage = 'Error al procesar el pago';
    if (strpos($errorMsg, 'No such customer') !== false) {
        $userMessage = 'Error al recuperar información del cliente';
    } elseif (strpos($errorMsg, 'Invalid API Key') !== false) {
        $userMessage = 'Error de configuración de Stripe. Contacta al administrador';
    } elseif (strpos($errorMsg, 'No API key provided') !== false) {
        $userMessage = 'Stripe no está configurado correctamente';
    }
    
    echo json_encode(['error' => $userMessage], JSON_UNESCAPED_UNICODE);
    exit;
} catch (\RuntimeException $e) {
    $errorMsg = $e->getMessage();
    error_log("Runtime Error en create-payment-intent: " . $errorMsg . " | File: " . $e->getFile() . ":" . $e->getLine());
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => $errorMsg], JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Exception $e) {
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("Error en create-payment-intent: " . $errorMsg . " | File: " . $errorFile . ":" . $errorLine . " | Trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    
    // En desarrollo, mostrar más detalles (cambiar en producción)
    $isDevelopment = (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false);
    $message = $isDevelopment 
        ? 'Error interno: ' . $errorMsg . ' (Ver logs para más detalles)'
        : 'Error interno del servidor. Por favor intenta de nuevo o contacta al soporte.';
    
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
} catch (\Throwable $e) {
    $errorMsg = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    error_log("Fatal Error en create-payment-intent: " . $errorMsg . " | File: " . $errorFile . ":" . $errorLine . " | Trace: " . $e->getTraceAsString());
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Error crítico del servidor. Contacta al administrador.'], JSON_UNESCAPED_UNICODE);
    exit;
}

