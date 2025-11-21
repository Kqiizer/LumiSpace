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
      <!-- Brand -->
      <a href="pos.php" class="brand">
        <img
          src="../images/LOGO LUMISPACE.png"
          alt="LumiSpace"
          class="brand-logo"
          width="48"
          height="48"
          loading="eager"
          decoding="async"
          onerror="this.style.display='none'"
        />

      </a>

      <nav class="menu">
        <a class="<?= is_active('pos.php')          ?>" href="pos.php">
          <span class="menu-icon">🛒</span>
          <span>Punto de Venta</span>
        </a>
        <a class="<?= is_active('ventas.php')       ?>" href="ventas.php">
          <span class="menu-icon">📋</span>
          <span>Facturación</span>
        </a>
        <a class="<?= is_active('estadisticas.php') ?>" href="estadisticas.php">
          <span class="menu-icon">📊</span>
          <span>Estadísticas</span>
        </a>
        <a class="<?= is_active('corte.php')        ?>" href="corte.php">
          <span class="menu-icon">💰</span>
          <span>Corte de Caja</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <div class="user-box">
  <div class="user-row">
    <span class="lbl">Cajero</span>
    <span class="val"><?= htmlspecialchars($cajeroN, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <div class="user-row">
    <span class="lbl">Caja</span>
    <span class="val"><?= htmlspecialchars($cajaL, ENT_QUOTES, 'UTF-8') ?></span>
  </div>
  <div class="user-row" style="border-top:1px solid rgba(255,255,255,0.15);padding-top:12px;margin-top:12px">
    <span class="lbl">Monto actual</span>
    <span class="val" id="montoActualSidebar" style="color:#10b981;font-size:16px">$0.00</span>
  </div>
</div>

        <!-- Botón Cerrar turno -->
        <button id="btnCerrarTurno" class="btn-exit danger">
          Cerrar turno
        </button>

        <a class="btn-exit" href="../index.php">Salir al inicio</a>
      </div>
    </aside>

    <main class="content">
      <?php if ($title): ?>
        <header class="page-header">
          <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
      <?php endif; ?>
  <?php
}

/**
 * Fin del layout
 * Incluye diálogos globales de abrir/cerrar turno + script principal
 */
function end_pos_page(): void {
  ?>
    </main>
    
    <!-- DIALOGOS GLOBALES -->

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
    <dialog id="dlgCerrarTurno" class="dlg">
      <form method="dialog" id="frmCerrarTurno" class="dlg-body" onsubmit="return false;">
        <h3>Cerrar turno</h3>

        <div class="info-box">
          <div class="info-row">
            <small class="info-label">Caja</small>
            <b id="lblCajaClose" class="info-value">—</b>
          </div>
          <div class="info-row">
            <small class="info-label">Saldo sugerido</small>
            <b id="lblSaldoActual" class="info-value-highlight">$0.00</b>
          </div>
        </div>

        <label>Contado en caja (saldo final)</label>
        <input id="inpSaldoFinal" type="number" step="0.01" min="0" class="input" placeholder="0.00" />

        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:24px">
          <button type="button" id="btnConfirmClose" class="btn btn-danger">Cerrar turno</button>
        </div>
      </form>
    </dialog>

    <script src="assets/pos.js"></script>
  </body>
  </html>
  <?php
}