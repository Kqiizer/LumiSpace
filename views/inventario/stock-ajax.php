<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

header("Content-Type: application/json; charset=utf-8");

$response = [
    "ok"     => false,
    "stock"  => 0,
    "stocks" => [],
    "error"  => null
];

try {
    $conn = getDBConnection();
    $producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
    $sucursal    = isset($_GET['sucursal']) && $_GET['sucursal'] !== '' 
                    ? trim($_GET['sucursal']) 
                    : 'Principal';

    if ($producto_id > 0) {
        // 🔹 Stock de un solo producto
        $sql = "SELECT IFNULL(cantidad,0) 
                FROM inventario 
                WHERE producto_id = ? AND sucursal = ? 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare (producto): " . $conn->error);
        }

        $stmt->bind_param("is", $producto_id, $sucursal);
        if (!$stmt->execute()) {
            throw new Exception("Error en execute (producto): " . $stmt->error);
        }

        $stmt->bind_result($cantidad);
        if ($stmt->fetch()) {
            $response["stock"] = (int)$cantidad;
        } else {
            $response["stock"] = 0; // no hay registro = sin stock
        }
        $stmt->close();

        $response["ok"] = true;

    } else {
        // 🔹 Stock de todos los productos en la sucursal
        $sql = "SELECT 
                    i.producto_id, 
                    p.nombre, 
                    IFNULL(i.cantidad,0) AS cantidad
                FROM inventario i
                INNER JOIN productos p ON p.id = i.producto_id
                WHERE i.sucursal = ?
                ORDER BY p.nombre ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare (todos): " . $conn->error);
        }

        $stmt->bind_param("s", $sucursal);
        if (!$stmt->execute()) {
            throw new Exception("Error en execute (todos): " . $stmt->error);
        }

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $response["stocks"][] = [
                "id"       => (int)$row["producto_id"],
                "nombre"   => $row["nombre"],
                "cantidad" => (int)$row["cantidad"]
            ];
        }
        $result->free();
        $stmt->close();

        $response["ok"] = true;
    }

} catch (Exception $e) {
    $response["error"] = $e->getMessage();
    error_log("stock-ajax.php: " . $e->getMessage());
}

// 🔹 En desarrollo: usa JSON_PRETTY_PRINT para depurar más fácil
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
