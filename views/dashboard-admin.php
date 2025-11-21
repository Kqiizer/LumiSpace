<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

// 🚨 Validación: solo rol admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../views/login.php?error=unauthorized");
    exit();
}

/* ======================================================
   DATOS DESDE LA BD
   ====================================================== */
$totalUsuarios     = getTotalUsuarios();
$totalGestores     = getTotalGestores();
$totalProductos    = getTotalProductos();
$ingresosMes       = getIngresosMes();
$usuariosRecientes = getUsuariosRecientes(5);
$inventarioResumen = getInventarioResumen();
$usuariosMensuales = getUsuariosMensuales();
$ventasMensuales   = getVentasMensuales();

// 📌 Última conexión
if (!isset($_SESSION['ultima_conexion'])) {
    $_SESSION['ultima_conexion'] = date('Y-m-d H:i:s');
}
$ultimaConexion = $_SESSION['ultima_conexion'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para iconos del sidebar -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    /* ===== MEJORAS PROFESIONALES ===== */
    body {
      font-family: 'Roboto', "Segoe UI", Arial, sans-serif;
    }
    
    .content { 
      padding: 24px;
      animation: fadeIn 0.6s ease;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .page-title {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 24px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.5px;
    }

    /* ===== GRID & CARDS MEJORADOS ===== */
    .grid { display: grid; gap: 20px; }
    .grid-4 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    .grid-2 { grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); }

    .card {
      background: var(--card-bg-1);
      border: 1px solid var(--card-bd);
      border-radius: 16px;
      padding: 20px;
      box-shadow: var(--shadow);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }
    
    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--act1), var(--act2));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 28px rgba(0,0,0,0.15);
    }
    
    .card:hover::before {
      transform: scaleX(1);
    }

    /* ===== MÉTRICAS PREMIUM ===== */
    .metric { 
      text-align: center;
      padding: 24px 20px;
    }
    
    .metric-title { 
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--muted);
      margin-bottom: 12px;
      display: block;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .metric-val { 
      font-size: 2.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      line-height: 1;
      margin: 12px 0;
    }
    
    .metric-val::after {
      content: "";
      display: block;
      height: 3px;
      width: 50px;
      background: linear-gradient(90deg, var(--act1), var(--act2));
      margin: 12px auto 0;
      border-radius: 3px;
    }

    /* ===== TÍTULOS DE SECCIÓN ===== */
    .card h3 {
      font-size: 1.1rem;
      font-weight: 700;
      margin: 0 0 20px 0;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--card-bd);
    }

    /* ===== LISTAS MEJORADAS ===== */
    .list { 
      list-style: none;
      margin: 0;
      padding: 0;
    }
    
    .list li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px;
      border-radius: 10px;
      margin-bottom: 8px;
      transition: all 0.2s ease;
      border: 1px solid transparent;
    }
    
    .list li:hover {
      background: var(--card-bg-2);
      border-color: var(--card-bd);
      transform: translateX(4px);
    }
    
    .ava {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
    }
    
    .pill {
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: 20px;
      box-shadow: 0 2px 8px rgba(161, 104, 58, 0.25);
    }

    /* ===== CHARTS PROFESIONALES ===== */
    .chart-container {
      position: relative;
      padding: 20px;
    }
    
    canvas {
      background: transparent !important;
      border: none !important;
      border-radius: 0 !important;
      padding: 0 !important;
    }

    /* ===== UTILIDADES ===== */
    .mt-16 { margin-top: 24px; }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .grid-4,
      .grid-2 {
        grid-template-columns: 1fr;
      }
      
      .page-title {
        font-size: 1.5rem;
      }
      
      .metric-val {
        font-size: 2rem;
      }
    }

    /* ===== MODO OSCURO MEJORADO ===== */
    body.dark .card {
      background: var(--card-bg-1);
      border: 1px solid var(--card-bd);
    }
    
    body.dark .card:hover {
      box-shadow: 0 12px 28px rgba(0,0,0,0.4);
    }
    
    body.dark .ava {
      background: linear-gradient(135deg, #d4a373, #c7925b);
      box-shadow: 0 4px 12px rgba(212, 163, 115, 0.4);
    }
    
    body.dark .pill {
      background: linear-gradient(135deg, #d4a373, #c7925b);
    }
    
    body.dark .list li:hover {
      background: rgba(255, 255, 255, 0.05);
    }
  </style>
</head>
<body>
  <?php include("../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include("../includes/header-admin.php"); ?>

    <section class="content">
      <h1 class="page-title">📊 Panel de Control</h1>

      <!-- MÉTRICAS -->
      <div class="grid grid-4">
        <article class="card metric">
          <span class="metric-title">Usuarios Totales</span>
          <div class="metric-val"><?= $totalUsuarios ?></div>
        </article>
        <article class="card metric">
          <span class="metric-title">Gestores Activos</span>
          <div class="metric-val"><?= $totalGestores ?></div>
        </article>
        <article class="card metric">
          <span class="metric-title">Productos</span>
          <div class="metric-val"><?= $totalProductos ?></div>
        </article>
        <article class="card metric">
          <span class="metric-title">Ingresos Mes</span>
          <div class="metric-val"><?= formatCurrency($ingresosMes) ?></div>
        </article>
      </div>

      <!-- GRÁFICOS -->
      <div class="grid grid-2 mt-16">
        <article class="card">
          <h3>📈 Crecimiento de Usuarios</h3>
          <div class="chart-container">
            <canvas id="usuariosChart" height="120"></canvas>
          </div>
        </article>
        <article class="card">
          <h3>💵 Ventas Mensuales</h3>
          <div class="chart-container">
            <canvas id="ventasChart" height="120"></canvas>
          </div>
        </article>
      </div>

      <!-- LISTAS -->
      <div class="grid grid-2 mt-16">
        <article class="card">
          <h3>👤 Usuarios Recientes</h3>
          <ul class="list">
            <?php foreach ($usuariosRecientes as $u): ?>
              <li>
                <div style="display:flex; gap:12px; align-items:center;">
                  <span class="ava"><?= strtoupper(substr($u['nombre'],0,2)) ?></span>
                  <div>
                    <div style="font-weight:600; color:var(--text); margin-bottom:2px;">
                      <?= htmlspecialchars($u['nombre']) ?>
                    </div>
                    <div style="font-size:0.85rem; color:var(--muted);">
                      <?= htmlspecialchars($u['email']) ?>
                    </div>
                  </div>
                </div>
                <div style="font-size:0.8rem; color:var(--muted); font-weight:500;">
                  <?= timeAgo($u['fecha_registro']) ?>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>

        <article class="card">
          <h3>📦 Resumen Inventario</h3>
          <ul class="list">
            <?php foreach ($inventarioResumen as $item): ?>
              <li>
                <span style="font-weight:600; color:var(--text);">
                  <?= htmlspecialchars($item['categoria']) ?>
                </span>
                <span class="pill"><?= (int)$item['cantidad'] ?> ítems</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // Configuración global profesional
  Chart.defaults.font.family = 'Roboto, system-ui, sans-serif';
  Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--muted') || '#7a6f65';

  // 📈 Gráfico de usuarios mensuales
  new Chart(document.getElementById('usuariosChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: "Usuarios nuevos",
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: "#a1683a",
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 300);
          gradient.addColorStop(0, 'rgba(161, 104, 58, 0.3)');
          gradient.addColorStop(1, 'rgba(161, 104, 58, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: '#a1683a',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7,
        pointHoverBorderWidth: 3
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            usePointStyle: true,
            padding: 15,
            font: { size: 12, weight: '600' }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.85)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          displayColors: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            font: { size: 11 }
          }
        },
        x: {
          grid: { display: false },
          ticks: {
            padding: 10,
            font: { size: 11 }
          }
        }
      }
    }
  });

  // 📊 Gráfico de ventas mensuales
  new Chart(document.getElementById('ventasChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($ventasMensuales, 'mes')) ?>,
      datasets: [{
        label: "Ingresos ($)",
        data: <?= json_encode(array_column($ventasMensuales, 'total')) ?>,
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 300);
          gradient.addColorStop(0, '#a1683a');
          gradient.addColorStop(1, '#8f5e4b');
          return gradient;
        },
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            usePointStyle: true,
            padding: 15,
            font: { size: 12, weight: '600' }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.85)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          displayColors: false,
          callbacks: {
            label: function(context) {
              return 'Ingresos: 
if (!isset($_SESSION['ultima_conexion'])) {
    $_SESSION['ultima_conexion'] = date('Y-m-d H:i:s');
}
$ultimaConexion = $_SESSION['ultima_conexion'];

// 📊 Cálculos adicionales para métricas avanzadas
$crecimientoUsuarios = count($usuariosMensuales) > 1 
    ? (($usuariosMensuales[count($usuariosMensuales)-1]['total'] - $usuariosMensuales[count($usuariosMensuales)-2]['total']) / $usuariosMensuales[count($usuariosMensuales)-2]['total']) * 100 
    : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../css/dashboard-professional.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <?php include("../includes/sidebar-admin.php"); ?>
  
  <main class="main">
    <?php include("../includes/header-admin.php"); ?>

    <section class="content">
      <!-- PAGE HEADER -->
      <div class="page-header">
        <div>
          <h1 class="page-title">📊 Panel de Control</h1>
          <p class="page-subtitle">Vista general del sistema • Última actualización: <?= date('d/m/Y H:i') ?></p>
        </div>
      </div>

      <!-- METRIC CARDS -->
      <div class="grid grid-4 gap-16">
        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">👥</div>
            <div class="metric-trend up" data-tooltip="Crecimiento vs mes anterior">
              ↑ <?= number_format(abs($crecimientoUsuarios), 1) ?>%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Usuarios Totales</div>
            <div class="metric-value"><?= number_format($totalUsuarios) ?></div>
          </div>
          <div class="metric-footer">
            <span>📈</span>
            <span>Usuarios activos en la plataforma</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">⚡</div>
            <div class="metric-trend up" data-tooltip="Estado del sistema">
              ✓ Activos
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Gestores Activos</div>
            <div class="metric-value"><?= number_format($totalGestores) ?></div>
          </div>
          <div class="metric-footer">
            <span>🔧</span>
            <span>Personal gestionando el sistema</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">📦</div>
            <div class="metric-trend up" data-tooltip="Productos en inventario">
              ✓ Stock
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Productos</div>
            <div class="metric-value"><?= number_format($totalProductos) ?></div>
          </div>
          <div class="metric-footer">
            <span>🏷️</span>
            <span>Artículos en catálogo</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">💰</div>
            <div class="metric-trend up" data-tooltip="Ingresos del mes actual">
              ↑ +15.3%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Ingresos del Mes</div>
            <div class="metric-value"><?= formatCurrency($ingresosMes) ?></div>
          </div>
          <div class="metric-footer">
            <span>💳</span>
            <span>Revenue generado este mes</span>
          </div>
        </article>
      </div>

      <!-- CHARTS -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📈 Crecimiento de Usuarios</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="usuariosChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💵 Ventas Mensuales</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="ventasChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICAS ADICIONALES -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📊 Ventas por Categoría</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Mensual</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="categoriasChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">🔥 Productos Más Vendidos</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Top 5</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="productosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICA DE INGRESOS DIARIOS -->
      <div class="grid mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💰 Ingresos Últimos 7 Días</h3>
            <div class="chart-controls">
              <button class="chart-btn active">7D</button>
              <button class="chart-btn">15D</button>
              <button class="chart-btn">30D</button>
            </div>
          </div>
          <div class="chart-wrapper" style="height: 200px;">
            <canvas id="ingresosDiariosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- LISTS & INVENTORY -->
      <div class="grid grid-2 gap-16 mt-24">
        <!-- USUARIOS RECIENTES -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              👤 Usuarios Recientes
              <span class="list-badge"><?= count($usuariosRecientes) ?></span>
            </h3>
            <a href="usuarios.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Ver todos →</a>
          </div>
          <div>
            <?php foreach ($usuariosRecientes as $u): ?>
              <div class="list-item">
                <div class="list-avatar"><?= strtoupper(substr($u['nombre'], 0, 2)) ?></div>
                <div class="list-content">
                  <div class="list-name"><?= htmlspecialchars($u['nombre']) ?></div>
                  <div class="list-meta"><?= htmlspecialchars($u['email']) ?> • <?= timeAgo($u['fecha_registro']) ?></div>
                </div>
                <button class="list-action">Ver perfil</button>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <!-- INVENTARIO -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              📦 Resumen de Inventario
              <span class="list-badge"><?= count($inventarioResumen) ?></span>
            </h3>
            <a href="inventario.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Gestionar →</a>
          </div>
          <div>
            <?php 
            $maxCantidad = max(array_column($inventarioResumen, 'cantidad'));
            foreach ($inventarioResumen as $item): 
              $porcentaje = ($item['cantidad'] / $maxCantidad) * 100;
            ?>
              <div class="inventory-item">
                <div class="inventory-header">
                  <span class="inventory-label"><?= htmlspecialchars($item['categoria']) ?></span>
                  <span class="inventory-value"><?= number_format((int)$item['cantidad']) ?> ítems</span>
                </div>
                <div class="inventory-bar">
                  <div class="inventory-fill" style="width: <?= $porcentaje ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="quick-actions">
        <div class="quick-action" onclick="window.location.href='usuarios.php'">
          <div class="quick-action-icon">👥</div>
          <div class="quick-action-label">Gestionar Usuarios</div>
        </div>
        <div class="quick-action" onclick="window.location.href='productos.php'">
          <div class="quick-action-icon">📦</div>
          <div class="quick-action-label">Administrar Productos</div>
        </div>
        <div class="quick-action" onclick="window.location.href='reportes.php'">
          <div class="quick-action-icon">📊</div>
          <div class="quick-action-label">Ver Reportes</div>
        </div>
        <div class="quick-action" onclick="window.location.href='configuracion.php'">
          <div class="quick-action-icon">⚙️</div>
          <div class="quick-action-label">Configuración</div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // Configuración global de Chart.js
  Chart.defaults.font.family = 'Roboto, system-ui, sans-serif';
  Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--muted');
  Chart.defaults.plugins.legend.display = false;

  // 📈 Gráfico de Usuarios Mensuales
  const ctxUsuarios = document.getElementById('usuariosChart').getContext('2d');
  const usuariosChart = new Chart(ctxUsuarios, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Nuevos Usuarios',
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: '#a1683a',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, 'rgba(161, 104, 58, 0.3)');
          gradient.addColorStop(1, 'rgba(161, 104, 58, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: '#a1683a',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Usuarios: ' + context.parsed.y;
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            precision: 0
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas Mensuales
  const ctxVentas = document.getElementById('ventasChart').getContext('2d');
  const ventasChart = new Chart(ctxVentas, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($ventasMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Ingresos ($)',
        data: <?= json_encode(array_column($ventasMensuales, 'total')) ?>,
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, '#a1683a');
          gradient.addColorStop(1, '#8f5e4b');
          return gradient;
        },
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas por Categoría (Pie Chart)
  const ctxCategorias = document.getElementById('categoriasChart').getContext('2d');
  <?php if (isset($ventasPorCategoria) && !empty($ventasPorCategoria)): ?>
  new Chart(ctxCategorias, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_column($ventasPorCategoria, 'categoria')) ?>,
      datasets: [{
        label: 'Ventas',
        data: <?= json_encode(array_column($ventasPorCategoria, 'total')) ?>,
        backgroundColor: [
          '#a1683a',
          '#8f5e4b',
          '#7a6a4b',
          '#c7925b',
          '#d4a373'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'right'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((context.parsed / total) * 100).toFixed(1);
              return context.label + ': 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.toLocaleString() + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
  <?php else: ?>
  // Si no hay datos, mostrar mensaje
  ctxCategorias.fillStyle = '#999';
  ctxCategorias.font = '14px Roboto';
  ctxCategorias.textAlign = 'center';
  ctxCategorias.fillText('No hay datos disponibles', ctxCategorias.canvas.width / 2, ctxCategorias.canvas.height / 2);
  <?php endif; ?>

  // 🔥 Gráfico de Productos Populares (Bar Horizontal)
  const ctxProductos = document.getElementById('productosChart').getContext('2d');
  <?php if (isset($productosPopulares) && !empty($productosPopulares)): ?>
  new Chart(ctxProductos, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($productosPopulares, 'nombre')) ?>,
      datasets: [{
        label: 'Unidades Vendidas',
        data: <?= json_encode(array_column($productosPopulares, 'cantidad')) ?>,
        backgroundColor: function(context) {
          const colors = ['#a1683a', '#8f5e4b', '#7a6a4b', '#c7925b', '#d4a373'];
          return colors[context.dataIndex % colors.length];
        },
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Vendidos: ' + context.parsed.x + ' unidades';
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            precision: 0
          }
        },
        y: {
          grid: {
            display: false
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxProductos.fillStyle = '#999';
  ctxProductos.font = '14px Roboto';
  ctxProductos.textAlign = 'center';
  ctxProductos.fillText('No hay datos disponibles', ctxProductos.canvas.width / 2, ctxProductos.canvas.height / 2);
  <?php endif; ?>

  // 💰 Gráfico de Ingresos Diarios (Area Chart)
  const ctxIngresos = document.getElementById('ingresosDiariosChart').getContext('2d');
  <?php if (isset($ingresosPorDia) && !empty($ingresosPorDia)): ?>
  new Chart(ctxIngresos, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($ingresosPorDia, 'fecha')) ?>,
      datasets: [{
        label: 'Ingresos Diarios',
        data: <?= json_encode(array_column($ingresosPorDia, 'total')) ?>,
        borderColor: '#1abc9c',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 200);
          gradient.addColorStop(0, 'rgba(26, 188, 156, 0.3)');
          gradient.addColorStop(1, 'rgba(26, 188, 156, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 4,
        pointBackgroundColor: '#1abc9c',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxIngresos.fillStyle = '#999';
  ctxIngresos.font = '14px Roboto';
  ctxIngresos.textAlign = 'center';
  ctxIngresos.fillText('No hay datos disponibles', ctxIngresos.canvas.width / 2, ctxIngresos.canvas.height / 2);
  <?php endif; ?>

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
              });
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            font: { size: 11 },
            callback: function(value) {
              return '
if (!isset($_SESSION['ultima_conexion'])) {
    $_SESSION['ultima_conexion'] = date('Y-m-d H:i:s');
}
$ultimaConexion = $_SESSION['ultima_conexion'];

// 📊 Cálculos adicionales para métricas avanzadas
$crecimientoUsuarios = count($usuariosMensuales) > 1 
    ? (($usuariosMensuales[count($usuariosMensuales)-1]['total'] - $usuariosMensuales[count($usuariosMensuales)-2]['total']) / $usuariosMensuales[count($usuariosMensuales)-2]['total']) * 100 
    : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../css/dashboard-professional.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <?php include("../includes/sidebar-admin.php"); ?>
  
  <main class="main">
    <?php include("../includes/header-admin.php"); ?>

    <section class="content">
      <!-- PAGE HEADER -->
      <div class="page-header">
        <div>
          <h1 class="page-title">📊 Panel de Control</h1>
          <p class="page-subtitle">Vista general del sistema • Última actualización: <?= date('d/m/Y H:i') ?></p>
        </div>
      </div>

      <!-- METRIC CARDS -->
      <div class="grid grid-4 gap-16">
        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">👥</div>
            <div class="metric-trend up" data-tooltip="Crecimiento vs mes anterior">
              ↑ <?= number_format(abs($crecimientoUsuarios), 1) ?>%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Usuarios Totales</div>
            <div class="metric-value"><?= number_format($totalUsuarios) ?></div>
          </div>
          <div class="metric-footer">
            <span>📈</span>
            <span>Usuarios activos en la plataforma</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">⚡</div>
            <div class="metric-trend up" data-tooltip="Estado del sistema">
              ✓ Activos
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Gestores Activos</div>
            <div class="metric-value"><?= number_format($totalGestores) ?></div>
          </div>
          <div class="metric-footer">
            <span>🔧</span>
            <span>Personal gestionando el sistema</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">📦</div>
            <div class="metric-trend up" data-tooltip="Productos en inventario">
              ✓ Stock
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Productos</div>
            <div class="metric-value"><?= number_format($totalProductos) ?></div>
          </div>
          <div class="metric-footer">
            <span>🏷️</span>
            <span>Artículos en catálogo</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">💰</div>
            <div class="metric-trend up" data-tooltip="Ingresos del mes actual">
              ↑ +15.3%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Ingresos del Mes</div>
            <div class="metric-value"><?= formatCurrency($ingresosMes) ?></div>
          </div>
          <div class="metric-footer">
            <span>💳</span>
            <span>Revenue generado este mes</span>
          </div>
        </article>
      </div>

      <!-- CHARTS -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📈 Crecimiento de Usuarios</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="usuariosChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💵 Ventas Mensuales</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="ventasChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICAS ADICIONALES -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📊 Ventas por Categoría</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Mensual</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="categoriasChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">🔥 Productos Más Vendidos</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Top 5</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="productosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICA DE INGRESOS DIARIOS -->
      <div class="grid mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💰 Ingresos Últimos 7 Días</h3>
            <div class="chart-controls">
              <button class="chart-btn active">7D</button>
              <button class="chart-btn">15D</button>
              <button class="chart-btn">30D</button>
            </div>
          </div>
          <div class="chart-wrapper" style="height: 200px;">
            <canvas id="ingresosDiariosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- LISTS & INVENTORY -->
      <div class="grid grid-2 gap-16 mt-24">
        <!-- USUARIOS RECIENTES -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              👤 Usuarios Recientes
              <span class="list-badge"><?= count($usuariosRecientes) ?></span>
            </h3>
            <a href="usuarios.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Ver todos →</a>
          </div>
          <div>
            <?php foreach ($usuariosRecientes as $u): ?>
              <div class="list-item">
                <div class="list-avatar"><?= strtoupper(substr($u['nombre'], 0, 2)) ?></div>
                <div class="list-content">
                  <div class="list-name"><?= htmlspecialchars($u['nombre']) ?></div>
                  <div class="list-meta"><?= htmlspecialchars($u['email']) ?> • <?= timeAgo($u['fecha_registro']) ?></div>
                </div>
                <button class="list-action">Ver perfil</button>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <!-- INVENTARIO -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              📦 Resumen de Inventario
              <span class="list-badge"><?= count($inventarioResumen) ?></span>
            </h3>
            <a href="inventario.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Gestionar →</a>
          </div>
          <div>
            <?php 
            $maxCantidad = max(array_column($inventarioResumen, 'cantidad'));
            foreach ($inventarioResumen as $item): 
              $porcentaje = ($item['cantidad'] / $maxCantidad) * 100;
            ?>
              <div class="inventory-item">
                <div class="inventory-header">
                  <span class="inventory-label"><?= htmlspecialchars($item['categoria']) ?></span>
                  <span class="inventory-value"><?= number_format((int)$item['cantidad']) ?> ítems</span>
                </div>
                <div class="inventory-bar">
                  <div class="inventory-fill" style="width: <?= $porcentaje ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="quick-actions">
        <div class="quick-action" onclick="window.location.href='usuarios.php'">
          <div class="quick-action-icon">👥</div>
          <div class="quick-action-label">Gestionar Usuarios</div>
        </div>
        <div class="quick-action" onclick="window.location.href='productos.php'">
          <div class="quick-action-icon">📦</div>
          <div class="quick-action-label">Administrar Productos</div>
        </div>
        <div class="quick-action" onclick="window.location.href='reportes.php'">
          <div class="quick-action-icon">📊</div>
          <div class="quick-action-label">Ver Reportes</div>
        </div>
        <div class="quick-action" onclick="window.location.href='configuracion.php'">
          <div class="quick-action-icon">⚙️</div>
          <div class="quick-action-label">Configuración</div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // Configuración global de Chart.js
  Chart.defaults.font.family = 'Roboto, system-ui, sans-serif';
  Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--muted');
  Chart.defaults.plugins.legend.display = false;

  // 📈 Gráfico de Usuarios Mensuales
  const ctxUsuarios = document.getElementById('usuariosChart').getContext('2d');
  const usuariosChart = new Chart(ctxUsuarios, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Nuevos Usuarios',
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: '#a1683a',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, 'rgba(161, 104, 58, 0.3)');
          gradient.addColorStop(1, 'rgba(161, 104, 58, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: '#a1683a',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Usuarios: ' + context.parsed.y;
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            precision: 0
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas Mensuales
  const ctxVentas = document.getElementById('ventasChart').getContext('2d');
  const ventasChart = new Chart(ctxVentas, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($ventasMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Ingresos ($)',
        data: <?= json_encode(array_column($ventasMensuales, 'total')) ?>,
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, '#a1683a');
          gradient.addColorStop(1, '#8f5e4b');
          return gradient;
        },
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas por Categoría (Pie Chart)
  const ctxCategorias = document.getElementById('categoriasChart').getContext('2d');
  <?php if (isset($ventasPorCategoria) && !empty($ventasPorCategoria)): ?>
  new Chart(ctxCategorias, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_column($ventasPorCategoria, 'categoria')) ?>,
      datasets: [{
        label: 'Ventas',
        data: <?= json_encode(array_column($ventasPorCategoria, 'total')) ?>,
        backgroundColor: [
          '#a1683a',
          '#8f5e4b',
          '#7a6a4b',
          '#c7925b',
          '#d4a373'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'right'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((context.parsed / total) * 100).toFixed(1);
              return context.label + ': 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.toLocaleString() + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
  <?php else: ?>
  // Si no hay datos, mostrar mensaje
  ctxCategorias.fillStyle = '#999';
  ctxCategorias.font = '14px Roboto';
  ctxCategorias.textAlign = 'center';
  ctxCategorias.fillText('No hay datos disponibles', ctxCategorias.canvas.width / 2, ctxCategorias.canvas.height / 2);
  <?php endif; ?>

  // 🔥 Gráfico de Productos Populares (Bar Horizontal)
  const ctxProductos = document.getElementById('productosChart').getContext('2d');
  <?php if (isset($productosPopulares) && !empty($productosPopulares)): ?>
  new Chart(ctxProductos, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($productosPopulares, 'nombre')) ?>,
      datasets: [{
        label: 'Unidades Vendidas',
        data: <?= json_encode(array_column($productosPopulares, 'cantidad')) ?>,
        backgroundColor: function(context) {
          const colors = ['#a1683a', '#8f5e4b', '#7a6a4b', '#c7925b', '#d4a373'];
          return colors[context.dataIndex % colors.length];
        },
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Vendidos: ' + context.parsed.x + ' unidades';
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            precision: 0
          }
        },
        y: {
          grid: {
            display: false
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxProductos.fillStyle = '#999';
  ctxProductos.font = '14px Roboto';
  ctxProductos.textAlign = 'center';
  ctxProductos.fillText('No hay datos disponibles', ctxProductos.canvas.width / 2, ctxProductos.canvas.height / 2);
  <?php endif; ?>

  // 💰 Gráfico de Ingresos Diarios (Area Chart)
  const ctxIngresos = document.getElementById('ingresosDiariosChart').getContext('2d');
  <?php if (isset($ingresosPorDia) && !empty($ingresosPorDia)): ?>
  new Chart(ctxIngresos, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($ingresosPorDia, 'fecha')) ?>,
      datasets: [{
        label: 'Ingresos Diarios',
        data: <?= json_encode(array_column($ingresosPorDia, 'total')) ?>,
        borderColor: '#1abc9c',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 200);
          gradient.addColorStop(0, 'rgba(26, 188, 156, 0.3)');
          gradient.addColorStop(1, 'rgba(26, 188, 156, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 4,
        pointBackgroundColor: '#1abc9c',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxIngresos.fillStyle = '#999';
  ctxIngresos.font = '14px Roboto';
  ctxIngresos.textAlign = 'center';
  ctxIngresos.fillText('No hay datos disponibles', ctxIngresos.canvas.width / 2, ctxIngresos.canvas.height / 2);
  <?php endif; ?>

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: { display: false },
          ticks: {
            padding: 10,
            font: { size: 11 }
          }
        }
      }
    }
  });

  // ✨ Animaciones de entrada
  document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(20px)';
      setTimeout(() => {
        card.style.transition = 'all 0.5s ease';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
      }, index * 100);
    });
  });
  </script>
</body>
</html>
if (!isset($_SESSION['ultima_conexion'])) {
    $_SESSION['ultima_conexion'] = date('Y-m-d H:i:s');
}
$ultimaConexion = $_SESSION['ultima_conexion'];

// 📊 Cálculos adicionales para métricas avanzadas
$crecimientoUsuarios = count($usuariosMensuales) > 1 
    ? (($usuariosMensuales[count($usuariosMensuales)-1]['total'] - $usuariosMensuales[count($usuariosMensuales)-2]['total']) / $usuariosMensuales[count($usuariosMensuales)-2]['total']) * 100 
    : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Admin - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../css/dashboard-professional.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Font Awesome para iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <?php include("../includes/sidebar-admin.php"); ?>
  
  <main class="main">
    <?php include("../includes/header-admin.php"); ?>

    <section class="content">
      <!-- PAGE HEADER -->
      <div class="page-header">
        <div>
          <h1 class="page-title">📊 Panel de Control</h1>
          <p class="page-subtitle">Vista general del sistema • Última actualización: <?= date('d/m/Y H:i') ?></p>
        </div>
      </div>

      <!-- METRIC CARDS -->
      <div class="grid grid-4 gap-16">
        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">👥</div>
            <div class="metric-trend up" data-tooltip="Crecimiento vs mes anterior">
              ↑ <?= number_format(abs($crecimientoUsuarios), 1) ?>%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Usuarios Totales</div>
            <div class="metric-value"><?= number_format($totalUsuarios) ?></div>
          </div>
          <div class="metric-footer">
            <span>📈</span>
            <span>Usuarios activos en la plataforma</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">⚡</div>
            <div class="metric-trend up" data-tooltip="Estado del sistema">
              ✓ Activos
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Gestores Activos</div>
            <div class="metric-value"><?= number_format($totalGestores) ?></div>
          </div>
          <div class="metric-footer">
            <span>🔧</span>
            <span>Personal gestionando el sistema</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">📦</div>
            <div class="metric-trend up" data-tooltip="Productos en inventario">
              ✓ Stock
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Productos</div>
            <div class="metric-value"><?= number_format($totalProductos) ?></div>
          </div>
          <div class="metric-footer">
            <span>🏷️</span>
            <span>Artículos en catálogo</span>
          </div>
        </article>

        <article class="metric-card">
          <div class="metric-header">
            <div class="metric-icon">💰</div>
            <div class="metric-trend up" data-tooltip="Ingresos del mes actual">
              ↑ +15.3%
            </div>
          </div>
          <div class="metric-body">
            <div class="metric-label">Ingresos del Mes</div>
            <div class="metric-value"><?= formatCurrency($ingresosMes) ?></div>
          </div>
          <div class="metric-footer">
            <span>💳</span>
            <span>Revenue generado este mes</span>
          </div>
        </article>
      </div>

      <!-- CHARTS -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📈 Crecimiento de Usuarios</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="usuariosChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💵 Ventas Mensuales</h3>
            <div class="chart-controls">
              <button class="chart-btn active" data-period="6m">6M</button>
              <button class="chart-btn" data-period="1y">1A</button>
              <button class="chart-btn" data-period="all">Todo</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="ventasChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICAS ADICIONALES -->
      <div class="grid grid-2 gap-16 mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">📊 Ventas por Categoría</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Mensual</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="categoriasChart"></canvas>
          </div>
        </article>

        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">🔥 Productos Más Vendidos</h3>
            <div class="chart-controls">
              <button class="chart-btn active">Top 5</button>
            </div>
          </div>
          <div class="chart-wrapper">
            <canvas id="productosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- GRÁFICA DE INGRESOS DIARIOS -->
      <div class="grid mt-24">
        <article class="chart-card">
          <div class="chart-header">
            <h3 class="chart-title">💰 Ingresos Últimos 7 Días</h3>
            <div class="chart-controls">
              <button class="chart-btn active">7D</button>
              <button class="chart-btn">15D</button>
              <button class="chart-btn">30D</button>
            </div>
          </div>
          <div class="chart-wrapper" style="height: 200px;">
            <canvas id="ingresosDiariosChart"></canvas>
          </div>
        </article>
      </div>

      <!-- LISTS & INVENTORY -->
      <div class="grid grid-2 gap-16 mt-24">
        <!-- USUARIOS RECIENTES -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              👤 Usuarios Recientes
              <span class="list-badge"><?= count($usuariosRecientes) ?></span>
            </h3>
            <a href="usuarios.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Ver todos →</a>
          </div>
          <div>
            <?php foreach ($usuariosRecientes as $u): ?>
              <div class="list-item">
                <div class="list-avatar"><?= strtoupper(substr($u['nombre'], 0, 2)) ?></div>
                <div class="list-content">
                  <div class="list-name"><?= htmlspecialchars($u['nombre']) ?></div>
                  <div class="list-meta"><?= htmlspecialchars($u['email']) ?> • <?= timeAgo($u['fecha_registro']) ?></div>
                </div>
                <button class="list-action">Ver perfil</button>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <!-- INVENTARIO -->
        <article class="list-card">
          <div class="list-header">
            <h3 class="list-title">
              📦 Resumen de Inventario
              <span class="list-badge"><?= count($inventarioResumen) ?></span>
            </h3>
            <a href="inventario.php" style="color: var(--act1); font-size: 0.9rem; font-weight: 600; text-decoration: none;">Gestionar →</a>
          </div>
          <div>
            <?php 
            $maxCantidad = max(array_column($inventarioResumen, 'cantidad'));
            foreach ($inventarioResumen as $item): 
              $porcentaje = ($item['cantidad'] / $maxCantidad) * 100;
            ?>
              <div class="inventory-item">
                <div class="inventory-header">
                  <span class="inventory-label"><?= htmlspecialchars($item['categoria']) ?></span>
                  <span class="inventory-value"><?= number_format((int)$item['cantidad']) ?> ítems</span>
                </div>
                <div class="inventory-bar">
                  <div class="inventory-fill" style="width: <?= $porcentaje ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="quick-actions">
        <div class="quick-action" onclick="window.location.href='usuarios.php'">
          <div class="quick-action-icon">👥</div>
          <div class="quick-action-label">Gestionar Usuarios</div>
        </div>
        <div class="quick-action" onclick="window.location.href='productos.php'">
          <div class="quick-action-icon">📦</div>
          <div class="quick-action-label">Administrar Productos</div>
        </div>
        <div class="quick-action" onclick="window.location.href='reportes.php'">
          <div class="quick-action-icon">📊</div>
          <div class="quick-action-label">Ver Reportes</div>
        </div>
        <div class="quick-action" onclick="window.location.href='configuracion.php'">
          <div class="quick-action-icon">⚙️</div>
          <div class="quick-action-label">Configuración</div>
        </div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  // Configuración global de Chart.js
  Chart.defaults.font.family = 'Roboto, system-ui, sans-serif';
  Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--muted');
  Chart.defaults.plugins.legend.display = false;

  // 📈 Gráfico de Usuarios Mensuales
  const ctxUsuarios = document.getElementById('usuariosChart').getContext('2d');
  const usuariosChart = new Chart(ctxUsuarios, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Nuevos Usuarios',
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: '#a1683a',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, 'rgba(161, 104, 58, 0.3)');
          gradient.addColorStop(1, 'rgba(161, 104, 58, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 5,
        pointBackgroundColor: '#a1683a',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Usuarios: ' + context.parsed.y;
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            precision: 0
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas Mensuales
  const ctxVentas = document.getElementById('ventasChart').getContext('2d');
  const ventasChart = new Chart(ctxVentas, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($ventasMensuales, 'mes')) ?>,
      datasets: [{
        label: 'Ingresos ($)',
        data: <?= json_encode(array_column($ventasMensuales, 'total')) ?>,
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 280);
          gradient.addColorStop(0, '#a1683a');
          gradient.addColorStop(1, '#8f5e4b');
          return gradient;
        },
        borderRadius: 8,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          titleFont: { size: 14, weight: 'bold' },
          bodyFont: { size: 13 },
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        },
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });

  // 📊 Gráfico de Ventas por Categoría (Pie Chart)
  const ctxCategorias = document.getElementById('categoriasChart').getContext('2d');
  <?php if (isset($ventasPorCategoria) && !empty($ventasPorCategoria)): ?>
  new Chart(ctxCategorias, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_column($ventasPorCategoria, 'categoria')) ?>,
      datasets: [{
        label: 'Ventas',
        data: <?= json_encode(array_column($ventasPorCategoria, 'total')) ?>,
        backgroundColor: [
          '#a1683a',
          '#8f5e4b',
          '#7a6a4b',
          '#c7925b',
          '#d4a373'
        ],
        borderWidth: 2,
        borderColor: '#fff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'right'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = ((context.parsed / total) * 100).toFixed(1);
              return context.label + ': 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.toLocaleString() + ' (' + percentage + '%)';
            }
          }
        }
      }
    }
  });
  <?php else: ?>
  // Si no hay datos, mostrar mensaje
  ctxCategorias.fillStyle = '#999';
  ctxCategorias.font = '14px Roboto';
  ctxCategorias.textAlign = 'center';
  ctxCategorias.fillText('No hay datos disponibles', ctxCategorias.canvas.width / 2, ctxCategorias.canvas.height / 2);
  <?php endif; ?>

  // 🔥 Gráfico de Productos Populares (Bar Horizontal)
  const ctxProductos = document.getElementById('productosChart').getContext('2d');
  <?php if (isset($productosPopulares) && !empty($productosPopulares)): ?>
  new Chart(ctxProductos, {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($productosPopulares, 'nombre')) ?>,
      datasets: [{
        label: 'Unidades Vendidas',
        data: <?= json_encode(array_column($productosPopulares, 'cantidad')) ?>,
        backgroundColor: function(context) {
          const colors = ['#a1683a', '#8f5e4b', '#7a6a4b', '#c7925b', '#d4a373'];
          return colors[context.dataIndex % colors.length];
        },
        borderRadius: 6
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Vendidos: ' + context.parsed.x + ' unidades';
            }
          }
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            precision: 0
          }
        },
        y: {
          grid: {
            display: false
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxProductos.fillStyle = '#999';
  ctxProductos.font = '14px Roboto';
  ctxProductos.textAlign = 'center';
  ctxProductos.fillText('No hay datos disponibles', ctxProductos.canvas.width / 2, ctxProductos.canvas.height / 2);
  <?php endif; ?>

  // 💰 Gráfico de Ingresos Diarios (Area Chart)
  const ctxIngresos = document.getElementById('ingresosDiariosChart').getContext('2d');
  <?php if (isset($ingresosPorDia) && !empty($ingresosPorDia)): ?>
  new Chart(ctxIngresos, {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($ingresosPorDia, 'fecha')) ?>,
      datasets: [{
        label: 'Ingresos Diarios',
        data: <?= json_encode(array_column($ingresosPorDia, 'total')) ?>,
        borderColor: '#1abc9c',
        backgroundColor: function(context) {
          const ctx = context.chart.ctx;
          const gradient = ctx.createLinearGradient(0, 0, 0, 200);
          gradient.addColorStop(0, 'rgba(26, 188, 156, 0.3)');
          gradient.addColorStop(1, 'rgba(26, 188, 156, 0.0)');
          return gradient;
        },
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointRadius: 4,
        pointBackgroundColor: '#1abc9c',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index'
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end'
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          padding: 12,
          borderRadius: 8,
          callbacks: {
            label: function(context) {
              return 'Ingresos: 

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + context.parsed.y.toLocaleString('es-MX', {minimumFractionDigits: 2});
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)',
            drawBorder: false
          },
          ticks: {
            padding: 10,
            callback: function(value) {
              return '

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html> + value.toLocaleString();
            }
          }
        },
        x: {
          grid: {
            display: false
          },
          ticks: {
            padding: 10
          }
        }
      }
    }
  });
  <?php else: ?>
  ctxIngresos.fillStyle = '#999';
  ctxIngresos.font = '14px Roboto';
  ctxIngresos.textAlign = 'center';
  ctxIngresos.fillText('No hay datos disponibles', ctxIngresos.canvas.width / 2, ctxIngresos.canvas.height / 2);
  <?php endif; ?>

  // 🎨 Animación de entrada para las cards
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, index) => {
      if (entry.isIntersecting) {
        setTimeout(() => {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }, index * 100);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.metric-card, .chart-card, .list-card').forEach((card) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
  });

  // 🎯 Ripple effect en botones
  document.querySelectorAll('.quick-action, .list-action, .chart-btn').forEach(el => {
    el.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.width = ripple.style.height = size + 'px';
      ripple.style.left = x + 'px';
      ripple.style.top = y + 'px';
      
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // 📊 Actualización en tiempo real (simulación)
  setInterval(() => {
    const metricValues = document.querySelectorAll('.metric-value');
    metricValues.forEach(el => {
      el.style.transform = 'scale(1.05)';
      setTimeout(() => {
        el.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Cada 30 segundos

  // 🎨 Animación de barras de inventario
  window.addEventListener('load', () => {
    document.querySelectorAll('.inventory-fill').forEach(bar => {
      const width = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => {
        bar.style.width = width;
      }, 300);
    });
  });

  // 🌓 Toggle de tema (si existe)
  const themeToggle = document.querySelector('[data-theme-toggle]');
  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
  }

  // Restaurar tema guardado
  if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark');
  }

  console.log('%c🚀 Dashboard LumiSpace v2.0', 'color: #a1683a; font-size: 16px; font-weight: bold;');
  console.log('%cPanel de administración cargado correctamente', 'color: #1abc9c; font-size: 12px;');
  </script>
</body>
</html>
