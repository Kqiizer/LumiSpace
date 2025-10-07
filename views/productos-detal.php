<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

/* ---------------- Base ---------------- */
$BASE    = defined('BASE_URL') ? rtrim(BASE_URL,'/').'/' : '/';
$USER_ID = (int)($_SESSION['usuario_id'] ?? 0);

/* ---------------- ID requerido ---------------- */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo "Producto no especificado."; exit; }

/* ---------------- Conexión ---------------- */
$conn = getDBConnection();
if (!$conn || $conn->connect_errno) { http_response_code(500); echo "Error de conexión a BD."; exit; }

/* ---------------- Helper imágenes ---------------- */
if (!function_exists('prod_img_url')) {
  function prod_img_url($raw, $BASE) {
    $raw = trim((string)$raw);
    if ($raw === '') return $BASE . 'images/default.png';
    $raw = str_replace('\\','/',$raw);

    if (preg_match('#^https?://#i', $raw)) return $raw;

    if (stripos($raw, '/images/productos/') === 0) return $BASE . ltrim($raw, '/');
    if (stripos($raw, 'images/productos/') === 0)  return $BASE . $raw;
    if (stripos($raw, '/uploads/productos/') === 0) return $BASE . ltrim($raw, '/');
    if (stripos($raw, 'uploads/productos/') === 0)  return $BASE . $raw;

    if (strpos($raw, '/') !== false) return $BASE . ltrim($raw, '/');
    return $BASE . 'images/productos/' . $raw;
  }
}

/* ---------------- Utilidades de esquema ---------------- */
if (!function_exists('ls_table_exists')) {
  function ls_table_exists(mysqli $conn, string $table): bool {
    $t = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    return ($res && $res->num_rows > 0);
  }
}
if (!function_exists('ls_column_exists')) {
  function ls_column_exists(mysqli $conn, string $table, string $col): bool {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $res = $conn->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
    return ($res && $res->num_rows > 0);
  }
}

/* ---------------- SELECT dinámico ---------------- */
$select = ["p.id","p.nombre","p.precio"];
$select[] = ls_column_exists($conn,'productos','descripcion')     ? "p.descripcion"      : "NULL AS descripcion";
$select[] = ls_column_exists($conn,'productos','precio_original') ? "p.precio_original"  : "NULL AS precio_original";
$select[] = ls_column_exists($conn,'productos','descuento')       ? "p.descuento"        : "NULL AS descuento";
$select[] = ls_column_exists($conn,'productos','stock')           ? "p.stock"            : "0 AS stock";
$select[] = ls_column_exists($conn,'productos','imagen')          ? "p.imagen"           : "'' AS imagen";

$join = "";

/* categoría */
if (ls_table_exists($conn,'categorias') && ls_column_exists($conn,'productos','categoria_id')) {
  $select[] = "c.id AS categoria_id";
  $select[] = "c.nombre AS categoria";
  $join    .= " LEFT JOIN categorias c ON p.categoria_id = c.id";
} else {
  $select[] = ls_column_exists($conn,'productos','categoria') ? "p.categoria AS categoria" : "NULL AS categoria";
  $select[] = "NULL AS categoria_id";
}

/* marca/proveedor */
if (ls_table_exists($conn,'proveedores') && ls_column_exists($conn,'productos','proveedor_id')) {
  $select[] = "pr.nombre AS marca";
  $join    .= " LEFT JOIN proveedores pr ON p.proveedor_id = pr.id";
} elseif (ls_table_exists($conn,'marcas') && ls_column_exists($conn,'productos','marca_id')) {
  $select[] = "m.nombre AS marca";
  $join    .= " LEFT JOIN marcas m ON p.marca_id = m.id";
} else {
  $select[] = "NULL AS marca";
}

$sql = "SELECT ".implode(",",$select)." FROM productos p{$join} WHERE p.id=? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) { http_response_code(500); echo "Error cargando el producto."; exit; }
$stmt->bind_param("i",$id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
if (!$prod) { http_response_code(404); echo "Producto no encontrado."; exit; }

/* ---------------- Precios ---------------- */
$precio         = (float)($prod['precio'] ?? 0);
$precioOriginal = (float)($prod['precio_original'] ?? 0);
$descuento      = isset($prod['descuento']) ? (float)$prod['descuento'] : null;
$mostrarOriginal = $precioOriginal > 0 && $precioOriginal > $precio;
if ($descuento === null && $mostrarOriginal) {
  $descuento = round(100 - ($precio * 100 / max(0.01, $precioOriginal)));
} elseif ($descuento === null) {
  $descuento = 0;
}

/* ---------------- Imágenes ---------------- */
$imgPrincipal = prod_img_url($prod['imagen'] ?? '', $BASE);
$thumbs = [$imgPrincipal];
if (ls_table_exists($conn,'producto_imagenes')) {
  if ($g = $conn->prepare("SELECT ruta FROM producto_imagenes WHERE producto_id=? ORDER BY orden ASC, id ASC")) {
    $g->bind_param("i",$id);
    if ($g->execute()) {
      $rs = $g->get_result();
      while ($row = $rs->fetch_assoc()) $thumbs[] = prod_img_url($row['ruta'] ?? '', $BASE);
    }
    $g->close();
  }
}
$thumbs = array_values(array_unique(array_filter($thumbs)));

/* ---------------- Relacionados ---------------- */
$rel = [];
$relCols = ["id","nombre"];
$relCols[] = ls_column_exists($conn,'productos','precio') ? "precio" : "0 AS precio";
$relCols[] = ls_column_exists($conn,'productos','imagen') ? "imagen" : "'' AS imagen";
$q = $conn->prepare("SELECT ".implode(",",$relCols)." FROM productos WHERE id<>? ORDER BY id DESC LIMIT 4");
if ($q) {
  $q->bind_param("i",$id);
  if ($q->execute()) {
    $r = $q->get_result();
    while ($row = $r->fetch_assoc()) {
      $row['img'] = prod_img_url((string)$row['imagen'],$BASE);
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
    .main-image img{display:block !important;}
    .thumbnail img{width:100%;height:100%;object-fit:cover;display:block;}
    @media (hover:none){ .product-card .product-actions{opacity:1;} }
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
            <img src="<?= htmlspecialchars($imgPrincipal) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" id="mainImage" style="display:block">
            <div class="image-placeholder"></div>
            <button class="zoom-btn" id="zoomBtn"><i class="fas fa-search-plus"></i></button>
            <div class="product-badges">
              <?php if (($descuento ?? 0) > 0): ?>
                <span class="badge discount"><?= (int)$descuento ?>% OFF</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="thumbnail-gallery" id="thumbs">
            <?php foreach ($thumbs as $i => $t): ?>
              <div class="thumbnail<?= $i===0 ? ' active' : '' ?>">
                <img src="<?= htmlspecialchars($t) ?>" alt="thumb <?= $i+1 ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Info -->
        <div class="product-info"
             data-id="<?= (int)$prod['id'] ?>"
             data-name="<?= htmlspecialchars($prod['nombre']) ?>"
             data-price="<?= number_format((float)$prod['precio'],2,'.','') ?>"
             data-stock="<?= (int)$prod['stock'] ?>"
             data-img="<?= htmlspecialchars($imgPrincipal) ?>">

          <div class="product-header">
            <h1 class="product-title"><?= htmlspecialchars($prod['nombre']) ?></h1>
            <div class="product-meta">
              <span class="product-sku">SKU: <strong><?= 'P'.str_pad((string)$prod['id'],5,'0',STR_PAD_LEFT) ?></strong></span>
              <?php if (!empty($prod['categoria'])): ?>
                <span class="product-category">Categoria: <a href="<?= $BASE ?>views/tienda.php?cat=<?= urlencode($prod['categoria']) ?>"><?= htmlspecialchars($prod['categoria']) ?></a></span>
              <?php endif; ?>
              <?php if (!empty($prod['marca'])): ?>
                <span class="product-brand">Brand: <a href="#"><?= htmlspecialchars($prod['marca']) ?></a></span>
              <?php endif; ?>
            </div>

            <div class="product-rating">
              <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
              <span class="rating-text">(4.8)</span>
              <a href="#reviews" class="reviews-link">124 Reviews</a>
            </div>
          </div>

          <div class="product-price">
            <span class="current-price">$<?= number_format((float)$prod['precio'], 2) ?></span>
            <?php if ($mostrarOriginal): ?>
              <span class="original-price">$<?= number_format($precioOriginal, 2) ?></span>
              <span class="discount-percent">Save <?= (int)$descuento ?>%</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($prod['descripcion'])): ?>
            <div class="product-description"><p><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></p></div>
          <?php endif; ?>

          <div class="product-options">
            <div class="option-group">
              <label class="option-label">Color:</label>
              <div class="color-options">
                <div class="color-option active" data-color="natural" style="background-color:#d4c4a8;"><span class="color-name">Natural Oak</span></div>
                <div class="color-option" data-color="dark" style="background-color:#8b7355;"><span class="color-name">Dark Walnut</span></div>
                <div class="color-option" data-color="white" style="background-color:#f5f2ef;"><span class="color-name">White</span></div>
              </div>
            </div>
          </div>

          <div class="purchase-section">
            <div class="quantity-selector">
              <label class="option-label">Quantity:</label>
              <div class="quantity-controls">
                <button class="qty-btn minus" id="qtyMinus">-</button>
                <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="<?= (int)$prod['stock'] ?>">
                <button class="qty-btn plus" id="qtyPlus">+</button>
              </div>
              <span class="stock-info" id="stockInfo"><?= (int)$prod['stock'] > 0 ? (int)$prod['stock'].' in stock' : 'Sin stock' ?></span>
            </div>

            <div class="action-buttons">
              <button class="add-to-cart-btn" id="addToCartBtn" <?= (int)$prod['stock']<=0 ? 'disabled' : '' ?>>
                <i class="fas fa-shopping-cart"></i> Add to Cart - $<?= number_format((float)$prod['precio'], 2) ?>
              </button>
              <button class="buy-now-btn" id="buyNowBtn" <?= (int)$prod['stock']<=0 ? 'disabled' : '' ?>>Buy Now</button>
            </div>

            <div class="secondary-actions">
              <button class="wishlist-btn" id="wishlistBtn"><i class="far fa-heart"></i> Add to Wishlist</button>
              <button class="compare-btn" id="compareBtn"><i class="fas fa-sync-alt"></i> Compare</button>
              <button class="share-btn" id="shareBtn"><i class="fas fa-share-alt"></i> Share</button>
            </div>
          </div>

          <div class="product-features">
            <div class="feature-item"><i class="fas fa-truck"></i><div><h4>Free Shipping</h4><p>Free delivery on orders over $100</p></div></div>
            <div class="feature-item"><i class="fas fa-undo-alt"></i><div><h4>30-Day Returns</h4><p>Easy returns within 30 days</p></div></div>
            <div class="feature-item"><i class="fas fa-shield-alt"></i><div><h4>2-Year Warranty</h4><p>Full warranty coverage</p></div></div>
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
            <tr><td><strong>Stock</strong></td><td><?= (int)$prod['stock'] ?></td></tr>
            <tr><td><strong>Categoría</strong></td><td><?= htmlspecialchars($prod['categoria'] ?: '—') ?></td></tr>
            <tr><td><strong>Marca</strong></td><td><?= htmlspecialchars($prod['marca'] ?: '—') ?></td></tr>
            <tr><td><strong>Price</strong></td><td>$<?= number_format((float)$prod['precio'],2) ?></td></tr>
            <?php if ($mostrarOriginal): ?>
              <tr><td><strong>Before</strong></td><td>$<?= number_format($precioOriginal,2) ?> (<?= (int)$descuento ?>% OFF)</td></tr>
            <?php endif; ?>
          </table>
        </div>
        <div class="tab-content" id="reviews">
          <div class="reviews-summary">
            <div class="rating-overview">
              <div class="average-rating">
                <span class="rating-number">4.8</span>
                <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
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
                <button class="action-btn rel-wish" data-id="<?= (int)$rp['id'] ?>" title="Add to Wishlist"><i class="fas fa-heart"></i></button>
                <a class="action-btn" title="Quick View" href="<?= $BASE ?>views/productos-detal.php?id=<?= (int)$rp['id'] ?>"><i class="fas fa-eye"></i></a>
                <button class="action-btn rel-comp" data-id="<?= (int)$rp['id'] ?>" title="Compare"><i class="fas fa-sync-alt"></i></button>
              </div>
            </div>
            <div class="product-info">
              <div class="product-category"><?= htmlspecialchars($prod['categoria'] ?: 'Product') ?></div>
              <h3 class="product-name"><?= htmlspecialchars($rp['nombre']) ?></h3>
              <div class="product-rating"><div class="stars">★★★★☆</div><span class="rating-count">(4.2)</span></div>
              <div class="product-price"><span class="current-price">$<?= number_format((float)$rp['precio'],2) ?></span></div>
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
  (function(){
    const BASE = document.body.dataset.base || '/';
    const USER = parseInt(document.body.dataset.user||'0',10);

    /* ---------- Mostrar imagen al cargar (quitar placeholder) ---------- */
    const mainImg = document.getElementById('mainImage');
    const mainPh  = document.querySelector('.main-image .image-placeholder');
    if (mainImg) {
      const done = () => { if (mainPh) mainPh.style.display='none'; mainImg.style.opacity='1'; };
      if (mainImg.complete) done(); else mainImg.addEventListener('load', done, {once:true});
    }

    /* ---------- Thumbs ---------- */
    const zoomed = document.getElementById('zoomedImage');
    const zoomPh = document.querySelector('.zoom-placeholder');
    function loadDoneZoom(){ if (zoomPh) zoomPh.style.display='none'; if (zoomed) zoomed.style.opacity='1'; }
    if (zoomed) { if (zoomed.complete) loadDoneZoom(); else zoomed.addEventListener('load', loadDoneZoom, {once:true}); }

    document.querySelectorAll('#thumbs .thumbnail img').forEach(img=>{
      img.addEventListener('click', ()=>{
        document.querySelectorAll('#thumbs .thumbnail').forEach(t=>t.classList.remove('active'));
        img.closest('.thumbnail').classList.add('active');
        if (mainImg) { mainImg.style.opacity='0.5'; mainImg.src = img.src; }
        if (zoomed)  { zoomed.style.opacity='0.5';  zoomed.src = img.src; }
      });
    });

    /* ---------- Zoom (usa .active que espera tu CSS) ---------- */
    const zoomBtn   = document.getElementById('zoomBtn');
    const zoomModal = document.getElementById('zoomModal');
    const zoomClose = document.getElementById('zoomClose');
    if (zoomBtn && zoomModal) {
      zoomBtn.addEventListener('click', ()=> { zoomModal.classList.add('active'); document.body.style.overflow='hidden'; });
      zoomClose?.addEventListener('click', ()=>{ zoomModal.classList.remove('active'); document.body.style.overflow=''; });
      zoomModal.addEventListener('click', (e)=>{ if(e.target.classList.contains('zoom-overlay')){ zoomModal.classList.remove('active'); document.body.style.overflow=''; }});
      document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape' && zoomModal.classList.contains('active')) { zoomModal.classList.remove('active'); document.body.style.overflow=''; }});
    }

    /* ---------- Cantidad ---------- */
    const info = document.querySelector('.product-info');
    const pid  = parseInt(info?.dataset.id||'0',10);
    const stockMax = parseInt(info?.dataset.stock||'0',10);
    const qtyInput = document.getElementById('qtyInput');
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus  = document.getElementById('qtyPlus');
    function clampQty(){
      let v = parseInt(qtyInput.value||'1',10);
      if (isNaN(v) || v<1) v=1;
      if (stockMax>0 && v>stockMax) v=stockMax;
      qtyInput.value = v;
      qtyMinus.disabled = v<=1;
      qtyPlus.disabled  = stockMax>0 ? v>=stockMax : false;
    }
    qtyInput.addEventListener('input',clampQty);
    qtyMinus.addEventListener('click', ()=>{ qtyInput.value = Math.max(1, parseInt(qtyInput.value||'1',10)-1); clampQty(); });
    qtyPlus.addEventListener('click',  ()=>{ qtyInput.value = (parseInt(qtyInput.value||'1',10)+1); clampQty(); });
    clampQty();

    /* ---------- Carrito ---------- */
    const addBtn= document.getElementById('addToCartBtn');
    const buyBtn= document.getElementById('buyNowBtn');
    const getQty = ()=> Math.max(1, parseInt(qtyInput.value||'1',10));

    async function postJSON(url, data){
      try{
        const res = await fetch(url, {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify(data)
        });
        const json = await res.json().catch(()=>null);
        return {ok: res.ok, json, status: res.status};
      }catch(e){ return {ok:false, error:String(e)}; }
    }

    async function addToCart(qty, thenGo=false){
      const payload = { producto_id: pid, product_id: pid, cantidad: qty, qty: qty };
      const r = await postJSON(BASE+'api/cart/add.php', payload);
      if (r.ok && (!r.json || r.json.ok !== false)) {
        if (thenGo) location.href = BASE+'includes/carrito.php';
        return true;
      }
      // Fallback: GET al carrito para agregar
      const url = BASE + 'includes/carrito.php?add=' + encodeURIComponent(pid) + '&qty=' + encodeURIComponent(qty);
      location.href = thenGo ? url : (BASE+'includes/carrito.php');
      return false;
    }

    addBtn?.addEventListener('click', async ()=>{
      if (!pid) return;
      const qty = getQty();
      const original = addBtn.innerHTML;
      addBtn.disabled = true;
      addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
      const ok = await addToCart(qty, false);
      if (ok) {
        addBtn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';
        setTimeout(()=>{ addBtn.innerHTML = original; addBtn.disabled = false; }, 900);
      }
    });

    buyBtn?.addEventListener('click', async ()=>{
      if (!pid) return;
      const qty = getQty();
      if (!USER) {
        const nextAfter = `${BASE}includes/carrito.php?add=${encodeURIComponent(pid)}&qty=${encodeURIComponent(qty)}`;
        location.href = `${BASE}views/login.php?next=${encodeURIComponent(nextAfter)}`;
        return;
      }
      buyBtn.disabled = true;
      buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
      await addToCart(qty, true); // redirige dentro
    });

    /* ---------- Wishlist ---------- */
    const wishBtn = document.getElementById('wishlistBtn');
    wishBtn?.addEventListener('click', async ()=>{
      if (!USER) {
        const next = location.pathname + location.search;
        location.href = BASE + 'views/login.php?next=' + encodeURIComponent(next);
        return;
      }
      wishBtn.disabled = true;
      try {
        const res = await fetch(BASE+'api/wishlist/toggle.php', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ producto_id: pid })
        });
        if (res.status === 401) {
          const next = location.pathname + location.search;
          location.href = BASE + 'views/login.php?next=' + encodeURIComponent(next);
          return;
        }
        const data = await res.json();
        if (!data.ok) throw new Error(data.msg||'Error');
        if (data.in_wishlist) location.href = BASE + 'includes/favoritos.php';
      } catch(e) {
        alert('No se pudo actualizar tu lista de favoritos.');
        console.error(e);
      } finally { wishBtn.disabled = false; }
    });

    /* ---------- Compare (localStorage) ---------- */
    const LS_COMP='ls_compare';
    const compBtn = document.getElementById('compareBtn');
    const getSet = (k)=> new Set(JSON.parse(localStorage.getItem(k)||'[]'));
    const saveSet= (k,s)=> localStorage.setItem(k, JSON.stringify([...s]));
    function toggleCompare(id){
      const s = getSet(LS_COMP);
      const key = String(id);
      s.has(key)? s.delete(key) : s.add(key);
      saveSet(LS_COMP, s);
    }
    compBtn?.addEventListener('click', ()=> toggleCompare(pid));

    /* ---------- Relacionados: solo wish/comp; el ojo (<a>) navega normal ---------- */
    const relWrap = document.querySelector('.related-products');
    relWrap?.addEventListener('click', async (e)=>{
      const wish = e.target.closest('.rel-wish');
      const comp  = e.target.closest('.rel-comp');
      if (!wish && !comp) return;

      e.preventDefault();
      e.stopPropagation();

      if (comp) { toggleCompare(comp.dataset.id); return; }

      if (wish) {
        if (!USER) {
          const next = location.pathname + location.search;
          location.href = BASE + 'views/login.php?next=' + encodeURIComponent(next);
          return;
        }
        wish.disabled = true;
        try {
          const res = await fetch(BASE+'api/wishlist/toggle.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ producto_id: parseInt(wish.dataset.id||'0',10) })
          });
          const data = await res.json();
          if (!data.ok) throw new Error(data.msg||'Error');
          if (data.in_wishlist) location.href = BASE + 'includes/favoritos.php';
        } catch(e) {
          alert('No se pudo actualizar tu lista de favoritos.');
        } finally { wish.disabled = false; }
      }
    });

    /* ---------- Tabs ---------- */
    const tabsHeader = document.querySelector('.product-tabs .tabs-header');
    const tabsContent= document.querySelector('.product-tabs .tabs-content');
    tabsHeader?.addEventListener('click', (e)=>{
      const btn = e.target.closest('.tab-btn');
      if (!btn) return;
      const tab = btn.dataset.tab;
      if (!tab) return;
      tabsHeader.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      tabsContent.querySelectorAll('.tab-content').forEach(c=>c.classList.remove('active'));
      const activeTab = tabsContent.querySelector('.tab-content#'+tab);
      if (activeTab) activeTab.classList.add('active');
    });

    /* ---------- Share ---------- */
    const shareBtn = document.getElementById('shareBtn');
    shareBtn?.addEventListener('click', async ()=>{
      try {
        if (navigator.share) {
          await navigator.share({ title: document.title, url: location.href });
        } else {
          await navigator.clipboard.writeText(location.href);
          alert('Enlace copiado al portapapeles.');
        }
      } catch {}
    });
  })();
  
  </script>

  <script src="<?= $BASE ?>js/header.js?v=<?= time() ?>"></script>
  <!-- Nota: NO cargamos productos-detal.js externo para evitar duplicar listeners -->
</body>
</html>
