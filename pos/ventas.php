<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Facturación (Ventas)', $cajeroNombre, $cajaLabel);
?>

<!-- KPIs -->
<div class="grid" style="grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:12px;">
  <div class="card"><div style="font-size:12px;color:#777">Efectivo total</div><div id="kEf" style="font-weight:700;font-size:18px">$0.00</div></div>
  <div class="card"><div style="font-size:12px;color:#777">Tarjeta total</div><div id="kTj" style="font-weight:700;font-size:18px">$0.00</div></div>
  <div class="card"><div style="font-size:12px;color:#777">E-commerce</div><div id="kEc" style="font-weight:700;font-size:18px">$0.00</div></div>
  <div class="card"><div style="font-size:12px;color:#777">Ventas</div><div id="kCount" style="font-weight:700;font-size:18px">0</div></div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom:12px;">
  <form id="filtros" style="display:grid;grid-template-columns: repeat(6, minmax(0,1fr)); gap:8px; align-items:end;">
    <div><label style="font-size:12px;color:#777">Desde</label><input id="fDesde" type="date" class="input"></div>
    <div><label style="font-size:12px;color:#777">Hasta</label><input id="fHasta" type="date" class="input"></div>
    <div>
      <label style="font-size:12px;color:#777">Método</label>
      <select id="fMetodo" class="input">
        <option value="">(todos)</option>
        <option value="efectivo">Efectivo</option>
        <option value="tarjeta">Tarjeta</option>
      </select>
    </div>
    <div><label style="font-size:12px;color:#777">Caja (opcional)</label><input id="fCaja" class="input" placeholder="Caja 1"></div>
    <div><label style="font-size:12px;color:#777">ID Cajero (opcional)</label><input id="fCajero" class="input" inputmode="numeric"></div>
    <div><button id="btnAplicar" type="submit" class="btn">Aplicar</button></div>
  </form>
</div>

<!-- Tabla -->
<div class="card">
  <div style="overflow:auto;">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Fecha</th>
          <th>Cajero</th>
          <th>Caja</th>
          <th>Método</th>
          <th style="text-align:right">Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="tbodyVentas">
        <tr><td colspan="7" style="text-align:center;color:#777">(sin datos)</td></tr>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
    <button id="btnPrev" class="btn" disabled>Anterior</button>
    <div id="lblPage" style="display:grid;place-items:center;padding:0 8px;color:#555">1 / 1</div>
    <button id="btnNext" class="btn" disabled>Siguiente</button>
  </div>
</div>

<!-- Modal Detalle -->
<!-- Modal Detalle -->
<dialog id="dlgVenta" class="dlg">
  <div class="dlg-body">
    <h3>Detalle de venta</h3>
    
    <div id="ventaHeader" style="background:var(--bg-2);padding:16px;border-radius:12px;margin-bottom:20px">
      <!-- Se llena dinámicamente -->
    </div>

    <div style="max-height:400px;overflow:auto;border:1px solid var(--border);border-radius:12px">
      <table class="table">
        <thead>
          <tr>
            <th>Producto</th>
            <th style="text-align:right">Precio</th>
            <th style="text-align:right">Cant.</th>
            <th style="text-align:right">Importe</th>
          </tr>
        </thead>
        <tbody id="ventaItems"></tbody>
      </table>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:24px">
      <button id="btnCerrarDetalle" class="btn">Cerrar</button>
    </div>
  </div>
</dialog>

<script>
(function initVentasPage(){
  const $ = (s,ctx=document)=>ctx.querySelector(s);
  const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
  const fmt = n => '$'+Number(n||0).toFixed(2);

  const tbody = $('#tbodyVentas');
  const lblPage = $('#lblPage');
  const btnPrev = $('#btnPrev');
  const btnNext = $('#btnNext');

  const fDesde  = $('#fDesde');
  const fHasta  = $('#fHasta');
  const fMetodo = $('#fMetodo');
  const fCaja   = $('#fCaja');
  const fCajero = $('#fCajero');
  const frm     = $('#filtros');

  // Prefill: caja desde localStorage si existe (la función está en pos.js)
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
    const r = await fetch('api.php',{method:'POST',body:new URLSearchParams(q)}).then(x=>x.json());
    if(!r.ok){ alert(r.error||'Error'); return; }

    // KPIs
    $('#kEf').textContent = fmt(r.kpis.efectivo);
    $('#kTj').textContent = fmt(r.kpis.tarjeta);
    $('#kEc').textContent = fmt(r.kpis.ecommerce||0);
    $('#kCount').textContent = r.kpis.ventas;

    // Tabla
    tbody.innerHTML = '';
    if (!r.data || !r.data.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#777">(sin datos)</td></tr>';
    } else {
      r.data.forEach((v,i)=>{
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${(r.page-1)*r.per_page + i + 1}</td>
          <td>${v.fecha}</td>
          <td>${v.cajero || '-'}</td>
          <td>${v.caja_id || '-'}</td>
          <td>${v.metodo_principal}</td>
          <td style="text-align:right">${fmt(v.total)}</td>
          <td><button class="btn btn-sm" data-ver="${v.id}">Ver detalle</button></td>
        `;
        tbody.appendChild(tr);
      });
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
      <div><b>Folio:</b> ${v.id}</div>
      <div><b>Fecha:</b> ${v.fecha}</div>
      <div><b>Cajero:</b> ${v.cajero || '-'}</div>
      <div><b>Caja:</b> ${v.caja_id || '-'}</div>
      <div><b>Método:</b> ${v.metodo_principal}</div>
      <div><b>Total:</b> ${fmt(v.total)}</div>
    `;
    const tb = $('#ventaItems');
    tb.innerHTML = '';
    (r.items||[]).forEach(it=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${it.nombre}</td>
        <td style="text-align:right">${fmt(it.precio)}</td>
        <td style="text-align:right">${it.cantidad}</td>
        <td style="text-align:right">${fmt(it.total_linea)}</td>
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
