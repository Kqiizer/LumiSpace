<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Facturación (Ventas)', $cajeroNombre, $cajaLabel);
?>

<!-- KPIs Simplificados -->
<div class="grid" style="grid-template-columns: repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:24px;">
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
          <th style="width:180px;text-align:center">Acciones</th>
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
<dialog id="dlgVenta" class="dlg dlg-sale-detail">
  <form method="dialog" class="dlg-body" onsubmit="return false;">
    <button type="button" class="sale-close" id="btnCerrarDetalle" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="sale-header-modern">
      <div class="sale-header-content">
        <div class="sale-header-icon">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <p class="sale-eyebrow">Detalle de venta</p>
          <h3 class="sale-title">Información de la venta</h3>
        </div>
      </div>
    </div>

    <div id="ventaHeader" class="sale-info-grid">
      <!-- Se llena dinámicamente -->
    </div>

    <div class="sale-products-section">
      <div class="section-header">
        <h4 class="section-title">Productos vendidos</h4>
        <span class="section-count" id="saleProductCount">0 productos</span>
      </div>
      <div class="sale-items-container">
        <div class="sale-items-header">
          <div class="sale-item-col-name">Producto</div>
          <div class="sale-item-col-price">Precio</div>
          <div class="sale-item-col-qty">Cant.</div>
          <div class="sale-item-col-total">Importe</div>
        </div>
        <div class="sale-items-list" id="ventaItems">
          <!-- Se llena dinámicamente -->
        </div>
      </div>
    </div>

    <div class="sale-actions">
      <button type="button" id="btnImprimirTicket" class="sale-btn-print">
        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6 14h12v8H6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Imprimir ticket</span>
      </button>
      <button type="button" onclick="document.getElementById('dlgVenta').close()" class="sale-btn-close">
        Cerrar
      </button>
    </div>
  </form>
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
          <td style="text-align:center;display:flex;gap:6px;justify-content:center">
            <button class="btn btn-sm" data-ver="${v.id}">Ver detalle</button>
            <button class="btn btn-sm" data-imprimir="${v.id}" style="background:var(--brand);color:white;border:none" title="Imprimir ticket">
              🖨️ Imprimir
            </button>
          </td>
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

  let ventaActualId = null; // Guardar ID de venta actual para imprimir

  // Detalle
  tbody.addEventListener('click', async (ev)=>{
    const btn = ev.target.closest('[data-ver]');
    if(!btn) return;
    const venta_id = btn.getAttribute('data-ver');
    ventaActualId = venta_id; // Guardar para imprimir
    const r = await fetch('api.php',{method:'POST',body:new URLSearchParams({action:'venta_detalle',venta_id})}).then(x=>x.json());
    if(!r.ok){ alert(r.error||'Error'); return; }

    const v = r.venta;
    const d = $('#dlgVenta');
    const items = r.items || [];
    
    // Actualizar contador de productos
    const productCount = $('#saleProductCount');
    if (productCount) {
      productCount.textContent = `${items.length} ${items.length === 1 ? 'producto' : 'productos'}`;
    }
    
    $('#ventaHeader').innerHTML = `
      <div class="sale-info-item">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Folio</small>
          <strong class="sale-info-value">#${v.id}</strong>
        </div>
      </div>
      <div class="sale-info-item">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <polyline points="12 6 12 12 16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Fecha</small>
          <strong class="sale-info-value">${v.fecha}</strong>
        </div>
      </div>
      <div class="sale-info-item">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Cajero</small>
          <strong class="sale-info-value">${v.cajero || '—'}</strong>
        </div>
      </div>
      <div class="sale-info-item">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="8" width="18" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
          <path d="M12 8v13M8 12H4a2 2 0 01-2-2V7a2 2 0 012-2h4M16 12h4a2 2 0 002-2V7a2 2 0 00-2-2h-4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Caja</small>
          <strong class="sale-info-value">${v.caja_id || '—'}</strong>
        </div>
      </div>
      <div class="sale-info-item">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
          <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Método</small>
          <strong class="sale-info-value">${v.metodo_principal}</strong>
        </div>
      </div>
      <div class="sale-info-item sale-info-total">
        <svg class="sale-info-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="sale-info-content">
          <small class="sale-info-label">Total</small>
          <strong class="sale-info-value-total">${fmt(v.total)}</strong>
        </div>
      </div>
    `;
    const tb = $('#ventaItems');
    tb.innerHTML = '';
    if (!items.length) {
      tb.innerHTML = `
        <div class="sale-empty">
          <p>No hay productos en esta venta</p>
        </div>
      `;
    } else {
      items.forEach(it => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'sale-item-row';
        itemDiv.innerHTML = `
          <div class="sale-item-col-name">
            <strong>${it.nombre}</strong>
          </div>
          <div class="sale-item-col-price">${fmt(it.precio)}</div>
          <div class="sale-item-col-qty">${it.cantidad}</div>
          <div class="sale-item-col-total">
            <strong>${fmt(it.total_linea)}</strong>
          </div>
        `;
        tb.appendChild(itemDiv);
      });
    }
    d.showModal();
  });

  // Imprimir desde tabla
  tbody.addEventListener('click', async (ev)=>{
    const btn = ev.target.closest('[data-imprimir]');
    if(!btn) return;
    const venta_id = btn.getAttribute('data-imprimir');
    await imprimirTicket(venta_id);
  });

  // Imprimir desde modal
  $('#btnImprimirTicket').addEventListener('click', async ()=>{
    if(ventaActualId) {
      await imprimirTicket(ventaActualId);
    }
  });

  // Función para imprimir ticket
  async function imprimirTicket(venta_id) {
    try {
      const r = await fetch('api.php', {
        method: 'POST',
        body: new URLSearchParams({action: 'venta_detalle', venta_id})
      }).then(x => x.json());
      
      if(!r.ok) {
        alert(r.error || 'Error al obtener datos del ticket');
        return;
      }

      const v = r.venta;
      const items = r.items || [];
      
      // Calcular subtotal
      const subtotal = items.reduce((sum, it) => sum + parseFloat(it.total_linea || 0), 0);
      
      // Crear ventana de impresión
      const ventanaImpresion = window.open('', '_blank');
      ventanaImpresion.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
          <title>Ticket de Venta #${v.id}</title>
          <style>
            @media print {
              @page { size: 80mm auto; margin: 0; }
              body { margin: 0; padding: 10px; font-family: 'Courier New', monospace; font-size: 12px; }
            }
            body {
              margin: 0;
              padding: 10px;
              font-family: 'Courier New', monospace;
              font-size: 12px;
              width: 80mm;
            }
            .ticket-header {
              text-align: center;
              border-bottom: 1px dashed #000;
              padding-bottom: 10px;
              margin-bottom: 10px;
            }
            .ticket-header h1 {
              margin: 0;
              font-size: 16px;
              font-weight: bold;
            }
            .ticket-info {
              margin: 8px 0;
              font-size: 11px;
            }
            .ticket-items {
              border-top: 1px dashed #000;
              border-bottom: 1px dashed #000;
              padding: 10px 0;
              margin: 10px 0;
            }
            .ticket-item {
              display: flex;
              justify-content: space-between;
              margin: 5px 0;
              font-size: 11px;
            }
            .ticket-total {
              text-align: right;
              margin-top: 10px;
              font-size: 14px;
              font-weight: bold;
            }
            .ticket-footer {
              text-align: center;
              margin-top: 15px;
              padding-top: 10px;
              border-top: 1px dashed #000;
              font-size: 10px;
            }
          </style>
        </head>
        <body>
          <div class="ticket-header">
            <h1>LUMISPACE</h1>
            <p style="margin:5px 0;font-size:10px">Ticket de Venta</p>
          </div>
          
          <div class="ticket-info">
            <div><strong>Folio:</strong> #${v.id}</div>
            <div><strong>Fecha:</strong> ${v.fecha}</div>
            <div><strong>Cajero:</strong> ${v.cajero || 'N/A'}</div>
            <div><strong>Caja:</strong> ${v.caja_id || 'N/A'}</div>
            <div><strong>Método:</strong> ${v.metodo_principal || 'N/A'}</div>
          </div>
          
          <div class="ticket-items">
            ${items.map(it => `
              <div class="ticket-item">
                <div>
                  <div><strong>${it.nombre}</strong></div>
                  <div style="font-size:10px">${it.cantidad} x ${fmt(it.precio)}</div>
                </div>
                <div><strong>${fmt(it.total_linea)}</strong></div>
              </div>
            `).join('')}
          </div>
          
          <div class="ticket-total">
            <div>TOTAL: ${fmt(v.total)}</div>
          </div>
          
          <div class="ticket-footer">
            <p>Gracias por su compra</p>
            <p style="font-size:9px">${new Date().toLocaleString('es-MX')}</p>
          </div>
        </body>
        </html>
      `);
      
      ventanaImpresion.document.close();
      
      // Esperar a que cargue y luego imprimir
      setTimeout(() => {
        ventanaImpresion.print();
      }, 250);
      
    } catch(error) {
      console.error('Error al imprimir:', error);
      alert('Error al generar el ticket de impresión');
    }
  }
  
  $('#btnCerrarDetalle').addEventListener('click', ()=> $('#dlgVenta').close());

  // Carga inicial
  cargar();
})();
</script>

<?php end_pos_page(); ?>