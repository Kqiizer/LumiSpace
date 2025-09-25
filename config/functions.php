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
function getVentasHoy(): array {
    $conn = getDBConnection();
    $sql = "SELECT COUNT(*) as transacciones, SUM(total) as total 
            FROM ventas 
            WHERE DATE(fecha) = CURDATE()";
    $res = $conn->query($sql);
    return $res->fetch_assoc() ?: ["transacciones" => 0, "total" => 0];
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

function getVentasMensuales(): array {
    $conn = getDBConnection();
    $sql = "SELECT MONTHNAME(fecha) as mes, SUM(total) as total 
            FROM ventas 
            WHERE YEAR(fecha)=YEAR(NOW())
            GROUP BY MONTH(fecha)";
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
   function getProductosMasVendidos(int $limit = 10): array {
    $conn = getDBConnection();
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, 
                   SUM(dv.cantidad) as total_vendido
            FROM productos p
            JOIN detalle_ventas dv ON p.id = dv.producto_id
            GROUP BY p.id
            ORDER BY total_vendido DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
}

   function getProductosPublicos($limit = 12): array {
    $conn = getDBConnection();
    $sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.precio_original, 
                   p.descuento, p.imagen, c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.estado = 'activo'
            ORDER BY p.creado_en DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
function getProductosPorCategoria($categoria = null, $limit = 12) {
    $conn = getDBConnection();
    $limit = (int)$limit; // ✅ Sanitizamos el límite

    if ($categoria) {
        $sql = "SELECT * FROM productos WHERE categoria = ? ORDER BY creado_en DESC LIMIT $limit";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error en prepare: " . $conn->error);
        }
        $stmt->bind_param("s", $categoria);
    } else {
        $sql = "SELECT * FROM productos ORDER BY creado_en DESC LIMIT $limit";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error en prepare: " . $conn->error);
        }
    }

    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_all(MYSQLI_ASSOC);
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
