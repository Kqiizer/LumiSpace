<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/../../config/functions.php";

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$count = 0;

if ($usuario_id > 0) {
    $conn = getDBConnection();
    // Verificar si la tabla existe
    $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
    if ($chk && $chk->num_rows > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM favoritos WHERE usuario_id=?");
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $count = (int)($row['count'] ?? 0);
            }
            $stmt->close();
        }
    }
} else {
    // Fallback a sesión si no está autenticado
    $count = isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}

echo json_encode(['count' => $count]);

