<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Facturación (Ventas)', $cajeroNombre, $cajaLabel);
?>

<!-- KPIs Simplificados -->
<div class="grid" style="grid-template-columns: repeat(4, minmax(0,1fr)); gap:16px; margin-bottom:24px;">
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Efectivo</div>
    <div id="kEf" style="font-weight:800;font-size:32px;color:var(--brand)">$0</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Tarjeta</div>
    <div id="kTj" style="font-weight:800;font-size:32px;color:var(--ok)">$0</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">E-commerce</div>
    <div id="kEc" style="font-weight:800;font-size:32px;color:var(--accent)">$0</div>
  </div>
  <div class="card" style="text-align:center;">
    <div style="font-size:12px;color:var(--ink-muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px">Ventas</div>
    <div id="kCount" style="font-weight:800;font-size:32px;color:var(--ink)">0</div>
  </div>
</div>

<!-- Filtros Compactos -->
<div class="card" style="margin-bottom:24px;">
  <form id="filtros" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end;">
    <div style="flex:1;min-width:140px">
      <label style="font-size:12px;color:var(--ink-muted);margin-bottom:4px;display:block">Desde</label>
      <input id="fDesde" type="date" class="input">
    </div>
    <div style="flex:1;min-width:140px">
      <label style="font-size:12px;color:var(--ink-muted);margin-bottom:4px;display:block">Hasta</label>
      <input id="fHasta" type="date" class="input">
    </div>
    <div style="flex:1;min-width:140px">
      <label style="font-size:12px;color:var(--ink-muted);margin-bottom:4px;display:block">Método</label>
      <select id="fMetodo" class="input">
        <option value="">Todos</option>
        <option value="efectivo">Efectivo</option>
        <option value="tarjeta">Tarjeta</option>
      </select>
    </div>
    <div style="flex:1;min-width:140px">
      <label style="font-size:12px;color:var(--ink-muted);margin-bottom:4px;display:block">Caja</label>
      <input id="fCaja" class="input" placeholder="Opcional">
    </div>
    <div style="flex:1;min-width:140px">
      <label style="font-size:12px;color:var(--ink-muted);margin-bottom:4px;display:block">ID Cajero</label>
      <input id="fCajero" class="input" inputmode="numeric" placeholder="Opcional">
    </div>
    <button id="btnAplicar" type="submit" class="btn btn-primary" style="height:44px;padding:0 24px">
      Filtrar
    </button>
  </form>
</div>

<!-- Tabla Moderna -->
<div class="card">
  <div style="overflow:auto;">
    <table class="table">
      <thead>
        <tr>
          <th style="width:60px">#</th>
          <th>Fecha y Hora</th>
          <th>Cajero</th>
          <th>Caja</th>
          <th>Método</th>
          <th style="text-align:right">Total</th>
          <th style="width:120px;text-align:center">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyVentas">
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ink-light)">Cargando...</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Paginación Mejorada -->
  <div style="display:flex;justify-content:space-between;align-items:center;margin-top:20px;padding-top:20px;border-top:1px solid var(--border-light)">
    <div style="color:var(--ink-muted);font-size:13px" id="lblInfo">Mostrando 0 ventas</div>
    <div style="display:flex;gap:8px;align-items:center">
      <button id="btnPrev" class="btn btn-sm" disabled>← Anterior</button>
      <div id="lblPage" style="padding:0 16px;color:var(--ink);font-weight:600;font-size:14px">1 / 1</div>
      <button id="btnNext" class="btn btn-sm" disabled>Siguiente →</button>
    </div>
  </div>
</div>

<!-- Modal Detalle Mejorado -->
<dialog id="dlgVenta" class="dlg" style="max-width:600px">
  <div class="dlg-body">
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:24px">
      <h3 style="margin:0">Detalle de venta</h3>
      <button id="btnCerrarDetalle" class="btn btn-sm" style="border:none;background:var(--bg-3)">✕</button>
    </div>
    
    <div id="ventaHeader" style="background:var(--bg-3);padding:20px;border-radius:var(--radius);margin-bottom:24px;display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
      <!-- Se llena dinámicamente -->
    </div>

    <div style="border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:24px">
      <div style="max-height:400px;overflow:auto">
        <table class="table">
          <thead>
            <tr>
              <th>Producto</th>
              <th style="text-align:right">Precio</th>
              <th style="text-align:right;width:80px">Cant.</th>
              <th style="text-align:right">Importe</th>
            </tr>
          </thead>
          <tbody id="ventaItems"></tbody>
        </table>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end">
      <button onclick="dlgVenta.close()" class="btn btn-primary">Cerrar</button>
    </div>
  </div>
</dialog>

<script>
(function initVentasPage(){
  const $ = (s,ctx=document)=>ctx.querySelector(s);
  const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
  const fmt = n => '$'+Number(n||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});

  const tbody = $('#tbodyVentas');
  const lblPage = $('#lblPage');
  const lblInfo = $('#lblInfo');
  const btnPrev = $('#btnPrev');
  const btnNext = $('#btnNext');

  const fDesde  = $('#fDesde');
  const fHasta  = $('#fHasta');
  const fMetodo = $('#fMetodo');
  const fCaja   = $('#fCaja');
  const fCajero = $('#fCajero');
  const frm     = $('#filtros');

  // Prefill caja desde localStorage
  try { if (typeof getCajaLS==='function') fCaja.value = getCajaLS(); } catch(e){}

  let state = { page:1, per_page:20, total:0 };

  async function cargar() {
    const q = {
      action:'ventas_list',
      desde:fDesde.value || '',
      hasta:fHasta.value || '',
      metodo:fMetodo.value || '',
      caja:fCaja.value.trim(),
      cajero:fCajero.value.trim(),
      page:state.page
    };
    
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ink-light)">Cargando...</td></tr>';
    
    const r = await fetch('api.php',{method:'POST',body:new URLSearchParams(q)}).then(x=>x.json());
    if(!r.ok){ 
      alert(r.error||'Error'); 
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--danger)">Error al cargar datos</td></tr>';
      return; 
    }

    // KPIs
    $('#kEf').textContent = fmt(r.kpis.efectivo);
    $('#kTj').textContent = fmt(r.kpis.tarjeta);
    $('#kEc').textContent = fmt(r.kpis.ecommerce||0);
    $('#kCount').textContent = r.kpis.ventas;

    // Tabla
    tbody.innerHTML = '';
    if (!r.data || !r.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--ink-light)">No se encontraron ventas</td></tr>';
      lblInfo.textContent = 'Mostrando 0 ventas';
    } else {
      r.data.forEach((v,i)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td style="font-weight:600;color:var(--ink-muted)">${(r.page-1)*r.per_page + i + 1}</td>
          <td style="font-size:13px">${v.fecha}</td>
          <td>${v.cajero || '<span style="color:var(--ink-light)">—</span>'}</td>
          <td>${v.caja_id || '<span style="color:var(--ink-light)">—</span>'}</td>
          <td><span style="display:inline-block;padding:4px 12px;background:${v.metodo_principal==='efectivo'?'var(--ok)':'var(--brand)'};color:white;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase">${v.metodo_principal}</span></td>
          <td style="text-align:right;font-weight:700;font-size:15px">${fmt(v.total)}</td>
          <td style="text-align:center"><button class="btn btn-sm" data-ver="${v.id}">Ver detalle</button></td>
        `;
        tbody.appendChild(tr);
      });
      
      const inicio = (r.page-1)*r.per_page + 1;
      const fin = Math.min(r.page*r.per_page, r.total);
      lblInfo.textContent = `Mostrando ${inicio}-${fin} de ${r.total} ventas`;
    }

    // Paginación
    state.total = r.total; state.page = r.page; state.per_page = r.per_page;
    const pages = Math.max(1, Math.ceil(state.total/state.per_page));
    lblPage.textContent = `${state.page} / ${pages}`;
    btnPrev.disabled = state.page<=1;
    btnNext.disabled = state.page>=pages;
  }

  // Filtros
  frm.addEventListener('submit', ev=>{ ev.preventDefault(); state.page=1; cargar(); });

  btnPrev.addEventListener('click', ()=>{ if(state.page>1){ state.page--; cargar(); } });
  btnNext.addEventListener('click', ()=>{
    const pages = Math.max(1, Math.ceil(state.total/state.per_page));
    if(state.page<pages){ state.page++; cargar(); }
  });

  // Detalle
  tbody.addEventListener('click', async (ev)=>{
    const btn = ev.target.closest('[data-ver]');
    if(!btn) return;
    const venta_id = btn.getAttribute('data-ver');
    const r = await fetch('api.php',{method:'POST',body:new URLSearchParams({action:'venta_detalle',venta_id})}).then(x=>x.json());
    if(!r.ok){ alert(r.error||'Error'); return; }

    const v = r.venta;
    const d = $('#dlgVenta');
    $('#ventaHeader').innerHTML = `
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Folio</span><b style="font-size:16px">#${v.id}</b></div>
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Fecha</span><b style="font-size:14px">${v.fecha}</b></div>
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Cajero</span><b style="font-size:14px">${v.cajero || '—'}</b></div>
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Caja</span><b style="font-size:14px">${v.caja_id || '—'}</b></div>
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Método</span><b style="font-size:14px">${v.metodo_principal}</b></div>
      <div><span style="font-size:11px;color:var(--ink-muted);text-transform:uppercase;display:block;margin-bottom:4px">Total</span><b style="font-size:18px;color:var(--brand)">${fmt(v.total)}</b></div>
    `;
    const tb = $('#ventaItems');
    tb.innerHTML = '';
    (r.items||[]).forEach(it=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><b>${it.nombre}</b></td>
        <td style="text-align:right">${fmt(it.precio)}</td>
        <td style="text-align:right;font-weight:600">${it.cantidad}</td>
        <td style="text-align:right;font-weight:700;color:var(--brand)">${fmt(it.total_linea)}</td>
      `;
      tb.appendChild(tr);
    });
    d.showModal();
  });
  
  $('#btnCerrarDetalle').addEventListener('click', ()=> $('#dlgVenta').close());

  // Carga inicial
  cargar();
})();
</script>

<?php end_pos_page(); ?>