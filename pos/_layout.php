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
      <a href="pos.php" class="brand">
        LUMISPACE
      </a>

      <nav class="menu">
        <p class="menu-heading">Operación</p>
        <a class="menu-link <?= is_active('pos.php') ?>" href="pos.php">
          <span class="menu-icon">
            <img src="posimg/carrito-punto de venta.png" alt="Punto de Venta" class="menu-icon-img">
          </span>
          <div class="menu-label">
            <span>Punto de Venta</span>
            <small>Ticket y catálogo</small>
          </div>
          <span class="menu-arrow">➜</span>
        </a>
        <a class="menu-link <?= is_active('ventas.php') ?>" href="ventas.php">
          <span class="menu-icon">
            <img src="posimg/facturacion.png" alt="Facturación" class="menu-icon-img">
          </span>
          <div class="menu-label">
            <span>Facturación</span>
            <small>Historial y CFDI</small>
          </div>
          <span class="menu-arrow">➜</span>
        </a>
        <a class="menu-link <?= is_active('estadisticas.php') ?>" href="estadisticas.php">
          <span class="menu-icon">
            <img src="posimg/facturacion.png" alt="Estadísticas" class="menu-icon-img">
          </span>
          <div class="menu-label">
            <span>Estadísticas</span>
            <small>Flujos y KPIs</small>
          </div>
          <span class="menu-arrow">➜</span>
        </a>
        <a class="menu-link <?= is_active('corte.php') ?>" href="corte.php">
          <span class="menu-icon">
            <img src="posimg/caja-registradora.png" alt="Corte de Caja" class="menu-icon-img">
          </span>
          <div class="menu-label">
            <span>Corte de Caja</span>
            <small>Saldo y turnos</small>
          </div>
          <span class="menu-arrow">➜</span>
        </a>
      </nav>

      <div class="sidebar-panel">
        <div class="sidebar-meta">
          <div>
            <span class="lbl">Cajero</span>
            <strong><?= htmlspecialchars($cajeroN, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span class="lbl">Caja</span>
            <strong><?= htmlspecialchars($cajaL, ENT_QUOTES, 'UTF-8') ?></strong>
          </div>
          <div>
            <span class="lbl">Monto actual</span>
            <strong id="montoActualSidebar">$0.00</strong>
          </div>
        </div>

        <div class="sidebar-actions">
          <button id="btnCerrarTurno" class="btn-exit danger">
            Cerrar turno
          </button>
          <a class="btn-exit ghost" href="../index.php">Salir al inicio</a>
        </div>
      </div>
    </aside>

    <main class="content">
      <div class="language-selector" id="languageSelector">
        <button type="button" class="lang-btn" id="btnLangToggle" aria-label="Cambiar idioma">
          <img src="posimg/traductor.png" alt="Idioma" class="lang-icon-img" id="langIconImg">
          <span class="lang-flag-btn" id="langFlagBtn">🇪🇸</span>
          <span class="lang-text" id="langText">Español</span>
          <svg class="lang-arrow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7 10l5 5 5-5z" fill="currentColor"/>
          </svg>
        </button>
        <div class="lang-dropdown" id="langDropdown">
          <button type="button" class="lang-option active" data-lang="es" data-code="ES" data-flag="🇪🇸" data-name="Español">
            <span class="lang-flag">🇪🇸</span>
            <span class="lang-name">Español</span>
            <svg class="lang-check" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>
            </svg>
          </button>
          <button type="button" class="lang-option" data-lang="en" data-code="EN" data-flag="🇺🇸" data-name="English">
            <span class="lang-flag">🇺🇸</span>
            <span class="lang-name">English</span>
            <svg class="lang-check" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>
            </svg>
          </button>
        </div>
      </div>
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
    <dialog id="dlgTurno" class="dlg dlg-open-shift">
      <form method="dialog" id="frmTurno" class="dlg-body" onsubmit="return false;">
        <div class="dlg-header-open">
          <div class="dlg-icon-wrapper">
            <svg class="dlg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z" fill="currentColor"/>
            </svg>
          </div>
          <h3>Apertura de turno</h3>
          <p class="dlg-subtitle">Configura los parámetros iniciales para comenzar el turno</p>
        </div>

        <div class="open-shift-form">
          <div class="form-group-modern">
            <label class="label-modern">
              <span class="label-text">Caja</span>
              <span class="label-hint">Selecciona la caja a utilizar</span>
            </label>
            <div class="select-wrapper-modern">
              <svg class="select-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" fill="currentColor"/>
              </svg>
              <select id="selCaja" class="select-modern">
                <option value="Caja 1">Caja 1</option>
                <option value="Caja 2">Caja 2</option>
                <option value="Caja 3">Caja 3</option>
              </select>
            </div>
          </div>

          <div class="form-group-modern">
            <label class="label-modern">
              <span class="label-text">Cajero</span>
              <span class="label-hint">Selecciona el cajero responsable</span>
            </label>
            <div class="select-wrapper-modern">
              <svg class="select-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
              </svg>
              <select id="selCajero" class="select-modern">
                <option value="">(cargando...)</option>
              </select>
            </div>
          </div>

          <div class="form-group-modern">
            <label class="label-modern">
              <span class="label-text">Saldo inicial</span>
              <span class="label-hint">Monto con el que inicia el turno</span>
            </label>
            <div class="input-wrapper-modern">
              <span class="input-prefix">$</span>
              <input id="inpSaldoInicial" type="number" step="0.01" min="0" class="input-modern" placeholder="0.00" />
            </div>
          </div>
        </div>

        <div class="dlg-actions-modern">
          <button type="button" class="btn-cancel" onclick="document.getElementById('dlgTurno').close()">Cancelar</button>
          <button type="button" id="btnAbrirTurno" class="btn btn-primary btn-confirm-open">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>
            </svg>
            Confirmar apertura
          </button>
        </div>
      </form>
    </dialog>

    <!-- Cerrar turno -->
    <dialog id="dlgCerrarTurno" class="dlg dlg-close-shift">
      <form method="dialog" id="frmCerrarTurno" class="dlg-body" onsubmit="return false;">
        <div class="dlg-header-close">
          <div class="dlg-icon-wrapper">
            <svg class="dlg-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" fill="currentColor"/>
            </svg>
          </div>
          <h3>Cerrar turno</h3>
          <p class="dlg-subtitle">Confirma el saldo final antes de cerrar el turno</p>
        </div>

        <div class="close-shift-info-grid">
          <div class="info-card-modern">
            <div class="info-card-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z" fill="currentColor"/>
              </svg>
            </div>
            <div class="info-card-content">
              <small class="info-label-modern">Caja</small>
              <b id="lblCajaClose" class="info-value-modern">—</b>
            </div>
          </div>

          <div class="info-card-modern highlight-card">
            <div class="info-card-icon highlight-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" fill="currentColor"/>
              </svg>
            </div>
            <div class="info-card-content">
              <small class="info-label-modern">Saldo sugerido</small>
              <b id="lblSaldoActual" class="info-value-highlight-modern">$0.00</b>
            </div>
          </div>
        </div>

        <div class="input-group-modern">
          <label class="label-modern">
            <span class="label-text">Contado en caja (saldo final)</span>
            <span class="label-hint">Ingresa el monto físico contado</span>
          </label>
          <div class="input-wrapper-modern">
            <span class="input-prefix">$</span>
            <input id="inpSaldoFinal" type="number" step="0.01" min="0" class="input-modern" placeholder="0.00" />
          </div>
        </div>

        <div class="dlg-actions-modern">
          <button type="button" class="btn-cancel" onclick="document.getElementById('dlgCerrarTurno').close()">Cancelar</button>
          <button type="button" id="btnConfirmClose" class="btn btn-danger btn-confirm-close">
            <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" fill="currentColor"/>
            </svg>
            Cerrar turno
          </button>
        </div>
      </form>
    </dialog>

    <script src="assets/pos.js"></script>
  </body>
  </html>
  <?php
}