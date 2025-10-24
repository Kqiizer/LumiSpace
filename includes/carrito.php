<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/* =========================
   BASE / LOGIN
========================= */
if (defined('BASE_URL')) {
  $BASE = rtrim(BASE_URL, '/').'/';
} else {
  $script = $_SERVER['SCRIPT_NAME'] ?? '/';
  $root   = rtrim(dirname(dirname($script)), '/');
  $BASE   = ($root === '' ? '/' : $root.'/');
}
$USER_ID = (int)($_SESSION['usuario_id'] ?? 0);

/* =========================
   Simulación de BD
========================= */
function getProductoById(int $id): ?array {
  $productos = [
    1 => ["nombre"=>"Lámpara Colgante Industrial","precio"=>54,"imagen"=>"imagenes/lamptecho.jpg","detalles"=>"COLGANTE • Negro Mate"],
    2 => ["nombre"=>"Lámpara decorativa RGB","precio"=>1250,"imagen"=>"imagenes/lampdeco.jpg","detalles"=>"Multicolor"],
    3 => ["nombre"=>"Lámpara de Techo DOMO 45","precio"=>450,"imagen"=>"imagenes/domo.jpg","detalles"=>"PLAFÓN • Aluminio"],
  ];
  return $productos[$id] ?? null;
}

/* =========================
   Utils
========================= */
function abs_url(string $path, string $BASE): string {
  if ($path === '') return $BASE.'images/default.png';
  if (preg_match('#^https?://#i', $path)) return $path;
  return $BASE . ltrim($path, '/');
}

/**
 * Normaliza el carrito a la forma canónica
 */
function normalize_cart(array &$carrito, string $BASE): void {
  $new = [];
  foreach ($carrito as $key => $row) {
    if (!is_array($row)) continue;

    $id  = (int)($row['id'] ?? $key);
    if ($id <= 0) continue;

    $qty = (int)($row['cantidad'] ?? ($row['qty'] ?? 1));
    if ($qty < 1) $qty = 1;

    $need = (!isset($row['precio']) || !isset($row['nombre']) || !isset($row['imagen']) || !isset($row['detalles']));
    $prod = $need ? getProductoById($id) : null;

    $precio   = isset($row['precio']) ? (float)$row['precio'] : (float)($prod['precio'] ?? 0);
    $nombre   = $row['nombre']   ?? ($prod['nombre']   ?? 'Producto');
    $imagen   = abs_url($row['imagen'] ?? ($prod['imagen'] ?? ''), $BASE);
    $detalles = $row['detalles'] ?? ($prod['detalles'] ?? '');

    $new[$id] = [
      'id'       => $id,
      'nombre'   => $nombre,
      'precio'   => $precio,
      'imagen'   => $imagen,
      'detalles' => $detalles,
      'cantidad' => $qty,
    ];
  }
  $carrito = $new;
}

/* Totales */
function compute_totals(array $carrito): array {
  $subtotal = 0.0;
  $totalQty = 0;
  foreach ($carrito as $it) {
    $precio = (float)($it['precio'] ?? 0);
    $cant   = (int)($it['cantidad'] ?? 1);
    $subtotal += $precio * $cant;
    $totalQty += $cant;
  }
  $envio = ($totalQty > 0) ? 50.00 : 0.00;
  $iva   = $subtotal * 0.16;
  $total = $subtotal + $envio + $iva;

  return compact('subtotal','envio','iva','total','totalQty');
}

/* Alta / modificación */
function addToCart(array &$carrito, int $id, int $qty, string $BASE): ?array {
  $qty = max(1, $qty);
  if (isset($carrito[$id])) {
    $carrito[$id]['cantidad'] += $qty;
    return $carrito[$id];
  }
  $prod = getProductoById($id);
  if (!$prod) return null;

  $item = [
    'id'       => $id,
    'nombre'   => $prod['nombre'],
    'precio'   => (float)$prod['precio'],
    'imagen'   => abs_url($prod['imagen'], $BASE),
    'detalles' => $prod['detalles'],
    'cantidad' => $qty,
  ];
  $carrito[$id] = $item;
  return $item;
}

/* =========================
   Inicializar + Normalizar
========================= */
$_SESSION['carrito'] = $_SESSION['carrito'] ?? [];
$carrito =& $_SESSION['carrito'];
normalize_cart($carrito, $BASE);

/* =========================
   Endpoints AJAX
========================= */
$isJsonRequest = (
  ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
  (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
    || isset($_POST['ajax']) || isset($_GET['ajax']))
);

if ($isJsonRequest) {
  header('Content-Type: application/json; charset=utf-8');
  $input = file_get_contents('php://input');
  $payload = json_decode($input, true);
  if (!is_array($payload)) $payload = $_POST;

  $action = $payload['action'] ?? '';
  $id     = (int)($payload['id'] ?? 0);
  $qty    = (int)($payload['qty'] ?? 1);

  $flash = null;

  switch ($action) {
    case 'add':
      if ($id > 0) {
        $item = addToCart($carrito, $id, $qty, $BASE);
        if ($item) {
          $flash = [
            'title'   => 'Producto agregado',
            'nombre'  => $item['nombre'],
            'imagen'  => $item['imagen'],
            'cantidad'=> (int)$item['cantidad']
          ];
        }
      }
      break;
    case 'remove':
      if ($id > 0 && isset($carrito[$id])) unset($carrito[$id]);
      break;
    case 'update':
      if ($id > 0 && isset($carrito[$id])) {
        $carrito[$id]['cantidad'] = max(1, $qty);
      }
      break;
    case 'clear':
      $carrito = [];
      break;
  }

  $totals = compute_totals($carrito);
  echo json_encode([
    'ok'     => true,
    'cart'   => array_values($carrito),
    'totals' => $totals,
    'flash'  => $flash,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   Acciones GET
========================= */
$redirectToSelf = function() use ($BASE) {
  header("Location: {$BASE}includes/carrito.php");
  exit;
};

if (isset($_GET['add'])) {
  $id  = (int)$_GET['add'];
  $qty = (int)($_GET['qty'] ?? 1);
  if ($id > 0) {
    $item = addToCart($carrito, $id, $qty, $BASE);
    if ($item) {
      $_SESSION['flash_added'] = [
        'title'   => 'Producto agregado al carrito',
        'nombre'  => $item['nombre'],
        'imagen'  => $item['imagen'],
        'cantidad'=> $qty
      ];
    }
  }
  $redirectToSelf();
}

if (isset($_GET['action'])) {
  $action = $_GET['action'];
  $id     = (int)($_GET['id'] ?? 0);

  if ($action === 'add' && $id > 0) {
    $item = addToCart($carrito, $id, (int)($_GET['qty'] ?? 1), $BASE);
    if ($item) {
      $_SESSION['flash_added'] = [
        'title'   => 'Producto agregado al carrito',
        'nombre'  => $item['nombre'],
        'imagen'  => $item['imagen'],
        'cantidad'=> (int)($_GET['qty'] ?? 1),
      ];
    }
    $redirectToSelf();
  }
  if ($action === 'remove' && isset($carrito[$id])) {
    unset($carrito[$id]);
    $redirectToSelf();
  }
  if ($action === 'update' && isset($carrito[$id])) {
    $qty = max(1, (int)($_GET['qty'] ?? 1));
    $carrito[$id]['cantidad'] = $qty;
    $redirectToSelf();
  }
  if ($action === 'clear') {
    $carrito = [];
    $redirectToSelf();
  }
}

/* =========================
   Totales HTML
========================= */
$totals = compute_totals($carrito);
$subtotal = $totals['subtotal'];
$envio    = $totals['envio'];
$iva      = $totals['iva'];
$total    = $totals['total'];

/* URL de checkout */
$checkoutUrl = $USER_ID
  ? "{$BASE}includes/checkout.php"
  : "{$BASE}views/login.php?next=" . urlencode($BASE.'includes/checkout.php');

/* Flash agregado */
$flash_added = $_SESSION['flash_added'] ?? null;
unset($_SESSION['flash_added']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Carrito de Compras - LumiSpace</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Tu carrito de compras en LumiSpace" />
  <link rel="stylesheet" href="<?= htmlspecialchars($BASE) ?>css/carrito.css?v=<?= time() ?>" />
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f9f7f4;color:#333;line-height:1.6}
    .container{max-width:1100px;margin:0 auto;padding:20px}
    h1{color:#8b7355;margin-bottom:20px;font-size:28px}
    
    .cart-actions-top{margin:10px 0 20px;display:flex;gap:12px;flex-wrap:wrap}
    .btn-outline{display:inline-block;padding:10px 16px;border:2px solid #d4c4a8;border-radius:10px;color:#8b7355;text-decoration:none;font-weight:600;transition:all .3s}
    .btn-outline:hover{background:#f5f2ef;transform:translateY(-2px)}
    .btn-danger{display:inline-block;padding:10px 16px;border:2px solid #e57373;border-radius:10px;color:#b71c1c;text-decoration:none;font-weight:600;transition:all .3s}
    .btn-danger:hover{background:#ffebee;transform:translateY(-2px)}

    .flash-added{display:flex;align-items:center;gap:14px;background:#e8f5e9;border:2px solid #81c784;border-radius:14px;padding:14px;margin:10px 0 20px;animation:slideDown .4s ease-out}
    @keyframes slideDown{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
    .flash-added img{width:60px;height:60px;object-fit:cover;border-radius:10px}
    .flash-title{font-weight:700;color:#2e7d32;font-size:16px}
    .flash-text{color:#388e3c;font-size:14px;margin-top:4px}

    .empty-cart{text-align:center;padding:60px 20px}
    .empty-cart p{font-size:18px;color:#999;margin-bottom:20px}

    .products-list{display:flex;flex-direction:column;gap:16px;margin-bottom:30px}
    .product-item{display:grid;grid-template-columns:110px 1fr auto;gap:16px;align-items:center;background:#fff;border:1px solid #e8e4de;border-radius:12px;padding:16px;transition:box-shadow .3s}
    .product-item:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
    .product-image img{width:100px;height:100px;object-fit:cover;border-radius:10px}
    .product-details{flex:1}
    .product-name{font-weight:700;color:#8b7355;font-size:16px;margin-bottom:4px}
    .product-type{color:#a0896b;font-size:13px}
    .product-actions{display:flex;align-items:center;gap:20px}
    
    .quantity-control{display:flex;align-items:center;gap:10px;background:#f5f2ef;padding:6px 10px;border-radius:8px}
    .qty-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border:1px solid #d4c4a8;border-radius:6px;color:#8b7355;text-decoration:none;font-weight:700;transition:all .2s;cursor:pointer}
    .qty-btn:hover{background:#fff;transform:scale(1.1)}
    .qty-value{min-width:32px;text-align:center;font-weight:700;color:#8b7355;font-size:15px}
    
    .remove-btn{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;color:#b71c1c;text-decoration:none;border:1px solid #ffcdd2;border-radius:8px;transition:all .2s;font-size:18px}
    .remove-btn:hover{background:#ffebee;transform:scale(1.1)}
    
    .product-price{text-align:right}
    .unit-price{color:#999;font-size:13px;margin-bottom:4px}
    .total-price{color:#8b7355;font-weight:700;font-size:18px}

    .order-summary{border:2px solid #d4c4a8;border-radius:14px;padding:24px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.05)}
    .order-summary h2{color:#8b7355;margin:0 0 16px;font-size:20px}
    .summary-row{display:flex;justify-content:space-between;padding:8px 0;color:#666;font-size:15px}
    .summary-row span:last-child{color:#8b7355;font-weight:600}
    .summary-row.total{font-weight:800;border-top:2px dashed #e0d6c9;margin-top:12px;padding-top:14px;font-size:18px;color:#8b7355}
    .summary-row.total span:last-child{color:#8b7355;font-size:22px}
    
    .pay-button{display:block;width:100%;margin-top:16px;background:#8b7355;color:#fff;text-decoration:none;padding:14px;border-radius:10px;text-align:center;font-weight:700;font-size:16px;transition:all .3s;border:none;cursor:pointer}
    .pay-button:hover{background:#6d5a42;transform:translateY(-2px);box-shadow:0 4px 12px rgba(139,115,85,.3)}

    @media (max-width:768px){
      .product-item{grid-template-columns:80px 1fr;padding:12px}
      .product-image img{width:80px;height:80px}
      .product-actions{grid-column:1 / -1;justify-content:space-between;margin-top:10px}
      .product-price{text-align:left}
      h1{font-size:24px}
    }
  </style>
</head>

<body data-base="<?= htmlspecialchars($BASE) ?>" data-user="<?= $USER_ID ?>">
  <div class="container">
    <h1>🛒 Tu Carrito</h1>

    <?php if ($flash_added): ?>
      <div class="flash-added" role="status" aria-live="polite">
        <img src="<?= htmlspecialchars($flash_added['imagen']) ?>" alt="<?= htmlspecialchars($flash_added['nombre']) ?>">
        <div>
          <div class="flash-title"><?= htmlspecialchars($flash_added['title']) ?></div>
          <div class="flash-text">
            <strong><?= htmlspecialchars($flash_added['nombre']) ?></strong>
            <?php if (!empty($flash_added['cantidad'])): ?>
              &nbsp;× <?= (int)$flash_added['cantidad'] ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($carrito)): ?>
      <div class="empty-cart">
        <p>🛍️ No tienes productos en el carrito</p>
        <a href="<?= htmlspecialchars($BASE) ?>index.php" class="btn-outline">⬅ Explorar Productos</a>
      </div>
    <?php else: ?>
      <div class="cart-actions-top">
        <a href="<?= htmlspecialchars($BASE) ?>index.php" class="btn-outline">⬅ Seguir comprando</a>
        <a href="<?= htmlspecialchars($BASE) ?>includes/carrito.php?action=clear" class="btn-danger" id="btnClear" onclick="return confirm('¿Estás seguro de vaciar el carrito?')">Vaciar carrito</a>
      </div>

      <div class="products-list" id="cartList">
        <?php foreach ($carrito as $item): ?>
          <?php
            $cant   = (int)($item['cantidad'] ?? 1);
            $precio = (float)($item['precio'] ?? 0);
            $itemId = (int)$item['id'];
          ?>
          <div class="product-item" data-id="<?= $itemId ?>">
            <div class="product-image">
              <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>">
            </div>

            <div class="product-details">
              <div class="product-name"><?= htmlspecialchars($item['nombre']) ?></div>
              <div class="product-type"><?= htmlspecialchars($item['detalles']) ?></div>
            </div>

            <div class="product-actions">
              <a href="<?= htmlspecialchars($BASE) ?>includes/carrito.php?action=remove&id=<?= $itemId ?>" 
                 class="remove-btn" 
                 title="Quitar producto"
                 onclick="return confirm('¿Quitar este producto del carrito?')">✕</a>
              
              <div class="quantity-control">
                <a class="qty-btn" 
                   href="<?= htmlspecialchars($BASE) ?>includes/carrito.php?action=update&id=<?= $itemId ?>&qty=<?= max(1, $cant-1) ?>"
                   aria-label="Disminuir cantidad">−</a>
                <span class="qty-value" aria-label="Cantidad"><?= $cant ?></span>
                <a class="qty-btn" 
                   href="<?= htmlspecialchars($BASE) ?>includes/carrito.php?action=update&id=<?= $itemId ?>&qty=<?= $cant+1 ?>"
                   aria-label="Aumentar cantidad">+</a>
              </div>

              <div class="product-price">
                <div class="unit-price">$<?= number_format($precio, 2) ?> c/u</div>
                <div class="total-price">$<?= number_format($precio * $cant, 2) ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="order-summary" id="orderSummary">
        <h2>Resumen del Pedido</h2>
        <div class="summary-row">
          <span>Subtotal (<?= $totals['totalQty'] ?> productos)</span>
          <span>$<?= number_format($subtotal, 2) ?></span>
        </div>
        <div class="summary-row">
          <span>Envío</span>
          <span>$<?= number_format($envio, 2) ?></span>
        </div>
        <div class="summary-row">
          <span>IVA (16%)</span>
          <span>$<?= number_format($iva, 2) ?></span>
        </div>
        <div class="summary-row total">
          <span>Total</span>
          <span>$<?= number_format($total, 2) ?></span>
        </div>

        <a href="<?= htmlspecialchars($checkoutUrl) ?>" class="pay-button">Proceder al Pago 💳</a>
      </div>
    <?php endif; ?>
  </div>

  <script src="<?= htmlspecialchars($BASE) ?>js/carrito.js?v=<?= time() ?>" defer></script>
</body>
</html>