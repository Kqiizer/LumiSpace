<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Historial de turnos', $cajeroNombre, $cajaLabel);
?>

<section class="card" style="margin-bottom:24px">
  <div style="display:flex;gap:16px;align-items:end;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
      <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Filtrar por caja</label>
      <select id="selCajaCorte" class="input">
        <option value="">Todas las cajas</option>
      </select>
    </div>
    <div style="flex:1;min-width:200px">
      <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Desde</label>
      <input id="inpDesde" type="date" class="input">
    </div>
    <div style="flex:1;min-width:200px">
      <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Hasta</label>
      <input id="inpHasta" type="date" class="input">
</section>

<section class="card">
  <h3 style="margin:0 0 20px 0;font-size:20px;font-weight:800">📋 Historial de turnos</h3>
  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th style="width:180px">Fecha</th>
          <th style="width:120px">Movimiento</th>
          <th>Cajero</th>
          <th style="width:100px">Caja</th>
          <th style="text-align:right;width:140px">Monto</th>
        </tr>
      </thead>
      <tbody id="tbMovimientos">
        <tr><td colspan="5" class="muted" style="text-align:center;padding:40px">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</section>

<script>
(async function initCorte() {
  const $ = (s) => document.querySelector(s);
  const mx = (n) => '$' + Number(n || 0).toFixed(2);
  const api = async (data) => {
    const r = await fetch('api.php', { method: 'POST', body: new URLSearchParams(data) });
    return r.json();
  };

  const selCaja = $('#selCajaCorte');
  const inpDesde = $('#inpDesde');
  const inpHasta = $('#inpHasta');
  const btnFiltrar = $('#btnFiltrar');
  const tbody = $('#tbMovimientos');

  // Cargar cajas
  const rCajas = await api({ action: 'cajas_list' });
  if (rCajas.ok && rCajas.data) {
    rCajas.data.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c;
      selCaja.appendChild(opt);
    });
  }

  // Fechas por defecto (último mes)
  const hoy = new Date();
  const hace30 = new Date(hoy);
  hace30.setDate(hoy.getDate() - 30);
  inpDesde.value = hace30.toISOString().split('T')[0];
  inpHasta.value = hoy.toISOString().split('T')[0];

  async function cargarHistorial() {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light)">Cargando...</td></tr>';

    const r = await api({
      action: 'turnos_historial',
      caja_id: selCaja.value,
      desde: inpDesde.value,
      hasta: inpHasta.value
    });

    if (!r.ok) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--danger)">Error al cargar datos</td></tr>';
      return;
    }

    const turnos = r.data || [];
    if (!turnos.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-light)">No se encontraron movimientos</td></tr>';
      return;
    }

    // Generar filas: una de apertura y una de cierre por cada turno
    let html = '';
    turnos.forEach(t => {
      // Apertura
      html += `
        <tr style="background:rgba(26, 188, 156, 0.08)">
          <td>${t.fecha_apertura}</td>
          <td><span style="display:inline-block;padding:4px 10px;background:var(--ok);color:white;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase">Apertura</span></td>
          <td>${t.cajero_nombre || '—'}</td>
          <td><b>${t.caja_id}</b></td>
          <td style="text-align:right;font-weight:700;color:var(--ok)">${mx(t.saldo_inicial)}</td>
        </tr>
      `;

      // Cierre (si existe)
      if (t.fecha_cierre) {
        html += `
          <tr style="background:rgba(231, 76, 60, 0.08)">
            <td>${t.fecha_cierre}</td>
            <td><span style="display:inline-block;padding:4px 10px;background:var(--danger);color:white;border-radius:12px;font-size:11px;font-weight:700;text-transform:uppercase">Cierre</span></td>
            <td>${t.cajero_nombre || '—'}</td>
            <td><b>${t.caja_id}</b></td>
            <td style="text-align:right;font-weight:700;color:var(--danger)">${mx(t.saldo_final)}</td>
          </tr>
        `;
      }
    });

    tbody.innerHTML = html;
  }

  btnFiltrar.addEventListener('click', cargarHistorial);
  await cargarHistorial();
})();
</script>

<?php end_pos_page(); ?>