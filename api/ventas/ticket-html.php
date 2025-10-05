<?php
require_once __DIR__ . "/../../config/functions.php";

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die("Falta ID de venta");
}

$conn = getDBConnection();

// Venta
$sql = "SELECT v.id, v.total, v.fecha, v.metodo_pago, u.nombre AS usuario, c.nombre AS cliente
        FROM ventas v
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        LEFT JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Detalles
$sql = "SELECT dv.cantidad, dv.subtotal, p.nombre
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ticket #<?= $venta['id']; ?></title>
<style>
body { font-family: monospace; width: 250px; margin: auto; }
h2 { text-align: center; }
hr { border: 0; border-top: 1px dashed #000; }
.total { font-size: 14px; font-weight: bold; text-align: right; }
</style>
</head>
<body onload="window.print()">

<h2>TICKET DE VENTA</h2>
<p>ID: <?= $venta['id']; ?><br>
Fecha: <?= $venta['fecha']; ?><br>
Cajero: <?= $venta['usuario']; ?><br>
Cliente: <?= $venta['cliente'] ?? 'General'; ?></p>
<hr>
<table width="100%">
<tr><th align="left">Producto</th><th>Cant</th><th align="right">Subtotal</th></tr>
<?php foreach($detalles as $d): ?>
<tr>
<td><?= htmlspecialchars($d['nombre']); ?></td>
<td align="center"><?= $d['cantidad']; ?></td>
<td align="right">$<?= number_format($d['subtotal'],2); ?></td>
</tr>
<?php endforeach; ?>
</table>
<hr>
<p class="total">TOTAL: $<?= number_format($venta['total'],2); ?></p>
<p>Método: <?= $venta['metodo_pago']; ?></p>
<hr>
<p style="text-align:center">¡Gracias por su compra!</p>

</body>
</html>
