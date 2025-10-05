<?php
// Cargar funciones globales
require_once __DIR__ . "/../config/functions.php";
require_once __DIR__ . '/../config/db.php';

// ================= DATOS =================
$ventasHoy       = getVentasHoy();             // Total de ventas del día
$ventasRecientes = getVentasRecientes(6);      // Últimas 6 ventas
$resumenHoy      = getResumenHoy();            // Resumen del día
$clientesHoy     = getClientesUnicosHoy();     // Clientes únicos hoy
$corteCaja       = getCorteCajaHoy();          // Corte por método de pago
$ventasCategoria = getVentasPorCategoria();    // Ventas por categoría
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Ventas - LumiSpace</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Estilos -->
    <link rel="stylesheet" href="../css/styles/dashboard.css">
</head>
<body>

    <h1>📊 Panel de Ventas</h1>

    <!-- Resumen del día -->
    <section class="resumen">
        <h2>Resumen del Día</h2>
        <?php if (!empty($resumenHoy)): ?>
        <ul>
            <li><strong>Total Vendido:</strong> $<?= number_format($resumenHoy['total'], 2) ?></li>
            <li><strong>Transacciones:</strong> <?= $resumenHoy['transacciones'] ?></li>
            <li><strong>Productos Vendidos:</strong> <?= $resumenHoy['productos'] ?></li>
            <li><strong>Clientes Únicos:</strong> <?= $clientesHoy ?></li>
        </ul>
        <?php else: ?>
            <p>No hay datos de ventas hoy.</p>
        <?php endif; ?>
    </section>

    <!-- Corte de caja -->
    <section class="corte-caja">
        <h2>Corte de Caja (Hoy)</h2>
        <?php if (!empty($corteCaja)): ?>
            <ul>
                <?php foreach ($corteCaja as $metodo => $total): ?>
                    <li><strong><?= htmlspecialchars($metodo) ?>:</strong> $<?= number_format($total, 2) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay registros de ventas hoy.</p>
        <?php endif; ?>
    </section>

    <!-- Ventas recientes -->
    <section class="ventas-recientes">
        <h2>Ventas Recientes</h2>
        <?php if (!empty($ventasRecientes)): ?>
            <table border="1" cellpadding="8" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Cliente</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventasRecientes as $venta): ?>
                        <tr>
                            <td>#<?= $venta['id'] ?></td>
                            <td><?= htmlspecialchars($venta['cliente'] ?? 'Desconocido') ?></td>
                            <td><?= htmlspecialchars($venta['productos']) ?></td>
                            <td>$<?= number_format($venta['total'], 2) ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($venta['fecha'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No hay ventas recientes.</p>
        <?php endif; ?>
    </section>

    <!-- Ventas por categoría -->
    <section class="ventas-categoria">
        <h2>Ventas por Categoría</h2>
        <?php if (!empty($ventasCategoria)): ?>
            <ul>
                <?php foreach ($ventasCategoria as $cat): ?>
                    <li><strong><?= htmlspecialchars($cat['categoria']) ?>:</strong> $<?= number_format($cat['total'], 2) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay registros de ventas por categoría.</p>
        <?php endif; ?>
    </section>

</body>
</html>
