<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/functions.php';

$carrito = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;

$favoritos = 0;
if (!empty($_SESSION['usuario_id'])) {
    $conn = getDBConnection();
    if ($stmt = $conn->prepare("SELECT COUNT(*) as c FROM favoritos WHERE usuario_id=?")) {
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->bind_result($c);
        if ($stmt->fetch()) $favoritos = (int)$c;
        $stmt->close();
    }
} else {
    $favoritos = isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}

echo json_encode([
  'ok' => true,
  'carrito' => $carrito,
  'favoritos' => $favoritos
]);
exit();
?>