<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . "/../config/functions.php";

/* ---------------- Base ---------------- */
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$USER_ID = (int) ($_SESSION['usuario_id'] ?? 0);

/* ---------------- ID requerido ---------------- */
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo "Producto no especificado.";
  exit;
}

/* ---------------- Conexión ---------------- */
$conn = getDBConnection();
if (!$conn || $conn->connect_errno) {
  http_response_code(500);
  echo "Error de conexión a BD.";
  exit;
}

/* ---------------- Helper imágenes ---------------- */
if (!function_exists('prod_img_url')) {
  function prod_img_url($raw, $BASE)
  {
    $raw = trim((string) $raw);
    if ($raw === '')
      return $BASE . 'images/default.png';
    $raw = str_replace('\\', '/', $raw);

    if (preg_match('#^https?://#i', $raw))
      return $raw;

    if (stripos($raw, '/images/productos/') === 0)
      return $BASE . ltrim($raw, '/');
    if (stripos($raw, 'images/productos/') === 0)
      return $BASE . $raw;
    if (stripos($raw, '/uploads/productos/') === 0)
      return $BASE . ltrim($raw, '/');
    if (stripos($raw, 'uploads/productos/') === 0)
      return $BASE . $raw;

    if (strpos($raw, '/') !== false)
      return $BASE . ltrim($raw, '/');
    return $BASE . 'images/productos/' . $raw;
  }
}

/* ---------------- Utilidades de esquema ---------------- */
if (!function_exists('ls_table_exists')) {
  function ls_table_exists(mysqli $conn, string $table): bool
  {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return ($res && $res->num_rows > 0);
  }
}
if (!function_exists('ls_column_exists')) {
  function ls_column_exists(mysqli $conn, string $table, string $col): bool
  {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return ($res && $res->num_rows > 0);
  }
}

/* ---------------- SELECT dinámico ---------------- */
$select = ["p.id", "p.nombre", "p.precio"];
$select[] = ls_column_exists($conn, 'productos', 'descripcion') ? "p.descripcion" : "NULL AS descripcion";
$select[] = ls_column_exists($conn, 'productos', 'precio_original') ? "p.precio_original" : "NULL AS precio_original";
$select[] = ls_column_exists($conn, 'productos', 'descuento') ? "p.descuento" : "NULL AS descuento";
$select[] = ls_column_exists($conn, 'productos', 'stock') ? "p.stock" : "0 AS stock";
$select[] = ls_column_exists($conn, 'productos', 'imagen') ? "p.imagen" : "'' AS imagen";

$join = "";

/* categoría */
if (ls_table_exists($conn, 'categorias') && ls_column_exists($conn, 'productos', 'categoria_id')) {
  $select[] = "c.id AS categoria_id";
  $select[] = "c.nombre AS categoria";
  $join .= " LEFT JOIN categorias c ON p.categoria_id = c.id";
} else {
  $select[] = ls_column_exists($conn, 'productos', 'categoria') ? "p.categoria AS categoria" : "NULL AS categoria";
  $select[] = "NULL AS categoria_id";
}

/* marca/proveedor */
if (ls_table_exists($conn, 'proveedores') && ls_column_exists($conn, 'productos', 'proveedor_id')) {
  $select[] = "pr.nombre AS marca";
  $join .= " LEFT JOIN proveedores pr ON p.proveedor_id = pr.id";
} elseif (ls_table_exists($conn, 'marcas') && ls_column_exists($conn, 'productos', 'marca_id')) {
  $select[] = "m.nombre AS marca";
  $join .= " LEFT JOIN marcas m ON p.marca_id = m.id";
} else {
  $select[] = "NULL AS marca";
}

$sql = "SELECT " . implode(",", $select) . " FROM productos p{$join} WHERE p.id=? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo "Error cargando el producto.";
  exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
if (!$prod) {
  http_response_code(404);
  echo "Producto no encontrado.";
  exit;
}

/* ---------------- Precios ---------------- */
$precio = (float) ($prod['precio'] ?? 0);
$precioOriginal = (float) ($prod['precio_original'] ?? 0);
$descuento = isset($prod['descuento']) ? (float) $prod['descuento'] : null;
$mostrarOriginal = $precioOriginal > 0 && $precioOriginal > $precio;
if ($descuento === null && $mostrarOriginal) {
  $descuento = round(100 - ($precio * 100 / max(0.01, $precioOriginal)));
} elseif ($descuento === null) {
  $descuento = 0;
}

/* ---------------- Imágenes ---------------- */
$imgPrincipal = prod_img_url($prod['imagen'] ?? '', $BASE);
$thumbs = [$imgPrincipal];
if (ls_table_exists($conn, 'producto_imagenes')) {
  if ($g = $conn->prepare("SELECT ruta FROM producto_imagenes WHERE producto_id=? ORDER BY orden ASC, id ASC")) {
    $g->bind_param("i", $id);
    if ($g->execute()) {
      $rs = $g->get_result();
      while ($row = $rs->fetch_assoc())
        $thumbs[] = prod_img_url($row['ruta'] ?? '', $BASE);
    }
    $g->close();
  }
}
$thumbs = array_values(array_unique(array_filter($thumbs)));

/* ---------------- Favoritos ---------------- */
$favoriteIds = [];
$isFavorite = false;
if ($USER_ID > 0 && favoritosAvailable()) {
  if ($favStmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?")) {
    $favStmt->bind_param("i", $USER_ID);
    if ($favStmt->execute()) {
      $favRes = $favStmt->get_result();
      while ($row = $favRes->fetch_assoc()) {
        $favoriteIds[] = (int)$row['producto_id'];
      }
    }
    $favStmt->close();
  }
  $isFavorite = in_array($id, $favoriteIds, true);
}

/* ---------------- Relacionados ---------------- */
$rel = [];
$relCols = ["id", "nombre"];
$relCols[] = ls_column_exists($conn, 'productos', 'precio') ? "precio" : "0 AS precio";
$relCols[] = ls_column_exists($conn, 'productos', 'imagen') ? "imagen" : "'' AS imagen";
$q = $conn->prepare("SELECT " . implode(",", $relCols) . " FROM productos WHERE id<>? ORDER BY id DESC LIMIT 4");
if ($q) {
  $q->bind_param("i", $id);
  if ($q->execute()) {
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) {
      $row['img'] = prod_img_url((string) $row['imagen'], $BASE);
      $rel[] = $row;
    }
  }
  $q->close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($prod['nombre']) ?> - LumiSpace</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- Tu CSS principal -->
  <link rel="stylesheet" href="<?= $BASE ?>css/product-styles.css">

  <!-- FIX puntuales: mostrar imagen y acciones en móvil -->
  <style>
    .main-image img {
      display: block !important;
    }

    .thumbnail img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    @media (hover:none) {
      .product-card .product-actions {
        opacity: 1;
      }
    }
  </style>
</head>

<body data-base="<?= htmlspecialchars($BASE) ?>" data-user="<?= $USER_ID ?>">

  <?php include __DIR__ . "/../includes/header.php"; ?>

  <main class="product-detail">
    <div class="container">
      <div class="product-layout">
        <!-- Galería -->
        <div class="product-gallery">
          <div class="main-image">
            <img src="<?= htmlspecialchars($imgPrincipal) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>"
              id="mainImage" style="display:block">
            <div class="image-placeholder"></div>
            <button class="zoom-btn" id="zoomBtn"><i class="fas fa-search-plus"></i></button>
            <div class="product-badges">
              <?php if (($descuento ?? 0) > 0): ?>
                <span class="badge discount"><?= (int) $descuento ?>% OFF</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="thumbnail-gallery" id="thumbs">
            <?php foreach ($thumbs as $i => $t): ?>
              <div class="thumbnail<?= $i === 0 ? ' active' : '' ?>">
                <img src="<?= htmlspecialchars($t) ?>" alt="thumb <?= $i + 1 ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Info -->
        <div class="product-info" data-id="<?= (int) $prod['id'] ?>" data-name="<?= htmlspecialchars($prod['nombre']) ?>"
          data-price="<?= number_format((float) $prod['precio'], 2, '.', '') ?>" data-stock="<?= (int) $prod['stock'] ?>"
          data-img="<?= htmlspecialchars($imgPrincipal) ?>">

          <div class="product-header">
            <h1 class="product-title"><?= htmlspecialchars($prod['nombre']) ?></h1>
            <div class="product-meta">
              <span class="product-sku">SKU:
                <strong><?= 'P' . str_pad((string) $prod['id'], 5, '0', STR_PAD_LEFT) ?></strong></span>
              <?php if (!empty($prod['categoria'])): ?>
                <span class="product-category">Categoria: <a
                    href="<?= $BASE ?>views/tienda.php?cat=<?= urlencode($prod['categoria']) ?>"><?= htmlspecialchars($prod['categoria']) ?></a></span>
              <?php endif; ?>
              <?php if (!empty($prod['marca'])): ?>
                <span class="product-brand">Brand: <a href="#"><?= htmlspecialchars($prod['marca']) ?></a></span>
              <?php endif; ?>
            </div>

            <div class="product-rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                  class="fas fa-star"></i><i class="fas fa-star"></i></div>
              <span class="rating-text">(4.8)</span>
              <a href="#reviews" class="reviews-link">124 Reviews</a>
            </div>
          </div>

          <div class="product-price">
            <span class="current-price">$<?= number_format((float) $prod['precio'], 2) ?></span>
            <?php if ($mostrarOriginal): ?>
              <span class="original-price">$<?= number_format($precioOriginal, 2) ?></span>
              <span class="discount-percent">Save <?= (int) $descuento ?>%</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($prod['descripcion'])): ?>
            <div class="product-description">
              <p><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></p>
            </div>
          <?php endif; ?>

          <div class="product-options">
            <div class="option-group">
              <label class="option-label">Color:</label>
              <div class="color-options">
                <div class="color-option active" data-color="natural" style="background-color:#d4c4a8;"><span
                    class="color-name">Natural Oak</span></div>
                <div class="color-option" data-color="dark" style="background-color:#8b7355;"><span
                    class="color-name">Dark Walnut</span></div>
                <div class="color-option" data-color="white" style="background-color:#f5f2ef;"><span
                    class="color-name">White</span></div>
              </div>
            </div>
          </div>

          <div class="purchase-section">
            <div class="quantity-selector">
              <label class="option-label">Quantity:</label>
              <div class="quantity-controls">
                <button class="qty-btn minus" id="qtyMinus">-</button>
                <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="<?= (int) $prod['stock'] ?>">
                <button class="qty-btn plus" id="qtyPlus">+</button>
              </div>
              <span class="stock-info"
                id="stockInfo"><?= (int) $prod['stock'] > 0 ? (int) $prod['stock'] . ' in stock' : 'Sin stock' ?></span>
            </div>

            <div class="action-buttons">
              <button class="add-to-cart-btn js-cart" id="addToCartBtn" data-id="<?= (int)$prod['id'] ?>" <?= (int) $prod['stock'] <= 0 ? 'disabled' : '' ?>>
                <i class="fas fa-shopping-cart"></i> Add to Cart - $<?= number_format((float) $prod['precio'], 2) ?>
              </button>
              <button class="buy-now-btn" id="buyNowBtn" <?= (int) $prod['stock'] <= 0 ? 'disabled' : '' ?>>Buy Now</button>
            </div>

            <div class="secondary-actions">
              <button
                class="wishlist-btn js-wish <?= $isFavorite ? 'active' : '' ?>"
                id="wishlistBtn"
                data-id="<?= (int)$prod['id'] ?>"
                type="button"
              >
                <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                <?= $isFavorite ? 'En tus favoritos' : 'Agregar a favoritos' ?>
              </button>
              <button class="compare-btn" id="compareBtn"><i class="fas fa-sync-alt"></i> Compare</button>
              <button class="share-btn" id="shareBtn"><i class="fas fa-share-alt"></i> Share</button>
            </div>
          </div>

          <div class="product-features">
            <div class="feature-item"><i class="fas fa-truck"></i>
              <div>
                <h4>Free Shipping</h4>
                <p>Free delivery on orders over $100</p>
              </div>
            </div>
            <div class="feature-item"><i class="fas fa-undo-alt"></i>
              <div>
                <h4>30-Day Returns</h4>
                <p>Easy returns within 30 days</p>
              </div>
            </div>
            <div class="feature-item"><i class="fas fa-shield-alt"></i>
              <div>
                <h4>2-Year Warranty</h4>
                <p>Full warranty coverage</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Product Tabs -->
  <section class="product-tabs">
    <div class="container">
      <div class="tabs-header">
        <button class="tab-btn active" data-tab="description">Description</button>
        <button class="tab-btn" data-tab="specifications">Specifications</button>
        <button class="tab-btn" data-tab="reviews">Reviews (124)</button>
        <button class="tab-btn" data-tab="shipping">Shipping & Returns</button>
      </div>
      <div class="tabs-content">
        <div class="tab-content active" id="description">
          <h3>Product Description</h3>
          <p><?= nl2br(htmlspecialchars($prod['descripcion'] ?? 'Producto sin descripción.')) ?></p>
        </div>
        <div class="tab-content" id="specifications">
          <h3>Technical Specifications</h3>
          <table class="specs-table">
            <tr>
              <td><strong>Stock</strong></td>
              <td><?= (int) $prod['stock'] ?></td>
            </tr>
            <tr>
              <td><strong>Categoría</strong></td>
              <td><?= htmlspecialchars($prod['categoria'] ?: '—') ?></td>
            </tr>
            <tr>
              <td><strong>Marca</strong></td>
              <td><?= htmlspecialchars($prod['marca'] ?: '—') ?></td>
            </tr>
            <tr>
              <td><strong>Price</strong></td>
              <td>$<?= number_format((float) $prod['precio'], 2) ?></td>
            </tr>
            <?php if ($mostrarOriginal): ?>
              <tr>
                <td><strong>Before</strong></td>
                <td>$<?= number_format($precioOriginal, 2) ?> (<?= (int) $descuento ?>% OFF)</td>
              </tr>
            <?php endif; ?>
          </table>
        </div>
        <div class="tab-content" id="reviews">
          <div class="reviews-summary">
            <div class="rating-overview">
              <div class="average-rating">
                <span class="rating-number">4.8</span>
                <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i
                    class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <span class="total-reviews">Based on 124 reviews</span>
              </div>
            </div>
          </div>
          <div class="reviews-list" id="reviewsList"></div>
        </div>
        <div class="tab-content" id="shipping">
          <h3>Shipping Information</h3>
          <p>Standard (Free) 5–7 business days. Express $15.99 (2–3 business days).</p>
          <h3>Returns Policy</h3>
          <p>30-day return window from delivery date. Items must be in original condition.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Related Products -->
  <section class="related-products">
    <div class="container">
      <h2 class="section-title">You Might Also Like</h2>
      <div class="products-grid">
        <?php foreach ($rel as $rp): ?>
          <div class="product-card">
            <div class="product-image" style="background-image:url('<?= htmlspecialchars($rp['img']) ?>')">
              <div class="product-actions">
                <?php $relFavorite = in_array((int)$rp['id'], $favoriteIds, true); ?>
                <button
                  class="action-btn js-wish <?= $relFavorite ? 'active' : '' ?>"
                  data-id="<?= (int)$rp['id'] ?>"
                  title="<?= $relFavorite ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
                >
                  <i class="<?= $relFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
                <a class="action-btn" title="Quick View"
                  href="<?= $BASE ?>views/productos-detal.php?id=<?= (int) $rp['id'] ?>"><i class="fas fa-eye"></i></a>
                <button class="action-btn rel-comp" data-id="<?= (int) $rp['id'] ?>" title="Compare"><i
                    class="fas fa-sync-alt"></i></button>
              </div>
            </div>
            <div class="product-info">
              <div class="product-category"><?= htmlspecialchars($prod['categoria'] ?: 'Product') ?></div>
              <h3 class="product-name"><?= htmlspecialchars($rp['nombre']) ?></h3>
              <div class="product-rating">
                <div class="stars">★★★★☆</div><span class="rating-count">(4.2)</span>
              </div>
              <div class="product-price"><span class="current-price">$<?= number_format((float) $rp['precio'], 2) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Zoom Modal -->
  <div class="zoom-modal" id="zoomModal">
    <div class="zoom-overlay">
      <button class="zoom-close" id="zoomClose">&times;</button>
      <div class="zoom-content">
        <img src="<?= htmlspecialchars($imgPrincipal) ?>" alt="Zoomed Product Image" id="zoomedImage">
        <div class="zoom-placeholder"></div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const BASE = document.body.dataset.base || '/';
      const USER = parseInt(document.body.dataset.user || '0', 10);

      /* ---------- Mostrar imagen al cargar (quitar placeholder) ---------- */
      const mainImg = document.getElementById('mainImage');
      const mainPh = document.querySelector('.main-image .image-placeholder');
      if (mainImg) {
        const done = () => { if (mainPh) mainPh.style.display = 'none'; mainImg.style.opacity = '1'; };
        if (mainImg.complete) done(); else mainImg.addEventListener('load', done, { once: true });
      }

      /* ---------- Thumbs ---------- */
      const zoomed = document.getElementById('zoomedImage');
      const zoomPh = document.querySelector('.zoom-placeholder');
      function loadDoneZoom() { if (zoomPh) zoomPh.style.display = 'none'; if (zoomed) zoomed.style.opacity = '1'; }
      if (zoomed) { if (zoomed.complete) loadDoneZoom(); else zoomed.addEventListener('load', loadDoneZoom, { once: true }); }

      document.querySelectorAll('#thumbs .thumbnail img').forEach(img => {
        img.addEventListener('click', () => {
          document.querySelectorAll('#thumbs .thumbnail').forEach(t => t.classList.remove('active'));
          img.closest('.thumbnail').classList.add('active');
          if (mainImg) { mainImg.style.opacity = '0.5'; mainImg.src = img.src; }
          if (zoomed) { zoomed.style.opacity = '0.5'; zoomed.src = img.src; }
        });
      });

      /* ---------- Zoom (usa .active que espera tu CSS) ---------- */
      const zoomBtn = document.getElementById('zoomBtn');
      const zoomModal = document.getElementById('zoomModal');
      const zoomClose = document.getElementById('zoomClose');
      if (zoomBtn && zoomModal) {
        zoomBtn.addEventListener('click', () => { zoomModal.classList.add('active'); document.body.style.overflow = 'hidden'; });
        zoomClose?.addEventListener('click', () => { zoomModal.classList.remove('active'); document.body.style.overflow = ''; });
        zoomModal.addEventListener('click', (e) => { if (e.target.classList.contains('zoom-overlay')) { zoomModal.classList.remove('active'); document.body.style.overflow = ''; } });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && zoomModal.classList.contains('active')) { zoomModal.classList.remove('active'); document.body.style.overflow = ''; } });
      }

      /* ---------- Cantidad ---------- */
      const info = document.querySelector('.product-info');
      const pid = parseInt(info?.dataset.id || '0', 10);
      const stockMax = parseInt(info?.dataset.stock || '0', 10);
      const qtyInput = document.getElementById('qtyInput');
      const qtyMinus = document.getElementById('qtyMinus');
      const qtyPlus = document.getElementById('qtyPlus');
      function clampQty() {
        let v = parseInt(qtyInput.value || '1', 10);
        if (isNaN(v) || v < 1) v = 1;
        if (stockMax > 0 && v > stockMax) v = stockMax;
        qtyInput.value = v;
        qtyMinus.disabled = v <= 1;
        qtyPlus.disabled = stockMax > 0 ? v >= stockMax : false;
      }
      qtyInput.addEventListener('input', clampQty);
      qtyMinus.addEventListener('click', () => { qtyInput.value = Math.max(1, parseInt(qtyInput.value || '1', 10) - 1); clampQty(); });
      qtyPlus.addEventListener('click', () => { qtyInput.value = (parseInt(qtyInput.value || '1', 10) + 1); clampQty(); });
      clampQty();

      /* ---------- Carrito ---------- */
      const addBtn = document.getElementById('addToCartBtn');
      const buyBtn = document.getElementById('buyNowBtn');
      const getQty = () => Math.max(1, parseInt(qtyInput.value || '1', 10));

      // Función para actualizar el contador del carrito
      function updateCartBadge(addedQty = 0) {
        // Intentar actualizar desde la API primero
        fetch(BASE + 'api/carrito/count.php')
          .then(res => res.json())
          .then(data => {
            const cartBadge = document.querySelector('.fa-shopping-cart .cart-badge, .fa-shopping-cart + .cart-badge, [data-cart-count], #cart-badge, .cart-badge');
            if (cartBadge && data.count !== undefined) {
              cartBadge.textContent = String(data.count);
              cartBadge.style.display = data.count > 0 ? 'inline-block' : 'none';
            }
          })
          .catch(() => {
            // Fallback: actualizar manualmente
            const cartBadge = document.querySelector('.fa-shopping-cart .cart-badge, .fa-shopping-cart + .cart-badge, [data-cart-count], #cart-badge, .cart-badge');
            if (cartBadge && addedQty > 0) {
              const current = parseInt(cartBadge.textContent || '0', 10);
              cartBadge.textContent = String(current + addedQty);
              cartBadge.style.display = 'inline-block';
            }
          });
      }

      // Función para agregar al carrito con cantidad personalizada
      async function addToCartWithQuantity(qty, thenGo = false) {
        if (!pid || qty <= 0) {
          throw new Error('Producto o cantidad inválida');
        }
        
        try {
          const response = await fetch(BASE + 'api/carrito/add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              producto_id: pid,
              product_id: pid,
              cantidad: qty,
              qty: qty
            })
          });

          // Verificar si la respuesta es JSON válido
          let data;
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            data = await response.json();
          } else {
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            throw new Error('Error del servidor: La respuesta no es válida');
          }

          if (response.ok && data.ok !== false) {
            // Actualizar contador de carrito en el header
            updateCartBadge(qty);
            
            if (thenGo) {
              location.href = BASE + 'includes/carrito.php';
            }
            return true;
          } else {
            throw new Error(data.msg || 'Error al agregar al carrito');
          }
        } catch (error) {
          console.error('Error al agregar al carrito:', error);
          
          // Fallback: Intentar agregar vía GET
          if (!thenGo) {
            // Solo retornar false si no vamos a redirigir
            return false;
          } else {
            // Si thenGo es true, redirigir con GET
            const url = BASE + 'includes/carrito.php?add=' + encodeURIComponent(pid) + '&qty=' + encodeURIComponent(qty);
            location.href = url;
            return false; // No esperar, ya redirigimos
          }
        }
      }

      // Override del evento del botón para usar cantidad del input
      addBtn?.addEventListener('click', async function(e) {
        // Prevenir que product-actions.js maneje este evento
        e.preventDefault();
        e.stopPropagation();
        
        if (!pid) {
          console.error('Error: Producto no válido');
          return;
        }
        
        const qty = getQty();
        if (qty <= 0) {
          alert('Por favor selecciona una cantidad válida');
          return;
        }
        
        const original = addBtn.innerHTML;
        addBtn.disabled = true;
        addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
        
        try {
          const ok = await addToCartWithQuantity(qty, false);
          
          if (ok) {
            addBtn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
            
            // Mostrar notificación toast si está disponible
            if (typeof showToast === 'function') {
              showToast('Producto agregado al carrito', 'success');
            }
            
            setTimeout(() => { 
              addBtn.innerHTML = original; 
              addBtn.disabled = false; 
            }, 1500);
          } else {
            throw new Error('No se pudo agregar al carrito');
          }
        } catch (error) {
          console.error('Error al agregar al carrito:', error);
          addBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
          
          // Mostrar notificación de error si está disponible
          if (typeof showToast === 'function') {
            showToast('Error al agregar al carrito. Intenta de nuevo.', 'error');
          }
          
          setTimeout(() => { 
            addBtn.innerHTML = original; 
            addBtn.disabled = false; 
          }, 2000);
        }
      }, true); // Usar capture phase para ejecutar antes que product-actions.js

      buyBtn?.addEventListener('click', async function(e) {
        // Prevenir que otros handlers manejen este evento
        e.preventDefault();
        e.stopPropagation();
        
        if (!pid) {
          alert('Error: Producto no válido');
          return;
        }
        
        const qty = getQty();
        if (qty <= 0) {
          alert('Por favor selecciona una cantidad válida');
          return;
        }
        
        // Si no está logueado, redirigir a login
        if (!USER) {
          const nextAfter = `${BASE}includes/carrito.php?add=${encodeURIComponent(pid)}&qty=${encodeURIComponent(qty)}`;
          location.href = `${BASE}views/login.php?next=${encodeURIComponent(nextAfter)}`;
          return;
        }
        
        // Deshabilitar botón y mostrar loading
        const originalHTML = buyBtn.innerHTML;
        buyBtn.disabled = true;
        buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        
        try {
          // Agregar al carrito
          const success = await addToCartWithQuantity(qty, false);
          
          if (success) {
            // Si se agregó correctamente, redirigir al checkout
            buyBtn.innerHTML = '<i class="fas fa-check"></i> Redirigiendo...';
            setTimeout(() => {
              location.href = BASE + 'includes/checkout.php';
            }, 500);
          } else {
            // Si falló, intentar agregar vía GET y redirigir
            const url = BASE + 'includes/carrito.php?add=' + encodeURIComponent(pid) + '&qty=' + encodeURIComponent(qty);
            location.href = url;
          }
        } catch (error) {
          console.error('Error en Buy Now:', error);
          buyBtn.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
          setTimeout(() => {
            buyBtn.innerHTML = originalHTML;
            buyBtn.disabled = false;
          }, 2000);
        }
      });

      /* ---------- Favoritos ---------- */
      // El botón de favoritos ya tiene la clase js-wish, así que product-actions.js lo manejará
      // Solo necesitamos actualizar el texto del botón cuando cambie el estado
      const wishBtn = document.getElementById('wishlistBtn');
      
      // Observer para actualizar el texto cuando cambie la clase active
      if (wishBtn) {
        const observer = new MutationObserver(() => {
          const isActive = wishBtn.classList.contains('active');
          const icon = wishBtn.querySelector('i');
          if (icon) {
            // Actualizar texto del botón
            const textNode = Array.from(wishBtn.childNodes).find(node => 
              node.nodeType === 3 && node.textContent.trim()
            );
            if (textNode) {
              textNode.textContent = isActive ? 'En tus favoritos' : 'Agregar a favoritos';
            } else {
              // Si no hay nodo de texto, actualizar todo el contenido
              wishBtn.innerHTML = `<i class="${isActive ? 'fas' : 'far'} fa-heart"></i> ${isActive ? 'En tus favoritos' : 'Agregar a favoritos'}`;
            }
          }
        });
        
        observer.observe(wishBtn, {
          attributes: true,
          attributeFilter: ['class']
        });
      }

      /* ---------- Compare (localStorage) ---------- */
      const LS_COMP = 'ls_compare';
      const compBtn = document.getElementById('compareBtn');
      const getSet = (k) => new Set(JSON.parse(localStorage.getItem(k) || '[]'));
      const saveSet = (k, s) => localStorage.setItem(k, JSON.stringify([...s]));
      function toggleCompare(id) {
        const s = getSet(LS_COMP);
        const key = String(id);
        s.has(key) ? s.delete(key) : s.add(key);
        saveSet(LS_COMP, s);
      }
      compBtn?.addEventListener('click', () => toggleCompare(pid));

      /* ---------- Relacionados: solo wish/comp; el ojo (<a>) navega normal ---------- */
      const relWrap = document.querySelector('.related-products');
      relWrap?.addEventListener('click', async (e) => {
        const comp = e.target.closest('.rel-comp');
        if (!comp) return;

        e.preventDefault();
        e.stopPropagation();
        toggleCompare(comp.dataset.id);
      });

      /* ---------- Tabs ---------- */
      const tabsHeader = document.querySelector('.product-tabs .tabs-header');
      const tabsContent = document.querySelector('.product-tabs .tabs-content');
      tabsHeader?.addEventListener('click', (e) => {
        const btn = e.target.closest('.tab-btn');
        if (!btn) return;
        const tab = btn.dataset.tab;
        if (!tab) return;
        tabsHeader.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        tabsContent.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        const activeTab = tabsContent.querySelector('.tab-content#' + tab);
        if (activeTab) activeTab.classList.add('active');
      });

      /* ---------- Share ---------- */
      const shareBtn = document.getElementById('shareBtn');
      shareBtn?.addEventListener('click', async () => {
        try {
          if (navigator.share) {
            await navigator.share({ title: document.title, url: location.href });
          } else {
            await navigator.clipboard.writeText(location.href);
            alert('Enlace copiado al portapapeles.');
          }
        } catch { }
      });
    })();

  </script>

  <script src="<?= $BASE ?>js/product-actions.js?v=<?= time() ?>"></script>
  <script src="<?= $BASE ?>js/header.js?v=<?= time() ?>"></script>
  <!-- Nota: NO cargamos productos-detal.js externo para evitar duplicar listeners -->
</body>

</html>