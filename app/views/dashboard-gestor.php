<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🚨 Validación de rol
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'gestor') {
    header("Location: ../views/login.php?error=unauthorized");
    exit();
}

/* =========================
   ARCHIVOS DE DATOS
   ========================= */
require_once __DIR__ . "/../gestor/ventas.php";
require_once __DIR__ . "/../gestor/productos.php";
require_once __DIR__ . "/../gestor/usuarios.php";
require_once __DIR__ . "/../gestor/helpers.php";

/* =========================
   DATOS DINÁMICOS
   ========================= */
$ventasHoy       = getVentasHoy();
$ventasRecientes = getVentasRecientes(6);
$productosTop    = getProductosDestacados(5);
$ventasMensuales = getVentasMensuales();
$ventasPorCat    = getVentasPorCategoria();
$corteCaja       = getCorteCajaHoy();
$ultimoAcceso    = getUltimoAcceso($_SESSION['usuario_id']);
$clientesHoy     = getClientesUnicosHoy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Gestor - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css" />
</head>
<body>
  <!-- Sidebar Gestor -->
  <?php include("../includes/sidebar-gestor.php"); ?>

  <main class="main">
    <?php include("../includes/header-gestor.php"); ?>

    <section class="content">
      <!-- 📊 MÉTRICAS RÁPIDAS -->
      <div class="grid grid-4 gap-16">
        <article class="card metric glass"><span class="metric-title">Ventas Hoy</span><div class="metric-val"><?= formatCurrency($ventasHoy) ?></div></article>
        <article class="card metric glass"><span class="metric-title">Productos Vendidos</span><div class="metric-val"><?= array_sum(array_column($ventasRecientes, 'cantidad')) ?></div></article>
        <article class="card metric glass"><span class="metric-title">Transacciones</span><div class="metric-val"><?= count($ventasRecientes) ?></div></article>
        <article class="card metric glass"><span class="metric-title">Clientes Únicos</span><div class="metric-val"><?= $clientesHoy ?></div></article>
      </div>

      <!-- 📈 GRÁFICOS -->
      <div class="grid grid-2-1 gap-16 mt-16">
        <article class="card p-16 glass">
          <header class="card-head"><span class="card-title">Tendencia de Ventas <?= date("Y") ?></span></header>
          <canvas id="ventasChart" height="110"></canvas>
        </article>
        <article class="card p-16 glass">
          <header class="card-head"><span class="card-title">Ventas por Categoría</span></header>
          <canvas id="categoriasChart" height="160"></canvas>
        </article>
      </div>

      <!-- 📋 LISTAS -->
      <div class="grid grid-2 gap-16 mt-16">
        <article class="card p-16 glass">
          <header class="card-head"><span class="card-title">Ventas Recientes</span></header>
          <ul class="list">
            <?php foreach ($ventasRecientes as $venta): ?>
              <li class="row glass">
                <span class="ava"><?= strtoupper(substr($venta['nombre'],0,2)) ?></span>
                <div class="info">
                  <div class="title"><?= htmlspecialchars($venta['nombre']) ?></div>
                  <div class="sub"><?= htmlspecialchars($venta['producto']) ?> x<?= (int)$venta['cantidad'] ?></div>
                </div>
                <div class="amount up"><?= formatCurrency($venta['total']) ?></div>
                <div class="time"><?= timeAgo($venta['fecha']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>

        <article class="card p-16 glass">
          <header class="card-head"><span class="card-title">Productos Destacados</span></header>
          <ul class="list">
            <?php foreach ($productosTop as $i=>$p): ?>
              <li class="row glass">
                <span class="num"><?= $i+1 ?></span>
                <div class="info">
                  <div class="title"><?= htmlspecialchars($p['nombre']) ?></div>
                  <div class="sub"><?= (int)$p['vendidos'] ?> vendidos</div>
                </div>
                <span class="pill up">+<?= rand(5,20) ?>%</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
      </div>

      <!-- ⚙️ WIDGETS -->
      <div class="grid grid-2 gap-16 mt-16">
        <div class="card p-16 glass">
          <h3>Corte de Caja</h3>
          <p>Total del día: <strong><?= formatCurrency($ventasHoy) ?></strong></p>
          <?php if ($corteCaja): ?>
            <ul>
              <?php foreach ($corteCaja as $metodo => $total): ?>
                <li><?= ucfirst($metodo) ?>: <?= formatCurrency($total) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No hay ventas registradas hoy.</p>
          <?php endif; ?>
        </div>

        <div class="card p-16 glass">
          <h3>Estado del Usuario</h3>
          <span class="status">Activo</span>
          <p>Último acceso: <?= $ultimoAcceso ? date("d/m/Y H:i", strtotime($ultimoAcceso)) : "Sin registro" ?></p>
        </div>
      </div>

      <!-- 🚀 ACCIONES RÁPIDAS -->
      <section class="card p-16 mt-16 glass">
        <header class="card-head"><span class="card-title">Acciones Rápidas</span></header>
        <div class="actions">
          <a href="pos.php" class="action glass">🧾 Nueva Venta</a>
          <a href="productos.php" class="action glass">➕ Agregar Producto</a>
          <a href="inventario.php" class="action glass">📦 Ver Inventario</a>
          <a href="reportes.php" class="action glass">📈 Generar Reporte</a>
        </div>
      </section>
    </section>
  </main>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // 📈 Gráfico Ventas Mensuales
  const ventasData = <?= json_encode($ventasMensuales) ?>;
  new Chart(document.getElementById('ventasChart'), {
    type: 'line',
    data: {
      labels: ventasData.map(v => `Mes ${v.mes}`),
      datasets: [{
        label: 'Ventas',
        data: ventasData.map(v => v.total),
        borderColor: "#d9a066",
        backgroundColor: "rgba(217, 160, 102, 0.4)",
        fill: true,
        tension: 0.3
      }]
    }
  });

  // 📊 Gráfico Ventas por Categoría
  const catData = <?= json_encode($ventasPorCat) ?>;
  new Chart(document.getElementById('categoriasChart'), {
    type: 'pie',
    data: {
      labels: catData.map(c => c.categoria),
      datasets: [{
        data: catData.map(c => c.total),
        backgroundColor: ["#d9a066", "#c58a5c", "#9c6d48", "#27ae60", "#e74c3c"]
      }]
    }
  });
  </script>
</body>
</html>
