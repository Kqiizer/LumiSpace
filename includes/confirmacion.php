<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Compra Confirmada - LumiSpace</title>
  <link rel="stylesheet" href="../css/carrito.css">
</head>
<body>
  <div class="container" style="text-align:center; padding:60px;">
    <h1>✅ ¡Gracias por tu compra!</h1>
    <p>Tu pedido ha sido registrado correctamente.</p>
    <?php if ($id): ?>
      <p><strong>ID de venta:</strong> <?= htmlspecialchars($id) ?></p>
    <?php endif; ?>
    <a href="../index.php" class="btn-outline">Volver al inicio</a>
  </div>
</body>
</html>
