<?php
declare(strict_types=1);
require_once __DIR__ . '/_layout.php';

start_pos_page('Punto de Venta');   
?>

<section class="pos-shell">


  <section class="scene-panel card">
    <div class="scene-panel__header">
      <div>
        <p class="eyebrow">Escenas de iluminación</p>
        <h3>Ajustes rápidos para el showroom</h3>
      </div>
      <div class="scene-tags">
        <span class="scene-tag">Decorativo</span>
        <span class="scene-tag">Retail premium</span>
      </div>
    </div>
    <div class="scene-panel__actions">
      <button type="button" class="scene-button active">
        <span>Lounge cálido</span>
        <small>3000K · dim a 40%</small>
      </button>
      <button type="button" class="scene-button">
        <span>Showroom blanco</span>
        <small>4000K · CRI 95</small>
      </button>
      <button type="button" class="scene-button">
        <span>Fachada LED</span>
        <small>6000K · IP65</small>
      </button>
    </div>
  </section>

  <div class="pos-stage">
    <section class="catalog-board card">
      <header class="catalog-toolbar">
        <div class="search-shell">
          <span class="search-icon">🔍</span>
          <input id="inpBuscar" type="search" placeholder="Buscar luminarias, colecciones o SKU">
        </div>
        <div class="toolbar-actions">
          <button type="button" class="ghost-btn">Escanear SKU</button>
        </div>
      </header>

      <div class="catalog-filters">
        <button type="button" class="filter-chip active">Colgantes</button>
        <button type="button" class="filter-chip">Wall washers</button>
        <button type="button" class="filter-chip">Tiras LED</button>
        <button type="button" class="filter-chip">Exterior</button>
        <button type="button" class="filter-chip">Accesorios</button>
      </div>

      <div id="gridProductos" class="products-grid">
        <div class="card product-placeholder">
          <span>Productos…</span>
        </div>
      </div>
    </section>
  </div>

  <!-- Botón flotante del carrito -->
  <button type="button" id="btnCarrito" class="cart-float-btn" aria-label="Ver ticket activo">
    <span class="cart-float-icon">🛒</span>
    <span class="cart-float-badge" id="badgeCart">0</span>
    <span class="cart-float-label">Ticket</span>
  </button>

  <!-- Modal de checkout -->
  <div class="checkout-overlay" id="ticketOverlay"></div>
  <div class="checkout-modal" id="ticketPanel">
    <button type="button" class="checkout-close" id="btnCerrarTicket" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="checkout-header-modern">
      <div class="checkout-header-content">
        <div class="checkout-header-icon">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
        <div>
          <p class="checkout-eyebrow">Ticket activo</p>
          <h3 class="checkout-title">Venta en sala</h3>
        </div>
      </div>
      <div class="checkout-status">
        <span class="status-indicator"></span>
        <span class="status-text">Turno en curso</span>
      </div>
    </div>

    <div class="checkout-meta-modern">
      <div class="meta-item">
        <svg class="meta-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
        </svg>
        <div class="meta-content">
          <small class="meta-label">Cliente</small>
          <strong class="meta-value">Mostrador</strong>
        </div>
      </div>
      <div class="meta-item">
        <svg class="meta-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2"/>
          <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="meta-content">
          <small class="meta-label">Asesor</small>
          <strong class="meta-value">Equipo Lumi</strong>
        </div>
      </div>
    </div>

    <div class="checkout-products-section">
      <div class="section-header">
        <h4 class="section-title">Productos</h4>
        <span class="section-count" id="productCount">0 items</span>
      </div>
      <div id="cartList" class="checkout-list-modern">
        <div class="checkout-empty">
          <svg class="empty-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3 6h18M16 10a4 4 0 11-8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <p class="empty-text">Carrito vacío</p>
          <small class="empty-hint">Agrega productos para continuar</small>
        </div>
      </div>
    </div>

    <div class="checkout-totals-modern">
      <div class="totals-header">
        <h4 class="totals-title">Resumen</h4>
      </div>
      <div class="totals-content">
        <div class="total-row">
          <span class="total-label">Subtotal</span>
          <strong class="total-value" id="cSubtotal">$0.00</strong>
        </div>
        <div class="total-row">
          <span class="total-label">IVA (16%)</span>
          <strong class="total-value" id="cIva">$0.00</strong>
        </div>
        <div class="total-row total-row-final">
          <span class="total-label">Total</span>
          <strong class="total-value-final" id="cTotal">$0.00</strong>
        </div>
      </div>
    </div>

    <div class="checkout-payments-modern">
      <div class="payments-header">
        <h4 class="payments-title">Método de pago</h4>
      </div>
      <div class="payments-grid">
        <label class="payment-card">
          <input type="radio" name="metodo" value="efectivo" checked>
          <div class="payment-card-content">
            <div class="payment-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
              </svg>
            </div>
            <div class="payment-info">
              <span class="payment-name">Efectivo</span>
              <small class="payment-desc">Pago inmediato</small>
            </div>
            <div class="payment-check">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
        </label>
        <label class="payment-card">
          <input type="radio" name="metodo" value="tarjeta">
          <div class="payment-card-content">
            <div class="payment-icon">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
                <path d="M5 16h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="payment-info">
              <span class="payment-name">Tarjeta</span>
              <small class="payment-desc">Terminal bancaria</small>
            </div>
            <div class="payment-check">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
        </label>
      </div>
    </div>

    <button type="button" id="btnPagar" class="checkout-btn-pay">
      <svg class="btn-pay-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span>Procesar pago</span>
    </button>
  </div>
    
    <button type="button" id="btnCerrarTicket" class="btn-close-checkout" aria-label="Cerrar modal">
      <span>✕</span>
    </button>
</section>

<!-- POPUP DE PAGO EN EFECTIVO -->
<dialog id="dlgEfectivo" class="dlg dlg-payment-cash">
  <form method="dialog" class="dlg-body" onsubmit="return false;">
    <button type="button" class="payment-close" onclick="document.getElementById('dlgEfectivo').close()" aria-label="Cerrar">
      <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </button>

    <div class="payment-header-cash">
      <div class="payment-icon-wrapper">
        <svg class="payment-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
          <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2"/>
        </svg>
      </div>
      <h3 class="payment-title">Pago en efectivo</h3>
      <p class="payment-subtitle">Ingresa el monto recibido del cliente</p>
    </div>

    <div class="payment-total-box">
      <div class="total-box-icon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="total-box-content">
        <small class="total-box-label">Total a pagar</small>
        <div id="lblTotalEfectivo" class="total-box-value">$0.00</div>
      </div>
    </div>

    <div class="payment-input-group">
      <label class="payment-input-label">
        <span class="label-text">Monto recibido</span>
        <span class="label-hint">Ingresa la cantidad que pagó el cliente</span>
      </label>
      <div class="payment-input-wrapper">
        <span class="input-prefix-large">$</span>
        <input id="inpMontoPagado" type="number" step="0.01" min="0" class="payment-input-large" placeholder="0.00" />
      </div>
    </div>

    <div id="cambioBox" class="payment-change-box" style="display:none">
      <div class="change-box-icon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="change-box-content">
        <small class="change-box-label">Cambio a entregar</small>
        <div id="lblCambio" class="change-box-value">$0.00</div>
      </div>
    </div>

    <div class="payment-actions">
      <button type="button" id="btnCancelarEfectivo" class="payment-btn-cancel">Cancelar</button>
      <button type="button" id="btnConfirmarEfectivo" class="payment-btn-confirm" disabled>
        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Confirmar pago</span>
      </button>
    </div>
  </form>
</dialog>
<?php end_pos_page(); ?>