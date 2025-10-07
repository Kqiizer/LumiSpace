<?php

// Ruta base del proyecto (ajusta "LumiSpace" si tu carpeta tiene otro nombre)
if (!defined("BASE_URL")) {
    define("BASE_URL", "/LumiSpace/");
}

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
   Usuarios (auth + registro)
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
            $cantidad_total += (int)$item['cantidad'];
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
            $cantidad = (int)$item['cantidad'];
            $precio   = (float)$item['precio'];
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
function getVentasHoy(): float {
    $conn = getDBConnection();
    $sql  = "SELECT IFNULL(SUM(total),0) as total FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res  = $conn->query($sql);
    return (float)($res->fetch_assoc()['total'] ?? 0);
}

// Resumen de hoy
function getResumenHoy(): array {
    $conn = getDBConnection();
    $sql  = "SELECT 
                IFNULL(SUM(total),0) as total,
                COUNT(id) as transacciones,
                IFNULL(SUM(cantidad_total),0) as productos
             FROM ventas
             WHERE DATE(fecha)=CURDATE()";
    $res = $conn->query($sql);
    return $res ? $res->fetch_assoc() : ['total'=>0,'transacciones'=>0,'productos'=>0];
}

// Últimas N ventas
function getVentasRecientes(int $limit=6): array {
    $conn = getDBConnection();
    $limit = max(1, (int)$limit); // siempre mínimo 1

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
function getClientesUnicosHoy(): int {
    $conn = getDBConnection();
    $sql  = "SELECT COUNT(DISTINCT cliente_id) as clientes FROM ventas WHERE DATE(fecha)=CURDATE()";
    $res  = $conn->query($sql);
    return (int)($res->fetch_assoc()['clientes'] ?? 0);
}

// Corte de caja por método de pago (hoy)
function getCorteCajaHoy(): array {
    $conn = getDBConnection();
    $sql  = "SELECT metodo_pago, SUM(total) as total 
             FROM ventas 
             WHERE DATE(fecha)=CURDATE()
             GROUP BY metodo_pago";
    $res  = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Ventas agrupadas por categoría (hoy)
function getVentasPorCategoria(): array {
    $conn = getDBConnection();
    $sql  = "SELECT cat.nombre as categoria, SUM(dv.subtotal) as total
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
function getVentaById(int $venta_id): ?array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT v.*, c.nombre as cliente, u.nombre as cajero
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id=c.id
        LEFT JOIN usuarios u ON v.usuario_id=u.id
        WHERE v.id=?
        LIMIT 1
    ");
    $stmt->bind_param("i",$venta_id);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();

    if (!$venta) return null;

    $stmt2 = $conn->prepare("
        SELECT dv.*, p.nombre as producto
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id=p.id
        WHERE dv.venta_id=?
    ");
    $stmt2->bind_param("i",$venta_id);
    $stmt2->execute();
    $venta['items'] = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    return $venta;
}
/**
 * Categorías más vendidas del mes
 */
function getCategoriasMasVendidasMes(int $limit = 10): array {
    $conn = getDBConnection();

    // Sanitizar límite
    $limit = (int)$limit;

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
function getVentasMensuales(): array {
    $conn = getDBConnection();
    $sql  = "SELECT MONTHNAME(fecha) as mes, SUM(total) as total 
             FROM ventas 
             WHERE YEAR(fecha)=YEAR(NOW())
             GROUP BY MONTH(fecha)
             ORDER BY MONTH(fecha)";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Ventas por día en el mes actual
function getVentasPorDiaMesActual(): array {
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
function getProductosMasVendidos(int $limit=10): array {
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
function getProductosMasVendidosMes(int $limit = 10): array {
    $conn = getDBConnection();

    // Sanitizar el límite para evitar SQL injection
    $limit = (int)$limit;

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
   Funciones para conexión
   ============================================================ */
function getDBConnection(): mysqli {
    static $conn;
    if ($conn instanceof mysqli) return $conn;

    $conn = new mysqli("localhost", "root", "", "lumispace");
    if ($conn->connect_error) {
        die("❌ Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

/* ============================================================
   Funciones para el Dashboard Admin
   ============================================================ */
function getTotalUsuarios(): int {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $res = $conn->query($sql);
    return (int)($res->fetch_assoc()['total'] ?? 0);
}

function getTotalGestores(): int {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM usuarios WHERE rol='gestor'";
    $res = $conn->query($sql);
    return (int)($res->fetch_assoc()['total'] ?? 0);
}

function getTotalProductos(): int {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as total FROM productos";
    $res = $conn->query($sql);
    return (int)($res->fetch_assoc()['total'] ?? 0);
}

function getIngresosMes(): float {
    $conn = getDBConnection();
    $sql = "SELECT SUM(total) as ingresos 
            FROM ventas 
            WHERE MONTH(fecha)=MONTH(NOW()) AND YEAR(fecha)=YEAR(NOW())";
    $res = $conn->query($sql);
    return (float)($res->fetch_assoc()['ingresos'] ?? 0);
}

function getUsuariosRecientes(int $limit=5): array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT nombre,email,fecha_registro FROM usuarios ORDER BY fecha_registro DESC LIMIT ?");
    $stmt->bind_param("i",$limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

function getInventarioResumen(): array {
    $conn = getDBConnection();
    $sql = "SELECT c.nombre as categoria, SUM(p.stock) as cantidad 
            FROM productos p
            JOIN categorias c ON p.categoria_id = c.id
            GROUP BY c.nombre";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function getUsuariosMensuales(): array {
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
function formatCurrency($amount) {
    return "$" . number_format($amount, 2, '.', ',');
}

function timeAgo($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    if ($diff < 60) return "justo ahora";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return "hace $minutes min";
    $hours = floor($minutes / 60);
    if ($hours < 24) return "hace $hours horas";
    $days = floor($hours / 24);
    return "hace $days días";
}
/* ===== Helpers de introspección ===== */
// config/functions.php

if (!function_exists('tableExists')) {
    function tableExists(mysqli $conn, string $table): bool {
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
    function columnExists(mysqli $conn, string $table, string $column): bool {
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
function publicImageUrl(?string $raw): string {
    $r = trim((string)$raw);
    $base = rtrim(BASE_URL, '/').'/';
    if ($r === '') return $base.'images/default.png';
    if (preg_match('#^https?://#i', $r)) return $r;
    if (stripos($r, 'images/') === 0) return $base.$r;
    return $base.'images/productos/'.$r;
}

/* ============================================================
   Logs y Pagos
   ============================================================ */
function registrarLog($usuario_id,$accion,$ip=''): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO logs (usuario_id,accion,ip) VALUES (?,?,?)");
    $stmt->bind_param("iss",$usuario_id,$accion,$ip);
    return $stmt->execute();
}

function registrarPago($venta_id,$metodo,$monto): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO pagos (venta_id,metodo,monto) VALUES (?,?,?)");
    $stmt->bind_param("isd",$venta_id,$metodo,$monto);
    return $stmt->execute();
}

function getPagosPorVenta($venta_id): array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM pagos WHERE venta_id=?");
    $stmt->bind_param("i",$venta_id);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

/* ============================================================
   Clientes
   ============================================================ */
function getClientes(): array {
    $conn = getDBConnection();
    $res = $conn->query("SELECT * FROM clientes ORDER BY creado_en DESC");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function insertarCliente($nombre,$email,$telefono,$direccion): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO clientes (nombre,email,telefono,direccion) VALUES (?,?,?,?)");
    $stmt->bind_param("ssss",$nombre,$email,$telefono,$direccion);
    return $stmt->execute();
}

/* ============================================================
   Inventario
   ============================================================ */
/* ============================
   INVENTARIO AVANZADO
   ============================ */
function getInventario(): array {
    $conn = getDBConnection();
    $sql = "SELECT i.*, p.nombre AS producto, p.precio 
            FROM inventario i
            JOIN productos p ON i.producto_id=p.id
            ORDER BY p.nombre ASC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getInventarioById(int $id): ?array {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM inventario WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function insertarInventario(int $producto_id, int $cantidad, string $sucursal='Principal'): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO inventario(producto_id, sucursal, cantidad) VALUES(?,?,?)");
    $stmt->bind_param("isi", $producto_id, $sucursal, $cantidad);
    return $stmt->execute();
}

function actualizarInventario(int $id, int $cantidad, string $sucursal): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE inventario SET cantidad=?, sucursal=? WHERE id=?");
    $stmt->bind_param("isi", $cantidad, $sucursal, $id);
    return $stmt->execute();
}

function eliminarInventario(int $id): bool {
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
    string $motivo='',
    string $sucursal='Principal'
): bool {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // 1. Insertar el movimiento en la bitácora
        $stmt = $conn->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar INSERT: " . $conn->error);
        }
        $stmt->bind_param("iisis", $producto_id, $usuario_id, $tipo, $cantidad, $motivo);
        $stmt->execute();

        // 2. Consultar stock actual
        $stmt2 = $conn->prepare("SELECT cantidad FROM inventario WHERE producto_id=? AND sucursal=? LIMIT 1");
        if (!$stmt2) {
            throw new Exception("Error al preparar SELECT: " . $conn->error);
        }
        $stmt2->bind_param("is", $producto_id, $sucursal);
        $stmt2->execute();
        $res = $stmt2->get_result()->fetch_assoc();
        $stockActual = $res ? (int)$res['cantidad'] : 0;

        // 3. Calcular nuevo stock en PHP
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
            if (!$stmt3) throw new Exception("Error en UPDATE inventario: " . $conn->error);
            $stmt3->bind_param("iis", $nuevoStock, $producto_id, $sucursal);
            $stmt3->execute();
        } else {
            $stmt3 = $conn->prepare("INSERT INTO inventario (producto_id, sucursal, cantidad) VALUES (?, ?, ?)");
            if (!$stmt3) throw new Exception("Error en INSERT inventario: " . $conn->error);
            $stmt3->bind_param("isi", $producto_id, $sucursal, $nuevoStock);
            $stmt3->execute();
        }

        // 5. Confirmar
        $conn->commit();
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("registrarMovimiento() fallo: ".$e->getMessage());
        return false;
    }
}


function getMovimientos(): array {
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
function getProductoPorId(int $id): ?array {
    $conn = getDBConnection();
    if ($id <= 0) return null;

    $joinCat = $joinProv = "";
    $selCat  = "NULL AS categoria_id, NULL AS categoria";
    $selProv = "NULL AS marca";

    if (tableExists($conn, "categorias") && columnExists($conn, "productos", "categoria_id")) {
        $joinCat = " LEFT JOIN categorias c ON p.categoria_id = c.id";
        $selCat  = "c.id AS categoria_id, c.nombre AS categoria";
    }
    if (tableExists($conn, "proveedores") && columnExists($conn, "productos", "proveedor_id")) {
        $joinProv = " LEFT JOIN proveedores pr ON p.proveedor_id = pr.id";
        $selProv  = "pr.nombre AS marca";
    } elseif (tableExists($conn, "marcas") && columnExists($conn, "productos", "marca_id")) {
        $joinProv = " LEFT JOIN marcas m ON p.marca_id = m.id";
        $selProv  = "m.nombre AS marca";
    }

    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_original, p.descuento,
                   p.stock, p.imagen, $selCat, $selProv
            FROM productos p{$joinCat}{$joinProv}
            WHERE p.id = ? LIMIT 1";

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
function getProductoImagenes(int $producto_id): array {
    $conn = getDBConnection();
    if (!tableExists($conn, "producto_imagenes")) return [];
    $stmt = $conn->prepare("SELECT ruta FROM producto_imagenes WHERE producto_id=? ORDER BY orden ASC, id ASC");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $rs = $stmt->get_result();
    $imgs = [];
    while ($row = $rs->fetch_assoc()) $imgs[] = publicImageUrl((string)$row['ruta']);
    return $imgs;
}

function getProductosPublicos(int $limit = 12): array {
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


function getProductos(): array {
    $conn = getDBConnection();
    $sql = "SELECT p.*, c.nombre as categoria, pr.nombre as proveedor
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
            ORDER BY p.id DESC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
// 🔴 Eliminar producto definitivamente
function eliminarProducto(int $id): bool {
    $conn = getDBConnection();
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
    int $stock,
    int $categoria_id,
    ?int $proveedor_id = null,
    ?string $imagenPath = null
): bool {
    $conn = getDBConnection();

    $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, proveedor_id, imagen) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("❌ Error en prepare(): " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssdiiss", $nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id, $imagenPath);

    return $stmt->execute();
}


function actualizarProducto($id, $nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id = null, $imagenPath = null): bool {
    $conn = getDBConnection();

    if ($imagenPath) {
        // Si se actualiza con nueva imagen
        if ($proveedor_id) {
            $sql = "UPDATE productos 
                    SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, proveedor_id=?, imagen=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiissi", $nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id, $imagenPath, $id);
        } else {
            $sql = "UPDATE productos 
                    SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, proveedor_id=NULL, imagen=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdissi", $nombre, $descripcion, $precio, $stock, $categoria_id, $imagenPath, $id);
        }
    } else {
        // Si NO hay imagen nueva, no tocar la columna imagen
        if ($proveedor_id) {
            $sql = "UPDATE productos 
                    SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, proveedor_id=? 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiisi", $nombre, $descripcion, $precio, $stock, $categoria_id, $proveedor_id, $id);
        } else {
            $sql = "UPDATE productos 
                    SET nombre=?, descripcion=?, precio=?, stock=?, categoria_id=?, proveedor_id=NULL 
                    WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $categoria_id, $id);
        }
    }

    return $stmt && $stmt->execute();
}
function inactivarProducto(int $id): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE productos SET estado='inactivo' WHERE id=?");
    if (!$stmt) {
        error_log("❌ Error en inactivarProducto prepare(): " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
function getProductosPorCategoria(?int $categoria_id = null, int $limit = 12): array {
    $conn  = getDBConnection();
    $limit = max(1, (int)$limit);

    // ===== Detectar columna de orden "reciente" =====
    static $orderCol = null;
    if ($orderCol === null) {
        $candidatas = ['creado_en','created_at','fecha_creacion','fecha'];
        $orderCol = 'id';
        foreach ($candidatas as $col) {
            $chk = $conn->query("SHOW COLUMNS FROM productos LIKE '{$col}'");
            if ($chk && $chk->num_rows > 0) { $orderCol = $col; break; }
        }
    }

    // ===== Detectar si existe la columna productos.categoria_id =====
    static $hasCategoriaId = null;
    if ($hasCategoriaId === null) {
        $chk = $conn->query("SHOW COLUMNS FROM productos LIKE 'categoria_id'");
        $hasCategoriaId = ($chk && $chk->num_rows > 0);
    }

    // ===== Construcción del SQL =====
    if ($hasCategoriaId) {
        // Esquema con FK categoria_id
        $sqlBase = "
            SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_original, p.descuento,
                   p.stock, p.imagen, c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
        ";

        if ($categoria_id) {
            // Usamos prepare; si falla, fallback a query() directa
            $sql = $sqlBase . " WHERE p.categoria_id = ? ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT " . (int)$limit;
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $categoria_id);
                $stmt->execute();
                $res = $stmt->get_result();
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            } else {
                error_log('getProductosPorCategoria prepare(FK) fallo: ' . $conn->error);
                $categoria_id = (int)$categoria_id;
                $sqlFall = $sqlBase . " WHERE p.categoria_id = {$categoria_id} ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT " . (int)$limit;
                $res = $conn->query($sqlFall);
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            }
        } else {
            $sql = $sqlBase . " ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT " . (int)$limit;
            $res = $conn->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }

    } else {
        // Esquema SIN categoria_id -> p.categoria (texto)
        $sqlBase = "
            SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_original, p.descuento,
                   p.stock, p.imagen, p.categoria AS categoria
            FROM productos p
        ";

        if ($categoria_id) {
            // Mapear id -> nombre con subquery
            $sql = $sqlBase . "
                WHERE p.categoria = (SELECT nombre FROM categorias WHERE id = ? LIMIT 1)
                ORDER BY p.`{$orderCol}` DESC, p.id DESC
                LIMIT " . (int)$limit;
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $categoria_id);
                $stmt->execute();
                $res = $stmt->get_result();
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            } else {
                error_log('getProductosPorCategoria prepare(texto) fallo: ' . $conn->error);
                // Fallback (menos seguro, pero casteamos todo)
                $categoria_id = (int)$categoria_id;
                $sqlFall = $sqlBase . "
                    WHERE p.categoria = (SELECT nombre FROM categorias WHERE id = {$categoria_id} LIMIT 1)
                    ORDER BY p.`{$orderCol}` DESC, p.id DESC
                    LIMIT " . (int)$limit;
                $res = $conn->query($sqlFall);
                return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
            }
        } else {
            $sql = $sqlBase . " ORDER BY p.`{$orderCol}` DESC, p.id DESC LIMIT " . (int)$limit;
            $res = $conn->query($sql);
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
    }
}


function getProductosCatalogo(?string $categoria = null, int $limit = null): array {
    $conn = getDBConnection();
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id";
    $params = [];
    $types  = "";

    if ($categoria) {
        $sql .= " WHERE c.nombre = ?";
        $params[] = $categoria;
        $types   .= "s";
    }

    $sql .= " ORDER BY p.nombre ASC";
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        $types   .= "i";
    }

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $productos = [];
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
    return $productos;
}

/* ============================================================
   Proveedores
   ============================================================ */
function getProveedores(): array {
    $conn = getDBConnection();
    $sql = "SELECT * FROM proveedores ORDER BY nombre";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function insertarProveedor(string $nombre, ?string $contacto, ?string $telefono, ?string $email, ?string $direccion): bool {
    $conn = getDBConnection();
    $sql = "INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log("Error en prepare(): " . $conn->error);
        return false;
    }

    $contacto  = !empty($contacto) ? $contacto : null;
    $telefono  = !empty($telefono) ? $telefono : null;
    $email     = !empty($email) ? $email : null;
    $direccion = !empty($direccion) ? $direccion : null;

    $stmt->bind_param("sssss", $nombre, $contacto, $telefono, $email, $direccion);
    return $stmt->execute();
}

/* ============================================================
   Categorías
   ============================================================ */

function getCategorias(): array {
    $conn = getDBConnection();
    $sql = "SELECT id, nombre FROM categorias ORDER BY nombre ASC";
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}


// 🔹 función para traer productos por categoría



function insertarCategoria($nombre, $descripcion, $imagenPath = null): bool {
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

function actualizarCategoria(int $id, string $nombre, string $descripcion, ?string $imagenPath = null): bool {
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

function eliminarCategoria(int $id): bool {
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM categorias WHERE id=?");
    if (!$stmt) {
        error_log("❌ Error en eliminarCategoria: " . $conn->error);
        return false;
    }
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
function favoritosAvailable(): bool {
    $conn = getDBConnection();
    return tableExists($conn, "favoritos")
        && columnExists($conn, "favoritos", "usuario_id")
        && columnExists($conn, "favoritos", "producto_id");
}

function toggleFavorito(int $usuario_id, int $producto_id): bool {
    if ($usuario_id <= 0 || !favoritosAvailable()) {
        // fallback sesión
        if (!isset($_SESSION)) session_start();
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

function getFavoritosCount(?int $usuario_id): int {
    if ($usuario_id && $usuario_id > 0 && favoritosAvailable()) {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM favoritos WHERE usuario_id=?");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        return (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
    }
    if (!isset($_SESSION)) session_start();
    return isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}
