<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

// === Leer JSON ===
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["ok" => false, "msg" => "Datos no válidos"]);
    exit;
}

// === Variables de entrada ===
$usuarioId  = $_SESSION['usuario_id'] ?? null; // id del cajero/gestor
$cart       = $data['detalles'] ?? [];
$nota       = trim($data['nota'] ?? '');
$pagos      = $data['pagos'] ?? ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0];
$descGlobal = (float)($data['desc_global_pct'] ?? 0);

// === Validación básica ===
if (!$cart) {
    echo json_encode(["ok"=>false,"msg"=>"Carrito vacío"]);
    exit;
}

// === Calcular totales ===
$subtotal = 0;
foreach ($cart as $it) {
    $precio   = (float)($it['precio'] ?? 0);
    $cant     = (int)($it['qty'] ?? 0);
    $descPct  = (float)($it['descPct'] ?? 0);
    if ($cant <= 0) continue;

    $precioDesc = $precio * (1 - $descPct/100);
    $subtotal  += $precioDesc * $cant;
}

$descMonto = round($subtotal * ($descGlobal/100), 2);
$base      = $subtotal - $descMonto;
$iva       = round($base * 0.16, 2);
$total     = round($base + $iva, 2);

// === Método principal ===
$metodoPrincipal = array_search(max($pagos), $pagos) ?: 'efectivo';

// === Guardar en BD ===
$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Cabecera
    $sql = "INSERT INTO ventas 
        (usuario_id, subtotal, descuento_total, iva, total, 
         pago_efectivo, pago_tarjeta, pago_transferencia, 
         metodo_pago, nota, fecha) 
        VALUES (?,?,?,?,?,?,?,?,?,?,NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iddddddss", 
        $usuarioId, $subtotal, $descMonto, $iva, $total,
        $pagos['efectivo'], $pagos['tarjeta'], $pagos['transferencia'],
        $metodoPrincipal, $nota
    );
    $stmt->execute();
    $ventaId = $conn->insert_id;

    // Detalles
    $sqlDet = "INSERT INTO detalle_ventas 
        (venta_id, producto_id, cantidad, precio_unitario, descuento_pct) 
        VALUES (?,?,?,?,?)";
    $stmtDet = $conn->prepare($sqlDet);

    $sqlStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmtStock = $conn->prepare($sqlStock);

    foreach ($cart as $it) {
        $pid     = (int)($it['id'] ?? 0);
        $cant    = (int)($it['qty'] ?? 0);
        $precio  = (float)($it['precio'] ?? 0);
        $descPct = (float)($it['descPct'] ?? 0);

        if ($pid <= 0 || $cant <= 0) continue;

        $stmtDet->bind_param("iiidi", $ventaId, $pid, $cant, $precio, $descPct);
        $stmtDet->execute();

        // Reducir stock
        $stmtStock->bind_param("ii", $cant, $pid);
        $stmtStock->execute();
    }

    $conn->commit();

    echo json_encode([
        "ok" => true,
        "venta_id" => $ventaId,
        "total" => $total,
        "msg" => "Venta registrada correctamente"
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        "ok" => false,
        "msg" => "Error al guardar venta: ".$e->getMessage()
    ]);
}
