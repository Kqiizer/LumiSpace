<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Estadísticas y reportes', $cajeroNombre, $cajaLabel);
?>

<!-- KPIs -->
<div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom:32px">
  <div class="card" style="text-align:center">
    <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;letter-spacing:0.5px">Ventas semana</small>
    <div id="kSemana" style="font-size:32px;font-weight:800;color:var(--brand);margin-top:8px">$0</div>
  </div>
  <div class="card" style="text-align:center">
    <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;letter-spacing:0.5px">Ventas hoy</small>
    <div id="kHoy" style="font-size:32px;font-weight:800;color:var(--ok);margin-top:8px">$0</div>
  </div>
  <div class="card" style="text-align:center">
    <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;letter-spacing:0.5px">Producto más vendido</small>
    <div id="kProdTop" style="font-size:16px;font-weight:700;color:var(--ink);margin-top:8px">—</div>
  </div>
  <div class="card" style="text-align:center">
    <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;letter-spacing:0.5px">Cajero destacado</small>
    <div id="kCajeroTop" style="font-size:16px;font-weight:700;color:var(--ink);margin-top:8px">—</div>
  </div>
</div>

<!-- Gráfica Diaria -->
<section class="card" style="margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h3 style="margin:0;font-size:18px">Ventas por día (últimos 7 días)</h3>
    <div style="display:flex;gap:16px;font-size:12px">
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#10b981;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Efectivo</span>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#2563eb;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Tarjeta</span>
      </div>
    </div>
  </div>
  <canvas id="chartDia" width="900" height="280"></canvas>
</section>

<!-- Gráfica Semanal -->
<section class="card" style="margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h3 style="margin:0;font-size:18px">Ventas por semana (últimas 4 semanas)</h3>
    <div style="display:flex;gap:16px;font-size:12px">
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#10b981;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Efectivo</span>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#2563eb;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Tarjeta</span>
      </div>
    </div>
  </div>
  <canvas id="chartSemana" width="900" height="280"></canvas>
</section>

<!-- Gráfica Mensual -->
<section class="card" style="margin-bottom:24px">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
    <h3 style="margin:0;font-size:18px">Ventas por mes (últimos 6 meses)</h3>
    <div style="display:flex;gap:16px;font-size:12px">
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#10b981;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Efectivo</span>
      </div>
      <div style="display:flex;align-items:center;gap:6px">
        <div style="width:16px;height:3px;background:#2563eb;border-radius:2px"></div>
        <span style="color:var(--ink-muted)">Tarjeta</span>
      </div>
    </div>
  </div>
  <canvas id="chartMes" width="900" height="280"></canvas>
</section>

<!-- Productos más vendidos -->
<section class="card">
  <h3 style="margin:0 0 20px 0;font-size:18px">Top 10 productos más vendidos</h3>
  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>Producto</th>
          <th style="text-align:right">Cantidad vendida</th>
          <th style="text-align:right">Ingresos</th>
        </tr>
      </thead>
      <tbody id="tbProductosTop">
        <tr><td colspan="4" style="text-align:center;padding:40px;color:var(--ink-light)">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</section>

<script>
(async function initEstadisticas(){
  const $ = (s) => document.querySelector(s);
  const mx = (n) => '$' + Number(n||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
const api = async (data) => {
  const r = await fetch('api.php', { method:'POST', body: new URLSearchParams(data) });
  const text = await r.text();
  try { return JSON.parse(text); }
  catch (e) {
    console.error('Respuesta NO JSON para', data, '→\n', text);
    throw new Error('Respuesta no es JSON');
  }
};


  // ========== Función para dibujar gráficas de líneas dobles ==========
  function drawDualLineChart(canvas, labels, dataEfectivo, dataTarjeta) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width;
    const H = canvas.height;
    ctx.clearRect(0, 0, W, H);

    const pad = 60;
    const padBottom = 50;
    const allData = [...dataEfectivo, ...dataTarjeta];
    const max = Math.max(...allData, 1);
    
    // Calcular coordenadas
    const X = (i) => pad + (i * (W - pad * 2) / (labels.length - 1 || 1));
    const Y = (v) => H - padBottom - ((v / max) * (H - pad - padBottom));

    // Dibujar ejes
    ctx.strokeStyle = '#e5e5e5';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(pad, H - padBottom);
    ctx.lineTo(W - pad, H - padBottom); // Eje X
    ctx.moveTo(pad, H - padBottom);
    ctx.lineTo(pad, pad); // Eje Y
    ctx.stroke();

    // Líneas de referencia horizontales
    ctx.strokeStyle = '#f5f5f5';
    ctx.lineWidth = 1;
    for (let i = 1; i <= 4; i++) {
      const y = pad + ((H - pad - padBottom) / 4) * i;
      ctx.beginPath();
      ctx.moveTo(pad, y);
      ctx.lineTo(W - pad, y);
      ctx.stroke();
    }

    // Valores en eje Y
    ctx.fillStyle = '#a3a3a3';
    ctx.font = '11px system-ui';
    ctx.textAlign = 'right';
    for (let i = 0; i <= 4; i++) {
      const val = (max / 4) * (4 - i);
      const y = pad + ((H - pad - padBottom) / 4) * i;
      ctx.fillText(mx(val), pad - 10, y + 4);
    }

    // Dibujar línea de EFECTIVO (verde)
    ctx.strokeStyle = '#10b981';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    dataEfectivo.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();

    // Puntos de EFECTIVO
    ctx.fillStyle = '#10b981';
    dataEfectivo.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      ctx.beginPath();
      ctx.arc(x, y, 4, 0, Math.PI * 2);
      ctx.fill();
      // Borde blanco
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2;
      ctx.stroke();
    });

    // Dibujar línea de TARJETA (azul)
    ctx.strokeStyle = '#2563eb';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.beginPath();
    dataTarjeta.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();

    // Puntos de TARJETA
    ctx.fillStyle = '#2563eb';
    dataTarjeta.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      ctx.beginPath();
      ctx.arc(x, y, 4, 0, Math.PI * 2);
      ctx.fill();
      // Borde blanco
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2;
      ctx.stroke();
    });

    // Labels en eje X
    ctx.fillStyle = '#737373';
    ctx.font = '11px system-ui';
    ctx.textAlign = 'center';
    const step = Math.ceil(labels.length / 7) || 1;
    labels.forEach((lb, i) => {
      if (i % step === 0 || i === labels.length - 1) {
        const x = X(i);
        ctx.fillText(lb.slice(-5), x, H - padBottom + 20);
      }
    });
  }

  // ========== Cargar datos y renderizar ==========
  try {
    // KPIs - Cargamos uno por uno para detectar errores
    console.log('Cargando KPIs...');
    
    const hoy = await api({action: 'stats_hoy'});
    console.log('Hoy:', hoy);
    
    const sem = await api({action: 'stats_semana'});
    console.log('Semana:', sem);
    
    const ptop = await api({action: 'stats_producto_top'});
    console.log('Producto top:', ptop);
    
    const ctop = await api({action: 'stats_cajero_top'});
    console.log('Cajero top:', ctop);

    $('#kHoy').textContent = mx(hoy.total || 0) + ` (${hoy.ventas || 0})`;
    $('#kSemana').textContent = mx(sem.total || 0) + ` (${sem.ventas || 0})`;
    $('#kProdTop').textContent = ptop.data 
      ? `${ptop.data.nombre} — ${ptop.data.cant} pzas` 
      : '—';
    $('#kCajeroTop').textContent = ctop.data 
      ? `${ctop.data.nombre} — ${ctop.data.ventas} ventas` 
      : '—';

    // Gráficas
    console.log('Cargando gráficas...');
    
    const diaria = await api({action: 'stats_ventas_diarias', dias: 7});
    console.log('Diaria:', diaria);
    
    const semanal = await api({action: 'stats_ventas_semanales', semanas: 4});
    console.log('Semanal:', semanal);
    
    const mensual = await api({action: 'stats_ventas_mensuales', meses: 6});
    console.log('Mensual:', mensual);
    
    const productos = await api({action: 'stats_productos_top', limit: 10});
    console.log('Productos:', productos);

    // Renderizar gráficas
    if (diaria.ok && diaria.labels && diaria.efectivo && diaria.tarjeta) {
      console.log('Dibujando gráfica diaria...');
      drawDualLineChart(
        $('#chartDia'), 
        diaria.labels, 
        diaria.efectivo, 
        diaria.tarjeta
      );
    } else {
      console.error('Datos incompletos para gráfica diaria:', diaria);
    }

    if (semanal.ok && semanal.labels && semanal.efectivo && semanal.tarjeta) {
      console.log('Dibujando gráfica semanal...');
      drawDualLineChart(
        $('#chartSemana'), 
        semanal.labels, 
        semanal.efectivo, 
        semanal.tarjeta
      );
    } else {
      console.error('Datos incompletos para gráfica semanal:', semanal);
    }

    if (mensual.ok && mensual.labels && mensual.efectivo && mensual.tarjeta) {
      console.log('Dibujando gráfica mensual...');
      drawDualLineChart(
        $('#chartMes'), 
        mensual.labels, 
        mensual.efectivo, 
        mensual.tarjeta
      );
    } else {
      console.error('Datos incompletos para gráfica mensual:', mensual);
    }

    // Top productos
    const tb = $('#tbProductosTop');
    if (productos.ok && productos.data && productos.data.length) {
      tb.innerHTML = productos.data.map((p, i) => `
        <tr>
          <td style="font-weight:700;color:var(--ink-muted)">${i + 1}</td>
          <td><b>${p.nombre}</b></td>
          <td style="text-align:right;font-weight:600">${p.cantidad} unidades</td>
          <td style="text-align:right;font-weight:700;color:var(--brand)">${mx(p.ingresos)}</td>
        </tr>
      `).join('');
    } else {
      tb.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--ink-light)">No hay datos disponibles</td></tr>';
    }

    console.log('✅ Estadísticas cargadas correctamente');

  } catch (e) {
    console.error('❌ Error cargando estadísticas:', e);
    console.error('Stack:', e.stack);
    alert('Error al cargar las estadísticas: ' + e.message);
  }
})();
</script>

<?php end_pos_page(); ?>