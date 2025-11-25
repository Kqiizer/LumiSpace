<?php
// Detección dinámica de BASE_URL para compatibilidad Hostinger/Docker
if (!defined("BASE_URL")) {
    $envBase = getenv('BASE_URL');
    if ($envBase !== false) {
        define("BASE_URL", $envBase);
    } else {
        // Normalizar rutas para evitar problemas de slashes en Windows/Linux
        $projectDir = str_replace('\\', '/', dirname(__DIR__));
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? $projectDir);

        // Si el proyecto está dentro del document root
        if (strpos($projectDir, $docRoot) === 0) {
            $folder = substr($projectDir, strlen($docRoot));
            // Asegurar formato /carpeta/ o /
            define("BASE_URL", rtrim($folder, '/') . '/');
        } else {
            // Fallback por si la estructura de carpetas es inusual
            define("BASE_URL", "/");
        }
    }
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
// ------------------------------------------------------------
// 📌 VENTAS POR CATEGORÍA — VERSIÓN CORREGIDA PARA HOSTINGER
// ------------------------------------------------------------
function getVentasPorCategoria(): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            c.nombre AS categoria,
            SUM(dv.cantidad * dv.precio) AS monto_total
        FROM ventas v
        INNER JOIN detalle_ventas dv ON dv.venta_id = v.id
        INNER JOIN productos p ON p.id = dv.producto_id
        LEFT JOIN categorias c ON c.id = p.categoria_id
        GROUP BY c.nombre
        ORDER BY monto_total DESC
    ";

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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



function getVentasMensuales(): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            DATE_FORMAT(v.fecha, '%Y-%m') AS mes,
            SUM(dv.cantidad * dv.precio) AS total
        FROM ventas v
        INNER JOIN detalle_ventas dv ON dv.venta_id = v.id
        GROUP BY mes
        ORDER BY mes
    ";

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Ventas por día en el mes actual
function getVentasPorDiaMesActual(): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            DAY(v.fecha) AS dia,
            SUM(dv.cantidad * dv.precio) AS total
        FROM ventas v
        INNER JOIN detalle_ventas dv ON dv.venta_id = v.id
        WHERE MONTH(v.fecha) = MONTH(CURRENT_DATE())
        AND YEAR(v.fecha) = YEAR(CURRENT_DATE())
        GROUP BY dia
        ORDER BY dia
    ";

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}


/* ============================================================
   PRODUCTOS MÁS VENDIDOS
   ============================================================ */

// Top N productos más vendidos (general)
function getProductosMasVendidos(int $limit = 5): array
{
    $conn = getDBConnection();

    $sql = "
        SELECT 
            p.nombre,
            SUM(dv.cantidad) AS total_vendido
        FROM detalle_ventas dv
        INNER JOIN productos p ON p.id = dv.producto_id
        GROUP BY p.id, p.nombre
        ORDER BY total_vendido DESC
        LIMIT $limit
    ";

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
               COUNT(p.id) AS cantidad
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        GROUP BY c.nombre
        ORDER BY cantidad DESC
    ";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
    // Verificar si existe la columna 'imagen' o 'featured_image'
    $check_imagen = $conn->query("SHOW COLUMNS FROM categorias LIKE 'imagen'");
    $check_featured = $conn->query("SHOW COLUMNS FROM categorias LIKE 'featured_image'");
    $has_imagen = $check_imagen && $check_imagen->num_rows > 0;
    $has_featured = $check_featured && $check_featured->num_rows > 0;

    $image_col = '';
    if ($has_featured) {
        $image_col = ', featured_image';
    } elseif ($has_imagen) {
        $image_col = ', imagen';
    }

    $sql = "SELECT id, nombre, descripcion{$image_col} FROM categorias ORDER BY nombre ASC";
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

function getBrandsOverview(): array
{
    $conn = getDBConnection();
    $hasBrandsTable = tableExists($conn, 'marcas');
    $brands = [];

    $productosTieneMarcaId = columnExists($conn, 'productos', 'marca_id');
    $productosTieneMarcaTexto = columnExists($conn, 'productos', 'marca');

    if ($hasBrandsTable) {
        $cols = [];
        $res = $conn->query("SHOW COLUMNS FROM marcas");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $cols[$row['Field']] = true;
            }
        }

        $selectFields = ["m.id", "m.nombre"];
        $selectFields[] = isset($cols['descripcion']) ? "m.descripcion" : "'' AS descripcion";
        $logoColumn = isset($cols['logo']) ? 'logo' : (isset($cols['imagen']) ? 'imagen' : null);
        $selectFields[] = $logoColumn ? "m.$logoColumn AS logo_path" : "'' AS logo_path";
        $selectFields[] = isset($cols['tagline']) ? "m.tagline" : "'' AS tagline";
        $selectFields[] = isset($cols['campania']) ? "m.campania" : "'' AS campania";
        $selectFields[] = isset($cols['color_acento']) ? "m.color_acento" : "NULL AS color_acento";
        $selectFields[] = isset($cols['destacada']) ? "m.destacada" : (isset($cols['es_destacada']) ? "m.es_destacada AS destacada" : "0 AS destacada");

        $join = '';
        if ($productosTieneMarcaId) {
            $join = "LEFT JOIN productos p ON p.marca_id = m.id";
        } elseif ($productosTieneMarcaTexto) {
            $join = "LEFT JOIN productos p ON LOWER(p.marca) = LOWER(m.nombre)";
        }

        if ($join !== '') {
            $ventasCol = columnExists($conn, 'productos', 'ventas') ? 'p.ventas' : '0';
            $selectFields[] = "COUNT(p.id) AS total_productos";
            $selectFields[] = "COALESCE(SUM($ventasCol), 0) AS popularidad";
        } else {
            $selectFields[] = "0 AS total_productos";
            $selectFields[] = "0 AS popularidad";
        }

        $sql = "SELECT " . implode(', ', $selectFields) . " FROM marcas m $join GROUP BY m.id ORDER BY m.nombre ASC";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $brands[] = [
                    'id' => (int) $row['id'],
                    'name' => $row['nombre'] ?? 'Marca',
                    'description' => $row['descripcion'] ?? '',
                    'logo' => publicImageUrl($row['logo_path'] ?? ''),
                    'tagline' => $row['tagline'] ?? '',
                    'campaign' => $row['campania'] ?? '',
                    'accent' => $row['color_acento'] ?? '',
                    'featured' => !empty($row['destacada']),
                    'products' => (int) ($row['total_productos'] ?? 0),
                    'popularity' => (int) ($row['popularidad'] ?? 0),
                ];
            }
        }
    }

    if (!$hasBrandsTable || empty($brands)) {
        if ($productosTieneMarcaTexto) {
            $sql = "SELECT p.marca AS nombre, COUNT(*) AS total_productos FROM productos p WHERE p.marca IS NOT NULL AND p.marca <> '' GROUP BY p.marca ORDER BY nombre";
            $res = $conn->query($sql);
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $brands[] = [
                        'id' => null,
                        'name' => $row['nombre'],
                        'description' => '',
                        'logo' => publicImageUrl('images/default.png'),
                        'tagline' => '',
                        'campaign' => '',
                        'accent' => '',
                        'featured' => false,
                        'products' => (int) $row['total_productos'],
                        'popularity' => (int) $row['total_productos'],
                    ];
                }
            }
        }
    }

    usort($brands, static function ($a, $b) {
        return $b['popularity'] <=> $a['popularity'];
    });

    if (!empty($brands)) {
        $highlightCount = 0;
        foreach ($brands as &$brand) {
            if ($brand['featured']) {
                continue;
            }
            if ($highlightCount < 3) {
                $brand['featured'] = true;
                $highlightCount++;
            }
        }
        unset($brand);
    }

    return $brands;
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

/**
 * Construye dinámicamente el SELECT y los JOIN necesarios para consultar productos
 * considerando columnas opcionales (precio_original, descuento, etc.)
 *
 * @return array{select:string[],join:string}
 */
function lsFavoritesProductSelect(mysqli $conn, bool $withAddedAt = false): array
{
    static $cache = [];
    $key = $withAddedAt ? 'with_added_at' : 'without_added_at';
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM productos");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }

    $select = [
        "p.id",
        "p.nombre",
        isset($cols['descripcion']) ? "p.descripcion" : "'' AS descripcion",
        isset($cols['precio']) ? "p.precio" : "0 AS precio",
        isset($cols['precio_original']) ? "p.precio_original" : "NULL AS precio_original",
        isset($cols['descuento']) ? "p.descuento" : "0 AS descuento",
        isset($cols['stock']) ? "p.stock" : "0 AS stock",
        isset($cols['imagen']) ? "p.imagen" : "'' AS imagen",
    ];

    if ($withAddedAt) {
        $select[] = "f.creado_en AS agregado_en";
    } else {
        $select[] = "NULL AS agregado_en";
    }

    static $hasCategoriasTable = null;
    if ($hasCategoriasTable === null) {
        $hasCategoriasTable = tableExists($conn, 'categorias');
    }

    if (isset($cols['categoria_id']) && $hasCategoriasTable) {
        $join = "LEFT JOIN categorias c ON p.categoria_id = c.id";
        $select[] = "COALESCE(c.nombre, '') AS categoria";
    } elseif (isset($cols['categoria'])) {
        $join = "";
        $select[] = "p.categoria AS categoria";
    } else {
        $join = "";
        $select[] = "'' AS categoria";
    }

    return $cache[$key] = [
        'select' => $select,
        'join' => $join,
    ];
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
        $parts = lsFavoritesProductSelect($conn, true);
        $sql = "
            SELECT " . implode(", ", $parts['select']) . "
            FROM favoritos f
            JOIN productos p ON f.producto_id = p.id
            {$parts['join']}
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
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        return array_map(static fn($row) => array_merge($row, [
            'imagen' => publicImageUrl($row['imagen'] ?? ''),
        ]), $rows);
    }
    // fallback sesión
    if (!isset($_SESSION))
        session_start();
    $favoritos_ids = $_SESSION['favoritos'] ?? [];
    if (empty($favoritos_ids))
        return [];

    $conn = getDBConnection();
    $parts = lsFavoritesProductSelect($conn, false);
    $placeholders = implode(',', array_fill(0, count($favoritos_ids), '?'));
    $types = str_repeat('i', count($favoritos_ids) * 2);
    $sql = "
        SELECT " . implode(", ", $parts['select']) . "
        FROM productos p
        {$parts['join']}
        WHERE p.id IN ($placeholders)
        ORDER BY FIELD(p.id, $placeholders)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error en prepare getFavoritos (sesión): " . $conn->error);
        return [];
    }

    $params = array_merge($favoritos_ids, $favoritos_ids);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    return array_map(static fn($row) => array_merge($row, [
        'imagen' => publicImageUrl($row['imagen'] ?? ''),
    ]), $rows);
}

/**
 * ============================================================
 * BLOG / CONTENIDO
 * ============================================================
 */
function getBlogPostsData(): array
{
    $conn = getDBConnection();
    $hasBlogTable = tableExists($conn, 'blog_posts');
    $posts = [];

    if ($hasBlogTable) {
        $columns = [];
        $res = $conn->query("SHOW COLUMNS FROM blog_posts");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $columns[$row['Field']] = true;
            }
        }

        $select = [
            'id',
            'titulo',
            $columns['slug'] ?? false ? 'slug' : "'' AS slug",
            $columns['categoria'] ?? false ? 'categoria' : ($columns['categoria_id'] ?? false ? 'categoria_id' : "'' AS categoria"),
            $columns['tags'] ?? false ? 'tags' : "'' AS tags",
            $columns['resumen'] ?? false ? 'resumen' : "SUBSTRING(contenido,1,180) AS resumen",
            'contenido',
            $columns['autor'] ?? false ? 'autor' : "'' AS autor",
            $columns['imagen_destacada'] ?? false ? 'imagen_destacada' : ($columns['imagen'] ?? false ? 'imagen' : "'' AS imagen_destacada"),
            $columns['publicado_en'] ?? false ? 'publicado_en' : ($columns['created_at'] ?? false ? 'created_at' : 'NOW() AS publicado_en'),
            $columns['relacionados'] ?? false ? 'relacionados' : "'' AS relacionados",
            $columns['destacado'] ?? false ? 'destacado' : "0 AS destacado",
        ];

        $sql = "SELECT " . implode(', ', $select) . " FROM blog_posts WHERE estado IS NULL OR estado = 'publicado' ORDER BY publicado_en DESC";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $postTags = [];
                if (!empty($row['tags'])) {
                    $postTags = array_values(array_filter(array_map('trim', explode(',', $row['tags']))));
                }
                $related = [];
                if (!empty($row['relacionados'])) {
                    $related = array_values(array_filter(array_map('trim', explode(',', $row['relacionados']))));
                }
                $posts[] = [
                    'id' => (int) $row['id'],
                    'title' => $row['titulo'] ?? 'Artículo',
                    'slug' => $row['slug'] ?? '',
                    'category' => $row['categoria'] ?? ($row['categoria_id'] ?? 'General'),
                    'tags' => $postTags,
                    'summary' => $row['resumen'] ?? '',
                    'content' => $row['contenido'] ?? '',
                    'author' => $row['autor'] ?? 'Equipo LumiSpace',
                    'image' => publicImageUrl($row['imagen_destacada'] ?? ''),
                    'published_at' => $row['publicado_en'] ?? date('Y-m-d'),
                    'related' => $related,
                    'featured' => !empty($row['destacado']),
                ];
            }
        }
    }

    if (empty($posts)) {
        $posts = [
            [
                'id' => 1,
                'title' => 'Tendencias de iluminación 2025',
                'category' => 'Tendencias',
                'tags' => ['Inspiración', 'Decoración'],
                'summary' => 'Descubre cómo integrar iluminación inteligente y acabados cálidos para crear ambientes acogedores.',
                'content' => 'La iluminación se convierte en protagonista con texturas naturales, domótica accesible y piezas escultóricas...',
                'author' => 'Equipo LumiSpace',
                'image' => publicImageUrl('images/blog/tendencias.jpg'),
                'published_at' => date('Y-m-d', strtotime('-10 days')),
                'related' => ['Lámpara Colgante Moderna Oslo', 'Lámpara de Techo Colgante'],
                'featured' => true,
            ],
            [
                'id' => 2,
                'title' => 'Guía para iluminar tu home office',
                'category' => 'Guías',
                'tags' => ['Productividad', 'Tips'],
                'summary' => 'Te contamos cómo equilibrar luz natural y artificial para evitar fatiga visual.',
                'content' => 'Trabajar desde casa requiere un esquema de luz que combine tareas, ambiente y acentos...',
                'author' => 'María Hernández',
                'image' => publicImageUrl('images/blog/homeoffice.jpg'),
                'published_at' => date('Y-m-d', strtotime('-20 days')),
                'related' => ['Lámpara de Mesa Escandinava', 'Lámpara Mesa Smart RGB'],
                'featured' => false,
            ],
            [
                'id' => 3,
                'title' => 'Cómo elegir focos eficientes',
                'category' => 'Consejos',
                'tags' => ['Sustentabilidad', 'Ahorro'],
                'summary' => 'Revisamos temperatura de color, lúmenes y consumo para que hagas una compra inteligente.',
                'content' => 'El LED sigue siendo el rey, pero hay matices importantes al momento de elegir...',
                'author' => 'Equipo LumiSpace',
                'image' => publicImageUrl('images/blog/eficientes.jpg'),
                'published_at' => date('Y-m-d', strtotime('-30 days')),
                'related' => ['Kit Bombillas LED vintage'],
                'featured' => false,
            ],
        ];
    }

    $categories = [];
    $tags = [];
    foreach ($posts as $post) {
        $category = $post['category'] ?: 'General';
        $categories[$category] = ($categories[$category] ?? 0) + 1;
        foreach ($post['tags'] as $tag) {
            $tags[$tag] = ($tags[$tag] ?? 0) + 1;
        }
    }

    usort($posts, static fn($a, $b) => strtotime($b['published_at']) <=> strtotime($a['published_at']));

    return [
        'posts' => $posts,
        'categories' => $categories,
        'tags' => $tags,
    ];
}

/**
 * ============================================================
 * 🔍 Buscador avanzado
 * ============================================================
 */
function lsGetProductColumnsMeta(): array
{
    static $meta = null;
    if ($meta !== null) {
        return $meta;
    }

    $conn = getDBConnection();
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM productos");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
        }
    }

    $meta = [
        'has_precio' => isset($cols['precio']),
        'has_precio_original' => isset($cols['precio_original']),
        'has_descuento' => isset($cols['descuento']),
        'has_stock' => isset($cols['stock']) || isset($cols['existencia']),
        'stock_column' => isset($cols['stock']) ? 'stock' : (isset($cols['existencia']) ? 'existencia' : null),
        'has_categoria_id' => isset($cols['categoria_id']),
        'has_categoria_text' => isset($cols['categoria']),
        'has_marca_id' => isset($cols['marca_id']),
        'has_proveedor_id' => isset($cols['proveedor_id']),
        'has_color' => isset($cols['color']),
        'has_talla' => isset($cols['talla']) || isset($cols['tamano']),
        'talla_column' => isset($cols['talla']) ? 'talla' : (isset($cols['tamano']) ? 'tamano' : null),
        'has_disponible' => isset($cols['disponible']),
        'has_popular' => isset($cols['ventas']) ? 'ventas' : (isset($cols['visitas']) ? 'visitas' : null),
        'has_rating' => isset($cols['calificacion']),
        'has_created' => isset($cols['creado_en']) ? 'creado_en' : (isset($cols['created_at']) ? 'created_at' : null),
    ];

    return $meta;
}

function normalizeSearchProductRow(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => $row['nombre'] ?? 'Producto',
        'description' => $row['descripcion'] ?? '',
        'category' => $row['categoria'] ?? 'Otros',
        'brand' => $row['marca'] ?? ($row['proveedor'] ?? ''),
        'price' => isset($row['precio']) ? (float) $row['precio'] : 0.0,
        'originalPrice' => isset($row['precio_original']) && $row['precio_original'] !== null ? (float) $row['precio_original'] : null,
        'discount' => isset($row['descuento']) ? (float) $row['descuento'] : 0.0,
        'stock' => isset($row['stock']) ? (int) $row['stock'] : 0,
        'availability' => isset($row['disponible']) ? (bool) $row['disponible'] : (isset($row['stock']) ? ((int) $row['stock'] > 0) : true),
        'color' => $row['color'] ?? null,
        'size' => $row['talla'] ?? null,
        'rating' => isset($row['calificacion']) ? (float) $row['calificacion'] : null,
        'image' => publicImageUrl($row['imagen'] ?? ''),
        'created_at' => $row['creado_en'] ?? ($row['created_at'] ?? null),
        'popularity' => isset($row['popularity']) ? (int) $row['popularity'] : (isset($row['ventas']) ? (int) $row['ventas'] : (isset($row['visitas']) ? (int) $row['visitas'] : 0)),
    ];
}

function searchProductos(array $options = []): array
{
    $conn = getDBConnection();
    $meta = lsGetProductColumnsMeta();

    $query = trim((string) ($options['q'] ?? ''));
    $category = trim((string) ($options['category'] ?? ''));
    $brand = trim((string) ($options['brand'] ?? ''));
    $color = trim((string) ($options['color'] ?? ''));
    $size = trim((string) ($options['size'] ?? ''));
    $available = isset($options['availability']) ? (string) $options['availability'] : '';
    $minPrice = isset($options['min_price']) ? (float) $options['min_price'] : null;
    $maxPrice = isset($options['max_price']) ? (float) $options['max_price'] : null;
    $discountOnly = !empty($options['discount_only']);
    $sort = (string) ($options['sort'] ?? 'relevance');
    $page = max(1, (int) ($options['page'] ?? 1));
    $perPage = (int) ($options['per_page'] ?? 12);
    $perPage = min(max($perPage, 6), 48);

    $select = [
        "p.id",
        "p.nombre",
        $meta['has_precio'] ? "p.precio" : "0 AS precio",
        $meta['has_precio_original'] ? "p.precio_original" : "NULL AS precio_original",
        $meta['has_descuento'] ? "p.descuento" : "0 AS descuento",
        $meta['has_stock'] ? "p.{$meta['stock_column']} AS stock" : "0 AS stock",
        $meta['has_disponible'] ? "p.disponible" : "NULL AS disponible",
        $meta['has_color'] ? "p.color" : "NULL AS color",
        $meta['has_talla'] ? "p.{$meta['talla_column']} AS talla" : "NULL AS talla",
        $meta['has_rating'] ? "p.calificacion" : "NULL AS calificacion",
        $meta['has_created'] ? "p.{$meta['has_created']} AS creado_en" : "NULL AS creado_en",
        "p.descripcion",
        "p.imagen",
    ];

    $joins = "";
    if ($meta['has_categoria_id'] && tableExists($conn, 'categorias')) {
        $joins .= " LEFT JOIN categorias c ON p.categoria_id = c.id";
        $select[] = "COALESCE(c.nombre, 'Sin categoría') AS categoria";
    } elseif ($meta['has_categoria_text']) {
        $select[] = "p.categoria AS categoria";
    } else {
        $select[] = "'Otros' AS categoria";
    }

    $brandWhereAlias = null;
    if ($meta['has_marca_id'] && tableExists($conn, 'marcas')) {
        $joins .= " LEFT JOIN marcas m ON p.marca_id = m.id";
        $select[] = "m.nombre AS marca";
        $brandWhereAlias = "m.nombre";
    } elseif ($meta['has_proveedor_id'] && tableExists($conn, 'proveedores')) {
        $joins .= " LEFT JOIN proveedores pr ON p.proveedor_id = pr.id";
        $select[] = "pr.nombre AS marca";
        $brandWhereAlias = "pr.nombre";
    } else {
        $select[] = "'' AS marca";
    }

    $where = ["1=1"];
    $params = [];
    $types = "";

    if ($query !== '') {
        $like = '%' . $query . '%';
        $whereParts = [
            "p.nombre LIKE ?",
            "p.descripcion LIKE ?"
        ];
        $params[] = $like;
        $types .= "s";
        $params[] = $like;
        $types .= "s";

        if ($meta['has_categoria_id']) {
            $whereParts[] = "c.nombre LIKE ?";
            $params[] = $like;
            $types .= "s";
        } elseif ($meta['has_categoria_text']) {
            $whereParts[] = "p.categoria LIKE ?";
            $params[] = $like;
            $types .= "s";
        }
        $whereParts[] = "SOUNDEX(p.nombre) = SOUNDEX(?)";
        $params[] = $query;
        $types .= "s";

        $where[] = '(' . implode(' OR ', $whereParts) . ')';
    }

    if ($category !== '') {
        if ($meta['has_categoria_id']) {
            if (ctype_digit($category)) {
                $where[] = "c.id = ?";
                $params[] = (int) $category;
                $types .= "i";
            } else {
                $where[] = "LOWER(c.nombre) = ?";
                $params[] = strtolower($category);
                $types .= "s";
            }
        } elseif ($meta['has_categoria_text']) {
            $where[] = "LOWER(p.categoria) = ?";
            $params[] = strtolower($category);
            $types .= "s";
        }
    }

    if ($brand !== '' && $brandWhereAlias) {
        $where[] = "LOWER($brandWhereAlias) = ?";
        $params[] = strtolower($brand);
        $types .= "s";
    }

    if ($color !== '' && $meta['has_color']) {
        $where[] = "LOWER(p.color) = ?";
        $params[] = strtolower($color);
        $types .= "s";
    }

    if ($size !== '' && $meta['has_talla']) {
        $where[] = "LOWER(p.{$meta['talla_column']}) = ?";
        $params[] = strtolower($size);
        $types .= "s";
    }

    if ($available !== '') {
        if ($available === 'in') {
            if ($meta['has_disponible']) {
                $where[] = "p.disponible = 1";
            } elseif ($meta['has_stock']) {
                $where[] = "p.{$meta['stock_column']} > 0";
            }
        } elseif ($available === 'out') {
            if ($meta['has_disponible']) {
                $where[] = "p.disponible = 0";
            } elseif ($meta['has_stock']) {
                $where[] = "p.{$meta['stock_column']} <= 0";
            }
        }
    }

    if ($minPrice !== null && $meta['has_precio']) {
        $where[] = "p.precio >= ?";
        $params[] = $minPrice;
        $types .= "d";
    }

    if ($maxPrice !== null && $meta['has_precio']) {
        $where[] = "p.precio <= ?";
        $params[] = $maxPrice;
        $types .= "d";
    }

    if ($discountOnly && $meta['has_descuento']) {
        $where[] = "p.descuento > 0";
    }

    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $sql = "SELECT " . implode(', ', $select) . " FROM productos p {$joins} {$whereSql} LIMIT 500";
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rawRows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $normalized = array_map('normalizeSearchProductRow', $rawRows);

    // Scoring for relevance
    if ($query !== '') {
        $queryLower = strtolower($query);
        foreach ($normalized as &$item) {
            $score = 0;
            $nameLower = strtolower($item['name']);
            similar_text($queryLower, $nameLower, $percent);
            $score += $percent;
            if (!empty($item['description'])) {
                similar_text($queryLower, strtolower($item['description']), $descPercent);
                $score += $descPercent / 4;
            }
            $lev = levenshtein($queryLower, $nameLower);
            if ($lev <= max(3, strlen($queryLower) / 3)) {
                $score += 20;
            }
            $item['_score'] = $score;
        }
        unset($item);
    }

    // Sorting
    $sortKey = strtolower($sort);
    usort($normalized, static function ($a, $b) use ($sortKey) {
        switch ($sortKey) {
            case 'price_asc':
                return $a['price'] <=> $b['price'];
            case 'price_desc':
                return $b['price'] <=> $a['price'];
            case 'popularity':
                return ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0);
            case 'rating':
                return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            case 'newest':
                return strtotime($b['created_at'] ?? 'now') <=> strtotime($a['created_at'] ?? 'now');
            default:
                return ($b['_score'] ?? 0) <=> ($a['_score'] ?? 0);
        }
    });

    $total = count($normalized);
    $totalPages = (int) ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    $pageItems = array_slice($normalized, $offset, $perPage);

    // Flag productos que ya están en favoritos para el usuario autenticado
    $userFavSet = [];
    $userId = $_SESSION['usuario_id'] ?? 0;
    if ($userId && tableExists($conn, 'favoritos')) {
        $stmtFav = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?");
        if ($stmtFav) {
            $stmtFav->bind_param("i", $userId);
            $stmtFav->execute();
            $favRes = $stmtFav->get_result();
            if ($favRes) {
                $ids = array_map('intval', array_column($favRes->fetch_all(MYSQLI_ASSOC), 'producto_id'));
                foreach ($ids as $favId) {
                    $userFavSet[$favId] = true;
                }
            }
            $stmtFav->close();
        }
    }

    foreach ($pageItems as &$item) {
        $item['in_wishlist'] = isset($userFavSet[$item['id']]);
    }
    unset($item);

    // Facets
    $facets = [
        'categories' => [],
        'brands' => [],
        'colors' => [],
        'sizes' => [],
        'availability' => ['in_stock' => 0, 'out_of_stock' => 0],
        'price' => ['min' => null, 'max' => null],
    ];

    foreach ($normalized as $item) {
        $cat = $item['category'] ?? 'Otros';
        $facets['categories'][$cat] = ($facets['categories'][$cat] ?? 0) + 1;

        if (!empty($item['brand'])) {
            $facets['brands'][$item['brand']] = ($facets['brands'][$item['brand']] ?? 0) + 1;
        }
        if (!empty($item['color'])) {
            $facets['colors'][$item['color']] = ($facets['colors'][$item['color']] ?? 0) + 1;
        }
        if (!empty($item['size'])) {
            $facets['sizes'][$item['size']] = ($facets['sizes'][$item['size']] ?? 0) + 1;
        }
        if (!empty($item['availability'])) {
            $facets['availability']['in_stock'] += 1;
        } else {
            $facets['availability']['out_of_stock'] += 1;
        }
        $facets['price']['min'] = $facets['price']['min'] === null ? $item['price'] : min($facets['price']['min'], $item['price']);
        $facets['price']['max'] = $facets['price']['max'] === null ? $item['price'] : max($facets['price']['max'], $item['price']);
    }

    // Clean temp keys
    foreach ($pageItems as &$item) {
        unset($item['_score']);
    }
    unset($item);

    return [
        'results' => $pageItems,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages,
        'facets' => $facets,
    ];
}

function getSearchSuggestions(string $term, int $limit = 8): array
{
    $term = trim($term);
    if ($term === '') {
        return [];
    }
    $conn = getDBConnection();
    $like = '%' . $term . '%';
    $stmt = $conn->prepare("SELECT DISTINCT p.nombre FROM productos p WHERE p.nombre LIKE ? ORDER BY p.nombre ASC LIMIT ?");
    $stmt->bind_param("si", $like, $limit * 3);
    $stmt->execute();
    $res = $stmt->get_result();
    $names = $res ? array_column($res->fetch_all(MYSQLI_ASSOC), 'nombre') : [];

    // Sort by similarity
    $termLower = strtolower($term);
    usort($names, static function ($a, $b) use ($termLower) {
        similar_text($termLower, strtolower($a), $aScore);
        similar_text($termLower, strtolower($b), $bScore);
        return $bScore <=> $aScore;
    });

    return array_slice($names, 0, $limit);
}

function logSearchQuery(?int $usuario_id, string $query, array $filters = [], int $resultsCount = 0): void
{
    $query = trim($query);
    if ($query === '') {
        return;
    }
    $conn = getDBConnection();
    static $tableChecked = false;
    if (!$tableChecked) {
        $sql = "CREATE TABLE IF NOT EXISTS busquedas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            termino VARCHAR(255) NOT NULL,
            filtros TEXT NULL,
            resultados INT DEFAULT 0,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $conn->query($sql);
        $tableChecked = true;
    }
    $stmt = $conn->prepare("INSERT INTO busquedas (usuario_id, termino, filtros, resultados) VALUES (?, ?, ?, ?)");
    $filtersJson = json_encode($filters, JSON_UNESCAPED_UNICODE);
    $uid = $usuario_id ?: null;
    $stmt->bind_param("issi", $uid, $query, $filtersJson, $resultsCount);
    $stmt->execute();
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
