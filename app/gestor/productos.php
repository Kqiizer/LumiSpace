<?php
require_once __DIR__ . "/../config/db.php";

// Crear producto
function crearProducto(string $nombre, float $precio, int $stock, string $categoria): bool {
    $db = getDBConnection();
    $sql = "INSERT INTO productos (nombre, precio, stock, categoria) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$nombre, $precio, $stock, $categoria]);
}

// Listar productos destacados
function getProductosDestacados(int $limit = 5): array {
    $db = getDBConnection();
    $sql = "SELECT nombre, vendidos, categoria FROM productos ORDER BY vendidos DESC LIMIT ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
