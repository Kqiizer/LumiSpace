<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🚨 Validación: solo usuarios con rol "admin" pueden entrar
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../views/login.php?error=unauthorized");
    exit();
}

/* ======================================================
   FUNCIONES TEMPORALES (quita esto cuando uses tu BD real)
   ====================================================== */
function getTotalUsuarios() { return 120; }
function getTotalGestores() { return 5; }
function getTotalProductos() { return 450; }
function getIngresosMes() { return 154000.75; }
function getUsuariosRecientes($limit = 5) {
    return [
        ["nombre"=>"Carlos Pérez","email"=>"carlos@example.com","fecha_registro"=>"2025-09-08 10:30:00"],
        ["nombre"=>"Ana López","email"=>"ana@example.com","fecha_registro"=>"2025-09-07 15:10:00"],
        ["nombre"=>"María Gómez","email"=>"maria@example.com","fecha_registro"=>"2025-09-06 11:45:00"],
    ];
}
function getInventarioResumen() {
    return [
        ["categoria"=>"Iluminación LED","cantidad"=>120],
        ["categoria"=>"Exteriores","cantidad"=>80],
        ["categoria"=>"Interiores","cantidad"=>250],
    ];
}
function getUsuariosMensuales() {
    return [
        ["mes"=>"Enero","total"=>15],
        ["mes"=>"Febrero","total"=>22],
        ["mes"=>"Marzo","total"=>30],
        ["mes"=>"Abril","total"=>28],
        ["mes"=>"Mayo","total"=>35],
        ["mes"=>"Junio","total"=>40],
        ["mes"=>"Julio","total"=>50],
        ["mes"=>"Agosto","total"=>45],
        ["mes"=>"Septiembre","total"=>20],
    ];
}
function formatCurrency($amount) { return "$" . number_format($amount, 2, '.', ','); }
function timeAgo($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    if ($diff < 60) return "justo ahora";
    $minutes = floor($diff / 60);
    if ($minutes < 60) return "hace $minutes min";
    $hours = floor($minutes / 60);
    if ($hours < 24) return "hace $hours horas";
    $days = floor($hours / 24);
    return "hace $days días";
}

/* ======================================================
   DATOS
   ====================================================== */
$totalUsuarios   = getTotalUsuarios();
$totalGestores   = getTotalGestores();
$totalProductos  = getTotalProductos();
$ingresosMes     = getIngresosMes();
$usuariosRecientes = getUsuariosRecientes(5);
$inventarioResumen = getInventarioResumen();
$usuariosMensuales = getUsuariosMensuales();

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
    .time-box {
      display:flex; justify-content:space-between; align-items:center;
      padding:12px 18px; margin:0 0 20px 0;
      border-radius:8px;
      background:linear-gradient(180deg, rgba(255,255,255,0.9), rgba(255,255,255,0.6));
      box-shadow:var(--shadow);
    }
    .time-box span {
      font-weight:700; font-size:1rem; color:var(--act1);
      text-shadow:0 0 6px rgba(143,94,75,.5);
    }
  </style>
</head>
<body>
  <?php include("../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include("../includes/header-admin.php"); ?>

    <!-- Hora y última conexión -->
    <div class="time-box">
      <span id="local-time">⏳ Cargando hora...</span>
      <span id="last-conn" data-time="<?= $ultimaConexion ?>">Última conexión: ...</span>
    </div>

    <section class="content">
      <!-- MÉTRICAS -->
      <div class="grid grid-4 gap-16">
        <article class="card metric"><div class="metric-top"><span class="metric-title">Usuarios Totales</span></div><div class="metric-val"><?= $totalUsuarios ?></div></article>
        <article class="card metric"><div class="metric-top"><span class="metric-title">Gestores Activos</span></div><div class="metric-val"><?= $totalGestores ?></div></article>
        <article class="card metric"><div class="metric-top"><span class="metric-title">Productos</span></div><div class="metric-val"><?= $totalProductos ?></div></article>
        <article class="card metric"><div class="metric-top"><span class="metric-title">Ingresos Mes</span></div><div class="metric-val"><?= formatCurrency($ingresosMes) ?></div></article>
      </div>

      <!-- GRÁFICO DE CRECIMIENTO -->
      <div class="grid grid-1 gap-16 mt-16">
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Crecimiento de Usuarios</span></header>
          <canvas id="usuariosChart" height="120"></canvas>
        </article>
      </div>

      <!-- LISTAS -->
      <div class="grid grid-2 gap-16 mt-16">
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Usuarios Recientes</span></header>
          <ul class="list">
            <?php foreach ($usuariosRecientes as $u): ?>
              <li class="row clickable">
                <span class="ava"><?= strtoupper(substr($u['nombre'],0,2)) ?></span>
                <div class="info"><div class="title"><?= htmlspecialchars($u['nombre']) ?></div><div class="sub"><?= htmlspecialchars($u['email']) ?></div></div>
                <div class="time"><?= timeAgo($u['fecha_registro']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>

        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Resumen Inventario</span></header>
          <ul class="list">
            <?php foreach ($inventarioResumen as $item): ?>
              <li class="row clickable">
                <span class="title"><?= htmlspecialchars($item['categoria']) ?></span>
                <span class="pill"><?= (int)$item['cantidad'] ?> items</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </article>
      </div>

      <!-- ACCIONES -->
      <section class="card p-16 mt-16">
        <header class="card-head">
          <span class="card-title">Acciones Administrativas 
            <button id="toggle-dark" class="action" style="padding:6px 14px;height:auto;font-size:.8rem;">🌙</button>
          </span>
        </header>
        <div class="actions">
          <a href="usuarios.php" class="action"><i>👥</i> Gestionar Usuarios</a>
          <a href="productos.php" class="action"><i>📦</i> Gestionar Productos</a>
          <a href="reportes.php" class="action"><i>📈</i> Reportes Globales</a>
          <a href="../logout.php" class="action"><i>🚪</i> Cerrar Sesión</a>
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
  // 🕒 Hora en vivo
  function updateTime() {
    const now = new Date();
    const fecha = now.toLocaleDateString("es-ES",{ weekday:"long", month:"short", day:"numeric" });
    const hora  = now.toLocaleTimeString("es-ES",{ hour:"2-digit", minute:"2-digit", second:"2-digit", hour12:true });
    document.getElementById("local-time").textContent = `${fecha} - ${hora}`;
  }
  setInterval(updateTime, 1000); updateTime();

  // 📌 Última conexión -> "hace X tiempo"
  function timeAgo(dateString) {
    const date = new Date(dateString.replace(" ", "T"));
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    let interval = Math.floor(seconds / 31536000);
    if (interval >= 1) return `hace ${interval} año${interval>1?'s':''}`;
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) return `hace ${interval} mes${interval>1?'es':''}`;
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return `hace ${interval} día${interval>1?'s':''}`;
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return `hace ${interval} hora${interval>1?'s':''}`;
    interval = Math.floor(seconds / 60);
    if (interval >= 1) return `hace ${interval} minuto${interval>1?'s':''}`;
    return "justo ahora";
  }

  function updateLastConn() {
    const el = document.getElementById("last-conn");
    const rawTime = el.getAttribute("data-time");
    if (rawTime) el.textContent = "Última conexión: " + timeAgo(rawTime);
  }
  setInterval(updateLastConn, 60000);
  updateLastConn();

  // 📈 Gráfico de usuarios mensuales
  new Chart(document.getElementById('usuariosChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode(array_column($usuariosMensuales, 'mes')) ?>,
      datasets: [{
        label: "Usuarios nuevos",
        data: <?= json_encode(array_column($usuariosMensuales, 'total')) ?>,
        borderColor: "#6c63ff",
        fill:false,
        tension:0.3
      }]
    }
  });
  </script>
</body>
</html>
