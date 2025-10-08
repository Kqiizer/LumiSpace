<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Estadísticas y reportes', $cajeroNombre, $cajaLabel);
?>

<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
  <div class="card">
    <small>Ventas semana</small>
    <div id="kSemana" style="font-size:28px;font-weight:800;color:var(--brand)">$0</div>
  </div>
  <div class="card">
    <small>Ventas hoy</small>
    <div id="kHoy" style="font-size:28px;font-weight:800;color:var(--ok)">$0</div>
  </div>
  <div class="card">
    <small>Producto más vendido</small>
    <div id="kProdTop" style="font-size:16px;font-weight:700;color:var(--ink)">—</div>
  </div>
  <div class="card">
    <small>Cajero destacado</small>
    <div id="kCajeroTop" style="font-size:16px;font-weight:700;color:var(--ink)">—</div>
  </div>
</div>

<section class="card" style="margin-top:12px; min-height:240px;">
  <h3 style="margin-top:0">Ventas por día</h3>
  <canvas id="chartDia" width="900" height="240"></canvas>
</section>

<section class="card" style="margin-top:12px; min-height:240px;">
  <h3 style="margin-top:0">Ventas por categoría</h3>
  <canvas id="chartCat" width="900" height="240"></canvas>
</section>

<?php end_pos_page(); ?>
