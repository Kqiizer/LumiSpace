<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

start_pos_page('Punto de Venta');   
?>

<div class="grid pos-grid">
  <!-- Productos -->
  <section class="card pos-products">
    <header style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
      <input type="search" placeholder="Buscar productos..." style="flex:1;padding:10px;border:1px solid #e5e5e5;border-radius:10px;">
      
    </header>
    <div id="gridProductos" class="grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
      <div class="card" style="height:120px;display:grid;place-items:center;color:#777">Productos…</div>
    </div>
  </section>

  <!-- Carrito -->
  <aside class="card pos-cart">
    <h3 style="margin-top:0;">Carrito</h3>
    <div id="cartList" style="min-height:120px;color:#777">(sin items)</div>
    <hr>
    <div style="display:grid;gap:6px">
      <div style="display:flex;justify-content:space-between"><span>Subtotal</span><b id="cSubtotal">$0.00</b></div>
      <div style="display:flex;justify-content:space-between"><span>IVA (16%)</span><b id="cIva">$0.00</b></div>
      <div style="display:flex;justify-content:space-between;font-size:16px"><span>Total</span><b id="cTotal">$0.00</b></div>
    </div>
    <div style="margin-top:12px;display:grid;gap:8px">
  <label style="display:flex;align-items:center;padding:10px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition)">
    <input type="radio" name="metodo" value="efectivo" checked style="margin-right:8px"> 
    <span style="font-weight:500">💵 Efectivo</span>
  </label>
  <label style="display:flex;align-items:center;padding:10px;border:2px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:var(--transition)">
    <input type="radio" name="metodo" value="tarjeta" style="margin-right:8px"> 
    <span style="font-weight:500">💳 Tarjeta</span>
  </label>
  <button id="btnPagar" class="btn-primary" style="padding:14px;font-size:15px;margin-top:8px">Procesar pago</button>
</div>
  </aside>
</div>

<!-- Popup apertura de turno (bloquea hasta confirmar) -->
<dialog id="dlgTurno" class="dlg">
  <form method="dialog" id="frmTurno" class="dlg-body" onsubmit="return false;">
    <h3 style="margin:0 0 12px">Apertura de turno</h3>

    <label style="display:block;margin:8px 0 4px">Caja</label>
    <select id="selCaja" class="input" style="width:100%">
      <option value="Caja 1">Caja 1</option>
      <option value="Caja 2">Caja 2</option>
      <option value="Caja 3">Caja 3</option>
    </select>

    <label style="display:block;margin:12px 0 4px">Cajero</label>
    <select id="selCajero" class="input" style="width:100%"></select>

    <label style="display:block;margin:12px 0 4px">Saldo inicial</label>
    <input id="inpSaldoInicial" type="number" step="0.01" min="0" class="input" style="width:100%" />

    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
      <button type="button" id="btnAbrirTurno" class="card">Confirmar</button>
    </div>
  </form>
</dialog>

<?php end_pos_page(); ?>
