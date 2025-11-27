<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();

/* ============================================================
   🔗 BASE Y DEPENDENCIAS
   ============================================================ */
require_once __DIR__ . "/../config/functions.php";

/* ============================================================
   🌐 BASE URL
   ============================================================ */
$BASE = defined('BASE_URL') ? BASE_URL : '/';
$USER_ID = (int) ($_SESSION['usuario_id'] ?? 0);

/* ============================================================
   🛒 SESIÓN DEL CARRITO
   ============================================================ */
$_SESSION['carrito'] = $_SESSION['carrito'] ?? [];

/* ============================================================
   ⚙️ ENDPOINT AJAX (añadir, actualizar, eliminar)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');

  $action = $_POST['action'] ?? '';
  $id = (int) ($_POST['id'] ?? 0);
  $qty = max(1, (int) ($_POST['qty'] ?? 1));

  switch ($action) {
    case 'add':
      carritoAgregar($id, $qty);
      break;

    case 'update':
      if (isset($_SESSION['carrito'][$id])) {
        $_SESSION['carrito'][$id]['cantidad'] = $qty;
      }
      break;

    case 'remove':
      carritoEliminar($id);
      break;

    case 'clear':
      carritoVaciar();
      break;
  }

  $totals = [
    'subtotal' => carritoTotal(),
    'total' => carritoTotal()
  ];

  echo json_encode([
    'ok' => true,
    'cart' => carritoObtener(),
    'totals' => $totals
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   💰 DATOS DEL CARRITO
   ============================================================ */
$carrito = carritoObtener();
// Debug: verificar que el carrito tenga datos
if (empty($carrito) && !empty($_SESSION['carrito'])) {
    // Si carritoObtener() devuelve vacío pero hay datos en sesión, forzar recarga
    error_log("Carrito vacío pero sesión tiene datos: " . print_r($_SESSION['carrito'], true));
}
$subtotal = carritoTotal();
$total = $subtotal;

$checkoutUrl = $USER_ID
  ? "{$BASE}includes/checkout.php"
  : "{$BASE}views/login.php?next=" . urlencode($BASE . 'includes/checkout.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrito de Compras | LumiSpace</title>
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/carrito.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap"
    rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body data-base="<?= htmlspecialchars($BASE) ?>">

  <!-- Hero Section -->
  <section class="cart-hero">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
      <span class="hero-tag"><i class="fas fa-shopping-cart"></i> Tu Selección</span>
      <h1 class="hero-title">Carrito de Compras</h1>
      <p class="hero-excerpt">
        Revisa tu selección antes de proceder al pago
      </p>
    </div>
  </section>

  <main class="container main-layout">
    <?php if (empty($carrito)): ?>
      <!-- Empty State -->
      <div class="empty-state reveal-on-scroll">
        <div class="empty-icon-wrapper">
          <i class="fas fa-shopping-bag empty-icon"></i>
          <div class="empty-pulse"></div>
        </div>
        <h2 class="empty-title">Tu carrito está vacío</h2>
        <p class="empty-text">
          Parece que aún no has agregado productos a tu carrito.
          Explora nuestra colección y encuentra las piezas perfectas para iluminar tu espacio.
        </p>
        <a href="<?= $BASE ?>index.php" class="btn-hero">
          Explorar Catálogo <i class="fas fa-arrow-right"></i>
        </a>
      </div>
    <?php else: ?>
      <!-- Cart Content -->
      <div class="cart-content">
        <div class="cart-header">
          <h2>Productos en tu carrito <span class="item-count"><?= count($carrito) ?></span></h2>
          <div class="cart-actions">
            <a href="<?= $BASE ?>index.php" class="btn-secondary">
              <i class="fas fa-arrow-left"></i> Seguir Comprando
            </a>
            <button id="clearCart" class="btn-danger">
              <i class="fas fa-trash-alt"></i> Vaciar Carrito
            </button>
          </div>
        </div>

        <div class="products-grid" id="cartList">
          <?php foreach ($carrito as $item): ?>
            <?php
            $id = (int) ($item['producto_id'] ?? $item['id']);
            $qty = (int) ($item['cantidad'] ?? 1);
            $precio = (float) ($item['precio'] ?? 0);
            $nombre = htmlspecialchars($item['nombre'] ?? 'Producto sin nombre');
            // carritoObtener() ya procesa la imagen con publicImageUrl(), así que la usamos directamente
            $imagen = htmlspecialchars($item['imagen'] ?? $BASE . 'images/default.png');
            $totalItem = $precio * $qty;
            ?>
            <article class="cart-card reveal-on-scroll" data-id="<?= $id ?>">
              <div class="card-image-wrapper">
                <div class="cart-image" style="background-image: url('<?= $imagen ?>');"></div>
                <button class="remove-btn" data-id="<?= $id ?>" title="Eliminar del carrito">
                  <i class="fas fa-times"></i>
                </button>
              </div>

              <div class="cart-content-area">
                <div class="cart-meta">
                  <?php if (!empty($item['categoria'])): ?>
                    <span class="category"><?= htmlspecialchars($item['categoria']) ?></span>
                  <?php endif; ?>
                </div>

                <h3 class="cart-title"><?= $nombre ?></h3>

                <div class="cart-footer">
                  <div class="quantity-control">
                    <button class="qty-btn" data-id="<?= $id ?>" data-diff="-1" <?= $qty <= 1 ? 'disabled' : '' ?>>
                      <i class="fas fa-minus"></i>
                    </button>
                    <span class="qty-value"><?= $qty ?></span>
                    <button class="qty-btn" data-id="<?= $id ?>" data-diff="1">
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>

                  <div class="price-wrapper">
                    <span class="unit-price">$<?= number_format($precio, 2) ?> c/u</span>
                    <span class="total-price">$<?= number_format($totalItem, 2) ?></span>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Order Summary Sidebar -->
      <aside class="order-summary">
        <h2>Resumen del Pedido</h2>

        <div class="summary-details">
          <div class="summary-divider"></div>

          <div class="summary-row total">
            <span>Total</span>
            <span class="summary-value" id="totalValue">$<?= number_format($total, 2) ?></span>
          </div>
        </div>

        <a href="<?= $checkoutUrl ?>" class="checkout-btn">
          <i class="fas fa-lock"></i>
          Proceder al Pago
        </a>

        <div class="trust-badges">
          <div class="trust-badge">
            <i class="fas fa-shield-alt"></i>
            <span>Pago Seguro</span>
          </div>
          <div class="trust-badge">
            <i class="fas fa-truck"></i>
            <span>Envío Rápido</span>
          </div>
        </div>
      </aside>
    <?php endif; ?>
  </main>

  <script>
    // Definir BASE_URL para product-actions.js (igual que en catálogo)
    const bodyBase = document.body.getAttribute('data-base');
    window.BASE_URL = bodyBase || '/';
  </script>
  <script src="<?= $BASE ?>js/product-actions.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const base = document.body.dataset.base;

      // Scroll animations
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
          }
        });
      }, { threshold: 0.1 });

      document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));

      async function post(action, id = 0, qty = 1) {
        const formData = new FormData();
        formData.append('action', action);
        if (id) formData.append('id', id);
        if (qty) formData.append('qty', qty);

        const response = await fetch(base + 'includes/carrito.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.ok) {
          location.reload();
        }
      }

      // Quantity controls
      document.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', e => {
          const id = e.currentTarget.dataset.id;
          const diff = parseInt(e.currentTarget.dataset.diff);
          const qtyEl = e.currentTarget.closest('.cart-card').querySelector('.qty-value');
          const newQty = Math.max(1, parseInt(qtyEl.textContent) + diff);
          post('update', id, newQty);
        });
      });

      // Remove buttons
      document.querySelectorAll('.remove-btn').forEach(btn => {
        btn.addEventListener('click', e => {
          const id = e.currentTarget.dataset.id;
          if (confirm('¿Eliminar este producto del carrito?')) {
            post('remove', id);
          }
        });
      });

      // Clear cart button
      const clearBtn = document.getElementById('clearCart');
      if (clearBtn) {
        clearBtn.addEventListener('click', () => {
          if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
            post('clear');
          }
        });
      }
    });
  </script>
</body>

</html>