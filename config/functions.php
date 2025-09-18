<?php
include_once(__DIR__ . "/db.php");
require_once __DIR__ . "/mail.php"; // 📩 enviar correos

/* ============================================================
   Helpers de seguridad y normalización
   ============================================================ */
function _normalizeEmail(string $email): string {
    return strtolower(trim($email));
}

function _sanitizeRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    $permitidos = ['usuario', 'cajero', 'gestor', 'admin'];
    return in_array($rol, $permitidos, true) ? $rol : 'usuario';
}

/* ============================================================
   Usuarios
   ============================================================ */
function obtenerUsuarioPorEmail(string $email): ?array {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $sql  = "SELECT id, nombre, email, password, rol, estado, proveedor, provider_id, email_verificado 
             FROM usuarios WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

function registrarUsuario(string $nombre, string $email, ?string $password, string $rol = "usuario") {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);
    $rol   = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    if (obtenerUsuarioPorEmail($email)) return false; // evitar duplicados

    $hash  = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
    $token = bin2hex(random_bytes(32));

    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado, token_verificacion) 
             VALUES (?, ?, ?, ?, 'activo', 'manual', NULL, 0, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $email, $hash, $rol, $token);

    if (!$stmt->execute()) {
        error_log("❌ Error en registrarUsuario: " . $stmt->error);
        return false;
    }

    $id = $stmt->insert_id;

    // Enviar correo de confirmación
    if (function_exists('enviarCorreo')) {
        $verifyLink = ($_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace') . "/views/verify.php?token=$token";
        $subject = "Confirma tu cuenta en LumiSpace";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
            <p>Tu cuenta en <b>LumiSpace</b> fue creada con éxito.</p>
            <p>Confirma tu correo electrónico haciendo clic en:</p>
            <p style='text-align:center;'>
                <a href='$verifyLink' style='display:inline-block;background:#4CAF50;color:#fff;padding:10px 18px;border-radius:6px;'>Confirmar Correo</a>
            </p>
        ";
        enviarCorreo($email, $subject, $body);
    }

    return $id;
}

function insertarUsuario(string $nombre, string $email, ?string $password, string $rol = 'usuario') {
    $res = registrarUsuario($nombre, $email, $password, $rol);
    return $res === false ? false : (int)$res;
}

function registrarUsuarioSocial(string $nombre, string $email, ?string $providerId = null, string $rol = "usuario", string $proveedor = "google"): ?array {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);
    $rol   = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return null;

    $user = obtenerUsuarioPorEmail($email);
    if ($user) return $user; // ya existe

    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado) 
             VALUES (?, ?, NULL, ?, 'activo', ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $rol, $proveedor, $providerId);

    if (!$stmt->execute()) {
        error_log("❌ Error en registrarUsuarioSocial: " . $stmt->error);
        return obtenerUsuarioPorEmail($email);
    }

    $id = $stmt->insert_id;

    if (function_exists('enviarCorreo')) {
        $subject = "¡Bienvenido a LumiSpace con Google!";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
            <p>Te has registrado en <b>LumiSpace</b> con tu cuenta de Google.</p>
            <p>Puedes iniciar sesión con tu correo: <b>$email</b></p>
        ";
        enviarCorreo($email, $subject, $body);
    }

    return [
        "id"          => $id,
        "nombre"      => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        "email"       => $email,
        "rol"         => $rol,
        "estado"      => "activo",
        "proveedor"   => $proveedor,
        "provider_id" => $providerId,
        "email_verificado" => 1
    ];
}

/* ============================================================
   Ventas
   ============================================================ */
function registrarVenta(int $usuario_id, int $cajero_id, array $cart, float $desc_global_pct = 0, array $pagos = [], string $nota = ''): ?int {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=LumiSpace;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    try {
        $pdo->beginTransaction();

        $subtotal = 0;
        foreach ($cart as $it) {
            $precio   = (float)$it['precio'];
            $qty      = (int)$it['qty'];
            $descPct  = (float)($it['descPct'] ?? 0);
            $precioDesc = $precio * (1 - $descPct/100);
            $subtotal += $precioDesc * $qty;
        }
        $desc_monto = $subtotal * ($desc_global_pct/100);
        $base       = $subtotal - $desc_monto;
        $iva        = round($base * 0.16, 2);
        $total      = round($base + $iva, 2);

        $pEfectivo = (float)($pagos['efectivo'] ?? 0);
        $pTarjeta  = (float)($pagos['tarjeta'] ?? 0);
        $pTransf   = (float)($pagos['transferencia'] ?? 0);
        $metodo_principal = array_search(max([$pEfectivo,$pTarjeta,$pTransf]), [$pEfectivo,$pTarjeta,$pTransf]);
        $metodo_principal = ['efectivo','tarjeta','transferencia'][$metodo_principal];

        // Insert venta
        $stmt = $pdo->prepare("INSERT INTO ventas (usuario_id, cajero_id, subtotal, descuento_total, iva, total, pago_efectivo, pago_tarjeta, pago_transferencia, metodo_principal, nota, fecha)
                               VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())");
        $stmt->execute([$usuario_id, $cajero_id, $subtotal, $desc_monto, $iva, $total, $pEfectivo, $pTarjeta, $pTransf, $metodo_principal, $nota]);
        $venta_id = (int)$pdo->lastInsertId();

        // Insert detalle + actualizar stock
        $stmtDet = $pdo->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, nombre, precio, cantidad, descuento_pct, total_linea)
                                  VALUES (?,?,?,?,?,?,?)");
        $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
        foreach ($cart as $it) {
            $precio   = (float)$it['precio'];
            $qty      = (int)$it['qty'];
            $descPct  = (float)($it['descPct'] ?? 0);
            $precioDesc = $precio * (1 - $descPct/100);
            $totalLinea = round($precioDesc * $qty, 2);

            $stmtDet->execute([$venta_id, (int)$it['id'], $it['nombre'], $precio, $qty, $descPct, $totalLinea]);
            $stmtStock->execute([$qty, (int)$it['id']]);
        }

        $pdo->commit();
        return $venta_id;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("❌ Error en registrarVenta: ".$e->getMessage());
        return null;
    }
}
