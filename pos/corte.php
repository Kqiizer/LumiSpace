<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Corte de caja', $cajeroNombre, $cajaLabel);
?>

<section class="card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
  <div style="flex:1;min-width:200px">
    <label style="display:block;font-size:12px;color:var(--ink-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Caja</label>
    <select id="selCajaCorte" class="input"></select>
  </div>
  <div style="padding-top:20px">
    <button id="btnVerCorte" class="btn">Ver corte</button>
  </div>
  <div style="margin-left:auto;padding-top:20px">
    <small class="muted">Ventana: <b id="lblWindow" style="color:var(--brand)">—</b></small>
  </div>
</section>

<div style="display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-top:20px">
  <div class="card">
    <small>Saldo inicial</small>
    <div id="kSaldoIni" style="font-size:24px;font-weight:800;color:var(--brand)">$0.00</div>
  </div>
  <div class="card">
    <small>Efectivo</small>
    <div id="kEf" style="font-size:24px;font-weight:800;color:var(--ok)">$0.00</div>
  </div>
  <div class="card">
    <small>Tarjeta</small>
    <div id="kTj" style="font-size:24px;font-weight:800;color:var(--accent)">$0.00</div>
  </div>
  <div class="card">
    <small>Saldo actual</small>
    <div id="kSaldoAct" style="font-size:24px;font-weight:800;color:var(--brand)">$0.00</div>
  </div>
</div>

<p id="msgCorte" class="muted" style="margin-top:12px;text-align:center"></p>

<section class="card" style="margin-top:12px">
  <h3 style="margin-top:0">Movimientos del turno</h3>
  <table class="table">
    <thead>
      <tr>
        <th style="width:180px">Fecha</th>
        <th style="width:120px">Método</th>
        <th style="width:120px">Tipo</th>
        <th style="text-align:right;width:140px">Monto</th>
      </tr>
    </thead>
    <tbody id="tbMovs">
      <tr><td colspan="4" class="muted">(sin datos)</td></tr>
    </tbody>
  </table>
</section>

<?php end_pos_page(); ?>
