<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../config/functions.php";
$conn = getDBConnection();

// Leer payload JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['cart'])) {
    echo json_encode(["ok"=>false,"msg"=>"Datos incompletos"]);
    exit;
}

$usuario_id = (int)($data['usuario_id'] ?? ($_SESSION['usuario_id'] ?? 0));
$metodo_pago = "efectivo"; // puedes mejorarlo para soportar varios
$nota = $conn->real_escape_string($data['nota'] ?? "");
$cart = $data['cart'];
$pagos = $data['pagos'] ?? [];
$descGlobal = (float)($data['desc_global_pct'] ?? 0);

// === Calcular totales ===
$subtotal = 0;
$cant_total = 0;
foreach ($cart as $item) {
    $subtotal += $item['precio'] * $item['qty'];
    $cant_total += $item['qty'];
}
$descuento = $subtotal * ($descGlobal/100);
$base = $subtotal - $descuento;
$iva = $base * 0.16;
$total = $base + $iva;

$conn->begin_transaction();

try {
    // 1. Insertar venta
    $stmt = $conn->prepare("INSERT INTO ventas (usuario_id, metodo_pago, total, cantidad_total, productos, nota, fecha) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $productosTxt = implode(", ", array_map(function($i){ return $i['nombre']." x".$i['qty']; }, $cart));
    $stmt->bind_param("isdiis", $usuario_id, $metodo_pago, $total, $cant_total, $productosTxt, $nota);
    if(!$stmt->execute()) throw new Exception("Error insertando venta: ".$stmt->error);
    $venta_id = $stmt->insert_id;

    // 2. Insertar detalle de venta y actualizar stock
    foreach ($cart as $item) {
        $prod_id = (int)$item['id'];
        $cant = (int)$item['qty'];
        $precio = (float)$item['precio'];
        $subtotal_item = $precio * $cant;

        // detalle
        $stmt2 = $conn->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio, subtotal) 
                                 VALUES (?,?,?,?,?)");
        $stmt2->bind_param("iiidd", $venta_id, $prod_id, $cant, $precio, $subtotal_item);
        if(!$stmt2->execute()) throw new Exception("Error detalle: ".$stmt2->error);

        // stock
        $stmt3 = $conn->prepare("UPDATE productos SET stock = stock - ? WHERE id=? AND stock >= ?");
        $stmt3->bind_param("iii", $cant, $prod_id, $cant);
        if(!$stmt3->execute() || $stmt3->affected_rows==0){
            throw new Exception("Stock insuficiente para producto #$prod_id");
        }
    }

    // 3. Insertar pagos (opcional, si manejas varias formas)
    if (!empty($pagos)) {
        foreach ($pagos as $metodo => $monto) {
            $monto = (float)$monto;
            if($monto <= 0) continue;
            $stmt4 = $conn->prepare("INSERT INTO pagos (venta_id, metodo, monto) VALUES (?,?,?)");
            $stmt4->bind_param("isd", $venta_id, $metodo, $monto);
            $stmt4->execute();
        }
    }

    $conn->commit();
    echo json_encode(["ok"=>true,"venta_id"=>$venta_id]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error en guardar.php: ".$e->getMessage());
    echo json_encode(["ok"=>false,"msg"=>$e->getMessage()]);
}
exit();
?>