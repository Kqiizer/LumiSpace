// ===== Helpers base =====
async function api(data) {
  const r = await fetch('api.php', { method: 'POST', body: new URLSearchParams(data) });
  return r.json();
}
const $  = (sel, ctx=document) => ctx.querySelector(sel);
const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

const getCajaLS = () => localStorage.getItem('pos_caja') || 'Caja 1';
const setCajaLS = (caja) => { localStorage.setItem('pos_caja', caja); document.cookie = `pos_caja=${caja}; path=/;`; };

// ===== Turno: cargar cajeros y prefill de saldo =====
async function cargarCajeros() {
  const r = await api({action:'cajeros_list'});
  if (!r.ok) { alert(r.error||'Error cargando cajeros'); return []; }
  return r.data || [];
}
async function prefillSaldo(cajaId) {
  const r = await api({action:'turno_last_by_caja', caja_id: cajaId});
  const inp = $('#inpSaldoInicial');
  if (!inp) return;
  if (!r.ok) { inp.value = '0.00'; return; }
  const s = Number(r.saldo_final||0);
  inp.value = s.toFixed(2);
}

// ===== Mostrar popup de apertura si no hay turno (global para todas las páginas) =====
async function showAperturaIfNeeded() {
  const dlg = $('#dlgTurno');
  if (!dlg) return; // por si acaso

  const cajaDefault = getCajaLS();
  let r = await api({action:'turno_actual', caja_id: cajaDefault});
  if (r.ok && r.turno) return; // ya hay turno

  // Preparar selects
  const selCaja = $('#selCaja');
  const selCajero = $('#selCajero');
  const btnAbrir = $('#btnAbrirTurno');

  const cajeros = await cargarCajeros();
  selCajero.innerHTML = cajeros.length
    ? cajeros.map(c=>`<option value="${c.id}">${c.id} — ${c.nombre}</option>`).join('')
    : '<option value="">(sin usuarios)</option>';

  selCaja.value = cajaDefault;
  await prefillSaldo(selCaja.value);
  selCaja.onchange = () => prefillSaldo(selCaja.value);

  dlg.showModal();

  btnAbrir.onclick = async ()=>{
    const caja_id = selCaja.value;
    const cajero_id = Number(selCajero.value || 0);
    const saldo_inicial = Number($('#inpSaldoInicial').value || '0');

    if (!caja_id) return alert('Selecciona la caja');
    if (!cajero_id) return alert('Selecciona el cajero');

    const rr = await api({action:'turno_open', caja_id, cajero_id, saldo_inicial});
    if (!rr.ok) return alert(rr.error||'No se pudo abrir el turno');

    setCajaLS(caja_id);
    dlg.close();
    location.reload();
  };
}

// ===== Corte de Caja =====
function money(n){ return `$${Number(n || 0).toFixed(2)}`; }

function fillCajaSelectForCorte(sel){
  // Si más adelante lees cajas desde BD, reemplaza este array.
  const cajas = ['Caja 1','Caja 2','Caja 3'];
  sel.innerHTML = cajas.map(c => `<option value="${c}">${c}</option>`).join('');
  sel.value = getCajaLS();
}

function renderMovs(movs){
  const tb = $('#tbMovs');
  if (!movs || !movs.length){
    tb.innerHTML = `<tr><td colspan="4" class="muted">(sin datos)</td></tr>`;
    return;
  }
  tb.innerHTML = movs.map(m => `
    <tr>
      <td>${m.fecha}</td>
      <td>${m.metodo || '-'}</td>
      <td>Venta</td>
      <td style="text-align:right">${money(m.monto)}</td>
    </tr>
  `).join('');
}

async function loadCorteCaja(){
  const sel = $('#selCajaCorte');
  const caja = sel?.value || getCajaLS();

  const r = await api({action:'corte_resumen', caja_id: caja});
  if (!r.ok){ alert(r.error || 'Error al cargar el corte'); return; }

  const d = r.data;
  if (!d){
    // No hay turno abierto para esta caja
    $('#kSaldoIni').textContent = money(0);
    $('#kEf').textContent       = money(0);
    $('#kTj').textContent       = money(0);
    $('#kSaldoAct').textContent = money(0);
    $('#lblWindow').textContent = '—';
    $('#msgCorte').textContent  = `No hay turno abierto en ${caja}.`;
    renderMovs([]);
    return;
  }

  $('#msgCorte').textContent    = '';
  $('#kSaldoIni').textContent   = money(d.saldo_inicial);
  $('#kEf').textContent         = money(d.ventas_efectivo);
  $('#kTj').textContent         = money(d.ventas_tarjeta);
  $('#kSaldoAct').textContent   = money(d.saldo_actual);
  $('#lblWindow').textContent   = `${d.inicio} — ${d.fin}`;

  renderMovs(d.movimientos || []);
}

function setupCorteEvents(){
  const sel = $('#selCajaCorte');
  const btn = $('#btnVerCorte');

  if (sel){
    fillCajaSelectForCorte(sel);
    sel.addEventListener('change', () => setCajaLS(sel.value));
  }
  if (btn){
    btn.addEventListener('click', loadCorteCaja);
  }
}

// Auto-init cuando entras a corte.php
document.addEventListener('DOMContentLoaded', () => {
  if (location.pathname.endsWith('/corte.php')){
    setupCorteEvents();
    loadCorteCaja();
  }
});

// ===== Botón "Cerrar turno" en sidebar =====
async function setupCerrarTurno() {
  const btn = $('#btnCerrarTurno');
  if (!btn) return;
  btn.addEventListener('click', async ()=>{
    const caja_id = getCajaLS();
    const d = $('#dlgCerrarTurno');
    $('#lblCajaClose').textContent = caja_id;

    // Traer saldo actual (sugerido)
    const r = await api({action:'corte_resumen', caja_id});
    if (!r.ok) return alert(r.error||'Error al obtener resumen');
    const saldoAct = Number((r.data && r.data.saldo_actual) || 0);
    $('#lblSaldoActual').textContent = `$${saldoAct.toFixed(2)}`;
    $('#inpSaldoFinal').value = saldoAct.toFixed(2);

    d.showModal();

    document.querySelector('#btnConfirmClose').onclick = async ()=>{
      const ra = await api({action:'turno_actual', caja_id});
      if (!ra.ok || !ra.turno) { alert('No hay turno activo'); return; }
      const turno_id = Number(ra.turno.id);
      const saldo_final = Number((document.querySelector('#inpSaldoFinal').value||'0'));

      const rc = await api({action:'turno_close', turno_id, saldo_final});
      if (!rc.ok) return alert(rc.error||'No se pudo cerrar el turno');

      localStorage.removeItem('pos_caja');
      document.cookie = 'pos_caja=; Max-Age=0; path=/;';
      d.close();
      location.href = 'pos.php';
    };
  });
}



// ========= estado del POS =========
let productos = [];
let cart = []; // {id, nombre, precio, qty}

// ========= UI render =========
function renderProductos() {
  const grid = $('#gridProductos');
  if (!grid) return;
  if (!productos.length) {
    grid.innerHTML = `<div class="card" style="height:120px;display:grid;place-items:center;color:#777">(sin datos)</div>`;
    return;
  }
  grid.innerHTML = productos.map(p => `
    <div class="card" style="display:grid;gap:8px">
      <div style="font-weight:600">${p.nombre}</div>
      <div style="color:#666">Stock: ${p.stock}</div>
      <div style="display:flex;justify-content:space-between;align-items:center">
        <b>$${Number(p.precio).toFixed(2)}</b>
        <button class="card btnAdd" data-id="${p.id}" data-nombre="${p.nombre}" data-precio="${p.precio}">Agregar</button>
      </div>
    </div>
  `).join('');

  $$('.btnAdd', grid).forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = Number(btn.dataset.id);
      const nombre = btn.dataset.nombre;
      const precio = Number(btn.dataset.precio);
      const found = cart.find(x=>x.id===id);
      if (found) found.qty++;
      else cart.push({id, nombre, precio, qty:1});
      renderCart();
    });
  });
}

function totals() {
  let subtotal = 0;
  for (const it of cart) subtotal += it.precio * it.qty;
  const iva = +(subtotal * 0.16).toFixed(2);
  const total = +(subtotal + iva).toFixed(2);
  return {subtotal, iva, total};
}

function renderCart() {
  const list = $('#cartList');
  if (!list) return;
  if (!cart.length) {
    list.innerHTML = '(sin items)';
  } else {
    list.innerHTML = cart.map((it,i)=>`
      <div style="display:flex;gap:8px;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;padding:6px 0">
        <div style="flex:1">
          <div><b>${it.nombre}</b></div>
          <small>$${it.precio.toFixed(2)}</small>
        </div>
        <div style="display:flex;gap:6px;align-items:center">
          <button class="card btnDec" data-i="${i}">−</button>
          <span>${it.qty}</span>
          <button class="card btnInc" data-i="${i}">+</button>
          <button class="card btnDel" data-i="${i}">x</button>
        </div>
      </div>
    `).join('');
  }

  // acciones qty
  $$('.btnDec', list).forEach(b=>b.addEventListener('click', ()=>{
    const i = Number(b.dataset.i);
    cart[i].qty--;
    if (cart[i].qty<=0) cart.splice(i,1);
    renderCart();
  }));
  $$('.btnInc', list).forEach(b=>b.addEventListener('click', ()=>{
    const i = Number(b.dataset.i);
    cart[i].qty++;
    renderCart();
  }));
  $$('.btnDel', list).forEach(b=>b.addEventListener('click', ()=>{
    const i = Number(b.dataset.i);
    cart.splice(i,1);
    renderCart();
  }));

  const t = totals();
  $('#cSubtotal').textContent = `$${t.subtotal.toFixed(2)}`;
  $('#cIva').textContent      = `$${t.iva.toFixed(2)}`;
  $('#cTotal').textContent    = `$${t.total.toFixed(2)}`;
}

// ========= cargar productos =========
async function cargarProductos(q='') {
  const r = await api({action:'productos_list', q, page:1, per_page:24});
  if (!r.ok) return alert(r.error||'Error productos_list');
  productos = r.data || [];
  renderProductos();
}

// ========= turnos helpers =========
async function asegurarTurno(caja_id='Caja 1') {
  let r = await api({action:'turno_actual', caja_id});
  if (!r.ok) throw new Error(r.error||'Error turno_actual');
  if (r.turno) return r.turno;

  // abrir automáticamente si no hay (ajusta cajero_id si hace falta)
  const cajero_id = 1;      // <-- cámbialo si tu cajero es otro id
  const saldo_inicial = 0;  // o pide en un prompt
  r = await api({action:'turno_open', caja_id, cajero_id, saldo_inicial});
  if (!r.ok) throw new Error(r.error||'No se pudo abrir el turno');
  // vuelve a consultar
  r = await api({action:'turno_actual', caja_id});
  if (!r.ok) throw new Error(r.error||'Error turno_actual');
  return r.turno;
}

// ========= pagar =========
async function pagar() {
  if (!cart.length) return alert('Carrito vacío');
  const metodo = ($('input[name=metodo]:checked')||{}).value || 'efectivo';
  const caja_id = 'Caja 1'; // puedes leer de la UI si luego quieres
  const turno = await asegurarTurno(caja_id);
  const cajero_id = Number(turno?.cajero_id || 1);

  const items = cart.map(it=>({producto_id: it.id, cantidad: it.qty}));
  const r = await api({
    action:'venta_crear',
    caja_id, cajero_id, metodo,
    items: JSON.stringify(items)
  });
  if (!r.ok) return alert(r.error||'Error al crear venta');

  alert(`Venta #${r.venta_id} registrada.\nTotal: $${Number(r.total).toFixed(2)}`);
  cart = [];
  renderCart();
  // refresca productos para ver stock actualizado
  cargarProductos($('#gridProductos') ? '' : '');
}

// ===== INIT GLOBAL =====
document.addEventListener('DOMContentLoaded', ()=>{
  // 1) Popup de apertura (si no hay turno) en cualquier página
  showAperturaIfNeeded();

  // 2) Botón cerrar turno global
  setupCerrarTurno();

  // 3) Si estamos en POS, conecta productos y pago
  if (document.getElementById('gridProductos')) {
    cargarProductos();
    const btnPagar = document.getElementById('btnPagar');
    if (btnPagar) btnPagar.addEventListener('click', pagar);

    const buscador = document.querySelector('input[type=search]');
    const recargar = Array.from(document.querySelectorAll('button')).find(b=>b.textContent?.includes('Recargar'));
    if (buscador) buscador.addEventListener('change', ()=>cargarProductos(buscador.value.trim()));
    if (recargar) recargar.addEventListener('click', ()=>cargarProductos(buscador?.value.trim()||''));
  }
});

// ======================= CORTE DE CAJA (GLOBAL) =======================
async function corteFillCajaSelect() {
  const sel = document.getElementById('selCajaCorte');
  if (!sel) return false;

  let cajas = [];
  try {
    const r = await api({action:'turnos_activos'});
    if (r.ok && Array.isArray(r.data) && r.data.length) {
      cajas = [...new Set(r.data.map(x => x.caja_id))];
    }
  } catch (_) {}

  // fallback si no hay turnos abiertos
  if (!cajas.length) cajas = ['Caja 1', 'Caja 2', 'Caja 3'];

  const preferida = getCajaLS();
  if (!cajas.includes(preferida)) cajas.unshift(preferida);
  cajas = [...new Set(cajas)];

  sel.innerHTML = cajas.map(c => `<option value="${c}">${c}</option>`).join('');
  sel.value = preferida;
  return true;
}

function cortePintarKPIs(d) {
  const k = (id,v)=>{ const el = document.getElementById(id); if (el) el.textContent = money(v||0); };
  k('kSaldoIni', d?.saldo_inicial);
  k('kEf',       d?.ventas_efectivo);
  k('kTj',       d?.ventas_tarjeta);
  k('kSaldoAct', d?.saldo_actual);
}

function cortePintarMovs(movs) {
  const tb = document.getElementById('tbMovs');
  if (!tb) return;
  if (!movs || !movs.length) {
    tb.innerHTML = `<tr><td colspan="4" class="muted">(sin datos)</td></tr>`;
    return;
  }
  tb.innerHTML = movs.map(m => `
    <tr>
      <td>${m.fecha}</td>
      <td>${m.metodo || '-'}</td>
      <td>venta</td>
      <td style="text-align:right">${money(m.monto)}</td>
    </tr>
  `).join('');
}

async function corteLoadResumen() {
  const sel = document.getElementById('selCajaCorte');
  if (!sel) return;

  const caja_id = sel.value;
  setCajaLS(caja_id);

  const msg = document.getElementById('msgCorte');
  if (msg) msg.textContent = 'Cargando…';

  try {
    const r = await api({action:'corte_resumen', caja_id});
    if (!r.ok) throw new Error(r.error || 'Error al consultar corte');

    const lbl = document.getElementById('lblWindow');
    if (!r.data) {
      cortePintarKPIs(null);
      cortePintarMovs([]);
      if (lbl) lbl.textContent = '—';
      if (msg) msg.textContent = 'No hay turno para esta caja.';
      return;
    }
    const d = r.data;
    if (lbl) lbl.textContent = `${d.inicio} — ${d.fin || 'ahora'}`;
    cortePintarKPIs(d);
    cortePintarMovs(d.movimientos || []);
    if (msg) msg.textContent = '';
  } catch (e) {
    cortePintarKPIs(null);
    cortePintarMovs([]);
    const lbl = document.getElementById('lblWindow'); if (lbl) lbl.textContent = '—';
    if (msg) msg.textContent = e.message || 'Error';
  }
}

async function initCortePage() {
  // Si no estamos en corte.php, no hace nada
  if (!document.getElementById('selCajaCorte')) return;

  await corteFillCajaSelect();
  await corteLoadResumen();

  const btn = document.getElementById('btnVerCorte');
  if (btn) btn.addEventListener('click', corteLoadResumen);
}

// Llamamos SIEMPRE a los globales y, además, a Corte si estamos en esa página.
document.addEventListener('DOMContentLoaded', async () => {
  // Estos ya los tienes globales; si no existen, no pasa nada.
  try { await showAperturaIfNeeded?.(); } catch(_) {}
  try { await setupCerrarTurno?.(); }   catch(_) {}

  initCortePage();
});

// =============== Helpers de formato (si no existen ya) ===============
function mx(n){ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n||0)); }

// =============== Dibujadores muy simples en <canvas> ===============
function drawLineChart(canvas, labels, data){
  if(!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;
  ctx.clearRect(0,0,W,H);

  // bordes y escalas
  const pad = 40;
  const max = Math.max(...data, 1);
  const X = (i)=> pad + (i*(W-pad*2)/(data.length-1||1));
  const Y = (v)=> H - pad - (v/max)*(H-pad*2);

  // ejes
  ctx.strokeStyle = '#ddd'; ctx.beginPath();
  ctx.moveTo(pad, H-pad); ctx.lineTo(W-pad, H-pad); // X
  ctx.moveTo(pad, H-pad); ctx.lineTo(pad, pad);     // Y
  ctx.stroke();

  // línea
  ctx.strokeStyle = '#7b6'; ctx.lineWidth = 2; ctx.beginPath();
  data.forEach((v,i)=>{ const x=X(i), y=Y(v); i?ctx.lineTo(x,y):ctx.moveTo(x,y); });
  ctx.stroke();

  // puntos
  ctx.fillStyle = '#7b6';
  data.forEach((v,i)=>{ const x=X(i), y=Y(v); ctx.beginPath(); ctx.arc(x,y,3,0,Math.PI*2); ctx.fill(); });

  // labels X (opcionales, solo algunos para no saturar)
  ctx.fillStyle = '#888'; ctx.font = '12px system-ui';
  const step = Math.ceil(labels.length/6) || 1;
  labels.forEach((lb,i)=>{ if(i%step===0){ ctx.fillText(lb.slice(5), X(i)-18, H-18); }});
}

function drawBarChart(canvas, labels, data){
  if(!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;
  ctx.clearRect(0,0,W,H);

  const pad = 40;
  const max = Math.max(...data, 1);
  const bw = (W - pad*2) / (data.length || 1) * 0.7;
  const X = (i)=> pad + i*((W-pad*2)/(data.length||1)) + ((W-pad*2)/(data.length||1) - bw)/2;
  const Y = (v)=> H - pad - (v/max)*(H-pad*2);

  // ejes
  ctx.strokeStyle = '#ddd'; ctx.beginPath();
  ctx.moveTo(pad, H-pad); ctx.lineTo(W-pad, H-pad);
  ctx.moveTo(pad, H-pad); ctx.lineTo(pad, pad);
  ctx.stroke();

  // barras
  ctx.fillStyle = '#68a';
  data.forEach((v,i)=>{ const x=X(i), y=Y(v), h=H-pad-y; ctx.fillRect(x, y, bw, h); });

  // labels X (muestra pocos)
  ctx.fillStyle = '#888'; ctx.font = '12px system-ui';
  const step = Math.ceil(labels.length/6) || 1;
  labels.forEach((lb,i)=>{ if(i%step===0){ ctx.fillText(lb, X(i), H-18); }});
}

// =============== Carga de estadísticas ===============
async function loadEstadisticas(){
  // solo corre si estamos en la página
  if(!document.getElementById('chartDia')) return;

  try{
    // KPIs
    const hoy   = await api({action:'stats_hoy'});
    const sem   = await api({action:'stats_semana'});
    const ptop  = await api({action:'stats_producto_top'});
    const ctop  = await api({action:'stats_cajero_top'});

    document.getElementById('kHoy').textContent     = mx(hoy.total) + ` (${hoy.ventas} ventas)`;
    document.getElementById('kSemana').textContent  = mx(sem.total) + ` (${sem.ventas} ventas)`;
    document.getElementById('kProdTop').textContent = ptop.data ? `${ptop.data.nombre} — ${ptop.data.cant} pzas` : '—';
    document.getElementById('kCajeroTop').textContent = ctop.data ? `${ctop.data.nombre} — ${ctop.data.ventas} ventas` : '—';

    // Serie por día
    const serie = await api({action:'stats_ventas_por_dia', dias: 7});
    drawLineChart(document.getElementById('chartDia'), serie.labels, serie.data);

    // Por categoría
    const cat = await api({action:'stats_ventas_por_categoria'});
    drawBarChart(document.getElementById('chartCat'), cat.labels, cat.data);

  }catch(e){
    console.error(e);
  }
}

// Hook global
document.addEventListener('DOMContentLoaded', loadEstadisticas);

