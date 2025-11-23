<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Corte de Caja', $cajeroNombre, $cajaLabel);
?>

<section class="card" style="margin-bottom:24px">
  <div style="display:flex;gap:16px;align-items:end;flex-wrap:wrap">
    <div style="flex:1;min-width:200px">
      <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Caja</label>
      <select id="selCajaCorte" class="input">
        <option value="">(cargando...)</option>
      </select>
    </div>
    <div style="flex:0 0 auto">
      <label style="display:block;font-size:12px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">&nbsp;</label>
      <button type="button" id="btnVerCorte" class="btn btn-primary">Actualizar</button>
    </div>
  </div>
</section>

<!-- KPIs -->
<div class="grid" style="grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:24px;">
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Saldo Inicial</div>
    <div id="kSaldoIni" style="font-weight:800;font-size:32px;color:var(--brand)">$0.00</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Ventas Efectivo</div>
    <div id="kEf" style="font-weight:800;font-size:32px;color:var(--ok)">$0.00</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Ventas Tarjeta</div>
    <div id="kTj" style="font-weight:800;font-size:32px;color:var(--accent)">$0.00</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Saldo Actual</div>
    <div id="kSaldoAct" style="font-weight:800;font-size:32px;color:var(--danger)">$0.00</div>
  </div>
</div>

<section class="card">
  <h3 style="margin:0 0 20px 0;font-size:20px;font-weight:800">📋 Movimientos del turno</h3>
  <div style="margin-bottom:12px;padding:12px;background:var(--bg-2);border-radius:12px;font-size:13px;color:var(--text-muted)">
    <strong>Ventana:</strong> <span id="lblWindow">—</span>
  </div>
  <div id="msgCorte" style="padding:12px;margin-bottom:16px;background:var(--bg-2);border-radius:12px;font-size:13px;color:var(--text-muted);text-align:center;display:none">Cargando...</div>
  <div style="overflow:auto">
    <table class="table">
      <thead>
        <tr>
          <th style="width:180px">Fecha</th>
          <th style="width:120px">Método</th>
          <th>Tipo</th>
          <th style="text-align:right;width:140px">Monto</th>
        </tr>
      </thead>
      <tbody id="tbMovs">
        <tr><td colspan="4" class="muted" style="text-align:center;padding:40px">Cargando...</td></tr>
      </tbody>
    </table>
  </div>
</section>

<script>
(function initCortePage() {
  // Helper functions
  const $ = (s) => document.querySelector(s);
  const money = (n) => `$${Number(n || 0).toFixed(2)}`;
  
  // Función helper para llamar a la API
  const api = async (data) => {
    const r = await fetch('api.php', {
      method: 'POST',
      body: new URLSearchParams(data)
    });
    return r.json();
  };
  
  // Helper para localStorage
  const getCajaLS = () => {
    try {
      if (typeof localStorage !== 'undefined') {
        return localStorage.getItem('pos_caja') || 'Caja 1';
      }
    } catch (e) {}
    return 'Caja 1';
  };
  
  const setCajaLS = (caja) => {
    try {
      if (typeof localStorage !== 'undefined') {
        localStorage.setItem('pos_caja', caja);
      }
    } catch (e) {}
  };

  const selCaja = $('#selCajaCorte');
  const btnVerCorte = $('#btnVerCorte');
  const tbody = $('#tbMovs');
  const msgCorte = $('#msgCorte');
  const lblWindow = $('#lblWindow');
  
  // Función para formatear fecha
  function formatearFecha(fechaStr) {
    if (!fechaStr) return '-';
    try {
      const fecha = new Date(fechaStr);
      if (isNaN(fecha.getTime())) return fechaStr; // Si no es válida, devolver original
      return fecha.toLocaleString('es-MX', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (e) {
      return fechaStr;
    }
  }

  // Función para llenar el select de cajas
  async function cargarCajas() {
    if (!selCaja) return;
    
    try {
      const r = await api({ action: 'cajas_list' });
      if (r.ok && Array.isArray(r.data) && r.data.length) {
        selCaja.innerHTML = r.data.map(c => `<option value="${c}">${c}</option>`).join('');
        const preferida = getCajaLS();
        if (preferida && r.data.includes(preferida)) {
          selCaja.value = preferida;
        } else {
          selCaja.selectedIndex = 0;
        }
      } else {
        selCaja.innerHTML = '<option value="">(sin cajas disponibles)</option>';
      }
    } catch (error) {
      console.error('Error al cargar cajas:', error);
      selCaja.innerHTML = '<option value="">Error al cargar</option>';
    }
  }

  // Función para pintar KPIs
  function pintarKPIs(d) {
    const k = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.textContent = money(v || 0);
    };
    k('kSaldoIni', d?.saldo_inicial);
    k('kEf', d?.ventas_efectivo);
    k('kTj', d?.ventas_tarjeta);
    k('kSaldoAct', d?.saldo_actual);
  }

  // Función para pintar movimientos
  function pintarMovimientos(movs) {
    if (!tbody) return;
    
    console.log('Movimientos recibidos:', movs);
    
    if (!movs || !movs.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="muted" style="text-align:center;padding:40px">(sin movimientos en este turno)</td></tr>';
      return;
    }
    
    tbody.innerHTML = movs.map(m => {
      const fecha = formatearFecha(m.fecha);
      const metodo = m.metodo || m.metodo_principal || '-';
      const monto = Number(m.monto || m.total || 0);
      
      return `
      <tr>
        <td style="font-size:13px">${fecha}</td>
        <td><span style="display:inline-block;padding:4px 10px;background:${metodo==='efectivo'?'var(--ok)':'var(--brand)'};color:white;border-radius:12px;font-size:11px;font-weight:600;text-transform:uppercase">${metodo}</span></td>
        <td>Venta</td>
        <td style="text-align:right;font-weight:600;font-size:14px">${money(monto)}</td>
      </tr>
    `;
    }).join('');
  }

  // Función para cargar el resumen del corte
  async function cargarResumen() {
    if (!selCaja || !selCaja.value) {
      if (msgCorte) msgCorte.textContent = 'Selecciona una caja';
      return;
    }

    const caja_id = selCaja.value;
    setCajaLS(caja_id);

    if (msgCorte) {
      msgCorte.textContent = 'Cargando…';
      msgCorte.style.display = 'block';
    }
    if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-light)">Cargando...</td></tr>';

    try {
      const r = await api({ action: 'corte_resumen', caja_id });
      
      if (!r.ok) {
        throw new Error(r.error || 'Error al consultar corte');
      }

      if (!r.data) {
        pintarKPIs(null);
        pintarMovimientos([]);
        if (lblWindow) lblWindow.textContent = '—';
        if (msgCorte) msgCorte.textContent = 'No hay turno para esta caja.';
        if (msgCorte) msgCorte.style.display = 'block';
        return;
      }

      const d = r.data;
      
      console.log('Datos del corte:', d);
      console.log('Movimientos:', d.movimientos);
      
      // Pintar ventana de tiempo
      if (lblWindow) {
        const inicio = d.inicio ? formatearFecha(d.inicio) : '—';
        const fin = d.fin ? formatearFecha(d.fin) : 'ahora';
        lblWindow.textContent = `${inicio} — ${fin}`;
      }
      
      // Pintar KPIs
      pintarKPIs(d);
      
      // Mostrar información de debug
      console.log(`Ventas en efectivo: $${d.ventas_efectivo}`);
      console.log(`Ventas en tarjeta: $${d.ventas_tarjeta}`);
      console.log(`Total de movimientos: ${d.movimientos_count || 0}`);
      
      // Verificar que hay movimientos
      if (d.movimientos && Array.isArray(d.movimientos) && d.movimientos.length > 0) {
        console.log(`Renderizando ${d.movimientos.length} movimientos:`, d.movimientos);
        pintarMovimientos(d.movimientos);
      } else {
        console.log('No hay movimientos o el array está vacío. Total ventas:', d.ventas_count);
        if (d.ventas_count > 0 && (!d.movimientos || d.movimientos.length === 0)) {
          if (msgCorte) {
            msgCorte.textContent = `Hay ${d.ventas_count} venta(s) pero no se pudieron cargar los detalles.`;
            msgCorte.style.display = 'block';
          }
        }
        pintarMovimientos([]);
      }
      
      // Ocultar mensaje solo si hay datos
      if (msgCorte && d.movimientos && d.movimientos.length > 0) {
        msgCorte.textContent = '';
        msgCorte.style.display = 'none';
      } else if (msgCorte && d.ventas_count > 0) {
        // Hay ventas pero no se cargaron los detalles
        msgCorte.textContent = `Total de ventas: ${d.ventas_count}. Total: ${money(d.ventas_total)}`;
        msgCorte.style.display = 'block';
      } else if (msgCorte && d.ventas_count === 0) {
        msgCorte.textContent = 'No hay ventas en este turno.';
        msgCorte.style.display = 'block';
      }
      
    } catch (error) {
      console.error('Error al cargar corte:', error);
      console.error('Stack:', error.stack);
      pintarKPIs(null);
      pintarMovimientos([]);
      if (lblWindow) lblWindow.textContent = '—';
      if (msgCorte) {
        msgCorte.textContent = 'Error: ' + (error.message || 'Error al cargar datos');
        msgCorte.style.display = 'block';
      }
    }
  }

  // Event listeners
  if (selCaja) {
    selCaja.addEventListener('change', cargarResumen);
  }

  if (btnVerCorte) {
    btnVerCorte.addEventListener('click', cargarResumen);
  }

  // Inicialización
  async function init() {
    await cargarCajas();
    if (selCaja && selCaja.value) {
      await cargarResumen();
    } else {
      // Si no hay caja seleccionada, cargar con la primera disponible
      setTimeout(async () => {
        if (selCaja && selCaja.value) {
          await cargarResumen();
        }
      }, 100);
    }
  }

  // Ejecutar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>

<?php end_pos_page(); ?>
