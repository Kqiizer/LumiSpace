<?php
include_once(__DIR__ . "/../config/db.php");

//////////////////////
// USUARIOS
//////////////////////

// Registro normal (correo + contraseña)
function registrarUsuario($nombre, $email, $password, $rol = "cliente") {
    $conn = getDBConnection();
    $hash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;

    $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);
    return $stmt->execute();
}

// Registro/inicio de sesión social (Google/Facebook)
function registrarUsuarioSocial($nombre, $email, $proveedor = "google", $rol = "cliente") {
    $conn = getDBConnection();

    // Verificar si ya existe
    $sqlCheck = "SELECT * FROM usuarios WHERE email = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $user = $stmtCheck->get_result()->fetch_assoc();

    if ($user) {
        return $user; // Ya existe → iniciar sesión directamente
    }

    // Si no existe → registrar sin password
    $sql = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, NULL, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $rol);
    $stmt->execute();

    // Obtener el usuario recién creado
    $id = $stmt->insert_id;
    return [
        "id" => $id,
        "nombre" => $nombre,
        "email" => $email,
        "rol" => $rol
    ];
}

// Buscar usuario por email (usado en login normal y social)
function obtenerUsuarioPorEmail($email) {
    $conn = getDBConnection();
    $sql = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

//////////////////////
// PRODUCTOS
//////////////////////
function obtenerProductos() {
    $conn = getDBConnection();
    $sql = "SELECT p.*, c.nombre AS categoria 
            FROM productos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function insertarProducto($nombre, $descripcion, $precio, $stock, $categoria_id, $imagen) {
    $conn = getDBConnection();
    $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiis", $nombre, $descripcion, $precio, $stock, $categoria_id, $imagen);
    return $stmt->execute();
}

//////////////////////
// VENTAS POS
//////////////////////
function registrarVentaPOS($usuario_id, $cliente_id, $total) {
    $conn = getDBConnection();
    $sql = "INSERT INTO ventas_pos (usuario_id, cliente_id, total) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", $usuario_id, $cliente_id, $total);
    $stmt->execute();
    return $conn->insert_id;
}

function registrarDetalleVentaPOS($venta_id, $producto_id, $cantidad, $precio_unitario) {
    $conn = getDBConnection();
    $subtotal = $cantidad * $precio_unitario;
    $sql = "INSERT INTO detalle_venta_pos (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidd", $venta_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
    return $stmt->execute();
}

//////////////////////
// PEDIDOS ONLINE
//////////////////////
function registrarPedido($cliente_id, $total) {
    $conn = getDBConnection();
    $sql = "INSERT INTO pedidos (cliente_id, total) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("id", $cliente_id, $total);
    $stmt->execute();
    return $conn->insert_id;
}

function registrarDetallePedido($pedido_id, $producto_id, $cantidad, $precio_unitario) {
    $conn = getDBConnection();
    $subtotal = $cantidad * $precio_unitario;
    $sql = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidd", $pedido_id, $producto_id, $cantidad, $precio_unitario, $subtotal);
    return $stmt->execute();
}

//////////////////////
// PAGOS
//////////////////////
function registrarPago($metodo, $monto, $venta_pos_id = null, $pedido_id = null) {
    $conn = getDBConnection();
    $sql = "INSERT INTO pagos (metodo, monto, venta_pos_id, pedido_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdii", $metodo, $monto, $venta_pos_id, $pedido_id);
    return $stmt->execute();
}

//////////////////////
// INVENTARIO
//////////////////////
function registrarMovimientoInventario($producto_id, $tipo, $cantidad, $motivo, $usuario_id) {
    $conn = getDBConnection();
    $sql = "INSERT INTO movimientos_inventario (producto_id, tipo, cantidad, motivo, usuario_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisi", $producto_id, $tipo, $cantidad, $motivo, $usuario_id);
    return $stmt->execute();
}
?>
