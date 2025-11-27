<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

// Solo administradores acceden
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header('Location: ../views/login.php?error=unauthorized');
    exit();
}

// Métricas básicas
$totalUsuarios     = getTotalUsuarios();
$totalGestores     = getTotalGestores();
$totalProductos    = getTotalProductos();
$ingresosMes       = getIngresosMes();
$usuariosRecientes = getUsuariosRecientes(6);
$inventarioResumen = getInventarioResumen();
$usuariosMensuales = getUsuariosMensuales();
$ventasMensuales   = getVentasMensuales();

// Datos avanzados para gráficas
$ventasPorCategoriaRaw = getVentasPorCategoria();
$ventasPorCategoria = array_map(static function (array $row): array {
    return [
        'categoria' => $row['categoria'] ?: 'Sin categoría',
        'monto'     => (float) ($row['monto_total'] ?? 0),
    ];
}, $ventasPorCategoriaRaw ?? []);

$productosPopularesRaw = getProductosMasVendidos(5);
$productosPopulares = array_map(static function (array $row): array {
    return [
        'nombre'   => $row['nombre'] ?? 'Producto',
        'cantidad' => (int) ($row['total_vendido'] ?? 0),
    ];
}, $productosPopularesRaw ?? []);

$ingresosPorDiaRaw = getVentasPorDiaMesActual();
$ingresosPorDia = array_map(static function (array $row): array {
    $dia = isset($row['dia']) ? (int) $row['dia'] : 0;
    return [
        'fecha' => sprintf('Día %02d', $dia),
        'total' => (float) ($row['total'] ?? 0),
    ];
}, $ingresosPorDiaRaw ?? []);

// Última conexión almacenada en sesión
if (!isset($_SESSION['ultima_conexion'])) {
    $_SESSION['ultima_conexion'] = date('Y-m-d H:i:s');
}
$ultimaConexion = $_SESSION['ultima_conexion'];

// Cálculo de crecimiento de usuarios vs mes anterior
$crecimientoUsuarios = 0;
$usuariosCount = count($usuariosMensuales);
if ($usuariosCount > 1) {
    $mesActual    = (int) $usuariosMensuales[$usuariosCount - 1]['total'];
    $mesAnterior  = (int) $usuariosMensuales[$usuariosCount - 2]['total'];
    $crecimientoUsuarios = $mesAnterior === 0
        ? ($mesActual > 0 ? 100 : 0)
        : (($mesActual - $mesAnterior) / $mesAnterior) * 100;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - LumiSpace</title>
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg: #f6f3ef;
      --card-bg: #ffffff;
      --card-bg-soft: #faf7f3;
      --card-border: #e0d7cf;
      --text: #2d1f16;
      --muted: #7a6f65;
      --accent: #a1683a;
      --accent-2: #c7925b;
      --success: #1abc9c;
      --shadow: 0 20px 45px rgba(29, 16, 5, 0.08);
    }

    body {
      font-family: 'Inter', "Segoe UI", Arial, sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .main {
      min-height: 100vh;
      background: var(--bg);
    }

    .content {
      padding: 32px;
      animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .page-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 24px;
    }

    .page-title {
      font-size: 2rem;
      font-weight: 800;
      margin: 0;
      background: linear-gradient(120deg, var(--accent), var(--accent-2));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .page-subtitle {
      color: var(--muted);
      margin: 4px 0 0 0;
      font-size: 0.95rem;
    }

    .badge-pill {
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(161, 104, 58, 0.1);
      color: var(--accent);
      font-weight: 600;
      font-size: 0.9rem;
    }

    .grid {
      display: grid;
      gap: 20px;
    }

    .grid-4 {
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .grid-2 {
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    }

    .metric-card,
    .chart-card,
    .list-card,
    .quick-actions {
      background: var(--card-bg);
      border-radius: 18px;
      padding: 20px;
      border: 1px solid var(--card-border);
      box-shadow: var(--shadow);
    }

    .metric-card {
      display: flex;
      flex-direction: column;
      gap: 12px;
      position: relative;
      overflow: hidden;
    }

    .metric-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(161,104,58,0.12), rgba(199,146,91,0));
      pointer-events: none;
    }

    .metric-label {
      font-size: 0.9rem;
      color: var(--muted);
      text-transform: uppercase;
      font-weight: 600;
      letter-spacing: 0.6px;
    }

    .metric-value {
      font-size: 2.2rem;
      font-weight: 800;
      margin: 0;
    }

    .metric-trend {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--success);
    }

    .chart-card h3,
    .list-card h3 {
      margin: 0 0 16px 0;
      font-size: 1.05rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .chart-wrapper {
      position: relative;
      height: 260px;
    }

    .list-card .list-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }

    .list-avatar {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }

    .inventory-item + .inventory-item {
      margin-top: 12px;
    }

    .inventory-bar {
      width: 100%;
      height: 8px;
      border-radius: 999px;
      background: var(--card-bg-soft);
      overflow: hidden;
    }

    .inventory-fill {
      height: 100%;
      border-radius: inherit;
      background: linear-gradient(135deg, var(--accent), var(--accent-2));
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
      gap: 16px;
      margin-top: 24px;
    }

    .quick-action {
      background: var(--card-bg-soft);
      border-radius: 16px;
      padding: 16px;
      text-align: center;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .quick-action:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow);
    }

    @media (max-width: 768px) {
      .content {
        padding: 24px 16px;
      }
      .page-title {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main">
    <?php include '../includes/header-admin.php'; ?>
      <div class="grid grid-4">
        <article class="metric-card">
          <span class="metric-label">Usuarios Totales</span>
          <p class="metric-value"><?= number_format($totalUsuarios) ?></p>
          <span class="metric-trend">
            <?= $crecimientoUsuarios >= 0 ? '▲' : '▼' ?>
            <?= number_format(abs($crecimientoUsuarios), 1) ?>% vs mes anterior
          </span>
        </article>

        <article class="metric-card">
          <span class="metric-label">Gestores Activos</span>
          <p class="metric-value"><?= number_format($totalGestores) ?></p>
          <span class="metric-trend">✓ Operativos</span>
        </article>

        <article class="metric-card">
          <span class="metric-label">Productos</span>
          <p class="metric-value"><?= number_format($totalProductos) ?></p>
          <span class="metric-trend">Inventario controlado</span>
        </article>

        <article class="metric-card">
          <span class="metric-label">Ingresos del Mes</span>
          <p class="metric-value"><?= formatCurrency($ingresosMes) ?></p>
          <span class="metric-trend">Meta mensual en progreso</span>
        </article>
      </div>

      <div class="grid grid-2" style="margin-top:24px;">
        <article class="chart-card">
          <h3>📈 Crecimiento de Usuarios</h3>
          <div class="chart-wrapper">
            <canvas id="usuariosChart" height="220"></canvas>
          </div>
        </article>
        <article class="chart-card">
          <h3>💵 Ventas Mensuales</h3>
          <div class="chart-wrapper">
            <canvas id="ventasChart" height="220"></canvas>
          </div>
        </article>
      </div>

      <div class="grid grid-2" style="margin-top:24px;">
        <article class="chart-card">
          <h3>📊 Ventas por Categoría</h3>
          <div class="chart-wrapper">
            <canvas id="categoriasChart" height="240"></canvas>
          </div>
        </article>
        <article class="chart-card">
          <h3>🔥 Productos Más Vendidos</h3>
          <div class="chart-wrapper">
            <canvas id="productosChart" height="240"></canvas>
          </div>
        </article>
      </div>

      <article class="chart-card" style="margin-top:24px;">
        <h3>💰 Ingresos últimos días</h3>
        <div class="chart-wrapper" style="height:220px;">
          <canvas id="ingresosChart" height="220"></canvas>
        </div>
      </article>

      <div class="grid grid-2" style="margin-top:24px;">
        <article class="list-card">
          <h3>👥 Usuarios recientes</h3>
          <?php if (!empty($usuariosRecientes)): ?>
            <?php foreach ($usuariosRecientes as $usuario): ?>
              <div class="list-item">
                <div style="display:flex; gap:12px; align-items:center;">
                  <span class="list-avatar"><?= strtoupper(substr($usuario['nombre'], 0, 2)) ?></span>
                  <div>
                    <div style="font-weight:600;"><?= htmlspecialchars($usuario['nombre']) ?></div>
                    <div style="font-size:0.85rem; color:var(--muted);">
                      <?= htmlspecialchars($usuario['email']) ?> · <?= timeAgo($usuario['fecha_registro']) ?>
                    </div>
                  </div>
                </div>
                <button class="quick-action" style="padding:8px 16px; font-size:0.8rem;" onclick="window.location.href='usuarios.php'">
                  Ver perfil
                </button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:var(--muted);">No hay registros recientes.</p>
          <?php endif; ?>
        </article>

        <article class="list-card">
          <h3>📦 Resumen de inventario</h3>
          <?php if (!empty($inventarioResumen)): ?>
            <?php
            $maxCantidad = max(array_column($inventarioResumen, 'cantidad'));
            foreach ($inventarioResumen as $item):
                $porcentaje = $maxCantidad > 0 ? ($item['cantidad'] / $maxCantidad) * 100 : 0;
            ?>
              <div class="inventory-item">
                <div style="display:flex; justify-content:space-between; font-weight:600;">
                  <span><?= htmlspecialchars($item['categoria']) ?></span>
                  <span><?= number_format((int) $item['cantidad']) ?> ítems</span>
                </div>
                <div class="inventory-bar">
                  <div class="inventory-fill" style="width: <?= number_format($porcentaje, 2, '.', '') ?>%;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:var(--muted);">Sin datos de inventario.</p>
          <?php endif; ?>
        </article>
      </div>

      <div class="quick-actions">
        <div class="quick-action" onclick="window.location.href='usuarios.php'">Gestionar usuarios</div>
        <div class="quick-action" onclick="window.location.href='productos.php'">Administrar productos</div>
        <div class="quick-action" onclick="window.location.href='reportes.php'">Reportes</div>
        <div class="quick-action" onclick="window.location.href='configuracion.php'">Configuración</div>
      </div>
    </section>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    const usuariosMensuales = <?= json_encode($usuariosMensuales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ventasMensuales   = <?= json_encode($ventasMensuales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ventasPorCategoria = <?= json_encode($ventasPorCategoria, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const productosPopulares = <?= json_encode($productosPopulares, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ingresosPorDia     = <?= json_encode($ingresosPorDia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = getComputedStyle(document.body).getPropertyValue('--muted') || '#7a6f65';

    const currencyFormatter = new Intl.NumberFormat('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    function renderEmptyState(canvasId, message) {
      const canvas = document.getElementById(canvasId);
      if (!canvas) return;
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.font = '14px Inter, sans-serif';
      ctx.fillStyle = '#9a9289';
      ctx.textAlign = 'center';
      ctx.fillText(message, canvas.width / 2, canvas.height / 2);
    }

    function buildGradient(ctx, color, height = 300) {
      const gradient = ctx.createLinearGradient(0, 0, 0, height);
      gradient.addColorStop(0, color.replace('1)', '0.3)'));
      gradient.addColorStop(1, color.replace('1)', '0)'));
      return gradient;
    }

    // Usuarios chart
    (function renderUsuarios() {
      const labels = usuariosMensuales.map(item => item.mes);
      const data = usuariosMensuales.map(item => Number(item.total));
      if (!labels.length) {
        renderEmptyState('usuariosChart', 'Sin datos suficientes');
        return;
      }

      const ctx = document.getElementById('usuariosChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Nuevos usuarios',
            data,
            borderColor: '#a1683a',
            backgroundColor: buildGradient(ctx, 'rgba(161,104,58,1)'),
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#a1683a'
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (context) => `Usuarios: ${context.parsed.y}`
              }
            }
          },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
          }
        }
      });
    })();

    // Ventas mensuales
    (function renderVentas() {
      const labels = ventasMensuales.map(item => item.mes);
      const data = ventasMensuales.map(item => Number(item.total));
      if (!labels.length) {
        renderEmptyState('ventasChart', 'Sin ventas registradas');
        return;
      }

      new Chart(document.getElementById('ventasChart'), {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Ingresos',
            data,
            backgroundColor: labels.map(() => '#c7925b'),
            borderRadius: 8,
            borderSkipped: false
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (context) => `Ingresos: ${currencyFormatter.format(context.parsed.y)}`
              }
            }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    })();

    // Categorías
    (function renderCategorias() {
      if (!ventasPorCategoria.length) {
        renderEmptyState('categoriasChart', 'Sin ventas por categoría');
        return;
      }

      new Chart(document.getElementById('categoriasChart'), {
        type: 'doughnut',
        data: {
          labels: ventasPorCategoria.map(item => item.categoria),
          datasets: [{
            data: ventasPorCategoria.map(item => item.monto),
            backgroundColor: ['#a1683a', '#c7925b', '#7a6a4b', '#1abc9c', '#f2994a'],
            borderWidth: 2
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            tooltip: {
              callbacks: {
                label: (context) => `${context.label}: ${currencyFormatter.format(context.parsed)}`
              }
            }
          }
        }
      });
    })();

    // Productos
    (function renderProductos() {
      if (!productosPopulares.length) {
        renderEmptyState('productosChart', 'No hay productos vendidos');
        return;
      }

      new Chart(document.getElementById('productosChart'), {
        type: 'bar',
        data: {
          labels: productosPopulares.map(item => item.nombre),
          datasets: [{
            label: 'Unidades',
            data: productosPopulares.map(item => item.cantidad),
            backgroundColor: '#a1683a',
            borderRadius: 6
          }]
        },
        options: {
          indexAxis: 'y',
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (context) => `Vendidos: ${context.parsed.x}`
              }
            }
          },
          scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } }
          }
        }
      });
    })();

    // Ingresos diarios
    (function renderIngresos() {
      if (!ingresosPorDia.length) {
        renderEmptyState('ingresosChart', 'Sin datos diarios');
        return;
      }

      const ctx = document.getElementById('ingresosChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: ingresosPorDia.map(item => item.fecha),
          datasets: [{
            label: 'Ingresos diarios',
            data: ingresosPorDia.map(item => item.total),
            borderColor: '#1abc9c',
            backgroundColor: buildGradient(ctx, 'rgba(26,188,156,1)', 220),
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 4,
            pointBackgroundColor: '#1abc9c'
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (context) => `Ingresos: ${currencyFormatter.format(context.parsed.y)}`
              }
            }
          },
          scales: { y: { beginAtZero: true } }
        }
      });
    })();

    // Animaciones ligeras
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = 1;
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.15 });

    document.querySelectorAll('.metric-card, .chart-card, .list-card, .quick-action').forEach(el => {
      el.style.opacity = 0;
      el.style.transform = 'translateY(15px)';
      el.style.transition = 'all 0.5s ease';
      observer.observe(el);
    });
  </script>
</body>
</html>

