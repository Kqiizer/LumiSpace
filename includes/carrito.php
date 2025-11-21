<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/* ============================================================
   🔗 BASE Y DEPENDENCIAS
   ============================================================ */
require_once __DIR__ . "/../config/functions.php"; // Incluye todo el core de LumiSpace

/* ============================================================
   🌐 BASE URL
   ============================================================ */
if (defined('BASE_URL')) {
  $BASE = rtrim(BASE_URL, '/').'/';
} else {
  $root = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $BASE = ($root === '' ? '/' : $root.'/');
}

$USER_ID = (int)($_SESSION['usuario_id'] ?? 0);

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
  $id     = (int)($_POST['id'] ?? 0);
  $qty    = max(1, (int)($_POST['qty'] ?? 1));

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
    'iva'      => carritoTotal() * 0.16,
    'envio'    => carritoTotal() > 1000 ? 0 : 150,
    'total'    => carritoTotal() * 1.16 + (carritoTotal() > 1000 ? 0 : 150)
  ];

  echo json_encode([
    'ok'     => true,
    'cart'   => carritoObtener(),
    'totals' => $totals
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   💰 DATOS DEL CARRITO
   ============================================================ */
$carrito = carritoObtener();
$subtotal = carritoTotal();
$iva      = $subtotal * 0.16;
$envio    = $subtotal > 1000 ? 0 : 150;
$total    = $subtotal + $iva + $envio;

$checkoutUrl = $USER_ID
  ? "{$BASE}includes/checkout.php"
  : "{$BASE}views/login.php?next=" . urlencode($BASE.'includes/checkout.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Carrito - LumiSpace</title>
  <link rel="stylesheet" href="<?= $BASE ?>css/carrito.css?v=<?= time() ?>">
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">
  <div class="container">
    <h1>🛍 Tu Carrito LumiSpace</h1>

    <?php if (empty($carrito)): ?>
      <div class="empty-cart">
        <p>No tienes productos en el carrito.</p>
        <a href="<?= $BASE ?>index.php" class="btn-outline">Explorar productos</a>
      </div>
    <?php else: ?>
      <div class="cart-actions-top">
        <a href="<?= $BASE ?>index.php" class="btn-outline">⬅ Seguir comprando</a>
        <button id="vaciarCarrito" class="btn-danger">Vaciar carrito</button>
      </div>

      <div class="products-list" id="cartList">
        <?php foreach ($carrito as $item): ?>
          <?php
            $id  = (int)($item['producto_id'] ?? $item['id']);
            $qty = (int)($item['cantidad'] ?? 1);
            $precio = (float)($item['precio'] ?? 0);
            $nombre = htmlspecialchars($item['nombre'] ?? 'Producto sin nombre');
            $imagen = htmlspecialchars(publicImageUrl($item['imagen'] ?? 'images/default.png'));
            $totalItem = $precio * $qty;
          ?>
          <div class="product-item" data-id="<?= $id ?>">
            <div class="product-image">
              <img src="<?= $imagen ?>" alt="<?= $nombre ?>">
            </div>
            <div class="product-details">
              <div class="product-name"><?= $nombre ?></div>
              <?php if (!empty($item['categoria'])): ?>
                <div class="product-type"><?= htmlspecialchars($item['categoria']) ?></div>
              <?php endif; ?>
            </div>
            <div class="product-actions">
              <button class="remove-btn" data-id="<?= $id ?>">✕</button>
              <div class="quantity-control">
                <button class="qty-btn" data-id="<?= $id ?>" data-diff="-1">−</button>
                <span class="qty-value"><?= $qty ?></span>
                <button class="qty-btn" data-id="<?= $id ?>" data-diff="1">+</button>
              </div>
              <div class="product-price">
                <div class="unit-price">$<?= number_format($precio, 2) ?></div>
                <div class="total-price">$<?= number_format($totalItem, 2) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="order-summary">
        <h2>Resumen</h2>
        <div class="summary-row"><span>Subtotal</span><span>$<?= number_format($subtotal, 2) ?></span></div>
        <div class="summary-row"><span>IVA (16%)</span><span>$<?= number_format($iva, 2) ?></span></div>
        <div class="summary-row"><span>Envío</span><span>$<?= number_format($envio, 2) ?></span></div>
        <div class="summary-row total"><span>Total</span><span>$<?= number_format($total, 2) ?></span></div>
        <a href="<?= $checkoutUrl ?>" class="pay-button">Proceder al pago 💳</a>
      </div>
    <?php endif; ?>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const base = document.body.dataset.base;

  async function post(action, id = 0, qty = 1) {
    const formData = new FormData();
    formData.append('action', action);
    if (id) formData.append('id', id);
    if (qty) formData.append('qty', qty);
    await fetch(base + 'includes/carrito.php', { method: 'POST', body: formData });
    location.reload();
  }

  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      const id = e.currentTarget.dataset.id;
      const diff = parseInt(e.currentTarget.dataset.diff);
      const qtyEl = e.currentTarget.closest('.product-item').querySelector('.qty-value');
      const newQty = Math.max(1, parseInt(qtyEl.textContent) + diff);
      post('update', id, newQty);
    });
  });

  document.querySelectorAll('.remove-btn').forEach(btn => {
    btn.addEventListener('click', e => {
      const id = e.currentTarget.dataset.id;
      if (confirm('¿Quitar este producto del carrito?')) post('remove', id);
    });
  });

  const vaciar = document.getElementById('vaciarCarrito');
  if (vaciar) vaciar.addEventListener('click', () => {
    if (confirm('¿Vaciar todo el carrito?')) post('clear');
  });
});
</script>
</body>
</html>
