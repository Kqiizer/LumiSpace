<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php'; // conexión global (getDBConnection)

/* ============================================================
   🧱 Crear carrito si no existe
   ============================================================ */
function obtenerOCrearCarrito(int $usuario_id): int {
    $conn = getDBConnection();

    // Verificar si existe carrito activo
    $stmt = $conn->prepare("SELECT id FROM carrito WHERE usuario_id = ? AND estado = 'abierto' LIMIT 1");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        return (int)$row['id'];
    }

    // Si no existe, crearlo
    $stmt = $conn->prepare("INSERT INTO carrito (usuario_id, estado, fecha_creacion) VALUES (?, 'abierto', NOW())");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    return (int)$conn->insert_id;
}

/* ============================================================
   🛒 Agregar producto al carrito
   ============================================================ */
function agregarProductoCarrito(int $usuario_id, int $producto_id, int $cantidad = 1): bool {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    // Validar producto
    $stmt = $conn->prepare("SELECT id, precio FROM productos WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $producto = $res->fetch_assoc();
    if (!$producto) return false;

    // Verificar si ya existe en carrito
    $stmt = $conn->prepare("SELECT id, cantidad FROM carrito_detalles WHERE carrito_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $carrito_id, $producto_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // Actualizar cantidad existente
        $nueva_cantidad = (int)$row['cantidad'] + $cantidad;
        $stmt = $conn->prepare("UPDATE carrito_detalles SET cantidad = ? WHERE id = ?");
        $stmt->bind_param("ii", $nueva_cantidad, $row['id']);
        return $stmt->execute();
    }

    // Insertar nuevo producto
    $precio = (float)$producto['precio'];
    $stmt = $conn->prepare("INSERT INTO carrito_detalles (carrito_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $carrito_id, $producto_id, $cantidad, $precio);
    return $stmt->execute();
}

/* ============================================================
   ✏️ Actualizar cantidad de producto
   ============================================================ */
function actualizarCantidadCarrito(int $usuario_id, int $producto_id, int $cantidad): bool {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    // Evitar cantidades menores a 1
    $cantidad = max(1, $cantidad);

    $stmt = $conn->prepare("UPDATE carrito_detalles 
                            SET cantidad = ? 
                            WHERE carrito_id = ? AND producto_id = ?");
    $stmt->bind_param("iii", $cantidad, $carrito_id, $producto_id);
    return $stmt->execute();
}

/* ============================================================
   ❌ Eliminar producto del carrito
   ============================================================ */
function eliminarDelCarrito(int $usuario_id, int $producto_id): bool {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    $stmt = $conn->prepare("DELETE FROM carrito_detalles WHERE carrito_id = ? AND producto_id = ?");
    $stmt->bind_param("ii", $carrito_id, $producto_id);
    return $stmt->execute();
}

/* ============================================================
   🧾 Obtener carrito completo del usuario
   ============================================================ */
function getCarritoUsuario(int $usuario_id): array {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    $stmt = $conn->prepare("
        SELECT cd.id AS detalle_id, p.id AS producto_id, p.nombre, p.imagen,
               cd.cantidad, cd.precio_unitario,
               (cd.cantidad * cd.precio_unitario) AS total
        FROM carrito_detalles cd
        INNER JOIN productos p ON cd.producto_id = p.id
        WHERE cd.carrito_id = ?");
    $stmt->bind_param("i", $carrito_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $carrito = [];
    while ($row = $res->fetch_assoc()) {
        $carrito[] = $row;
    }
    return $carrito;
}

/* ============================================================
   💰 Calcular totales del carrito
   ============================================================ */
function getTotalesCarrito(int $usuario_id): array {
    $items = getCarritoUsuario($usuario_id);
    $subtotal = 0.0;

    foreach ($items as $i) {
        $subtotal += (float)$i['total'];
    }

    $envio = $subtotal > 0 ? 50.0 : 0.0;
    $iva = $subtotal * 0.16;
    $total = $subtotal + $envio + $iva;

    return [
        'subtotal' => round($subtotal, 2),
        'envio'    => round($envio, 2),
        'iva'      => round($iva, 2),
        'total'    => round($total, 2)
    ];
}

/* ============================================================
   🧹 Vaciar carrito
   ============================================================ */
function vaciarCarrito(int $usuario_id): bool {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    $stmt = $conn->prepare("DELETE FROM carrito_detalles WHERE carrito_id = ?");
    $stmt->bind_param("i", $carrito_id);
    return $stmt->execute();
}

/* ============================================================
   ✅ Finalizar compra
   ============================================================ */
function finalizarCompra(int $usuario_id, string $metodo_pago = 'efectivo'): bool {
    $conn = getDBConnection();
    $carrito_id = obtenerOCrearCarrito($usuario_id);

    // Marcar carrito como cerrado
    $stmt = $conn->prepare("UPDATE carrito SET estado = 'cerrado', metodo_pago = ?, fecha_cierre = NOW() WHERE id = ?");
    $stmt->bind_param("si", $metodo_pago, $carrito_id);
    $ok = $stmt->execute();

    // Crear nuevo carrito vacío para futuras compras
    if ($ok) {
        $conn->query("INSERT INTO carrito (usuario_id, estado, fecha_creacion) VALUES ($usuario_id, 'abierto', NOW())");
    }

    return $ok;
}
