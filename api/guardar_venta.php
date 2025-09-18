<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

// Leer JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["ok" => false, "msg" => "Datos no válidos"]);
    exit;
}

// Validaciones mínimas
$total    = isset($data['total']) ? floatval($data['total']) : 0;
$metodo   = $data['metodo'] ?? 'efectivo';
$nota     = trim($data['nota'] ?? '');
$detalles = $data['detalles'] ?? [];
$usuarioId= $_SESSION['usuario_id'] ?? null; // si manejas login

if ($total <= 0 || empty($detalles)) {
    echo json_encode(["ok" => false, "msg" => "Venta sin productos o total inválido"]);
    exit;
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // Insertar cabecera de la venta
    $sql = "INSERT INTO ventas (usuario_id, total, metodo_pago, nota) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $usuarioId, $total, $metodo, $nota);
    $stmt->execute();
    $ventaId = $conn->insert_id;

    // Insertar cada producto en detalle_ventas
    $sqlDetalle = "INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario) VALUES (?,?,?,?)";
    $stmtDet = $conn->prepare($sqlDetalle);

    foreach ($detalles as $item) {
        $pid   = (int)($item['id'] ?? 0);
        $cant  = (int)($item['qty'] ?? 0);
        $precio= (float)($item['precio'] ?? 0);

        if ($pid > 0 && $cant > 0) {
            $stmtDet->bind_param("iiid", $ventaId, $pid, $cant, $precio);
            $stmtDet->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        "ok"    => true,
        "id"    => $ventaId,
        "total" => $total,
        "msg"   => "Venta registrada correctamente"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "ok"  => false,
        "msg" => "Error al guardar la venta: " . $e->getMessage()
    ]);
}
