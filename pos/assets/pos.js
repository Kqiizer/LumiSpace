// ===== Helpers base =====
async function api(data) {
  const r = await fetch('api.php', { method: 'POST', body: new URLSearchParams(data) });
  return r.json();
}
const $  = (sel, ctx=document) => ctx.querySelector(sel);
const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

// Caja preferida y sesión de turno (misma máquina)
const getCajaLS = () =>
  localStorage.getItem('pos_caja') ||
  (document.cookie.match(/pos_caja=([^;]+)/)?.[1]) ||
  null;

const setCajaLS = (caja) => {
  localStorage.setItem('pos_caja', String(caja));
  document.cookie = `pos_caja=${String(caja)}; path=/;`;
};


// Sesión local del turno (para reanudar tras recarga)
const getTurnoSession = () => {
  try { return JSON.parse(localStorage.getItem('pos_turno_session') || 'null'); } catch { return null; }
};
const setTurnoSession = (sess) => localStorage.setItem('pos_turno_session', JSON.stringify(sess));
const clearTurnoSession = () => localStorage.removeItem('pos_turno_session');

// Estado global del monto en caja
let montoActualCaja = 0;

function actualizarMontoSidebar() {
  const el = document.getElementById('montoActualSidebar');
  if (el) el.textContent = '$' + montoActualCaja.toFixed(2);
}

async function cargarMontoInicial() {
  const caja_id = getCajaLS();
  const r = await api({ action: 'turno_actual', caja_id });
  if (r.ok && r.turno) {
    montoActualCaja = parseFloat(r.turno.saldo_inicial || 0);
    
    // Sumar ventas en efectivo del turno actual
    const rCorte = await api({ action: 'corte_resumen', caja_id });
    if (rCorte.ok && rCorte.data) {
      montoActualCaja = parseFloat(rCorte.data.saldo_actual || montoActualCaja);
    }
    
    actualizarMontoSidebar();
  }
}

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
  if (!dlg) return;

  const sess = getTurnoSession();

  // Si hay sesión activa, verificar que el turno siga abierto
  if (sess?.caja_id && sess?.turno_id) {
    const r = await api({ action:'turno_actual', caja_id: sess.caja_id });
    if (r.ok && r.turno
        && Number(r.turno.id) === Number(sess.turno_id)
        && String(r.turno.caja_id) === String(sess.caja_id)) {

      setCajaLS(sess.caja_id);

      // bloquear salida/retroceso
      window.onbeforeunload = (e) => { e.preventDefault(); return '¿Estás seguro de salir? Tienes un turno abierto.'; };
      history.pushState(null, '', location.href);
      window.onpopstate = () => {
        history.pushState(null, '', location.href);
        alert('No puedes salir mientras tengas un turno abierto. Cierra el turno primero.');
      };
      return;
    } else {
      clearTurnoSession();
    }
  }

  // --- Popup de apertura ---
  const selCaja   = $('#selCaja');
  const selCajero = $('#selCajero');
  const btnAbrir  = $('#btnAbrirTurno');
  const inpSaldo  = $('#inpSaldoInicial');

  // Cargar cajeros
  const cajeros = await cargarCajeros();
  selCajero.innerHTML = cajeros.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

  // Cargar cajas y deshabilitar las en uso
  const rCajas   = await api({ action: 'cajas_list' });
  const rActivos = await api({ action: 'turnos_activos' });
  const cajasEnUso = (rActivos.ok && Array.isArray(rActivos.data)) ? rActivos.data.map(t => String(t.caja_id)) : [];

  if (rCajas.ok && Array.isArray(rCajas.data) && rCajas.data.length) {
    selCaja.innerHTML = rCajas.data.map(c => {
      const enUso = cajasEnUso.includes(String(c));
      return `<option value="${c}" ${enUso ? 'disabled' : ''}>${c} ${enUso ? '(En uso)' : ''}</option>`;
    }).join('');
    // Seleccionar la primera libre si existe
    const optLibre = Array.from(selCaja.options).find(o => !o.disabled);
    selCaja.value = optLibre ? optLibre.value : '';
  } else {
    selCaja.innerHTML = `<option value="">(sin cajas)</option>`;
  }

  // Prefill de saldo inicial
  selCaja.onchange = async () => {
    const caja = selCaja.value;
    if (!caja || selCaja.selectedOptions[0]?.disabled) return;
    const r = await api({ action: 'turno_last_by_caja', caja_id: caja });
    inpSaldo.value = (r.ok && r.saldo_final) ? Number(r.saldo_final).toFixed(2) : '0.00';
  };
  if (selCaja.value && !selCaja.selectedOptions[0]?.disabled) selCaja.onchange();

  // Confirmar apertura
  btnAbrir.onclick = async () => {
    const caja_id       = selCaja.value;
    const cajero_id     = Number(selCajero.value);
    const saldo_inicial = parseFloat(inpSaldo.value || '0');

    if (!caja_id || selCaja.selectedOptions[0]?.disabled) return alert('Esta caja está en uso o no es válida');
    if (!cajero_id) return alert('Selecciona un cajero');

    const r = await api({ action: 'turno_open', caja_id, cajero_id, saldo_inicial });
    if (!r.ok) return alert(r.error || 'Error al abrir turno');

    // Guardar preferencia y sesión del turno
    setCajaLS(String(caja_id));
    setTurnoSession({ caja_id: String(caja_id), turno_id: Number(r.turno_id), ts: Date.now() });

    dlg.close();
    location.reload();
  };

  // Mostrar modal (no se puede cerrar con ESC)
  dlg.addEventListener('cancel', (e) => e.preventDefault());
  dlg.showModal();
}

// ===== Corte de Caja =====
function money(n){ return `$${Number(n || 0).toFixed(2)}`; }

async function fillCajaSelectForCorte(sel) {
  const rCajas = await api({ action:'cajas_list' });
  if (!rCajas.ok || !Array.isArray(rCajas.data) || !rCajas.data.length) {
    sel.innerHTML = `<option value="">(sin cajas)</option>`;
    return;
  }
  const cajas = rCajas.data;
  sel.innerHTML = cajas.map(c => `<option value="${c}">${c}</option>`).join('');

  const preferida = getCajaLS();
  if (preferida && cajas.includes(preferida)) sel.value = preferida;
  else sel.selectedIndex = 0;
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
  if (!r.ok){ 
    alert(r.error || 'Error al cargar el corte'); 
    return; 
  }

  const d = r.data;
  if (!d){
    // No hay turno para esta caja
    $('#kSaldoIni').textContent = money(0);
    $('#kEf').textContent       = money(0);
    $('#kTj').textContent       = money(0);
    $('#kSaldoAct').textContent = money(0);
    $('#lblWindow').textContent = '—';
    $('#msgCorte').textContent  = `No hay turno disponible en ${caja}.`;
    renderMovs([]);
    return;
  }

  $('#msgCorte').textContent    = '';
  $('#kSaldoIni').textContent   = money(d.saldo_inicial);
  $('#kEf').textContent         = money(d.ventas_efectivo);
  $('#kTj').textContent         = money(d.ventas_tarjeta);
  $('#kSaldoAct').textContent   = money(d.saldo_actual);
  $('#lblWindow').textContent = `${d.inicio} — ${d.fin || 'ahora'}`;+

  renderMovs(d.movimientos || []);
}

async function setupCerrarTurno() {
  const btn = $('#btnCerrarTurno');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    const caja_id = getCajaLS();
    const rTurno = await api({ action:'turno_actual', caja_id });
    if (!rTurno.ok || !rTurno.turno) return alert('No hay turno activo para cerrar');

    // Sugerencia de saldo actual
    const rRes = await api({ action:'corte_resumen', caja_id });
    const saldoAct = Number(rRes?.data?.saldo_actual || 0);
    
    $('#lblCajaClose').textContent = caja_id;
    $('#lblSaldoActual').textContent = `$${saldoAct.toFixed(2)}`;
    $('#inpSaldoFinal').value = saldoAct.toFixed(2);

    const d = $('#dlgCerrarTurno');
    if (!d) return alert('No se encontró el diálogo de cierre');
    d.showModal();


    $('#btnConfirmClose').onclick = async () => {
      const saldo_final = parseFloat($('#inpSaldoFinal').value || '0');
      
      if (!confirm('¿Confirmas el cierre del turno? Esta acción no se puede deshacer.')) return;
      
      const rc = await api({ 
        action:'turno_close', 
        turno_id: Number(rTurno.turno.id), 
        saldo_final 
      });
      
      if (!rc.ok) return alert(rc.error || 'No se pudo cerrar');
      
      alert('Turno cerrado exitosamente');
      clearTurnoSession();
      
      // Liberar restricciones de navegación
      window.onbeforeunload = null;
      window.onpopstate = null;
      
      d.close();
      location.href = 'pos.php';
    };
  });
}
// Botón flotante del carrito - Abrir/cerrar modal de checkout
const btnCarrito = $('#btnCarrito');
const ticketPanel = $('#ticketPanel');
const ticketOverlay = $('#ticketOverlay');
const btnCerrarTicket = $('#btnCerrarTicket');

function toggleTicketPanel() {
  if (!ticketPanel || !ticketOverlay) return;
  
  const isActive = ticketPanel.classList.contains('active');
  
  if (isActive) {
    // Cerrar modal
    ticketPanel.classList.remove('active');
    ticketOverlay.classList.remove('active');
    document.body.style.overflow = '';
  } else {
    // Abrir modal
    ticketPanel.classList.add('active');
    ticketOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Scroll al inicio del modal
    setTimeout(() => {
      if (ticketPanel) {
        ticketPanel.scrollTop = 0;
      }
    }, 100);
  }
}

if (btnCarrito) {
  btnCarrito.addEventListener('click', (e) => {
    e.stopPropagation();
    // Si existe el panel moderno, usarlo
    if (ticketPanel && ticketOverlay) {
      toggleTicketPanel();
    } else {
      // Fallback al diálogo antiguo
      const dlg = $('#dlgCarrito');
      if (dlg) dlg.showModal();
    }
  });
}

if (ticketOverlay) {
  ticketOverlay.addEventListener('click', (e) => {
    if (e.target === ticketOverlay) {
      toggleTicketPanel();
    }
  });
}

if (btnCerrarTicket) {
  btnCerrarTicket.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleTicketPanel();
  });
}

// Cerrar con ESC
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && ticketPanel && ticketPanel.classList.contains('active')) {
    toggleTicketPanel();
  }
});

// Prevenir que el modal se cierre al hacer clic dentro de él
if (ticketPanel) {
  ticketPanel.addEventListener('click', (e) => {
    e.stopPropagation();
  });
}

const metodoRadios = $$('input[name=metodo]');
if (metodoRadios.length) {
  // Los estilos ahora se manejan con :checked en CSS, pero mantenemos compatibilidad
  const syncMetodoHighlight = () => {
    metodoRadios.forEach(radio => {
      const wrapper = radio.closest('.payment-card');
      if (!wrapper) return;
      // El estilo se maneja con CSS :checked, pero podemos agregar clase si es necesario
      wrapper.classList.toggle('is-active', radio.checked);
    });
  };
  metodoRadios.forEach(radio => radio.addEventListener('change', syncMetodoHighlight));
  syncMetodoHighlight();
}

// Compatibilidad: también manejar dlgCarrito si existe (versión antigua)
const btnCerrarCarrito = $('#btnCerrarCarrito');
if (btnCerrarCarrito) {
  btnCerrarCarrito.addEventListener('click', () => {
    const dlg = $('#dlgCarrito');
    if (dlg) dlg.close();
  });
}

// Cargar monto inicial de la caja
cargarMontoInicial();


// ========= estado del POS =========
let productos = [];
let cart = []; // {id, nombre, precio, qty}

// ========= UI render =========
// ========= UI render =========
function renderProductos() {
  const grid = $('#gridProductos');
  if (!grid) return;
  if (!productos.length) {
    grid.innerHTML = `<div class="card" style="height:120px;display:grid;place-items:center;color:#777">(sin datos)</div>`;
    return;
  }
  
  grid.innerHTML = productos.map(p => {
  const imgSrc = p.imagen 
  ? `../images/productos/${p.imagen}` 
  : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect fill="%23f5f5f5" width="100" height="100"/%3E%3Ctext x="50" y="50" text-anchor="middle" dy=".3em" fill="%23999" font-size="14"%3ESin imagen%3C/text%3E%3C/svg%3E';
    
    return `
      <div class="card" data-id="${p.id}" data-nombre="${p.nombre}" data-precio="${p.precio}">
        <img src="${imgSrc}" alt="${p.nombre}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' viewBox=\\'0 0 100 100\\'%3E%3Crect fill=\\'%23f5f5f5\\' width=\\'100\\' height=\\'100\\'/%3E%3Ctext x=\\'50\\' y=\\'50\\' text-anchor=\\'middle\\' dy=\\'.3em\\' fill=\\'%23999\\' font-size=\\'14\\'%3ESin imagen%3C/text%3E%3C/svg%3E'">
        <strong>${p.nombre}</strong>
        <div class="stock-info">Stock: ${p.stock}</div>
        <div class="price">$${Number(p.precio).toFixed(2)}</div>
      </div>
    `;
  }).join('');
  
  // Agregar event listener a cada card
  $$('.card[data-id]', grid).forEach(card => {
    card.addEventListener('click', () => {
      const id = Number(card.dataset.id);
      const nombre = card.dataset.nombre;
      const precio = Number(card.dataset.precio);
      const found = cart.find(x => x.id === id);
      if (found) found.qty++;
      else cart.push({id, nombre, precio, qty: 1});
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
  const badge = $('#badgeCart');
  
  if (!list) return;
  
<<<<<<< HEAD
  // Calcular cantidad total
  const totalQty = cart.reduce((sum, it) => sum + it.qty, 0);
  
  // Actualizar contador de productos
  const productCount = $('#productCount');
  if (productCount) {
    productCount.textContent = totalQty > 0 ? `${totalQty} ${totalQty === 1 ? 'item' : 'items'}` : '0 items';
  }

  if (!cart.length) {
    list.innerHTML = `
      <div class="checkout-empty">
        <svg class="empty-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M3 6h18M16 10a4 4 0 11-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <p class="empty-text">Carrito vacío</p>
        <small class="empty-hint">Agrega productos para continuar</small>
      </div>
    `;
    if (badge) {
      badge.textContent = '0';
      badge.style.display = 'none';
    }

    // totales en cero
    const t0 = {subtotal:0, iva:0, total:0};
    $('#cSubtotal') && ($('#cSubtotal').textContent = `$${t0.subtotal.toFixed(2)}`);
    $('#cIva')      && ($('#cIva').textContent      = `$${t0.iva.toFixed(2)}`);
    $('#cTotal')    && ($('#cTotal').textContent    = `$${t0.total.toFixed(2)}`);
    return;
  }

  // Determinar qué HTML usar basado en qué elementos existen en el DOM
  const useModernUI = $('#ticketPanel') || list.classList.contains('checkout-list-modern');
  
  if (useModernUI) {
    // UI moderna con checkout-item-modern
    list.innerHTML = cart.map((it, i) => `
      <div class="checkout-item-modern">
        <div class="checkout-item-content">
          <div class="checkout-item-main">
            <h5 class="checkout-item-name">${it.nombre}</h5>
            <div class="checkout-item-price">$${it.precio.toFixed(2)}</div>
          </div>
          <div class="checkout-item-qty-modern">
            <button data-i="${i}" class="qty-btn qty-dec btnDec" aria-label="Decrementar">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
            <span class="qty-value">${it.qty}</span>
            <button data-i="${i}" class="qty-btn qty-inc btnInc" aria-label="Incrementar">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
          <div class="checkout-item-total">
            <span class="item-total-label">Subtotal</span>
            <strong class="item-total-value">$${(it.precio * it.qty).toFixed(2)}</strong>
          </div>
        </div>
        <button data-i="${i}" class="checkout-item-remove cart-item-remove" aria-label="Eliminar">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </button>
      </div>
    `).join('');
  } else {
    // UI simple con cart-item (compatibilidad)
    list.innerHTML = cart.map((it, i) => `
      <div class="cart-item">
        <div class="cart-item-info">
          <div class="cart-item-name">${it.nombre}</div>
          <div class="cart-item-price">$${it.precio.toFixed(2)}</div>
        </div>
        <div class="cart-item-qty">
          <button data-i="${i}" class="btnDec">−</button>
          <span>${it.qty}</span>
          <button data-i="${i}" class="btnInc">+</button>
        </div>
        <button data-i="${i}" class="cart-item-remove">×</button>
      </div>
    `).join('');
  }

  // Actualizar badge del botón flotante
  if (badge) {
    if (totalQty > 0) {
      badge.textContent = String(totalQty);
      badge.style.display = 'flex';
      
      // Animación cuando se agrega un producto
      badge.style.animation = 'none';
      setTimeout(() => {
        badge.style.animation = 'badgePulse 0.5s ease';
      }, 10);
    } else {
      badge.textContent = '0';
      badge.style.display = 'none';
    }
  }

  // Eventos de cantidad
  $$('.btnDec', list).forEach(b => b.addEventListener('click', () => {
    const i = Number(b.dataset.i);
    cart[i].qty--;
    if (cart[i].qty <= 0) cart.splice(i, 1);
    renderCart();
  }));
  
  $$('.btnInc', list).forEach(b => b.addEventListener('click', () => {
    const i = Number(b.dataset.i);
    cart[i].qty++;
    renderCart();
  }));
  
  $$('.cart-item-remove', list).forEach(b => b.addEventListener('click', () => {
    const i = Number(b.dataset.i);
    cart.splice(i, 1);
    renderCart();
  }));

  // <<< actualizar totales >>>
  const t = totals();
  $('#cSubtotal') && ($('#cSubtotal').textContent = `$${t.subtotal.toFixed(2)}`);
  $('#cIva')      && ($('#cIva').textContent      = `$${t.iva.toFixed(2)}`);
  $('#cTotal')    && ($('#cTotal').textContent    = `$${t.total.toFixed(2)}`);
}

async function pagar() {
  if (!cart.length) return alert('Carrito vacío');

  try {
    const metodo  = ($('input[name=metodo]:checked') || {}).value || 'efectivo';
    const caja_id = getCajaLS();
    const turno   = await asegurarTurno(caja_id); // si falla, cae al catch
    const cajero_id = Number(turno?.cajero_id || 1);
    const t = totals();

    // ---- EFECTIVO ----
    if (metodo === 'efectivo') {
      const dlg = $('#dlgEfectivo');
      if (!dlg) return alert('No se encontró el diálogo de pago en efectivo');

      // Prefill UI
      $('#lblTotalEfectivo').textContent = '$' + t.total.toFixed(2);
      $('#inpMontoPagado').value = '';
      $('#cambioBox').style.display = 'none';
      $('#btnConfirmarEfectivo').disabled = true;

      // Handler cálculo de cambio
      const inp = $('#inpMontoPagado');
      inp.oninput = () => {
        const pagado = parseFloat(inp.value || 0);
        const cambio = pagado - t.total;
        if (cambio >= 0) {
          $('#cambioBox').style.display = 'block';
          $('#lblCambio').textContent = '$' + cambio.toFixed(2);
          $('#btnConfirmarEfectivo').disabled = false;
        } else {
          $('#cambioBox').style.display = 'none';
          $('#btnConfirmarEfectivo').disabled = true;
        }
      };

      // Confirmar pago
      const btnOk = $('#btnConfirmarEfectivo');
      const btnCancel = $('#btnCancelarEfectivo');
      btnOk.onclick = async () => {
        const pagado = parseFloat($('#inpMontoPagado').value || 0);
        const cambio = pagado - t.total;
        if (cambio < 0) return alert('El monto pagado es insuficiente');

        // Registrar venta
        const items = cart.map(it => ({ producto_id: it.id, cantidad: it.qty }));
        btnOk.disabled = true;
        try {
          const r = await api({
            action: 'venta_crear',
            caja_id, cajero_id, metodo,
            items: JSON.stringify(items)
          });
          if (!r.ok) return alert(r.error || 'Error al crear venta');

          // Actualizar monto en caja (entra el total efectivamente cobrado)
          montoActualCaja = montoActualCaja + t.total;
          actualizarMontoSidebar();

          alert(`Venta #${r.venta_id} registrada.\nTotal: $${t.total.toFixed(2)}\nPagado: $${pagado.toFixed(2)}\nCambio: $${cambio.toFixed(2)}`);

          // Reset UI/estado
          cart = [];
          renderCart();
          cargarProductos();
          dlg.close();
          // Cerrar también el modal del carrito si existe (compatibilidad)
          const dlgCarrito = $('#dlgCarrito');
          if (dlgCarrito) dlgCarrito.close();
          // Cerrar también el panel de ticket si existe
          if (ticketPanel && ticketPanel.classList.contains('active')) {
            toggleTicketPanel();
          }
        } finally {
          btnOk.disabled = false;
        }
      };

      btnCancel.onclick = () => dlg.close();
      dlg.showModal();
      return; // ya no seguimos a tarjeta
    }

    // ---- TARJETA ----
    const items = cart.map(it => ({ producto_id: it.id, cantidad: it.qty }));
    const r = await api({
      action: 'venta_crear',
      caja_id, cajero_id, metodo,
      items: JSON.stringify(items)
    });
    if (!r.ok) return alert(r.error || 'Error al crear venta');

    alert(`Venta #${r.venta_id} registrada.\nTotal: $${t.total.toFixed(2)}\nMétodo: Tarjeta`);

    cart = [];
    renderCart();
    cargarProductos();
    // Cerrar también el modal del carrito si existe (compatibilidad)
    const dlgCarrito = $('#dlgCarrito');
    if (dlgCarrito) dlgCarrito.close();
    // Cerrar también el panel de ticket si existe
    if (ticketPanel && ticketPanel.classList.contains('active')) {
      toggleTicketPanel();
    }

  } catch (e) {
    alert(e?.message || 'Abre un turno para continuar.');
  }
}



// ========= cargar productos =========
async function cargarProductos(q='') {
  const r = await api({action:'productos_list', q, page:1, per_page:24});
  if (!r.ok) return alert(r.error||'Error productos_list');
  productos = r.data || [];
  renderProductos();
}

// ========= turnos helpers =========
async function asegurarTurno(caja_id) {
  if (!caja_id) {
    await showAperturaIfNeeded();
    throw new Error('Selecciona una caja para continuar');
  }
  const r = await api({ action: 'turno_actual', caja_id });
  if (!r.ok) throw new Error(r.error || 'Error turno_actual');
  if (!r.turno) {
    await showAperturaIfNeeded();
    throw new Error('Abre un turno para continuar');
  }
  return r.turno;
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

  const rCajas = await api({ action:'cajas_list' });
  if (!rCajas.ok || !Array.isArray(rCajas.data) || !rCajas.data.length) {
    sel.innerHTML = `<option value="">(sin cajas)</option>`;
    return;
  }
  const cajas = rCajas.data;

  sel.innerHTML = cajas.map(c => `<option value="${c}">${c}</option>`).join('');
  const preferida = getCajaLS();
  if (preferida && cajas.includes(preferida)) sel.value = preferida;
  else sel.selectedIndex = 0;
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

// Ir a secciones
document.addEventListener('keydown', (e) => {
  if (e.altKey && !e.shiftKey && !e.ctrlKey) {
    if (e.key === '1') location.href = 'pos.php';
    if (e.key === '2') location.href = 'ventas.php';
    if (e.key === '3') location.href = 'estadisticas.php';
    if (e.key === '4') location.href = 'corte.php';
  }
});

// Grid de productos: flechas + enter
let prodIndex = 0;
function focusProducto(i) {
  const cards = $$('#gridProductos .card[data-id]');
  if (!cards.length) return;
  prodIndex = (i + cards.length) % cards.length;
  cards.forEach(c => c.tabIndex = 0);
  cards[prodIndex].focus({preventScroll:false});
  cards[prodIndex].scrollIntoView({block:'nearest'});
}
document.addEventListener('keydown', (e) => {
  const inPos = !!$('#gridProductos');
  if (!inPos) return;
  const cards = $$('#gridProductos .card[data-id]');
  if (!cards.length) return;

  const cols = Math.max(1, Math.floor($('#gridProductos').clientWidth / cards[0].clientWidth));
  if (['ArrowRight','ArrowLeft','ArrowUp','ArrowDown','Enter'].includes(e.key)) e.preventDefault();

  if (e.key==='ArrowRight') focusProducto(prodIndex + 1);
  if (e.key==='ArrowLeft')  focusProducto(prodIndex - 1);
  if (e.key==='ArrowDown')  focusProducto(prodIndex + cols);
  if (e.key==='ArrowUp')    focusProducto(prodIndex - cols);
  if (e.key==='Enter') {
    const card = $$('#gridProductos .card[data-id]')[prodIndex];
    if (card) card.click();
  }
});

// Selects/enum: espacio abre, enter confirma (nativo del navegador)
// (No hace falta código extra: sólo aseguramos que los <select> tengan focus al tabular)

// POS: buscar en tiempo real
document.addEventListener('DOMContentLoaded', () => {
  const busc = document.querySelector('input[type=search]');
  if (busc) {
    let t; busc.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(() => cargarProductos(busc.value.trim()), 250);
    });
  }
});

async function fetchVentaCompleta(venta_id){
  const r = await api({action:'venta_detalle', venta_id});
  if (!r.ok) throw new Error(r.error || 'No se pudo cargar la venta');
  return r;
}

async function descargarTicket(venta_id){
  const { jsPDF } = window.jspdf || {};
  if (!jsPDF) { alert('No se encontró jsPDF'); return; }

  const r = await fetchVentaCompleta(venta_id);
  const v = r.venta, items = r.items || [];

  const doc = new jsPDF({ unit:'pt', format:'a5' });
  let y = 40;

  doc.setFontSize(16); doc.text('LumiSpace - Ticket', 40, y); y += 18;
  doc.setFontSize(11);
  doc.text(`Folio: #${v.id}`, 40, y); y+=14;
  doc.text(`Fecha: ${v.fecha}`, 40, y); y+=14;
  doc.text(`Cajero: ${v.cajero||'-'}`, 40, y); y+=14;
  doc.text(`Caja: ${v.caja_id||'-'}`, 40, y); y+=20;

  doc.setFont(undefined, 'bold'); doc.text('Producto', 40, y); doc.text('Cant', 260, y); doc.text('Importe', 320, y); doc.setFont(undefined, 'normal'); y+=12;
  doc.line(40, y, 380, y); y+=10;

  items.forEach(it=>{
    const imp = (Number(it.precio)*Number(it.cantidad)).toFixed(2);
    doc.text(String(it.nombre).slice(0,28), 40, y);
    doc.text(String(it.cantidad), 265, y, {align:'right'});
    doc.text(`$${imp}`, 380, y, {align:'right'});
    y+=14;
  });

  y+=8; doc.line(40, y, 380, y); y+=14;
  doc.text(`Subtotal: $${Number(v.subtotal).toFixed(2)}`, 260, y, {align:'right'}); y+=14;
  doc.text(`IVA: $${Number(v.iva).toFixed(2)}`, 260, y, {align:'right'}); y+=14;
  doc.setFont(undefined, 'bold');
  doc.text(`TOTAL: $${Number(v.total).toFixed(2)}`, 260, y, {align:'right'});

  doc.save(`ticket_${v.id}.pdf`);
}

<<<<<<< HEAD
// ===== Selector de Idioma =====
document.addEventListener('DOMContentLoaded', () => {
  const langSelector = $('#languageSelector');
  const langBtn = $('#btnLangToggle');
  const langDropdown = $('#langDropdown');
  const langText = $('#langText');
  const langFlagBtn = $('#langFlagBtn');
  const langOptions = $$('.lang-option');

  if (!langSelector || !langBtn || !langDropdown) return;

  // Obtener idioma guardado o usar español por defecto
  const getSavedLang = () => {
    return localStorage.getItem('pos_language') || 'es';
  };

  const setSavedLang = (lang) => {
    localStorage.setItem('pos_language', lang);
    document.documentElement.lang = lang;
    
    // Disparar evento personalizado para que otros scripts puedan escuchar el cambio
    window.dispatchEvent(new CustomEvent('languageChanged', { detail: { lang } }));
  };

  // Actualizar UI según idioma guardado
  const updateLangUI = () => {
    const currentLang = getSavedLang();
    langOptions.forEach(opt => {
      const lang = opt.dataset.lang;
      if (lang === currentLang) {
        opt.classList.add('active');
        // Actualizar botón principal
        langFlagBtn.textContent = opt.dataset.flag;
        langText.textContent = opt.dataset.name;
      } else {
        opt.classList.remove('active');
      }
    });
  };

  // Inicializar
  updateLangUI();
  document.documentElement.lang = getSavedLang();

  // Toggle dropdown
  langBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    langSelector.classList.toggle('active');
  });

  // Cerrar al hacer click fuera
  document.addEventListener('click', (e) => {
    if (!langSelector.contains(e.target)) {
      langSelector.classList.remove('active');
    }
  });

  // Cambiar idioma
  langOptions.forEach(opt => {
    opt.addEventListener('click', () => {
      const lang = opt.dataset.lang;
      const flag = opt.dataset.flag;
      const name = opt.dataset.name;
      
      // Animación suave al cambiar
      langFlagBtn.style.transform = 'scale(0.8) rotate(-10deg)';
      langText.style.opacity = '0.5';
      
      setTimeout(() => {
        setSavedLang(lang);
        langFlagBtn.textContent = flag;
        langText.textContent = name;
        updateLangUI();
        
        langFlagBtn.style.transform = 'scale(1) rotate(0deg)';
        langText.style.opacity = '1';
        langSelector.classList.remove('active');
      }, 150);
    });
  });

  // Prevenir que el dropdown se cierre al hacer click dentro
  langDropdown.addEventListener('click', (e) => {
    e.stopPropagation();
  });
});
