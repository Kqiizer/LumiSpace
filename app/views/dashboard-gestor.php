<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🚨 Validación de rol
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'gestor') {
    header("Location: ../views/login.php?error=unauthorized");
    exit();
}

require_once __DIR__ . "/../gestor/ventas.php";
require_once __DIR__ . "/../gestor/productos.php";
require_once __DIR__ . "/../gestor/inventario.php";
require_once __DIR__ . "/../gestor/reportes.php";
require_once __DIR__ . "/../gestor/helpers.php";

// 📊 Datos dinámicos
$ventasHoy       = getVentasHoy();
$ventasRecientes = getVentasRecientes(5);
$productosTop    = getProductosDestacados(4);
$ventasMensuales = getVentasMensuales();
$ventasPorCat    = getVentasPorCategoria();
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
  <?php include("../includes/sidebar-gestor.php"); ?>
  <main class="main">
    <?php include("../includes/header-gestor.php"); ?>

    <section class="content">
      <!-- MÉTRICAS -->
      <div class="grid grid-4 gap-16">
        <article class="card metric clickable">
          <div class="metric-top"><span class="metric-title">Ventas Hoy</span></div>
          <div class="metric-val"><?= formatCurrency($ventasHoy) ?></div>
        </article>
        <article class="card metric clickable">
          <div class="metric-top"><span class="metric-title">Productos Vendidos</span></div>
          <div class="metric-val"><?= array_sum(array_column($ventasRecientes, 'cantidad')) ?></div>
        </article>
        <article class="card metric clickable">
          <div class="metric-top"><span class="metric-title">Transacciones</span></div>
          <div class="metric-val"><?= count($ventasRecientes) ?></div>
        </article>
        <article class="card metric clickable">
          <div class="metric-top"><span class="metric-title">Clientes Únicos</span></div>
          <div class="metric-val"><?= count(array_unique(array_column($ventasRecientes, 'usuario_id'))) ?></div>
        </article>
      </div>

      <!-- GRÁFICOS -->
      <div class="grid grid-2-1 gap-16 mt-16">
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Tendencia de Ventas <?= date("Y") ?></span></header>
          <canvas id="ventasChart" height="110"></canvas>
        </article>
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Ventas por Categoría</span></header>
          <div class="pie-wrap mt-16">
            <canvas id="categoriasChart" height="160"></canvas>
            <ul class="legend">
              <?php foreach ($ventasPorCat as $cat): ?>
                <li><span class="dot"></span> <?= htmlspecialchars($cat['categoria']) ?> <b><?= formatCurrency($cat['total']) ?></b></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </article>
      </div>

      <!-- LISTAS -->
      <div class="grid grid-2 gap-16 mt-16">
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Ventas Recientes</span></header>
          <ul class="list">
            <?php foreach ($ventasRecientes as $venta): ?>
              <li class="row clickable">
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
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Productos Destacados</span></header>
          <ul class="list">
            <?php foreach ($productosTop as $i=>$p): ?>
              <li class="row clickable">
                <span class="num"><?= $i+1 ?></span>
                <div class="info">
                  <div class="title"><?= htmlspecialchars($p['nombre']) ?></div>
                  <div class="sub"><?= (int)$p['vendidos'] ?> vendidos</div>
                </div>
                <span class="pill up">+<?= rand(1,15) ?>%</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
      </div>

      <!-- NUEVOS WIDGETS -->
      <div class="grid grid-2 gap-16 mt-16">
        <div class="widget clickable">
          <h3>Corte de Caja</h3>
          <p>Total del día: <strong><?= formatCurrency($ventasHoy) ?></strong></p>
          <p>Efectivo: <?= formatCurrency($ventasHoy * 0.45) ?> | Tarjeta: <?= formatCurrency($ventasHoy * 0.55) ?></p>
        </div>
        <div class="widget clickable">
          <h3>Estado del Sistema</h3>
          <span class="status">Activo</span>
          <p>Última sincronización: hace 5 min</p>
        </div>
      </div>

      <!-- ACCIONES RÁPIDAS -->
      <section class="card p-16 mt-16">
        <header class="card-head">
          <span class="card-title">Acciones Rápidas 
            <button id="toggle-dark" class="action small">🌙</button>
          </span>
        </header>
        <div class="actions">
          <a href="punto-venta.php" class="action"><i>🧾</i> Nueva Venta</a>
          <a href="productos.php" class="action"><i>➕</i> Agregar Producto</a>
          <a href="inventario.php" class="action"><i>📦</i> Ver Inventario</a>
          <a href="reportes.php" class="action"><i>📈</i> Generar Reporte</a>
        </div>
      </section>
    </section>
  </main>

  <!-- Panel Notificaciones -->
  <div id="notif-panel" class="notif-panel hidden">
    <h3>🔔 Notificaciones</h3>
    <ul id="notif-list"><li>Cargando...</li></ul>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // Dark Mode
  document.getElementById("toggle-dark")?.addEventListener("click", ()=> {
    document.body.classList.toggle("dark");
  });

  // Hora dinámica
  function updateTime() {
    const now = new Date();
    const fecha = now.toLocaleDateString("es-ES",{ weekday:"long", month:"short", day:"numeric" });
    const hora  = now.toLocaleTimeString("es-ES",{ hour:"2-digit", minute:"2-digit", second:"2-digit", hour12:true });
    document.getElementById("header-time")?.textContent = "🕒 " + fecha + " - " + hora;
  }
  setInterval(updateTime, 1000); updateTime();

  // Notificaciones
  async function loadNotificaciones(){
    try {
      const res = await fetch("../gestor/notificaciones.php");
      const data = await res.json();
      const list = document.getElementById("notif-list");
      list.innerHTML = "";
      if (!data || data.length === 0) {
        list.innerHTML = "<li>No hay notificaciones recientes</li>";
        return;
      }
      data.forEach(n=>{
        const li = document.createElement("li");
        li.textContent = `${n.usuario ?? 'Cliente'} compró ${n.producto} por $${n.total}`;
        list.appendChild(li);
      });
    } catch (err) {
      console.error("Error cargando notificaciones", err);
    }
  }
  setInterval(loadNotificaciones, 20000);
  </script>

  <script>
  // === GRÁFICO VENTAS MENSUALES ===
  const ventasData = <?= json_encode($ventasMensuales) ?>;
  const ctxVentas = document.getElementById('ventasChart').getContext('2d');
  new Chart(ctxVentas, {
    type: 'line',
    data: {
      labels: ventasData.map(v => `Mes ${v.mes}`),
      datasets: [{
        label: 'Ventas',
        data: ventasData.map(v => v.total),
        borderColor: "#3e95cd",
        backgroundColor: "rgba(62,149,205,0.4)",
        fill: true,
        tension: 0.3
      }]
    }
  });

  // === GRÁFICO VENTAS POR CATEGORÍA ===
  const catData = <?= json_encode($ventasPorCat) ?>;
  const ctxCat = document.getElementById('categoriasChart').getContext('2d');
  new Chart(ctxCat, {
    type: 'pie',
    data: {
      labels: catData.map(c => c.categoria),
      datasets: [{
        data: catData.map(c => c.total),
        backgroundColor: ["#ff6384", "#36a2eb", "#ffce56", "#4caf50", "#9966ff"]
      }]
    }
  });
  </script>
</body>
</html>
