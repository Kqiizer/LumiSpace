<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 🚨 Validación: solo usuarios con rol "gestor" pueden entrar
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

  <style>
    /* Animaciones generales */
    @keyframes fadeInUp { from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:translateY(0);} }
    .card, .widget, .action, .row { animation: fadeInUp .6s ease forwards; }

    /* Tarjetas hover */
    .card.clickable:hover {
      transform: translateY(-6px) scale(1.03) rotateX(2deg);
      transition: all .25s ease;
      box-shadow: 0 15px 30px rgba(0,0,0,0.25);
    }

    /* Botones con gradiente */
    .action {
      position: relative; display:inline-flex; align-items:center; justify-content:center; gap:10px;
      border-radius: var(--radius); padding:14px 22px; height:64px;
      font-size:1rem; font-weight:600; color:#fff; cursor:pointer; text-decoration:none;
      transition: all 0.3s ease; box-shadow: var(--shadow); overflow:hidden;
      background: linear-gradient(90deg, var(--act1), var(--act2), var(--act3));
      background-size:200% 200%;
    }
    .action:hover {
      background-position:right center; filter:brightness(1.15);
      transform:translateY(-3px) scale(1.02);
      box-shadow:0 6px 18px rgba(0,0,0,0.3);
    }

    /* Ripple */
    .ripple {
      position:absolute; border-radius:50%; transform:scale(0);
      animation:ripple-anim .7s linear; pointer-events:none; width:140px; height:140px; opacity:.45;
    }
    @keyframes ripple-anim { to { transform:scale(4); opacity:0; } }

    /* Widgets */
    .widget { display:flex; flex-direction:column; justify-content:center;
      padding:18px; border-radius:var(--radius);
      background:linear-gradient(180deg, rgba(255,255,255,0.8), rgba(255,255,255,0.6));
      border:1px solid rgba(255,255,255,0.3); box-shadow:var(--shadow); backdrop-filter:blur(12px); font-weight:600;
    }
    .widget h3 { margin:0; font-size:1.1rem; color:var(--text); }
    .widget p { margin:6px 0 0; color:var(--muted); font-size:.9rem; }

    /* Estado sistema */
    .status { position:relative; display:inline-block; padding:6px 14px; border-radius:20px; font-size:.9rem; font-weight:700; color:#fff;
      background:var(--success); animation:pulse 1.8s infinite; }
    .status.offline { background:var(--danger); }
    @keyframes pulse {
      0%{ box-shadow:0 0 0 0 rgba(24,160,104,0.6); }
      70%{ box-shadow:0 0 0 10px rgba(24,160,104,0); }
      100%{ box-shadow:0 0 0 0 rgba(24,160,104,0); }
    }

    /* Hora */
    #local-time, #header-time {
      font-weight:700; font-size:1rem; color:var(--act1);
      text-shadow:0 0 8px rgba(143,94,75,.6), 0 0 12px rgba(143,94,75,.4);
    }

    /* Dark Mode */
    body.dark { --bg-1:#1e1b17; --bg-2:#2a2621; --text:#f0e9e2; --muted:#a89f97;
      --card-bg-1:#2d2722; --card-bg-2:#3a342d; --card-bd:#423a32; }

    /* Notificaciones */
    .notif-panel {
      position:fixed; top:70px; right:20px; width:280px;
      background:linear-gradient(180deg, var(--card-bg-1), var(--card-bg-2));
      border:1px solid var(--card-bd); border-radius:var(--radius);
      box-shadow:var(--shadow); padding:14px; z-index:1000;
    }
    .notif-panel.hidden{ display:none; }
    .notif-panel h3{ margin:0 0 10px; font-size:1rem; color:var(--text); }
    .notif-panel ul{ list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px; }
    .notif-panel li{ font-size:.9rem; color:var(--muted); }
  </style>
</head>
<body>
  <?php include("../includes/sidebar-gestor.php"); ?>
  <main class="main">
    <?php include("../includes/header-gestor.php"); ?>

    <section class="content">
      <!-- MÉTRICAS -->
      <div class="grid grid-4 gap-16">
        <article class="card metric clickable"><div class="metric-top"><span class="metric-title">Ventas Hoy</span></div><div class="metric-val"><?= formatCurrency($ventasHoy) ?></div></article>
        <article class="card metric clickable"><div class="metric-top"><span class="metric-title">Productos Vendidos</span></div><div class="metric-val"><?= array_sum(array_column($ventasRecientes, 'cantidad')) ?></div></article>
        <article class="card metric clickable"><div class="metric-top"><span class="metric-title">Transacciones</span></div><div class="metric-val"><?= count($ventasRecientes) ?></div></article>
        <article class="card metric clickable"><div class="metric-top"><span class="metric-title">Clientes Únicos</span></div><div class="metric-val"><?= count(array_unique(array_column($ventasRecientes, 'usuario_id'))) ?></div></article>
      </div>

      <!-- GRÁFICOS -->
      <div class="grid grid-2-1 gap-16 mt-16">
        <article class="card p-16 clickable"><header class="card-head"><span class="card-title">Tendencia de Ventas 2024</span></header><canvas id="ventasChart" height="110"></canvas></article>
        <article class="card p-16 clickable">
          <header class="card-head"><span class="card-title">Ventas por Categoría</span></header>
          <div class="filters mt-16">
            <button class="action led">LED</button>
            <button class="action int">Interiores</button>
            <button class="action ext">Exteriores</button>
          </div>
          <div class="pie-wrap mt-16">
            <canvas id="categoriasChart" height="160"></canvas>
            <ul class="legend">
              <?php foreach ($ventasPorCat as $cat): ?>
                <li><span class="dot"></span> <?= htmlspecialchars($cat['categoria']) ?> <b>$<?= number_format($cat['total'], 2) ?></b></li>
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
                <div class="info"><div class="title"><?= htmlspecialchars($venta['nombre']) ?></div><div class="sub"><?= htmlspecialchars($venta['producto']) ?> x<?= (int)$venta['cantidad'] ?></div></div>
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
                <div class="info"><div class="title"><?= htmlspecialchars($p['nombre']) ?></div><div class="sub"><?= (int)$p['vendidos'] ?> vendidos</div></div>
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
          <p>Total del día: <strong>$25,600</strong></p>
          <p>Efectivo: $12,300 | Tarjeta: $13,300</p>
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
            <button id="toggle-dark" class="action" style="padding:6px 14px;height:auto;font-size:.8rem;">🌙</button>
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
  // Ripple effect
  document.querySelectorAll('.clickable, .action').forEach(el => {
    el.addEventListener('click', function(e) {
      let ripple = document.createElement("span");
      ripple.classList.add("ripple");
      ripple.style.background = getComputedStyle(document.documentElement).getPropertyValue("--act1");
      this.appendChild(ripple);
      let rect = this.getBoundingClientRect();
      ripple.style.left = (e.clientX - rect.left - 70) + "px";
      ripple.style.top = (e.clientY - rect.top - 70) + "px";
      setTimeout(()=>ripple.remove(), 700);
    });
  });

  // Hora dinámica
  function updateTime() {
    const now   = new Date();
    const fecha = now.toLocaleDateString("es-ES",{ weekday:"long", month:"short", day:"numeric" });
    const hora  = now.toLocaleTimeString("es-ES",{ hour:"2-digit", minute:"2-digit", second:"2-digit", hour12:true });
    const txt   = `${fecha} - ${hora}`;
    document.getElementById("local-time")?.textContent = txt;
    document.getElementById("header-time")?.textContent = "🕒 " + txt;
  }
  setInterval(updateTime, 1000); updateTime();

  // Dark Mode
  document.getElementById("toggle-dark")?.addEventListener("click", ()=>{
    document.body.classList.toggle("dark");
  });

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
        li.textContent = `${n.mensaje} • ${new Date(n.fecha).toLocaleTimeString("es-ES",{hour:"2-digit",minute:"2-digit"})}`;
        list.appendChild(li);
      });
    } catch (err) {
      console.error("Error cargando notificaciones", err);
    }
  }
  setInterval(loadNotificaciones, 20000);

  const nb = document.getElementById("notif-btn");
  const np = document.getElementById("notif-panel");
  if (nb && np){
    nb.addEventListener("click", ()=>{
      np.classList.toggle("hidden");
      if (!np.classList.contains("hidden")) loadNotificaciones();
    });
    document.addEventListener("click", (e)=>{
      if (!np.contains(e.target) && !nb.contains(e.target)) np.classList.add("hidden");
    });
  }
  </script>
</body>
</html>
