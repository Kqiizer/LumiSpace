<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

header("Content-Type: application/json; charset=UTF-8");

// ==========================
// 📥 Leer JSON del POS
// ==========================
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo json_encode(["ok" => false, "msg" => "Datos no válidos"]);
    exit;
}

// ==========================
// ✅ Validaciones mínimas
// ==========================
$total     = isset($data['total']) ? floatval($data['total']) : 0;
$metodo    = $data['metodo'] ?? 'efectivo';
$nota      = trim($data['nota'] ?? '');
$detalles  = $data['detalles'] ?? [];
$usuarioId = $_SESSION['usuario_id'] ?? null; // si manejas login

if ($total <= 0 || empty($detalles)) {
    echo json_encode(["ok" => false, "msg" => "Venta sin productos o total inválido"]);
    exit;
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    // ==========================
    // 📝 Insertar cabecera
    // ==========================
    $sql = "INSERT INTO ventas (usuario_id, total, metodo_pago, nota, fecha) 
            VALUES (?,?,?,?,NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $usuarioId, $total, $metodo, $nota);
    $stmt->execute();
    $ventaId = $conn->insert_id;

    // ==========================
    // 📦 Insertar detalle + stock
    // ==========================
    $sqlDetalle = "INSERT INTO detalle_ventas 
                    (venta_id, producto_id, cantidad, precio_unitario, total_linea) 
                   VALUES (?,?,?,?,?)";
    $stmtDet = $conn->prepare($sqlDetalle);

    $sqlStock = "UPDATE productos SET stock = stock - ? WHERE id = ?";
    $stmtStock = $conn->prepare($sqlStock);

    foreach ($detalles as $item) {
        $pid    = (int)($item['id'] ?? 0);
        $cant   = (int)($item['qty'] ?? 0);
        $precio = (float)($item['precio'] ?? 0);

        if ($pid > 0 && $cant > 0) {
            $totalLinea = round($precio * $cant, 2);

            // Insertar detalle
            $stmtDet->bind_param("iiidd", $ventaId, $pid, $cant, $precio, $totalLinea);
            $stmtDet->execute();

            // Actualizar stock
            $stmtStock->bind_param("ii", $cant, $pid);
            $stmtStock->execute();
        }
    }

    $conn->commit();

    echo json_encode([
        "ok"    => true,
        "id"    => $ventaId,
        "total" => $total,
        "msg"   => "✅ Venta registrada correctamente"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "ok"  => false,
        "msg" => "❌ Error al guardar la venta: " . $e->getMessage()
    ]);
}
