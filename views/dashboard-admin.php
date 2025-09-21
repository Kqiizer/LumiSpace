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
  <style>
    /* ===== PALETA CLARA POR DEFECTO ===== */
    body {
      background: linear-gradient(135deg, #f5f5f7, #ffffff, #ececec);
      font-family: "Segoe UI", Arial, sans-serif;
      color: #222;
    }
    .content { padding: 24px; }
    .page-title {
      font-size: 1.8rem; font-weight: 700;
      margin-bottom: 12px; color: #ff8c42;
    }

    /* ===== GRID & CARDS ===== */
    .grid { display: grid; gap: 20px; }
    .grid-4 { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
    .grid-2 { grid-template-columns: 1fr 1fr; }

    .card {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 6px 14px rgba(0,0,0,.08);
      transition: transform .2s ease;
    }
    .card:hover { transform: translateY(-4px); }

    /* ===== MÉTRICAS ===== */
    .metric { text-align: center; }
    .metric-title { font-size: .95rem; color: #666; margin-bottom: 8px; display:block; }
    .metric-val { font-size: 1.8rem; font-weight: 700; color: #222; }
    .metric-val::after {
      content:""; display:block; height:2px; width:30px;
      background:#ff8c42; margin:6px auto 0; border-radius:2px;
    }

    /* ===== LISTAS ===== */
    .list { list-style: none; margin: 0; padding: 0; }
    .list li {
      display: flex; align-items: center; justify-content: space-between;
      padding: 10px; border-bottom: 1px solid #eee;
    }
    .ava {
      width: 36px; height: 36px; border-radius: 50%;
      background: #ff8c42; color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-weight: bold;
    }
    .pill {
      background: #ff8c42; color: #fff;
      font-size: .8rem; padding: 4px 10px;
      border-radius: 20px;
    }

    /* ===== CHARTS ===== */
    canvas {
      background: #fafafa;
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 10px;
    }

    /* ===== MODO OSCURO (se activa con body.dark) ===== */
    body.dark {
      background: linear-gradient(135deg, #1c1b1a, #2e2620, #3d322b);
      color: #fff;
    }
    body.dark .card {
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      color: #fff;
    }
    body.dark .metric-title { color: #ccc; }
    body.dark .metric-val { color: #fff; }
    body.dark .list li { border-bottom: 1px solid rgba(255,255,255,0.1); }
    body.dark .ava { background: #ffb366; color: #1c1b1a; }
    body.dark .pill { background: #ffb366; color: #1c1b1a; }
    body.dark canvas { background: rgba(255,255,255,0.05); border:none; }
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
          <canvas id="usuariosChart" height="120"></canvas>
        </article>
        <article class="card">
          <h3>💵 Ventas Mensuales</h3>
          <canvas id="ventasChart" height="120"></canvas>
        </article>
      </div>

      <!-- LISTAS -->
      <div class="grid grid-2 mt-16">
        <article class="card">
          <h3>👤 Usuarios Recientes</h3>
          <ul class="list">
            <?php foreach ($usuariosRecientes as $u): ?>
              <li>
                <div style="display:flex; gap:10px; align-items:center;">
                  <span class="ava"><?= strtoupper(substr($u['nombre'],0,2)) ?></span>
                  <div>
                    <div><strong><?= htmlspecialchars($u['nombre']) ?></strong></div>
                    <div style="font-size:.85rem; color:#666;"><?= htmlspecialchars($u['email']) ?></div>
                  </div>
                </div>
                <div style="font-size:.8rem; color:#999;"><?= timeAgo($u['fecha_registro']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>

        <article class="card">
          <h3>📦 Resumen Inventario</h3>
          <ul class="list">
            <?php foreach ($inventarioResumen as $item): ?>
              <li>
                <span><?= htmlspecialchars($item['categoria']) ?></span>
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
  // 📈 Gráfico de usuarios mensuales
  new Chart(document.getElementById('usuariosChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: "Usuarios nuevos",
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: "#ff8c42",
        backgroundColor: "rgba(255,140,66,0.2)",
        fill:true,
        tension:0.4
      }]
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
        backgroundColor: "#6c63ff"
      }]
    },
    options: { plugins:{legend:{display:false}} }
  });
  </script>
</body>
</html>
