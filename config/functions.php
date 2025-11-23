<?php

// Ruta base del proyecto (ajusta "LumiSpace" si tu carpeta tiene otro nombre)
if (!defined("BASE_URL")) {
    $envBase = getenv('BASE_URL');
    define("BASE_URL", $envBase !== false ? $envBase : "/LumiSpace/");
}

include_once(__DIR__ . "/db.php");
require_once __DIR__ . "/mail.php"; // 📩 enviar correos

/* ============================================================
   Helpers de seguridad y normalización
   ============================================================ */
function _normalizeEmail(string $email): string
{
    return strtolower(trim($email));
}

function _sanitizeRol(?string $rol): string
{
    $rol = strtolower(trim((string) $rol));
    $permitidos = ['usuario', 'cajero', 'gestor', 'admin'];
    return in_array($rol, $permitidos, true) ? $rol : 'usuario';
}

/* ============================================================
   🛒 CARRITO (versión con detalles de productos)
   ============================================================ */

// Agregar un producto al carrito
function carritoAgregar(int $producto_id, int $cantidad = 1): void
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $_SESSION['carrito'] = $_SESSION['carrito'] ?? [];

    // Si ya existe, sumar cantidad
    if (isset($_SESSION['carrito'][$producto_id])) {
        $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
    } else {
        $producto = getProductoById($producto_id);
        if ($producto) {
            $_SESSION['carrito'][$producto_id] = [
                'producto_id' => $producto['id'],
                'nombre' => $producto['nombre'],
                'precio' => (float) $producto['precio'],
                'imagen' => $producto['imagen'] ?? 'images/default.png',
                'cantidad' => $cantidad,
                'categoria' => $producto['categoria'] ?? '',
                'subtotal' => (float) $producto['precio'] * $cantidad
            ];
        }
    }
}

// Eliminar producto del carrito
function carritoEliminar(int $producto_id): void
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    unset($_SESSION['carrito'][$producto_id]);
}

// Vaciar todo el carrito
function carritoVaciar(): void
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    unset($_SESSION['carrito']);
}

// Obtener todos los productos del carrito (ya con detalles)
function carritoObtener(): array
{
    if (session_status() === PHP_SESSION_NONE)
        session_start();
    $carrito = $_SESSION['carrito'] ?? [];
    $resultado = [];

    foreach ($carrito as $id => $item) {
        if (!isset($item['precio'], $item['cantidad']))
            continue;

        $subtotal = $item['precio'] * $item['cantidad'];
        $resultado[] = [
            'producto_id' => $id,
            'nombre' => $item['nombre'],
            'precio' => $item['precio'],
            'imagen' => publicImageUrl($item['imagen']),
            'cantidad' => $item['cantidad'],
            'subtotal' => $subtotal
        ];
    }

    return $resultado;
}

// Calcular total general del carrito
function carritoTotal(): float
{
    $items = carritoObtener();
    $total = 0;
    foreach ($items as $i) {
        $total += $i['subtotal'];
    }
    return $total;
}

/* ============================================================
   Usuarios (auth + registro)
   ============================================================ */
function obtenerUsuarioPorEmail(string $email): ?array
{
    $conn = getDBConnection();
    $email = _normalizeEmail($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $sql = "SELECT id, nombre, email, password, rol, estado, proveedor, provider_id, email_verificado 
             FROM usuarios WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

function registrarUsuario(string $nombre, string $email, ?string $password, string $rol = "usuario")
{
    $conn = getDBConnection();
    $email = _normalizeEmail($email);
    $rol = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return false;
    if (obtenerUsuarioPorEmail($email))
        return false; // evitar duplicados

    $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
    $token = bin2hex(random_bytes(32));

    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado, token_verificacion) 
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

function insertarUsuario(string $nombre, string $email, ?string $password, string $rol = 'usuario')
{
    $res = registrarUsuario($nombre, $email, $password, $rol);
    return $res === false ? false : (int) $res;
}

function registrarUsuarioSocial(string $nombre, string $email, ?string $providerId = null, string $rol = "usuario", string $proveedor = "google"): ?array
{
    $conn = getDBConnection();
    $email = _normalizeEmail($email);
    $rol = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return null;

    $user = obtenerUsuarioPorEmail($email);
    if ($user)
        return $user; // ya existe

    $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado) 
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
        "id" => $id,
        "nombre" => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        "email" => $email,
        "rol" => $rol,
        "estado" => "activo",
        "proveedor" => $proveedor,
        "provider_id" => $providerId,
        "email_verificado" => 1
    ];
}

/* ============================================================
   REGISTRO DE VENTAS
   ============================================================ */

/**
 * Registrar una nueva venta (POS o en línea).
 */
function registrarVenta(
    ?int $cliente_id,
    ?int $usuario_id,
    array $items,
    string $metodo_pago,
    float $total
): ?int {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // Calcular cantidad total
        $cantidad_total = 0;
        foreach ($items as $item) {
            $cantidad_total += (int) $item['cantidad'];
        }

        // 1. Insertar venta
        $stmt = $conn->prepare("
            INSERT INTO ventas (cliente_id, usuario_id, metodo_pago, total, cantidad_total, fecha)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            throw new Exception("Error prepare ventas: " . $conn->error);
        }

        // s = string, i = integer, d = double
        $stmt->bind_param(
            "iisdi",
            $cliente_id,
            $usuario_id,
            $metodo_pago,
            $total,
            $cantidad_total
        );
        $stmt->execute();
        $venta_id = $stmt->insert_id;
        $stmt->close();

        // 2. Insertar detalle de la venta
        $stmtDetalle = $conn->prepare("
            INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        if (!$stmtDetalle) {
            throw new Exception("Error prepare detalle: " . $conn->error);
        }

        foreach ($items as $item) {
            $cantidad = (int) $item['cantidad'];
            $precio = (float) $item['precio'];
            $subtotal = $cantidad * $precio;

            $stmtDetalle->bind_param(
                "iiidd",
                $venta_id,
                $item['producto_id'],
                $cantidad,
                $precio,
                $subtotal
            );
            $stmtDetalle->execute();

            // 3. Actualizar inventario (salida)
            registrarMovimiento(
                $item['producto_id'],
                $usuario_id ?? 0,
                'salida',
                $cantidad,
                "Venta #$venta_id"
            );
        }
        $stmtDetalle->close();

        // 4. Registrar pago
        registrarPago($venta_id, $metodo_pago, $total);

        $conn->commit();
        return $venta_id;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("❌ registrarVenta fallo: " . $e->getMessage());
        return null;
    }
}


/* ============================================================
   CONSULTAS DE VENTAS (REPORTES Y DASHBOARD)
   ============================================================ */

// Total de ventas de hoy
function getVentasHoy(): float
{
    $conn = getDBConnection();
    $sql = "SELECT IFNULL(SUM(total),0) as total FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res = $conn->query($sql);
    return (float) ($res->fetch_assoc()['total'] ?? 0);
}

// Resumen de hoy
function getResumenHoy(): array
{
    $conn = getDBConnection();
    $sql = "SELECT 
                IFNULL(SUM(total),0) as total,
                COUNT(id) as transacciones,
                IFNULL(SUM(cantidad_total),0) as productos
             FROM ventas
             WHERE DATE(fecha)=CURDATE()";
    $res = $conn->query($sql);
    return $res ? $res->fetch_assoc() : ['total' => 0, 'transacciones' => 0, 'productos' => 0];
}

// Últimas N ventas
function getVentasRecientes(int $limit = 6): array
{
    $conn = getDBConnection();
    $limit = max(1, (int) $limit); // siempre mínimo 1

    $sql = "
        SELECT v.id, 
               c.nombre AS cliente, 
               v.total, 
               v.fecha,
               GROUP_CONCAT(p.nombre SEPARATOR ', ') AS productos
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
        LEFT JOIN productos p ON dv.producto_id = p.id
        GROUP BY v.id
        ORDER BY v.fecha DESC
        LIMIT $limit
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare: " . $conn->error);
        return [];
    }
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Clientes únicos atendidos hoy
function getClientesUnicosHoy(): int
{
    $conn = getDBConnection();
    $sql = "SELECT COUNT(DISTINCT cliente_id) as clientes FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res = $conn->query($sql);
    return (int) ($res->fetch_assoc()['clientes'] ?? 0);
}

// Corte de caja por método de pago (hoy)
function getCorteCajaHoy(): array
{
    $conn = getDBConnection();
    $sql = "SELECT metodo_pago, SUM(total) as total 
             FROM ventas 
             WHERE DATE(fecha)=CURDATE()
             GROUP BY metodo_pago";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Ventas agrupadas por categoría (hoy)
function getVentasPorCategoria(): array
{
    $conn = getDBConnection();
    $sql = "SELECT cat.nombre as categoria, SUM(dv.subtotal) as total
             FROM detalle_ventas dv
             INNER JOIN productos p ON dv.producto_id = p.id
             INNER JOIN categorias cat ON p.categoria_id = cat.id
             INNER JOIN ventas v ON dv.venta_id = v.id
             WHERE DATE(v.fecha) = CURDATE()
             GROUP BY cat.id";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Detalle completo de una venta
function getVentaById(int $venta_id): ?array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT v.*, c.nombre as cliente, u.nombre as cajero
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id=c.id
        LEFT JOIN usuarios u ON v.usuario_id=u.id
        WHERE v.id=?
        LIMIT 1
    ");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();

    if (!$venta)
        return null;

    $stmt2 = $conn->prepare("
        SELECT dv.*, p.nombre as producto
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id=p.id
        WHERE dv.venta_id=?
    ");
    $stmt2->bind_param("i", $venta_id);
    $stmt2->execute();
    $venta['items'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    return $venta;
}
/**
 * Categorías más vendidas del mes
 */
function getCategoriasMasVendidasMes(int $limit = 10): array
{
    $conn = getDBConnection();

    // Sanitizar límite
    $limit = (int) $limit;

    $sql = "
        SELECT c.id, c.nombre AS categoria,
               SUM(dv.cantidad) AS total_vendido,
               SUM(dv.cantidad * dv.precio_unitario) AS ingresos
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        JOIN categorias c ON p.categoria_id = c.id
        JOIN ventas v ON dv.venta_id = v.id
        WHERE MONTH(v.fecha) = MONTH(NOW())
          AND YEAR(v.fecha) = YEAR(NOW())
        GROUP BY c.id
        ORDER BY total_vendido DESC
        LIMIT $limit
    ";

    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}



/* ============================================================
   REPORTES MENSUALES
   ============================================================ */

// Ventas por mes (para gráficas del dashboard)
function getVentasMensuales(): array
{
    $conn = getDBConnection();
    $sql = "SELECT MONTHNAME(fecha) as mes, SUM(total) as total 
             FROM ventas 
             WHERE YEAR(fecha)=YEAR(NOW())
             GROUP BY MONTH(fecha)
             ORDER BY MONTH(fecha)";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Ventas por día en el mes actual
function getVentasPorDiaMesActual(): array
{
    $conn = getDBConnection();
    $sql = "SELECT DAY(fecha) as dia, SUM(total) as total
            FROM ventas
            WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())
            GROUP BY DAY(fecha)
            ORDER BY dia ASC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


/* ============================================================
   PRODUCTOS MÁS VENDIDOS
   ============================================================ */

// Top N productos más vendidos (general)
function getProductosMasVendidos(int $limit = 10): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT p.id, p.nombre, p.precio, p.imagen,
               SUM(dv.cantidad) as total_vendido,
               SUM(dv.subtotal) as ingresos
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        JOIN ventas v ON dv.venta_id = v.id
        GROUP BY p.id
        ORDER BY total_vendido DESC
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

// Top N productos más vendidos del mes actual
function getProductosMasVendidosMes(int $limit = 10): array
{
    $conn = getDBConnection();

    // Sanitizar el límite para evitar SQL injection
    $limit = (int) $limit;

    $sql = "
        SELECT p.id, p.nombre, p.precio, p.imagen,
               SUM(dv.cantidad) AS total_vendido,
               SUM(dv.cantidad * dv.precio_unitario) AS ingresos
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        JOIN ventas v ON dv.venta_id = v.id
        WHERE MONTH(v.fecha) = MONTH(NOW()) 
          AND YEAR(v.fecha) = YEAR(NOW())
        GROUP BY p.id
        ORDER BY total_vendido DESC
        LIMIT $limit
    ";

    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


/* ============================================================
   Funciones para el Dashboard Admin
<?php
/* ============================================================
   🔧 FUNCIONES ADMIN USUARIOS (Panel)
   ============================================================ */

function getTodosLosUsuarios(): array
{
    $conn = getDBConnection();
    $sql = "SELECT 
                id, 
                nombre, 
                email, 
                rol, 
                estado, 
                proveedor, 
                email_verificado, 
                fecha_registro 
            FROM usuarios 
            ORDER BY id DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


function getUsuarioPorId(int $id): ?array
{
    $conn = getDBConnection();

    $sql = "SELECT 
                id,
                nombre,
                email,
                password,
                telefono,
                direccion,
                rol,
                puesto,
                num_empleado,
                fecha_ingreso,
                salario,
                sucursal,
                estado,
                proveedor,
                provider_id,
                email_verificado,
                token_verificacion,
                reset_token,
                reset_expira,
                ultimo_acceso,
                fecha_registro
            FROM usuarios
            WHERE id = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error en prepare getUsuarioPorId: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $usuario = $res->fetch_assoc() ?: null;

    $stmt->close();
    return $usuario;
}


function actualizarUsuarioAdmin(int $id, string $nombre, string $email, string $rol, string $estado): bool
{
    $conn = getDBConnection();
    $sql = "UPDATE usuarios SET nombre=?, email=?, rol=?, estado=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $email, $rol, $estado, $id);
    return $stmt->execute();
}

function eliminarUsuarioAdmin(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

/* ============================================================
   Funciones para conexión
   ============================================================ */
if (!function_exists('getDBConnection')) {
    function getDBConnection(): mysqli
    {
        static $conn;
        if ($conn instanceof mysqli)
            return $conn;

        $conn = new mysqli("localhost", "root", "", "lumispace");
        if ($conn->connect_error) {
            die("❌ Error de conexión: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        return $conn;
    }
}

/* ============================================================
   Funciones para el Dashboard Admin
   ============================================================ */
function getTotalUsuarios(): int
{
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $res = $conn->query($sql);
    return (int) ($res->fetch_assoc()['total'] ?? 0);
}

function getTotalGestores(): int
{
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'gestor'";
    $res = $conn->query($sql);

    if (!$res) {
        error_log("❌ Error SQL en getTotalGestores(): " . $conn->error);
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}
function getTotalPorRol(string $rol): int
{
    $conn = getDBConnection();

    // 🔍 Verificar conexión
    if (!$conn) {
        error_log("❌ Error: conexión a BD no disponible en getTotalPorRol()");
        return 0;
    }

    // 🔹 Usar el campo correcto: "roles"
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE roles = ?");
    if (!$stmt) {
        error_log("❌ Error en prepare getTotalPorRol(): " . $conn->error);
        return 0;
    }

    // Enlazar parámetro
    $stmt->bind_param("s", $rol);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res) {
        error_log("⚠️ Error al ejecutar getTotalPorRol(): " . $stmt->error);
        return 0;
    }

    $row = $res->fetch_assoc();
    return (int) ($row['total'] ?? 0);
}

function getTotalProductos(): int
{
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM productos";
    $res = $conn->query($sql);
    return (int) ($res->fetch_assoc()['total'] ?? 0);
}

function getIngresosMes(): float
{
    $conn = getDBConnection();
    $sql = "SELECT SUM(total) as ingresos 
            FROM ventas 
            WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())";
    $res = $conn->query($sql);
    return (float) ($res->fetch_assoc()['ingresos'] ?? 0);
}

function getUsuariosRecientes(int $limit = 5): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT nombre,email,fecha_registro FROM usuarios ORDER BY fecha_registro DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function getInventarioResumen(): array
{
    $conn = getDBConnection();
    $sql = "
        SELECT c.nombre AS categoria, 
               COALESCE(SUM(p.stock), 0) AS cantidad
        FROM categorias c
        LEFT JOIN productos p ON p.categoria_id = c.id
        GROUP BY c.id, c.nombre
        ORDER BY c.nombre ASC
    ";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


function getUsuariosMensuales(): array
{
    $conn = getDBConnection();
    $sql = "SELECT MONTHNAME(fecha_registro) as mes, COUNT(*) as total 
            FROM usuarios 
            WHERE YEAR(fecha_registro)=YEAR(NOW())
            GROUP BY MONTH(fecha_registro)";
    $res = $conn->query($sql);
    return $res->fetch_all(MYSQLI_ASSOC);
}

/* ============================================================
   Helpers de formato
   ============================================================ */
function formatCurrency($amount)
{
    return "$" . number_format($amount, 2, '.', ',');
}

function timeAgo($date)
{
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    if ($diff < 60)
        return "justo ahora";
    $minutes = floor($diff / 60);
    if ($minutes < 60)
        return "hace $minutes min";
    $hours = floor($minutes / 60);
    if ($hours < 24)
        return "hace $hours horas";
    $days = floor($hours / 24);
    return "hace $days días";
}
/* ===== Helpers de introspección ===== */
// config/functions.php

if (!function_exists('tableExists')) {
    function tableExists(mysqli $conn, string $table): bool
    {
        // Sanear nombre de tabla por seguridad básica
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->bind_param("s", $table);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('columnExists')) {
    function columnExists(mysqli $conn, string $table, string $column): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $stmt = $conn->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $stmt->bind_param("s", $column);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res && $res->num_rows > 0;
    }
}

/* ===== URL pública de imagen (admin sube a /images/productos) ===== */
function publicImageUrl(?string $raw): string
{
    $r = trim((string) $raw);
    $base = rtrim(BASE_URL, '/') . '/';
    if ($r === '')
        return $base . 'images/default.png';
    if (preg_match('#^https?://#i', $r))
        return $r;
    if (stripos($r, 'images/') === 0)
        return $base . $r;
    return $base . 'images/productos/' . $r;
}

/* ============================================================
   Logs y Pagos
   ============================================================ */
function registrarLog($usuario_id, $accion, $ip = ''): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id,accion,ip) VALUES (?,?,?)");
    $stmt->bind_param("iss", $usuario_id, $accion, $ip);
    return $stmt->execute();
}

function registrarPago($venta_id, $metodo, $monto): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO pagos (venta_id,metodo,monto) VALUES (?,?,?)");
    $stmt->bind_param("isd", $venta_id, $metodo, $monto);
    return $stmt->execute();
}

function getPagosPorVenta($venta_id): array
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM pagos WHERE venta_id=?");
    $stmt->bind_param("i", $venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

/* ============================================================
   Clientes
   ============================================================ */
function getClientes(): array
{
    $conn = getDBConnection();
    $res = $conn->query("SELECT * FROM clientes ORDER BY creado_en DESC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function insertarCliente($nombre, $email, $telefono, $direccion): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO clientes (nombre,email,telefono,direccion) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss", $nombre, $email, $telefono, $direccion);
    return $stmt->execute();
}

/* ============================================================
   Inventario
   ============================================================ */
function getInventarioByProducto(int $producto_id): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            i.id AS inventario_id,
            i.producto_id,
            p.nombre AS producto,
            i.sucursal,
            i.cantidad,
            p.precio,
            (i.cantidad * p.precio) AS valor_total
        FROM inventario i
        INNER JOIN productos p ON i.producto_id = p.id
        WHERE i.producto_id = ?
        ORDER BY i.sucursal ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error en getInventarioByProducto prepare(): " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Obtener todo el inventario (por producto y sucursal)
 */
function getInventario(): array
{
    $conn = getDBConnection();
    $sql = "
        SELECT 
            i.id,
            i.producto_id,
            p.nombre AS producto,
            IFNULL(i.sucursal, 'Principal') AS sucursal,
            i.cantidad,
            p.precio
        FROM inventario i
        JOIN productos p ON p.id = i.producto_id
        ORDER BY p.nombre ASC
    ";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


/**
 * Obtener un registro de inventario por ID
 */
function getInventarioById(int $id): ?array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            i.id,
            i.producto_id,
            p.nombre AS producto,
            IFNULL(i.sucursal, 'Principal') AS sucursal,
            IFNULL(i.cantidad, 0) AS cantidad,
            p.precio
        FROM inventario i
        INNER JOIN productos p ON i.producto_id = p.id
        WHERE i.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("getInventarioById error: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    return $row ?: null;
}

/**
 * Actualizar inventario
 */
function actualizarInventario(int $id, int $producto_id, int $cantidad, string $sucursal): bool
{
    $conn = getDBConnection();

    $sql = "UPDATE inventario SET cantidad=?, sucursal=? WHERE id=? AND producto_id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("actualizarInventario error: " . $conn->error);
        return false;
    }

    $stmt->bind_param("isii", $cantidad, $sucursal, $id, $producto_id);
    return $stmt->execute();
}


function insertarInventario(int $producto_id, int $cantidad, string $sucursal = 'Principal'): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO inventario(producto_id, sucursal, cantidad) VALUES(?,?,?)");
    $stmt->bind_param("isi", $producto_id, $sucursal, $cantidad);
    return $stmt->execute();
}


function eliminarInventario(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM inventario WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}


/* ============================
   MOVIMIENTOS DE INVENTARIO
   ============================ */
function registrarMovimiento(
    int $producto_id,
    int $usuario_id,
    string $tipo,
    int $cantidad,
    string $motivo = '',
    string $sucursal = 'Principal'
): bool {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // 1. Insertar el movimiento en la bitácora con sucursal
        $stmt = $conn->prepare("
            INSERT INTO movimientos_inventario 
            (producto_id, usuario_id, tipo, cantidad, motivo, sucursal, creado_en) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            throw new Exception("Error al preparar INSERT: " . $conn->error);
        }
        $stmt->bind_param("iissss", $producto_id, $usuario_id, $tipo, $cantidad, $motivo, $sucursal);
        $stmt->execute();

        // 2. Consultar stock actual
        $stmt2 = $conn->prepare("SELECT cantidad FROM inventario WHERE producto_id=? AND sucursal=? LIMIT 1");
        if (!$stmt2) {
            throw new Exception("Error al preparar SELECT: " . $conn->error);
        }
        $stmt2->bind_param("is", $producto_id, $sucursal);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stockActual = $res ? (int) $res['cantidad'] : 0;

        // 3. Calcular nuevo stock
        if ($tipo === 'entrada') {
            $nuevoStock = $stockActual + $cantidad;
        } elseif ($tipo === 'salida') {
            $nuevoStock = max(0, $stockActual - $cantidad);
        } else { // ajuste
            $nuevoStock = $cantidad;
        }

        // 4. Insertar o actualizar inventario
        if ($res) {
            $stmt3 = $conn->prepare("UPDATE inventario SET cantidad=? WHERE producto_id=? AND sucursal=?");
            if (!$stmt3)
                throw new Exception("Error en UPDATE inventario: " . $conn->error);
            $stmt3->bind_param("iis", $nuevoStock, $producto_id, $sucursal);
            $stmt3->execute();
        } else {
            $stmt3 = $conn->prepare("INSERT INTO inventario (producto_id, sucursal, cantidad) VALUES (?, ?, ?)");
            if (!$stmt3)
                throw new Exception("Error en INSERT inventario: " . $conn->error);
            $stmt3->bind_param("isi", $producto_id, $sucursal, $nuevoStock);
            $stmt3->execute();
        }

        // 5. Confirmar
        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("registrarMovimiento() fallo: " . $e->getMessage());
        return false;
    }
}
function getMovimientoById(int $id): ?array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            m.id,
            m.producto_id,
            p.nombre AS producto,
            m.tipo,
            m.cantidad,
            m.motivo,
            m.usuario_id,
            u.nombre AS usuario,
            m.sucursal,
            m.creado_en
        FROM movimientos_inventario m
        JOIN productos p ON m.producto_id = p.id
        JOIN usuarios  u ON m.usuario_id  = u.id
        WHERE m.id = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("getMovimientoById() error: " . $conn->error);
        return null;
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $res ?: null;
}



function actualizarMovimiento(
    int $id,
    int $producto_id,
    string $tipo,
    int $cantidad,
    string $motivo,
    int $usuario_id
): bool {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // 🔹 Obtener movimiento anterior
        $stmt = $conn->prepare("SELECT cantidad, tipo, sucursal FROM movimientos_inventario WHERE id=? LIMIT 1");
        if (!$stmt)
            throw new Exception("Error SELECT movimiento: " . $conn->error);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$old)
            throw new Exception("Movimiento no encontrado");

        $oldCantidad = (int) $old['cantidad'];
        $oldTipo = $old['tipo'];
        $sucursal = $old['sucursal'];

        // 🔹 Revertir efecto anterior
        if ($oldTipo === 'entrada') {
            $ajusteAnterior = -$oldCantidad;
        } elseif ($oldTipo === 'salida') {
            $ajusteAnterior = $oldCantidad;
        } else { // ajuste
            $ajusteAnterior = 0;
        }

        // 🔹 Nuevo ajuste
        if ($tipo === 'entrada') {
            $ajusteNuevo = $cantidad;
        } elseif ($tipo === 'salida') {
            $ajusteNuevo = -$cantidad;
        } else { // ajuste manual
            $ajusteNuevo = $cantidad - $oldCantidad;
        }

        $ajusteFinal = $ajusteAnterior + $ajusteNuevo;

        // 🔹 Actualizar movimiento
        $stmt = $conn->prepare("
            UPDATE movimientos_inventario
            SET tipo=?, cantidad=?, motivo=?, usuario_id=?, creado_en=NOW()
            WHERE id=?
        ");
        if (!$stmt)
            throw new Exception("Error UPDATE movimiento: " . $conn->error);
        $stmt->bind_param("sisii", $tipo, $cantidad, $motivo, $usuario_id, $id);
        $stmt->execute();
        $stmt->close();

        // 🔹 Actualizar inventario
        $stmt = $conn->prepare("SELECT id, cantidad FROM inventario WHERE producto_id=? AND sucursal=? LIMIT 1");
        if (!$stmt)
            throw new Exception("Error SELECT inventario: " . $conn->error);
        $stmt->bind_param("is", $producto_id, $sucursal);
        $stmt->execute();
        $inv = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($inv) {
            $nuevoStock = max(0, $inv['cantidad'] + $ajusteFinal);
            $stmt = $conn->prepare("UPDATE inventario SET cantidad=? WHERE id=?");
            if (!$stmt)
                throw new Exception("Error UPDATE inventario: " . $conn->error);
            $stmt->bind_param("ii", $nuevoStock, $inv['id']);
            $stmt->execute();
            $stmt->close();
        }

        // 🔹 (opcional) Actualizar columna stock en productos
        if ($ajusteFinal !== 0) {
            $stmt = $conn->prepare("UPDATE productos SET stock = GREATEST(0, stock + ?) WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("ii", $ajusteFinal, $producto_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("actualizarMovimiento() error: " . $e->getMessage());
        return false;
    }
}


function eliminarMovimiento(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM movimientos_inventario WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

function getMovimientos(): array
{
    $conn = getDBConnection();
    $sql = "SELECT m.*, p.nombre AS producto, u.nombre AS usuario
            FROM movimientos_inventario m
            JOIN productos p ON m.producto_id=p.id
            JOIN usuarios u ON m.usuario_id=u.id
            ORDER BY m.creado_en DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}



/* ============================================================
   Productos
    ============================================================ */
/* ===== Detalle seguro por ID (se adapta a tu BD) ===== */
function getProductoPorId(int $id): ?array
{
    $conn = getDBConnection();
    if ($id <= 0)
        return null;

    $joinCat = $joinProv = "";
    $selCat = "NULL AS categoria_id, NULL AS categoria";
    $selProv = "NULL AS marca";

    if (tableExists($conn, "categorias") && columnExists($conn, "productos", "categoria_id")) {
        $joinCat = " LEFT JOIN categorias c ON p.categoria_id = c.id";
        $selCat = "c.id AS categoria_id, c.nombre AS categoria";
    }
    if (tableExists($conn, "proveedores") && columnExists($conn, "productos", "proveedor_id")) {
        $joinProv = " LEFT JOIN proveedores pr ON p.proveedor_id = pr.id";
        $selProv = "pr.nombre AS marca";
    } elseif (tableExists($conn, "marcas") && columnExists($conn, "productos", "marca_id")) {
        $joinProv = " LEFT JOIN marcas m ON p.marca_id = m.id";
        $selProv = "m.nombre AS marca";
    }

    $sql = "
        SELECT 
            p.id, p.nombre, p.descripcion, p.precio, p.precio_original, p.descuento,
            p.stock_inicial,
            -- 👇 cálculo de stock real
            (p.stock_inicial 
              + IFNULL((SELECT SUM(cantidad) FROM inventario i WHERE i.producto_id = p.id AND i.tipo = 'entrada'), 0)
              - IFNULL((SELECT SUM(cantidad) FROM inventario i WHERE i.producto_id = p.id AND i.tipo = 'salida'), 0)
            ) AS stock_real,
            p.imagen, 
            $selCat, 
            $selProv
        FROM productos p
        {$joinCat}
        {$joinProv}
        WHERE p.id = ? 
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("getProductoPorId prepare: {$conn->error} | SQL: {$sql}");
        return null;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $prod = $stmt->get_result()->fetch_assoc();
    return $prod ?: null;
}

/* Thumbs adicionales si tienes la tabla producto_imagenes */
function getProductoImagenes(int $producto_id): array
{
    $conn = getDBConnection();
    if (!tableExists($conn, "producto_imagenes"))
        return [];
    $stmt = $conn->prepare("SELECT ruta FROM producto_imagenes WHERE producto_id=? ORDER BY orden ASC, id ASC");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $rs = $stmt->get_result();
    $imgs = [];
    while ($row = $rs->fetch_assoc())
        $imgs[] = publicImageUrl((string) $row['ruta']);
    return $imgs;
}

function getProductosPublicos(int $limit = 12): array
{
    $conn = getDBConnection();

    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.imagen, 
                   c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.estado = 'activo'
            ORDER BY p.id DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error en prepare getProductosPublicos: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}


/**
 * Obtener todos los productos con su stock REAL (suma de inventario por sucursales).
 */
function getProductos(): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            p.id,
            p.nombre,
            p.descripcion,
            p.precio,
            p.imagen,
            p.creado_en,
            c.nombre AS categoria,
            pr.nombre AS proveedor,
            COALESCE(SUM(i.cantidad),0) AS stock_real
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
        LEFT JOIN inventario i ON p.id = i.producto_id
        GROUP BY 
            p.id, p.nombre, p.descripcion, p.precio, p.imagen, p.creado_en, 
            c.nombre, pr.nombre
        ORDER BY p.id DESC
    ";

    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


/**
 * Obtener un producto por su ID con su stock REAL (suma de inventario).
 */
function getProductoById(int $id): ?array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            p.id,
            p.nombre,
            p.descripcion,
            p.precio,
            -- 👇 stock REAL desde inventario
            COALESCE(SUM(i.cantidad), 0) AS stock_real,
            c.nombre AS categoria,
            pr.nombre AS proveedor,
            p.imagen,
            p.creado_en
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
        LEFT JOIN inventario i ON p.id = i.producto_id
        WHERE p.id = ?
        GROUP BY 
            p.id, p.nombre, p.descripcion, p.precio, 
            c.nombre, pr.nombre, p.imagen, p.creado_en
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_assoc() : null;
}


// 🔴 Eliminar producto definitivamente
function eliminarProducto(int $id): bool
{
    $conn = getDBConnection();

    // eliminar inventario asociado
    $conn->query("DELETE FROM inventario WHERE producto_id=$id");

    $stmt = $conn->prepare("DELETE FROM productos WHERE id=?");
    if (!$stmt) {
        error_log("❌ Error en eliminarProducto prepare(): " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}



function insertarProducto(
    string $nombre,
    string $descripcion,
    float $precio,
    int $stockInicial,
    int $categoria_id,
    ?int $proveedor_id = null,
    ?string $imagenPath = null
): bool {
    $conn = getDBConnection();

    $sql = "INSERT INTO productos (nombre, descripcion, precio, categoria_id, proveedor_id, imagen) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("❌ Error en insertarProducto prepare(): " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssdiss", $nombre, $descripcion, $precio, $categoria_id, $proveedor_id, $imagenPath);

    if ($stmt->execute()) {
        $producto_id = $conn->insert_id;
        $stmt->close();

        // ✅ Registrar stock inicial directamente en inventario
        if ($stockInicial > 0) {
            $stmt2 = $conn->prepare("INSERT INTO inventario (producto_id, sucursal, cantidad) VALUES (?, 'Principal', ?)");
            $stmt2->bind_param("ii", $producto_id, $stockInicial);
            $stmt2->execute();
            $stmt2->close();
        }

        return true;
    }

    return false;
}


function actualizarProducto(
    int $id,
    string $nombre,
    string $descripcion,
    float $precio,
    int $stock,
    int $categoria_id,
    ?int $proveedor_id = null,
    ?string $imagenPath = null
): bool {
    $conn = getDBConnection();

    if ($imagenPath) {
        $sql = "UPDATE productos 
                SET nombre=?, descripcion=?, precio=?, categoria_id=?, proveedor_id=?, imagen=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $categoria_id, $proveedor_id, $imagenPath, $id);
    } else {
        $sql = "UPDATE productos 
                SET nombre=?, descripcion=?, precio=?, categoria_id=?, proveedor_id=? 
                WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $categoria_id, $proveedor_id, $id);
    }

    if ($stmt && $stmt->execute()) {
        $stmt->close();

        // ✅ Actualizar inventario para el producto
        $stmt2 = $conn->prepare("SELECT id FROM inventario WHERE producto_id=? AND sucursal='Principal' LIMIT 1");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if ($res) {
            // actualizar stock existente
            $stmt3 = $conn->prepare("UPDATE inventario SET cantidad=? WHERE producto_id=? AND sucursal='Principal'");
            $stmt3->bind_param("ii", $stock, $id);
            $stmt3->execute();
            $stmt3->close();
        } else {
            // insertar si no existía en inventario
            $stmt3 = $conn->prepare("INSERT INTO inventario (producto_id, sucursal, cantidad) VALUES (?, 'Principal', ?)");
            $stmt3->bind_param("ii", $id, $stock);
            $stmt3->execute();
            $stmt3->close();
        }

        return true;
    }

    return false;
}

function inactivarProducto(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE productos SET estado='inactivo' WHERE id=?");
    if (!$stmt) {
        error_log("❌ Error en inactivarProducto prepare(): " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
function getProductosPorCategoria(?int $categoria_id = null, int $limit = 12): array
{
    $conn = getDBConnection();
    $limit = max(1, (int) $limit);

    // ==============================
    // 🔍 Detectar columnas existentes
    // ==============================
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $res = $conn->query("SHOW COLUMNS FROM productos");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cols[] = $row['Field'];
            }
        }
    }

    // ==============================
    // 🧩 Columnas seguras a seleccionar
    // ==============================
    $select = ['p.id', 'p.nombre'];
    foreach (['descripcion', 'precio', 'precio_original', 'descuento', 'stock', 'imagen'] as $c) {
        if (in_array($c, $cols))
            $select[] = "p.$c";
    }

    // ==============================
    // 📆 Detectar columna de orden (reciente)
    // ==============================
    static $orderCol = null;
    if ($orderCol === null) {
        $candidatas = ['creado_en', 'created_at', 'fecha_creacion', 'fecha'];
        $orderCol = 'id';
        foreach ($candidatas as $col) {
            if (in_array($col, $cols)) {
                $orderCol = $col;
                break;
            }
        }
    }

    // ==============================
    // 🏷️ Detectar si hay categoria_id
    // ==============================
    $hasCategoriaId = in_array('categoria_id', $cols);

    // ==============================
    // 🏗️ Construcción base del SQL
    // ==============================
    if ($hasCategoriaId) {
        // FK con categorias
        $sqlBase = "
            SELECT " . implode(", ", $select) . ", c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
        ";
    } else {
        // Campo texto categoría
        $catCol = in_array('categoria', $cols) ? 'p.categoria' : "'' AS categoria";
        $sqlBase = "SELECT " . implode(", ", $select) . ", $catCol FROM productos p";
    }

    // ==============================
    // ⚙️ Aplicar filtros
    // ==============================
    if ($categoria_id) {
        if ($hasCategoriaId) {
            $sql = $sqlBase . " WHERE p.categoria_id = ? ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $categoria_id, $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            }
        } else {
            $sql = $sqlBase . "
                WHERE p.categoria = (SELECT nombre FROM categorias WHERE id = ? LIMIT 1)
                ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $categoria_id, $limit);
                $stmt->execute();
                $res = $stmt->get_result();
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            }
        }
    }

    // ==============================
    // 🧾 Sin filtro de categoría
    // ==============================
    $sql = $sqlBase . " ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT " . (int) $limit;
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
function getProductosCatalogo(?string $categoria = null, ?int $limit = null): array
{
    $conn = getDBConnection();

    // =====================================
    // 🔍 Verificar columnas opcionales (una sola vez)
    // =====================================
    static $hasCategoriaId = null, $hasInventario = null;
    if ($hasCategoriaId === null || $hasInventario === null) {
        $cols = [];
        $res = $conn->query("SHOW COLUMNS FROM productos");
        if ($res) {
            while ($r = $res->fetch_assoc())
                $cols[] = $r['Field'];
        }
        $hasCategoriaId = in_array('categoria_id', $cols);
        $hasInventario = $conn->query("SHOW TABLES LIKE 'inventario'")?->num_rows > 0;
    }

    // =====================================
    // 🧩 Selección dinámica de columnas
    // =====================================
    $select = "p.id, p.nombre";
    $select .= in_array('precio', $cols) ? ", p.precio" : "";
    $select .= in_array('imagen', $cols) ? ", p.imagen" : "";
    $select .= $hasCategoriaId ? ", c.nombre AS categoria" : "";

    // Si hay tabla inventario → calcular stock real
    $select .= $hasInventario ? ", COALESCE(SUM(i.cantidad), 0) AS stock_real" : "";

    // =====================================
    // 🏗️ Construcción base del SQL
    // =====================================
    $sql = "SELECT $select FROM productos p";

    if ($hasCategoriaId) {
        $sql .= " LEFT JOIN categorias c ON p.categoria_id = c.id";
    }
    if ($hasInventario) {
        $sql .= " LEFT JOIN inventario i ON p.id = i.producto_id";
    }

    // =====================================
    // ⚙️ Filtro opcional por categoría
    // =====================================
    $params = [];
    $types = "";
    if ($categoria && $hasCategoriaId) {
        $sql .= " WHERE c.nombre = ?";
        $params[] = $categoria;
        $types .= "s";
    }

    // =====================================
    // 📊 Agrupar y ordenar
    // =====================================
    $group = "p.id, p.nombre";
    if (in_array('precio', $cols))
        $group .= ", p.precio";
    if (in_array('imagen', $cols))
        $group .= ", p.imagen";
    if ($hasCategoriaId)
        $group .= ", c.nombre";

    $sql .= " GROUP BY $group ORDER BY p.nombre ASC";

    // =====================================
    // 🚦 Límite opcional
    // =====================================
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . (int) $limit;
    }

    // =====================================
    // 🧠 Ejecutar consulta segura
    // =====================================
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ getProductosCatalogo() prepare error: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("❌ getProductosCatalogo() execute error: " . $stmt->error);
        return [];
    }

    $res = $stmt->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


/* ============================================================
   Proveedores
   ============================================================ */
function getProveedores(): array
{
    $conn = getDBConnection();
    $sql = "SELECT * FROM proveedores ORDER BY nombre";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function insertarProveedor(string $nombre, ?string $contacto, ?string $telefono, ?string $email, ?string $direccion): bool
{
    $conn = getDBConnection();
    $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("❌ Error en prepare(): " . $conn->error);
        return false;
    }

    // Si vienen vacíos, los mandamos como NULL
    $contacto = !empty($contacto) ? $contacto : null;
    $telefono = !empty($telefono) ? $telefono : null;
    $email = !empty($email) ? $email : null;
    $direccion = !empty($direccion) ? $direccion : null;

    $stmt->bind_param("sssss", $nombre, $contacto, $telefono, $email, $direccion);

    if (!$stmt->execute()) {
        error_log("❌ Error en execute(): " . $stmt->error);
        return false;
    }

    return true;
}
function actualizarProveedor(int $id, string $nombre, ?string $contacto, ?string $telefono, ?string $email, ?string $direccion): bool
{
    $conn = getDBConnection();
    $sql = "UPDATE proveedores 
            SET nombre=?, contacto=?, telefono=?, email=?, direccion=? 
            WHERE id=?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("❌ Error en prepare(): " . $conn->error);
        return false;
    }

    // Si vienen vacíos, los mandamos como NULL
    $contacto = !empty($contacto) ? $contacto : null;
    $telefono = !empty($telefono) ? $telefono : null;
    $email = !empty($email) ? $email : null;
    $direccion = !empty($direccion) ? $direccion : null;

    $stmt->bind_param("sssssi", $nombre, $contacto, $telefono, $email, $direccion, $id);

    if (!$stmt->execute()) {
        error_log("❌ Error en execute(): " . $stmt->error);
        return false;
    }

    return true;
}

/* ============================================================
   Categorías
   ============================================================ */

function getCategorias(): array
{
    $conn = getDBConnection();
    $sql = "SELECT id, nombre, descripcion FROM categorias ORDER BY nombre ASC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


// 🔹 función para traer productos por categoría



function insertarCategoria($nombre, $descripcion, $imagenPath = null): bool
{
    $conn = getDBConnection();
    if ($imagenPath) {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion, imagen) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $descripcion, $imagenPath);
    } else {
        $stmt = $conn->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);
    }
    return $stmt->execute();
}

function actualizarCategoria(int $id, string $nombre, string $descripcion, ?string $imagenPath = null): bool
{
    $conn = getDBConnection();
    if ($imagenPath) {
        $sql = "UPDATE categorias SET nombre=?, descripcion=?, imagen=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $nombre, $descripcion, $imagenPath, $id);
    } else {
        $sql = "UPDATE categorias SET nombre=?, descripcion=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
    }
    return $stmt->execute();
}

function eliminarCategoria(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
    if (!$stmt) {
        error_log("❌ Error en eliminarCategoria: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
function favoritosAvailable(): bool
{
    $conn = getDBConnection();
    return tableExists($conn, "favoritos")
        && columnExists($conn, "favoritos", "usuario_id")
        && columnExists($conn, "favoritos", "producto_id");
}

function toggleFavorito(int $usuario_id, int $producto_id): bool
{
    if ($usuario_id <= 0 || !favoritosAvailable()) {
        // fallback sesión
        if (!isset($_SESSION))
            session_start();
        $_SESSION['favoritos'] = $_SESSION['favoritos'] ?? [];
        if (in_array($producto_id, $_SESSION['favoritos'])) {
            $_SESSION['favoritos'] = array_values(array_diff($_SESSION['favoritos'], [$producto_id]));
        } else {
            $_SESSION['favoritos'][] = $producto_id;
            $_SESSION['favoritos'] = array_values(array_unique($_SESSION['favoritos']));
        }
        return true;
    }
    $conn = getDBConnection();
    $chk = $conn->prepare("SELECT 1 FROM favoritos WHERE usuario_id=? AND producto_id=?");
    $chk->bind_param("ii", $usuario_id, $producto_id);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    if ($exists) {
        $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id=? AND producto_id=?");
        $del->bind_param("ii", $usuario_id, $producto_id);
        return $del->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO favoritos (usuario_id, producto_id, creado_en) VALUES (?,?,NOW())");
        $ins->bind_param("ii", $usuario_id, $producto_id);
        return $ins->execute();
    }
}

function getFavoritosCount(?int $usuario_id): int
{
    if ($usuario_id && $usuario_id > 0 && favoritosAvailable()) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM favoritos WHERE usuario_id=?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }
    if (!isset($_SESSION))
        session_start();
    return isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}
function getFavoritos(?int $usuario_id): array
{
    if ($usuario_id && $usuario_id > 0 && favoritosAvailable()) {
        $conn = getDBConnection();
        $sql = "
            SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria
            FROM favoritos f
            JOIN productos p ON f.producto_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE f.usuario_id = ?
            ORDER BY f.creado_en DESC
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("❌ Error en prepare getFavoritos: " . $conn->error);
            return [];
        }
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
    // fallback sesión
    if (!isset($_SESSION))
        session_start();
    $favoritos_ids = $_SESSION['favoritos'] ?? [];
    if (empty($favoritos_ids))
        return [];

    $conn = getDBConnection();
    $placeholders = implode(',', array_fill(0, count($favoritos_ids), '?'));
    $types = str_repeat('i', count($favoritos_ids));

    $sql = "
        SELECT p.id, p.nombre, p.descripcion, p.precio, p.imagen, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id IN ($placeholders)
        ORDER BY FIELD(p.id, $placeholders)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error en prepare getFavoritos (sesión): " . $conn->error);
        return [];
    }

    // Bind dinámico de parámetros
    $params = array_merge($favoritos_ids, $favoritos_ids);
    $stmt->bind_param($types . $types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/* ============================================================
   📝 BLOG FUNCTIONS
   ============================================================ */

/**
 * Obtener posts del blog paginados
 */
function getBlogPosts(int $limit = 10, int $offset = 0, string $estado = 'publicado'): array
{
    $conn = getDBConnection();
    $sql = "SELECT b.*, u.nombre as autor_nombre 
            FROM blog_posts b 
            LEFT JOIN usuarios u ON b.autor_id = u.id 
            WHERE b.estado = ? 
            ORDER BY b.fecha_creacion DESC 
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $estado, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Obtener un post por su slug
 */
function getBlogPostBySlug(string $slug): ?array
{
    $conn = getDBConnection();
    $sql = "SELECT b.*, u.nombre as autor_nombre 
            FROM blog_posts b 
            LEFT JOIN usuarios u ON b.autor_id = u.id 
            WHERE b.slug = ? LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Crear un nuevo post
 */
function createBlogPost(array $data): bool
{
    $conn = getDBConnection();
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['titulo'])));

    // Ensure slug is unique
    $originalSlug = $slug;
    $count = 1;
    while (getBlogPostBySlug($slug)) {
        $slug = $originalSlug . '-' . $count;
        $count++;
    }

    $sql = "INSERT INTO blog_posts (titulo, slug, contenido, imagen_portada, autor_id, estado) 
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssis",
        $data['titulo'],
        $slug,
        $data['contenido'],
        $data['imagen_portada'],
        $data['autor_id'],
        $data['estado']
    );

    return $stmt->execute();
}

/**
 * Actualizar un post existente
 */
function updateBlogPost(int $id, array $data): bool
{
    $conn = getDBConnection();
    // Only update slug if title changed, or keep it? usually better to keep slug stable or allow manual update.
    // For simplicity, we'll regenerate slug if title changes, but ideally we should check.
    // Let's keep slug stable for now unless explicitly requested.

    $sql = "UPDATE blog_posts 
            SET titulo = ?, contenido = ?, imagen_portada = ?, estado = ? 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssi",
        $data['titulo'],
        $data['contenido'],
        $data['imagen_portada'],
        $data['estado'],
        $id
    );

    return $stmt->execute();
}

/**
 * Eliminar un post
 */
function deleteBlogPost(int $id): bool
{
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
