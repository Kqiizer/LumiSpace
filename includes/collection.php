<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/').'/' : '/';

// Usuario actual (para wishlist)
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Categorías (para tabs)
$categorias = getCategorias();

// Filtro por categoría (?cat=ID numérico)
$categoriaSeleccionada = isset($_GET['cat']) ? (int)$_GET['cat'] : null;

/**
 * Intento principal: productos por categoría (recientes)
 * Fallbacks: catálogo genérico y públicos, para que siempre se vea algo.
 */
$productos = getProductosPorCategoria($categoriaSeleccionada, 12);
if (empty($productos)) {
  // si no hay nada por categoría (o función no trajo), intenta catálogo genérico
  $productos = getProductosCatalogo(null, 12);
}
if (empty($productos)) {
  // último fallback: públicos
  $productos = getProductosPublicos(12);
}

/**
 * Favoritos del usuario (para pintar el estado del corazón)
 */
$favoritosSet = [];
if ($usuario_id) {
  $conn = getDBConnection();
  $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
  if ($chk && $chk->num_rows > 0) {
    if ($stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?")) {
      $stmt->bind_param("i", $usuario_id);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $favoritosSet[(int)$row['producto_id']] = true;
        }
        $res->free();
      }
      $stmt->close();
    }
  }
}

/**
 * Helper de imagen: acepta
 * - nombre de archivo => images/productos/archivo
 * - images/productos/... (o /images/productos/...) => tal cual
 * - uploads/productos/... (o /uploads/productos/...) => tal cual
 * - http(s)://... => tal cual
 */
function prod_img_url($raw, $BASE) {
  $raw = trim((string)$raw);
  if ($raw === '') return $BASE . 'images/default.png';

  $raw = str_replace('\\','/',$raw);
  // Absoluta
  if (preg_match('#^https?://#i', $raw)) return $raw;

  // Normalizaciones de raíz
  if (stripos($raw, '/images/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'images/productos/') === 0)  return $BASE . $raw;

  if (stripos($raw, '/uploads/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'uploads/productos/') === 0)  return $BASE . $raw;

  // Si trae cualquier otra carpeta relativa, la respetamos
  if (strpos($raw, '/') !== false) return $BASE . ltrim($raw, '/');

  // Solo nombre de archivo
  return $BASE . 'images/productos/' . $raw;
}
?>

<!-- Products Collection -->
<section class="collection" 
         data-base="<?= htmlspecialchars($BASE) ?>" 
         data-user="<?= (int)$usuario_id ?>">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">Our Products</div>
      <h2 class="section-title">Our Products Collections</h2>

      <!-- Filtros -->
      <div class="filter-tabs">
        <a href="<?= $BASE ?>index.php" class="filter-tab <?= !$categoriaSeleccionada ? 'active' : '' ?>">All Products</a>
        <?php foreach ($categorias as $c): ?>
          <a href="<?= $BASE ?>index.php?cat=<?= (int)$c['id'] ?>" 
             class="filter-tab <?= ($categoriaSeleccionada === (int)$c['id']) ? 'active' : '' ?>">
            <?= htmlspecialchars($c['nombre']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Productos -->
    <div class="products-showcase">
      <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $p): 
          $img = prod_img_url($p['imagen'] ?? '', $BASE);
          $precio = (float)($p['precio'] ?? 0);
          $precioOriginal = isset($p['precio_original']) ? (float)$p['precio_original'] : 0;
          $mostrarOriginal = $precioOriginal > 0 && $precioOriginal > $precio;
          $descuento = !empty($p['descuento']) ? (int)$p['descuento'] : ($mostrarOriginal ? (int)round(100 - ($precio*100/max(0.01,$precioOriginal))) : 0);
          $prodId = (int)($p['id'] ?? 0);
          $categoriaNombre = $p['categoria'] ?? '';
          $enFav = !empty($favoritosSet[$prodId]);
        ?>
          <div class="product-card"
               data-id="<?= $prodId ?>"
               data-nombre='<?= htmlspecialchars($p["nombre"] ?? "", ENT_QUOTES, "UTF-8") ?>'
               data-precio="<?= number_format($precio, 2, '.', '') ?>"
               data-img="<?= htmlspecialchars($img) ?>"
               data-categoria='<?= htmlspecialchars($categoriaNombre ?: "Sin categoría", ENT_QUOTES, "UTF-8") ?>'>

            <!-- Imagen (diseño original con background) -->
            <div class="product-image" 
                 style="background-image: url('<?= htmlspecialchars($img) ?>'); 
                        background-size: cover; 
                        background-position: center;">
              <?php if ($descuento > 0): ?>
                <div class="discount-badge"><?= $descuento ?>% off</div>
              <?php endif; ?>

              <div class="product-actions">
                <!-- ❤️ Wishlist (toggle en BD; si se agrega -> ir a favoritos) -->
                <button class="action-btn js-wish <?= $enFav ? 'active' : '' ?>" 
                        title="<?= $enFav ? 'In wishlist' : 'Add to Wishlist' ?>" 
                        type="button" 
                        aria-pressed="<?= $enFav ? 'true' : 'false' ?>">
                  <i class="fas fa-heart"></i>
                </button>

                <!-- ⮕ Detalle (debajo del corazón, como pediste) -->
                <a class="action-btn" 
                   title="Ver detalle" 
                   href="<?= $BASE ?>views/productos-detal.php?id=<?= $prodId ?>">
                  <i class="fas fa-arrow-right"></i>
                </a>

                <!-- 🔁 Compare (localStorage) -->
                <button class="action-btn js-compare" 
                        title="Compare" 
                        type="button" 
                        aria-pressed="false">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>

            <!-- Info -->
            <div class="product-info">
              <div class="product-brand"><?= htmlspecialchars($categoriaNombre ?: 'Sin categoría') ?></div>
              <div class="product-name"><?= htmlspecialchars($p['nombre'] ?? '') ?></div>

              <div class="product-rating">
                <div class="stars">⭐</div>
                <span class="rating-number"><?= number_format($p['rating'] ?? 4.5, 1) ?></span>
              </div>

              <div class="product-price">
                <span class="current-price">$<?= number_format($precio, 2) ?></span>
                <?php if ($mostrarOriginal): ?>
                  <span class="original-price">$<?= number_format($precioOriginal, 2) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay productos disponibles por ahora.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
// ======= Wishlist (BD, con redirección a "Me gusta") + Compare (localStorage) =======
(function(){
  const $section = document.querySelector('section.collection');
  if (!$section) return;
  const $grid = $section.querySelector('.products-showcase');
  const BASE = $section.dataset.base || '/';
  const USER_ID = parseInt($section.dataset.user || '0', 10);

  const LS_COMP = 'ls_compare';
  const getSet  = (key)=> new Set(JSON.parse(localStorage.getItem(key)||'[]'));
  const saveSet = (key, set)=> localStorage.setItem(key, JSON.stringify([...set]));

  $grid.addEventListener('click', async (e)=>{
    const card = e.target.closest('.product-card');
    if(!card) return;

    // ❤️ Wishlist -> toggle en BD + redirigir a favoritos si se agregó
    const wishBtn = e.target.closest('.js-wish');
    if (wishBtn) {
      if (!USER_ID) {
        // No logueado: ir a login y volver
        const next = location.pathname + location.search + '#p'+card.dataset.id;
        location.href = BASE + 'views/login.php?next=' + encodeURIComponent(next);
        return;
      }

      wishBtn.disabled = true;
      try {
        const res = await fetch(BASE + 'api/wishlist/toggle.php', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ producto_id: parseInt(card.dataset.id,10) })
        });

        if (res.status === 401) {
          const next = location.pathname + location.search + '#p'+card.dataset.id;
          location.href = BASE + 'views/login.php?next=' + encodeURIComponent(next);
          return;
        }

        const data = await res.json();
        if (!data.ok) throw new Error(data.msg || 'Error');

        wishBtn.classList.toggle('active', !!data.in_wishlist);
        wishBtn.setAttribute('aria-pressed', data.in_wishlist ? 'true':'false');

        if (data.in_wishlist) {
          // si se agregó, vamos a favoritos
          window.location.href = BASE + 'includes/favoritos.php';
        }
      } catch(err) {
        console.error(err);
        alert('No se pudo actualizar tu lista de favoritos.');
      } finally {
        wishBtn.disabled = false;
      }
      return;
    }

    // 🔁 Compare (localStorage)
    const compBtn = e.target.closest('.js-compare');
    if (compBtn) {
      const id = String(card.dataset.id);
      const s = getSet(LS_COMP);
      s.has(id) ? s.delete(id) : s.add(id);
      saveSet(LS_COMP, s);
      compBtn.classList.toggle('active', s.has(id));
      compBtn.setAttribute('aria-pressed', s.has(id) ? 'true':'false');
      return;
    }
  });
})();
</script>
