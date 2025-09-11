<?php
require_once __DIR__ . "/../config/db.php";

// Consultar inventario
function getInventario(): array {
    $db = getDBConnection();
    $sql = "SELECT id, nombre, stock, categoria FROM productos ORDER BY stock ASC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Actualizar stock
function actualizarStock(int $productoId, int $cantidad): bool {
    $db = getDBConnection();
    $sql = "UPDATE productos SET stock = stock + ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$cantidad, $productoId]);
}
