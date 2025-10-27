<?php
declare(strict_types=1);

require_once __DIR__ . '/utils.php';

/**
 * Datos base para el layout (cajero/caja)
 * Lee caja desde ?caja=..., si no, cookie pos_caja, si no, 'Caja 1'
 */
$db     = db();
$cajaId = $_GET['caja'] ?? ($_COOKIE['pos_caja'] ?? 'Caja 1');
$turno  = turno_actual($db, $cajaId); // null si no hay turno abierto

// Variables globales que pueden usar las páginas o el layout
$cajeroNombre = (string)($turno['cajero_nombre'] ?? '—');
$cajaLabel    = (string)($turno['caja_id']       ?? $cajaId);

/**
 * Marca de item activo en el sidebar
 */
function is_active(string $file): string {
  return basename($_SERVER['SCRIPT_NAME']) === $file ? 'active' : '';
}

/**
 * Inicio del layout (GLOBAL)
 * Si NO pasas $cajeroN o $cajaL, toma los globales calculados arriba.
 */
function start_pos_page(string $title = '', ?string $cajeroN = null, ?string $cajaL = null): void {
  // fallback global
  global $cajeroNombre, $cajaLabel;
  $cajeroN = $cajeroN ?? $cajeroNombre;
  $cajaL   = $cajaL   ?? $cajaLabel;
  ?>
  <!doctype html>
  <html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?: 'LumiSpace POS', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/pos.css">
  </head>
  <body class="pos-layout">
    <aside class="sidebar">
      <div class="brand">
        <div class="logo">LS</div>
        <div class="brand-text">
          <strong>LumiSpace</strong>
          <small>Iluminación premium</small>
        </div>
      </div>

      <nav class="menu">
        <a class="<?= is_active('pos.php')          ?>" href="pos.php">Punto de Venta</a>
        <a class="<?= is_active('ventas.php')       ?>" href="ventas.php">Facturación</a>
        <a class="<?= is_active('estadisticas.php') ?>" href="estadisticas.php">Estadísticas</a>
        <a class="<?= is_active('corte.php')        ?>" href="corte.php">Corte de Caja</a>
      </nav>

      <div class="sidebar-footer">
        <div class="user-box">
          <div class="lbl">Cajero</div>
          <div class="val"><?= htmlspecialchars($cajeroN, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="lbl">Caja</div>
          <div class="val"><?= htmlspecialchars($cajaL, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <!-- Botón Cerrar turno -->
        <button id="btnCerrarTurno" class="btn-exit danger" style="margin-bottom:8px">
          Cerrar turno
        </button>

        <a class="btn-exit" href="../index.php">Salir al inicio</a>
      </div>
    </aside>

    <main class="content">
      <?php if ($title): ?>
        <header class="page-header"><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1></header>
      <?php endif; ?>
  <?php
}

/**
 * Fin del layout
 * Incluye diálogos globales de abrir/cerrar turno + script principal
 */
function end_pos_page(): void {
  ?>
    <!-- DIALOGOS GLOBALES -->

 <!-- Popup apertura de turno -->
<!-- Popup apertura de turno -->
<dialog id="dlgTurno" class="dlg">
  <form method="dialog" id="frmTurno" class="dlg-body" onsubmit="return false;">
    <h3>Apertura de turno</h3>

    <label>Caja</label>
    <select id="selCaja" class="input">
      <option value="Caja 1">Caja 1</option>
      <option value="Caja 2">Caja 2</option>
      <option value="Caja 3">Caja 3</option>
    </select>

    <label>Cajero</label>
    <select id="selCajero" class="input">
      <option value="">(cargando...)</option>
    </select>

    <label>Saldo inicial</label>
    <input id="inpSaldoInicial" type="number" step="0.01" min="0" class="input" placeholder="0.00" />

    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
      <button type="button" id="btnAbrirTurno" class="btn btn-primary">Confirmar apertura</button>
    </div>
  </form>
</dialog>

    <!-- Cerrar turno -->
<!-- Cerrar turno -->
<dialog id="dlgCerrarTurno" class="dlg">
  <form method="dialog" id="frmCerrarTurno" class="dlg-body" onsubmit="return false;">
    <h3>Cerrar turno</h3>

    <div style="background:var(--bg-2);padding:16px;border-radius:12px;margin-bottom:20px">
      <div style="display:grid;gap:12px">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;font-weight:600;letter-spacing:0.5px">Caja</small>
          <b id="lblCajaClose" style="color:var(--ink);font-size:15px">—</b>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <small style="color:var(--ink-muted);text-transform:uppercase;font-size:11px;font-weight:600;letter-spacing:0.5px">Saldo sugerido</small>
          <b id="lblSaldoActual" style="color:var(--brand);font-size:18px;font-weight:800">$0.00</b>
        </div>
      </div>
    </div>

    <label>Contado en caja (saldo final)</label>
    <input id="inpSaldoFinal" type="number" step="0.01" min="0" class="input" placeholder="0.00" />

    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
      <button type="button" id="btnConfirmClose" class="btn btn-danger">Cerrar turno</button>
    </div>
  </form>
</dialog>

    </main>
    <script src="assets/pos.js"></script>
  </body>
  </html>
  <?php
}
