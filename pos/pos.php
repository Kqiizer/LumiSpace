<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

start_pos_page('Punto de Venta');   
?>

<!-- Grid de productos a pantalla completa -->
<section class="card pos-products-full">
  <header style="display:flex;gap:10px;align-items:center;margin-bottom:16px;">
    <input id="inpBuscar" type="search" placeholder="Buscar productos..." style="flex:1;padding:12px;border:2px solid var(--border-light);border-radius:var(--radius-sm);">
    <button id="btnCarrito" class="btn btn-primary" style="min-width:160px;position:relative">
      🛒 Ver Carrito <span id="badgeCart" style="position:absolute;top:-8px;right:-8px;background:var(--danger);color:white;border-radius:50%;width:24px;height:24px;display:grid;place-items:center;font-size:12px;font-weight:800">0</span>
    </button>
  </header>
  
  <div id="gridProductos" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(220px, 1fr));gap:20px;overflow-y:auto;max-height:calc(100vh - 220px);padding:10px">
    <div class="card" style="height:120px;display:grid;place-items:center;color:#777">Productos…</div>
  </div>
</section>

<!-- POPUP DEL CARRITO -->
<dialog id="dlgCarrito" class="dlg" style="max-width:500px">
  <div class="dlg-body">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="margin:0">🛒 Carrito de compras</h3>
      <button id="btnCerrarCarrito" class="btn btn-sm" style="border:none;background:var(--bg-3)">✕</button>
    </div>

    <div id="cartList" style="max-height:300px;overflow-y:auto;margin-bottom:20px;display:flex;flex-direction:column;gap:12px">
      <p class="muted" style="text-align:center;padding:40px 20px">(sin items)</p>
    </div>

    <hr>

    <div style="display:grid;gap:8px;margin-bottom:20px">
      <div style="display:flex;justify-content:space-between"><span>Subtotal</span><b id="cSubtotal">$0.00</b></div>
      <div style="display:flex;justify-content:space-between"><span>IVA (16%)</span><b id="cIva">$0.00</b></div>
      <div style="display:flex;justify-content:space-between;font-size:18px;padding-top:12px;border-top:2px solid var(--brand)"><span style="font-weight:800">Total</span><b id="cTotal" style="color:var(--brand)">$0.00</b></div>
    </div>

    <div style="display:grid;gap:10px;margin-bottom:20px">
      <label style="display:flex;align-items:center;padding:12px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition)">
        <input type="radio" name="metodo" value="efectivo" checked style="margin-right:10px;width:20px;height:20px"> 
        <span style="font-weight:600">💵 Efectivo</span>
      </label>
      <label style="display:flex;align-items:center;padding:12px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition)">
        <input type="radio" name="metodo" value="tarjeta" style="margin-right:10px;width:20px;height:20px"> 
        <span style="font-weight:600">💳 Tarjeta</span>
      </label>
    </div>

    <button id="btnPagar" class="btn-primary" style="width:100%;padding:16px;font-size:16px;font-weight:800">Procesar pago</button>
  </div>
</dialog>

<!-- POPUP DE PAGO EN EFECTIVO -->
<dialog id="dlgEfectivo" class="dlg" style="max-width:400px">
  <div class="dlg-body">
    <h3 style="margin:0 0 20px">💵 Pago en efectivo</h3>
    
    <div style="background:var(--bg-3);padding:20px;border-radius:var(--radius-sm);margin-bottom:20px;text-align:center">
      <small style="color:var(--text-muted);text-transform:uppercase;font-size:11px;font-weight:700">Total a pagar</small>
      <div id="lblTotalEfectivo" style="font-size:32px;font-weight:900;color:var(--brand);margin-top:8px">$0.00</div>
    </div>

    <label style="display:block;margin-bottom:8px;font-size:13px;font-weight:700;color:var(--text-muted);text-transform:uppercase">¿Con cuánto paga el cliente?</label>
    <input id="inpMontoPagado" type="number" step="0.01" min="0" class="input" placeholder="0.00" style="width:100%;text-align:right;font-size:20px;font-weight:700">

    <div id="cambioBox" style="background:var(--ok);color:white;padding:16px;border-radius:var(--radius-sm);margin-top:20px;text-align:center;display:none">
      <small style="font-size:11px;font-weight:700;text-transform:uppercase;opacity:0.9">Cambio</small>
      <div id="lblCambio" style="font-size:28px;font-weight:900;margin-top:4px">$0.00</div>
    </div>

    <div style="display:flex;gap:12px;margin-top:24px">
      <button id="btnCancelarEfectivo" class="btn" style="flex:1">Cancelar</button>
      <button id="btnConfirmarEfectivo" class="btn-primary" style="flex:1" disabled>Confirmar</button>
    </div>
  </div>
</dialog>

<?php end_pos_page(); ?>