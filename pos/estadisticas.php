<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Estadísticas y reportes', $cajeroNombre, $cajaLabel);
?>

<!-- KPIs -->
<div class="grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom:32px">
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
<section class="card chart-section" style="margin-bottom:20px">
  <div class="chart-header">
    <div>
      <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--text);letter-spacing:-0.3px">Ventas por día</h3>
      <small style="color:var(--text-muted);font-size:10px;font-weight:500">Últimos 7 días</small>
    </div>
    <div class="chart-legend">
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--ok), var(--ok-light))"></div>
        <span>Efectivo</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--accent), var(--accent-light))"></div>
        <span>Tarjeta</span>
      </div>
    </div>
  </div>
  <div class="chart-container">
    <canvas id="chartDia" width="900" height="240"></canvas>
  </div>
</section>

<!-- Gráfica Semanal -->
<section class="card chart-section" style="margin-bottom:20px">
  <div class="chart-header">
    <div>
      <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--text);letter-spacing:-0.3px">Ventas por semana</h3>
      <small style="color:var(--text-muted);font-size:10px;font-weight:500">Últimas 4 semanas</small>
    </div>
    <div class="chart-legend">
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--ok), var(--ok-light))"></div>
        <span>Efectivo</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--accent), var(--accent-light))"></div>
        <span>Tarjeta</span>
      </div>
    </div>
  </div>
  <div class="chart-container">
    <canvas id="chartSemana" width="900" height="240"></canvas>
  </div>
</section>

<!-- Gráfica Mensual -->
<section class="card chart-section" style="margin-bottom:20px">
  <div class="chart-header">
    <div>
      <h3 style="margin:0;font-size:16px;font-weight:700;color:var(--text);letter-spacing:-0.3px">Ventas por mes</h3>
      <small style="color:var(--text-muted);font-size:10px;font-weight:500">Últimos 6 meses</small>
    </div>
    <div class="chart-legend">
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--ok), var(--ok-light))"></div>
        <span>Efectivo</span>
      </div>
      <div class="legend-item">
        <div class="legend-dot" style="background:linear-gradient(135deg, var(--accent), var(--accent-light))"></div>
        <span>Tarjeta</span>
      </div>
    </div>
  </div>
  <div class="chart-container">
    <canvas id="chartMes" width="900" height="240"></canvas>
  </div>
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


  // ========== Función para dibujar gráficas de líneas dobles elegantes ==========
  function drawDualLineChart(canvas, labels, dataEfectivo, dataTarjeta) {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Ajustar tamaño del canvas según el contenedor (responsivo)
    const container = canvas.parentElement;
    let W = 900;
    const H = 320;
    
    if (container) {
      const rect = container.getBoundingClientRect();
      W = Math.max(rect.width - 40, 600); // Mínimo 600px, con padding
    }
    
    // Establecer tamaño del canvas (usar devicePixelRatio para alta resolución)
    const dpr = window.devicePixelRatio || 1;
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    canvas.style.width = W + 'px';
    canvas.style.height = H + 'px';
    ctx.scale(dpr, dpr);
    
    // Usar dimensiones lógicas
    const width = W;
    const height = H;
    ctx.clearRect(0, 0, width, height);

    // Colores del tema LumiSpace
    const colorEfectivo = '#52b788'; // var(--ok)
    const colorEfectivoLight = '#7bd9a7'; // var(--ok-light)
    const colorTarjeta = '#8c9bff'; // var(--accent)
    const colorTarjetaLight = '#c7d0ff'; // var(--accent-light)
    const colorGrid = 'rgba(123, 108, 96, 0.12)'; // var(--text-muted) suave
    const colorAxis = 'rgba(123, 108, 96, 0.25)';
    const colorText = 'rgba(33, 22, 16, 0.7)'; // var(--text) suave
    const colorTextMuted = 'rgba(123, 108, 96, 0.8)'; // var(--text-muted)

    const pad = 70;
    const padBottom = 60;
    const padTop = 20;
    const allData = [...dataEfectivo, ...dataTarjeta];
    const max = Math.max(...allData, 1) * 1.1; // 10% más para espacio superior
    
    // Calcular coordenadas
    const X = (i) => pad + (i * (width - pad * 2) / (labels.length - 1 || 1));
    const Y = (v) => height - padBottom - ((v / max) * (height - padTop - padBottom));

    // Fondo con gradiente sutil
    const bgGradient = ctx.createLinearGradient(0, 0, 0, height);
    bgGradient.addColorStop(0, 'rgba(249, 244, 236, 0.3)');
    bgGradient.addColorStop(1, 'rgba(255, 255, 255, 0.1)');
    ctx.fillStyle = bgGradient;
    ctx.fillRect(pad, padTop, width - pad * 2, height - padTop - padBottom);

    // Líneas de referencia horizontales (grid)
    ctx.strokeStyle = colorGrid;
    ctx.lineWidth = 1;
    for (let i = 0; i <= 5; i++) {
      const y = padTop + ((height - padTop - padBottom) / 5) * i;
      ctx.beginPath();
      ctx.setLineDash([4, 4]);
      ctx.moveTo(pad, y);
      ctx.lineTo(width - pad, y);
      ctx.stroke();
      ctx.setLineDash([]);
    }

    // Dibujar área bajo la línea de EFECTIVO con gradiente
    const efectivoGradient = ctx.createLinearGradient(0, padTop, 0, height - padBottom);
    efectivoGradient.addColorStop(0, 'rgba(82, 183, 136, 0.25)');
    efectivoGradient.addColorStop(1, 'rgba(82, 183, 136, 0.05)');
    
    ctx.fillStyle = efectivoGradient;
    ctx.beginPath();
    ctx.moveTo(X(0), height - padBottom);
    dataEfectivo.forEach((v, i) => {
      ctx.lineTo(X(i), Y(v));
    });
    ctx.lineTo(X(dataEfectivo.length - 1), height - padBottom);
    ctx.closePath();
    ctx.fill();

    // Dibujar área bajo la línea de TARJETA con gradiente
    const tarjetaGradient = ctx.createLinearGradient(0, padTop, 0, height - padBottom);
    tarjetaGradient.addColorStop(0, 'rgba(140, 155, 255, 0.25)');
    tarjetaGradient.addColorStop(1, 'rgba(140, 155, 255, 0.05)');
    
    ctx.fillStyle = tarjetaGradient;
    ctx.beginPath();
    ctx.moveTo(X(0), height - padBottom);
    dataTarjeta.forEach((v, i) => {
      ctx.lineTo(X(i), Y(v));
    });
    ctx.lineTo(X(dataTarjeta.length - 1), height - padBottom);
    ctx.closePath();
    ctx.fill();

    // Dibujar línea de EFECTIVO con gradiente
    const efectivoLineGradient = ctx.createLinearGradient(0, padTop, 0, height - padBottom);
    efectivoLineGradient.addColorStop(0, colorEfectivoLight);
    efectivoLineGradient.addColorStop(1, colorEfectivo);
    
    ctx.strokeStyle = efectivoLineGradient;
    ctx.lineWidth = 3.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.shadowColor = 'rgba(82, 183, 136, 0.4)';
    ctx.shadowBlur = 8;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 2;
    ctx.beginPath();
    dataEfectivo.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();
    ctx.shadowBlur = 0;

    // Puntos de EFECTIVO con efecto elegante
    dataEfectivo.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      
      // Sombra del punto
      ctx.fillStyle = 'rgba(82, 183, 136, 0.3)';
      ctx.beginPath();
      ctx.arc(x, y + 2, 6, 0, Math.PI * 2);
      ctx.fill();
      
      // Punto principal con gradiente
      const pointGradient = ctx.createRadialGradient(x, y, 0, x, y, 6);
      pointGradient.addColorStop(0, colorEfectivoLight);
      pointGradient.addColorStop(0.7, colorEfectivo);
      pointGradient.addColorStop(1, '#3d8f5f');
      
      ctx.fillStyle = pointGradient;
      ctx.beginPath();
      ctx.arc(x, y, 6, 0, Math.PI * 2);
      ctx.fill();
      
      // Borde blanco brillante
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2.5;
      ctx.stroke();
      
      // Punto interior brillante
      ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
      ctx.beginPath();
      ctx.arc(x - 1.5, y - 1.5, 2, 0, Math.PI * 2);
      ctx.fill();
    });

    // Dibujar línea de TARJETA con gradiente
    const tarjetaLineGradient = ctx.createLinearGradient(0, padTop, 0, height - padBottom);
    tarjetaLineGradient.addColorStop(0, colorTarjetaLight);
    tarjetaLineGradient.addColorStop(1, colorTarjeta);
    
    ctx.strokeStyle = tarjetaLineGradient;
    ctx.lineWidth = 3.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.shadowColor = 'rgba(140, 155, 255, 0.4)';
    ctx.shadowBlur = 8;
    ctx.shadowOffsetX = 0;
    ctx.shadowOffsetY = 2;
    ctx.beginPath();
    dataTarjeta.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      i ? ctx.lineTo(x, y) : ctx.moveTo(x, y);
    });
    ctx.stroke();
    ctx.shadowBlur = 0;

    // Puntos de TARJETA con efecto elegante
    dataTarjeta.forEach((v, i) => {
      const x = X(i);
      const y = Y(v);
      
      // Sombra del punto
      ctx.fillStyle = 'rgba(140, 155, 255, 0.3)';
      ctx.beginPath();
      ctx.arc(x, y + 2, 6, 0, Math.PI * 2);
      ctx.fill();
      
      // Punto principal con gradiente
      const pointGradient = ctx.createRadialGradient(x, y, 0, x, y, 6);
      pointGradient.addColorStop(0, colorTarjetaLight);
      pointGradient.addColorStop(0.7, colorTarjeta);
      pointGradient.addColorStop(1, '#6b7ae8');
      
      ctx.fillStyle = pointGradient;
      ctx.beginPath();
      ctx.arc(x, y, 6, 0, Math.PI * 2);
      ctx.fill();
      
      // Borde blanco brillante
      ctx.strokeStyle = '#ffffff';
      ctx.lineWidth = 2.5;
      ctx.stroke();
      
      // Punto interior brillante
      ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
      ctx.beginPath();
      ctx.arc(x - 1.5, y - 1.5, 2, 0, Math.PI * 2);
      ctx.fill();
    });

    // Ejes con estilo elegante
    ctx.strokeStyle = colorAxis;
    ctx.lineWidth = 1.5;
    ctx.beginPath();
    ctx.moveTo(pad, height - padBottom);
    ctx.lineTo(width - pad, height - padBottom); // Eje X
    ctx.moveTo(pad, padTop);
    ctx.lineTo(pad, height - padBottom); // Eje Y
    ctx.stroke();

    // Valores en eje Y con tipografía mejorada
    ctx.fillStyle = colorTextMuted;
    ctx.font = '600 12px Inter, system-ui, sans-serif';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let i = 0; i <= 5; i++) {
      const val = (max / 5) * (5 - i);
      const y = padTop + ((height - padTop - padBottom) / 5) * i;
      ctx.fillText(mx(val), pad - 16, y);
    }

    // Labels en eje X con tipografía mejorada
    ctx.fillStyle = colorText;
    ctx.font = '500 12px Inter, system-ui, sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'top';
    const step = Math.ceil(labels.length / 8) || 1;
    labels.forEach((lb, i) => {
      if (i % step === 0 || i === labels.length - 1) {
        const x = X(i);
        ctx.fillText(lb.slice(-5), x, height - padBottom + 24);
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

    // Guardar datos para redibujar al redimensionar
    window.chartData = {
      diaria: diaria.ok ? diaria : null,
      semanal: semanal.ok ? semanal : null,
      mensual: mensual.ok ? mensual : null
    };

    // Redibujar gráficas al redimensionar la ventana
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        if (window.chartData.diaria) {
          drawDualLineChart($('#chartDia'), window.chartData.diaria.labels, 
            window.chartData.diaria.efectivo, window.chartData.diaria.tarjeta);
        }
        if (window.chartData.semanal) {
          drawDualLineChart($('#chartSemana'), window.chartData.semanal.labels, 
            window.chartData.semanal.efectivo, window.chartData.semanal.tarjeta);
        }
        if (window.chartData.mensual) {
          drawDualLineChart($('#chartMes'), window.chartData.mensual.labels, 
            window.chartData.mensual.efectivo, window.chartData.mensual.tarjeta);
        }
      }, 250);
    });

  } catch (e) {
    console.error('❌ Error cargando estadísticas:', e);
    console.error('Stack:', e.stack);
    alert('Error al cargar las estadísticas: ' + e.message);
  }
})();
</script>

<?php end_pos_page(); ?>