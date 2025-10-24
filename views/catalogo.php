<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener todas las categorías y productos
$categorias_db = getCategorias();
$conn = getDBConnection();

// Verificar columna activo
$check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$has_activo = $check_activo && $check_activo->num_rows > 0;

// Obtener todos los productos agrupados por categoría
$productos_agrupados = [];
$productos_por_categoria = [];
$todos_productos = [];

foreach ($categorias_db as $cat) {
  $cat_id = (int)$cat['id'];
  $productos = getProductosPorCategoria($cat_id, 200);
  
  if (!empty($productos)) {
    $productos_agrupados[$cat_id] = [
      'categoria' => $cat,
      'productos' => $productos
    ];
    $productos_por_categoria[$cat_id] = count($productos);
    $todos_productos = array_merge($todos_productos, $productos);
  } else {
    $productos_por_categoria[$cat_id] = 0;
  }
}

// Obtener favoritos
$favoritosSet = [];
if ($usuario_id) {
  $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
  if ($chk && $chk->num_rows > 0) {
    if ($stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?")) {
      $stmt->bind_param("i", $usuario_id);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $favoritosSet[(int)$row['producto_id']] = true;
        }
      }
      $stmt->close();
    }
  }
}

// Estadísticas
$stats = ['productos' => 0, 'clientes' => 0, 'pedidos' => 0, 'categorias' => count($categorias_db)];
$result = $conn->query("SELECT COUNT(*) as total FROM productos");
if ($result) $stats['productos'] = $result->fetch_assoc()['total'];
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol != 'admin'");
if ($result) $stats['clientes'] = $result->fetch_assoc()['total'];

function getCategoryImage($imagen, $BASE) {
  if (empty($imagen)) return $BASE . 'images/categorias/default.jpg';
  if (preg_match('#^https?://#i', $imagen)) return $imagen;
  if (strpos($imagen, '/') === 0) return $BASE . ltrim($imagen, '/');
  return $BASE . 'images/categorias/' . $imagen;
}

function prod_img_url($raw, $BASE) {
  $raw = trim((string)$raw);
  if ($raw === '') return $BASE . 'images/default.png';
  $raw = str_replace('\\', '/', $raw);
  if (preg_match('#^https?://#i', $raw)) return $raw;
  if (stripos($raw, '/images/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'images/productos/') === 0) return $BASE . $raw;
  if (stripos($raw, '/uploads/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'uploads/productos/') === 0) return $BASE . $raw;
  if (strpos($raw, '/') !== false) return $BASE . ltrim($raw, '/');
  return $BASE . 'images/productos/' . $raw;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo Completo - LumiSpace</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/responsive.css">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f5;
      color: #333;
    }

    .page-wrapper { min-height: 100vh; display: flex; flex-direction: column; }
    .main-content { flex: 1; }
    .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }

    /* Hero */
    .catalog-hero {
      background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
      padding: 80px 0 60px;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .catalog-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
    }

    .hero-content {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .hero-title {
      font-size: 48px;
      font-weight: 800;
      margin-bottom: 16px;
    }

    .hero-subtitle {
      font-size: 18px;
      opacity: 0.95;
      margin-bottom: 30px;
    }

    .hero-stats {
      display: flex;
      justify-content: center;
      gap: 40px;
      flex-wrap: wrap;
    }

    .hero-stat {
      text-align: center;
    }

    .hero-stat-number {
      font-size: 32px;
      font-weight: 800;
      display: block;
    }

    .hero-stat-label {
      font-size: 14px;
      opacity: 0.9;
    }

    /* Toolbar */
    .catalog-toolbar {
      background: white;
      padding: 20px 0;
      border-bottom: 1px solid #e0e0e0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .toolbar-content {
      display: flex;
      gap: 20px;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-box {
      flex: 1;
      min-width: 280px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s;
    }

    .search-box input:focus {
      outline: none;
      border-color: #a1683a;
      box-shadow: 0 0 0 3px rgba(161, 104, 58, 0.1);
    }

    .search-box i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
    }

    .clear-search {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #999;
      cursor: pointer;
      padding: 4px 8px;
      display: none;
    }

    .clear-search.visible { display: block; }

    .filter-group {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .filter-select {
      padding: 10px 16px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 14px;
      cursor: pointer;
      background: white;
      min-width: 150px;
    }

    .filter-select:focus {
      outline: none;
      border-color: #a1683a;
    }

    .results-count {
      padding: 10px 20px;
      background: rgba(161, 104, 58, 0.1);
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      color: #a1683a;
      white-space: nowrap;
    }

    .view-toggle {
      display: flex;
      gap: 8px;
      background: #f0f0f0;
      padding: 4px;
      border-radius: 8px;
    }

    .view-btn {
      padding: 8px 12px;
      border: none;
      background: transparent;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s;
      color: #666;
    }

    .view-btn.active {
      background: white;
      color: #a1683a;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Filtros rápidos */
    .quick-filters {
      background: white;
      padding: 20px 0;
    }

    .filters-scroll {
      display: flex;
      gap: 12px;
      overflow-x: auto;
      padding-bottom: 8px;
    }

    .filters-scroll::-webkit-scrollbar { height: 6px; }
    .filters-scroll::-webkit-scrollbar-thumb {
      background: #a1683a;
      border-radius: 3px;
    }

    .filter-chip {
      padding: 10px 20px;
      border: 2px solid #e0e0e0;
      border-radius: 50px;
      background: white;
      cursor: pointer;
      white-space: nowrap;
      font-weight: 600;
      font-size: 14px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-chip:hover {
      border-color: #a1683a;
      transform: translateY(-2px);
    }

    .filter-chip.active {
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: white;
      border-color: #a1683a;
    }

    .filter-chip .count {
      background: rgba(255,255,255,0.3);
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 12px;
    }

    .filter-chip.active .count {
      background: rgba(255,255,255,0.25);
    }

    /* Productos Grid */
    .products-section {
      padding: 40px 0 80px;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 24px;
      transition: all 0.3s;
    }

    .products-grid.list-view {
      grid-template-columns: 1fr;
    }

    .products-grid.list-view .product-card {
      display: grid;
      grid-template-columns: 250px 1fr;
      gap: 24px;
    }

    .products-grid.list-view .product-image-wrapper {
      height: 100%;
      min-height: 200px;
    }

    /* Product Card */
    .product-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgba(0,0,0,0.15);
    }

    .product-card.hidden { display: none; }

    .product-image-wrapper {
      position: relative;
      height: 280px;
      overflow: hidden;
      background: #f5f5f5;
    }

    .product-image {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      transition: transform 0.5s ease;
    }

    .product-card:hover .product-image {
      transform: scale(1.1);
    }

    .product-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: #ff6b6b;
      color: white;
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: 700;
      font-size: 0.8rem;
      z-index: 2;
    }

    .product-actions {
      position: absolute;
      top: 12px;
      right: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      opacity: 0;
      transform: translateX(20px);
      transition: all 0.3s ease;
      z-index: 2;
    }

    .product-card:hover .product-actions {
      opacity: 1;
      transform: translateX(0);
    }

    .action-btn {
      width: 40px;
      height: 40px;
      border: none;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      color: #333;
    }

    .action-btn:hover {
      transform: scale(1.15);
      background: #a1683a;
      color: white;
    }

    .action-btn.active {
      background: #ff6b6b;
      color: white;
    }

    .product-info {
      padding: 20px;
    }

    .product-category-tag {
      color: #a1683a;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    .product-name {
      font-size: 1.05rem;
      font-weight: 700;
      margin-bottom: 10px;
      color: #333;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 2.2em;
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
    }

    .stars {
      color: #ffc107;
      font-size: 0.85rem;
    }

    .rating-number {
      font-weight: 600;
      font-size: 0.8rem;
      color: #666;
    }

    .product-price {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .current-price {
      font-size: 1.35rem;
      font-weight: 800;
      color: #a1683a;
    }

    .original-price {
      font-size: 0.95rem;
      color: #999;
      text-decoration: line-through;
    }

    /* Estado vacío */
    .empty-state {
      text-align: center;
      padding: 80px 20px;
      color: #999;
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 20px;
      opacity: 0.3;
    }

    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 12px;
      color: #666;
    }

    .empty-state button {
      margin-top: 20px;
      padding: 12px 32px;
      background: #a1683a;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .empty-state button:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
    }

    /* Loading */
    .loading-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
    }

    .loading-overlay.active { display: flex; }

    .spinner {
      width: 50px;
      height: 50px;
      border: 4px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: white;
      color: #333;
      padding: 16px 24px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.2);
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 10000;
      animation: slideIn 0.3s ease;
      max-width: 400px;
    }

    @keyframes slideIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .toast.success { border-left: 4px solid #51cf66; }
    .toast.error { border-left: 4px solid #ff6b6b; }
    .toast.warning { border-left: 4px solid #ffd43b; }
    .toast.info { border-left: 4px solid #4dabf7; }

    /* Responsive */
    @media (max-width: 1200px) {
      .products-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 768px) {
      .hero-title { font-size: 36px; }
      .toolbar-content { flex-direction: column; align-items: stretch; }
      .filter-group { flex-wrap: wrap; }
      .products-grid { grid-template-columns: repeat(2, 1fr); gap: 16px; }
      .products-grid.list-view { grid-template-columns: 1fr; }
      .products-grid.list-view .product-card { grid-template-columns: 1fr; }
      .product-actions { opacity: 1; transform: translateX(0); }
    }

    @media (max-width: 480px) {
      .products-grid { grid-template-columns: 1fr; }
      .hero-stats { gap: 20px; }
    }
  </style>
</head>
<body>

  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>

  <div class="page-wrapper">
    <div class="main-content">
      
      <?php include __DIR__ . "/../includes/header.php"; ?>

      <!-- Hero -->
      <section class="catalog-hero">
        <div class="container">
          <div class="hero-content">
            <h1 class="hero-title">Catálogo Completo</h1>
            <p class="hero-subtitle">Explora toda nuestra colección de <?= count($todos_productos) ?> productos</p>
            <div class="hero-stats">
              <div class="hero-stat">
                <span class="hero-stat-number"><?= $stats['productos'] ?></span>
                <span class="hero-stat-label">Productos</span>
              </div>
              <div class="hero-stat">
                <span class="hero-stat-number"><?= $stats['categorias'] ?></span>
                <span class="hero-stat-label">Categorías</span>
              </div>
              <div class="hero-stat">
                <span class="hero-stat-number"><?= $stats['clientes'] ?></span>
                <span class="hero-stat-label">Clientes</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Toolbar -->
      <section class="catalog-toolbar">
        <div class="container">
          <div class="toolbar-content">
            <!-- Búsqueda -->
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" id="searchInput" placeholder="Buscar productos...">
              <button class="clear-search" id="clearSearch">
                <i class="fas fa-times"></i>
              </button>
            </div>

            <!-- Filtros -->
            <div class="filter-group">
              <select id="sortSelect" class="filter-select">
                <option value="recientes">Más recientes</option>
                <option value="precio_asc">Precio: Menor a Mayor</option>
                <option value="precio_desc">Precio: Mayor a Menor</option>
                <option value="nombre">Nombre: A-Z</option>
                <option value="rating">Mejor valorados</option>
              </select>

              <div class="results-count">
                <i class="fas fa-box"></i>
                <span id="resultsCount"><?= count($todos_productos) ?></span> productos
              </div>

              <div class="view-toggle">
                <button class="view-btn active" data-view="grid" title="Vista Grid">
                  <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" data-view="list" title="Vista Lista">
                  <i class="fas fa-list"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Filtros por categoría -->
      <section class="quick-filters">
        <div class="container">
          <div class="filters-scroll">
            <button class="filter-chip active" data-category="">
              <i class="fas fa-th"></i>
              <span>Todos</span>
              <span class="count"><?= count($todos_productos) ?></span>
            </button>
            <?php foreach ($categorias_db as $cat): 
              $count = $productos_por_categoria[(int)$cat['id']] ?? 0;
              if ($count > 0):
            ?>
              <button class="filter-chip" data-category="<?= (int)$cat['id'] ?>">
                <span><?= htmlspecialchars($cat['nombre']) ?></span>
                <span class="count"><?= $count ?></span>
              </button>
            <?php endif; endforeach; ?>
          </div>
        </div>
      </section>

      <!-- Productos -->
      <section class="products-section">
        <div class="container">
          <div class="products-grid" id="productsGrid" data-base="<?= htmlspecialchars($BASE) ?>">
            <?php foreach ($todos_productos as $p): 
              $prodId = (int)($p['id'] ?? 0);
              $img = prod_img_url($p['imagen'] ?? '', $BASE);
              $precio = (float)($p['precio'] ?? 0);
              $precioOriginal = isset($p['precio_original']) ? (float)$p['precio_original'] : 0;
              $mostrarOriginal = $precioOriginal > 0 && $precioOriginal > $precio;
              $descuento = !empty($p['descuento']) ? (int)$p['descuento'] : ($mostrarOriginal ? (int)round(100 - ($precio*100/max(0.01,$precioOriginal))) : 0);
              $enFav = !empty($favoritosSet[$prodId]);
              $rating = $p['rating'] ?? 4.5;
              $catId = $p['categoria_id'] ?? 0;
              $catNombre = $p['categoria'] ?? 'Producto';
            ?>
              <div class="product-card"
                   data-id="<?= $prodId ?>"
                   data-category="<?= $catId ?>"
                   data-nombre="<?= htmlspecialchars($p["nombre"] ?? "", ENT_QUOTES) ?>"
                   data-precio="<?= number_format($precio, 2, '.', '') ?>"
                   data-rating="<?= $rating ?>">

                <div class="product-image-wrapper">
                  <div class="product-image" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
                  <?php if ($descuento > 0): ?>
                    <div class="product-badge">-<?= $descuento ?>%</div>
                  <?php endif; ?>

                  <div class="product-actions">
                    <button class="action-btn js-wish <?= $enFav ? 'active' : '' ?>" title="Favorito">
                      <i class="fas fa-heart"></i>
                    </button>
                    <a class="action-btn" title="Ver detalle" href="<?= $BASE ?>views/productos-detal.php?id=<?= $prodId ?>">
                      <i class="fas fa-eye"></i>
                    </a>
                    <button class="action-btn js-cart" title="Agregar al carrito">
                      <i class="fas fa-shopping-cart"></i>
                    </button>
                  </div>
                </div>

                <div class="product-info">
                  <div class="product-category-tag"><?= htmlspecialchars($catNombre) ?></div>
                  <div class="product-name"><?= htmlspecialchars($p['nombre'] ?? '') ?></div>

                  <div class="product-rating">
                    <div class="stars">
                      <?php 
                      $fullStars = floor($rating);
                      for ($i = 0; $i < $fullStars; $i++) echo '⭐';
                      ?>
                    </div>
                    <span class="rating-number"><?= number_format($rating, 1) ?></span>
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
          </div>

          <div class="empty-state" id="emptyState" style="display: none;">
            <i class="fas fa-search"></i>
            <h3>No se encontraron productos</h3>
            <p>Intenta ajustar los filtros o términos de búsqueda</p>
            <button onclick="resetFilters()">Limpiar filtros</button>
          </div>
        </div>
      </section>

    </div>

    <?php include __DIR__ . "/../includes/footer.php"; ?>
  </div>

  <script src="<?= $BASE ?>js/header.js"></script>
  <script>
  (()=>{
    const BASE_URL = '<?= $BASE ?>';
    const USER_ID = <?= $usuario_id ?>;
    
    const $grid = document.getElementById('productsGrid');
    const $search = document.getElementById('searchInput');
    const $clearSearch = document.getElementById('clearSearch');
    const $sort = document.getElementById('sortSelect');
    const $count = document.getElementById('resultsCount');
    const $empty = document.getElementById('emptyState');
    const $loading = document.getElementById('loadingOverlay');
    
    const LS_CART = 'ls_cart';
    let allCards = [];
    let currentCategory = '';
    let searchTimeout;
    
    const getCart = () => JSON.parse(localStorage.getItem(LS_CART) || '[]');
    const saveCart = c => localStorage.setItem(LS_CART, JSON.stringify(c));
    
    // Toast
    const toast = (msg, type = 'info') => {
      const existing = document.querySelector('.toast');
      if (existing) existing.remove();
      
      const t = document.createElement('div');
      t.className = `toast ${type}`;
      const icons = { success: 'check-circle', error: 'exclamation-circle', warning: 'exclamation-triangle', info: 'info-circle' };
      t.innerHTML = `<i class="fas fa-${icons[type]}"></i><span>${msg}</span>`;
      document.body.appendChild(t);
      
      setTimeout(() => {
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 300);
      }, 3000);
    };
    
    // Filtrar y ordenar
    const filterAndSort = () => {
      const query = $search.value.trim().toLowerCase();
      
      let visible = allCards.filter(card => {
        // Filtro de categoría
        const catMatch = currentCategory === '' || card.dataset.category === currentCategory;
        if (!catMatch) return false;
        
        // Filtro de búsqueda
        if (!query) return true;
        
        const nombre = card.dataset.nombre.toLowerCase();
        return nombre.includes(query);
      });

      // Ordenar
      const orden = $sort.value;
      if (orden !== 'recientes') {
        visible.sort((a, b) => {
          switch(orden) {
            case 'precio_asc':
              return parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio);
            case 'precio_desc':
              return parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio);
            case 'nombre':
              return a.dataset.nombre.localeCompare(b.dataset.nombre);
            case 'rating':
              return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
            default:
              return 0;
          }
        });
      }

      // Aplicar visibilidad
      allCards.forEach(card => card.classList.add('hidden'));
      visible.forEach((card, i) => {
        card.classList.remove('hidden');
        card.style.order = i;
      });

      $count.textContent = visible.length;

      // Mostrar/ocultar estado vacío
      if (visible.length === 0) {
        $grid.style.display = 'none';
        $empty.style.display = 'block';
      } else {
        $grid.style.display = '';
        $empty.style.display = 'none';
      }
    };

    // Reset filtros
    window.resetFilters = () => {
      currentCategory = '';
      $search.value = '';
      $clearSearch.classList.remove('visible');
      $sort.value = 'recientes';
      document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.classList.toggle('active', chip.dataset.category === '');
      });
      filterAndSort();
      toast('Filtros limpiados', 'info');
    };

    // Búsqueda con debounce
    $search.addEventListener('input', (e) => {
      $clearSearch.classList.toggle('visible', e.target.value.trim() !== '');
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(filterAndSort, 300);
    });

    $clearSearch.addEventListener('click', () => {
      $search.value = '';
      $clearSearch.classList.remove('visible');
      filterAndSort();
    });

    // Ordenamiento
    $sort.addEventListener('change', filterAndSort);

    // Filtros por categoría
    document.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentCategory = chip.dataset.category;
        filterAndSort();
      });
    });

    // Toggle vista
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const view = btn.dataset.view;
        $grid.classList.toggle('list-view', view === 'list');
        localStorage.setItem('catalogView', view);
      });
    });

    // Restaurar vista guardada
    const savedView = localStorage.getItem('catalogView');
    if (savedView === 'list') {
      document.querySelector('.view-btn[data-view="list"]').click();
    }

    // Favoritos
    document.querySelectorAll('.js-wish').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        if (!USER_ID) {
          toast('Debes iniciar sesión para agregar favoritos', 'warning');
          setTimeout(() => {
            location.href = BASE_URL + 'views/login.php?next=' + encodeURIComponent(location.pathname);
          }, 1500);
          return;
        }

        const card = btn.closest('.product-card');
        const prodId = parseInt(card.dataset.id);
        btn.disabled = true;

        try {
          const res = await fetch(BASE_URL + 'api/wishlist/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: prodId })
          });

          const data = await res.json();
          if (!data.ok) throw new Error(data.msg || 'Error');

          btn.classList.toggle('active', data.in_wishlist);
          toast(data.in_wishlist ? '❤️ Agregado a favoritos' : '💔 Eliminado de favoritos', 'success');
        } catch (err) {
          console.error(err);
          toast('Error al actualizar favoritos', 'error');
        } finally {
          btn.disabled = false;
        }
      });
    });

    // Carrito
    document.querySelectorAll('.js-cart').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        const card = btn.closest('.product-card');
        const prodId = parseInt(card.dataset.id);
        const nombre = card.dataset.nombre;
        const precio = parseFloat(card.dataset.precio);
        const imagen = card.querySelector('.product-image').style.backgroundImage.slice(5, -2);
        
        const cart = getCart();
        const existing = cart.find(item => item.id === prodId);
        
        if (existing) {
          existing.cantidad++;
          toast(`Cantidad actualizada (${existing.cantidad})`, 'success');
        } else {
          cart.push({
            id: prodId,
            nombre: nombre,
            precio: precio,
            imagen: imagen,
            cantidad: 1
          });
          toast('🛒 Agregado al carrito', 'success');
        }
        
        saveCart(cart);
        
        // Actualizar badge
        const badge = document.querySelector('.cart-badge');
        if (badge) {
          const total = cart.reduce((sum, item) => sum + item.cantidad, 0);
          badge.textContent = total;
          badge.style.display = total > 0 ? 'flex' : 'none';
        }

        // Animación
        btn.style.animation = 'pulse 0.5s';
        setTimeout(() => btn.style.animation = '', 500);
      });
    });

    // Click en producto
    document.querySelectorAll('.product-card').forEach(card => {
      card.addEventListener('click', (e) => {
        if (!e.target.closest('.action-btn')) {
          const prodId = card.dataset.id;
          window.location.href = `${BASE_URL}views/productos-detal.php?id=${prodId}`;
        }
      });
    });

    // Inicializar
    allCards = Array.from($grid.querySelectorAll('.product-card'));
    
    // Scroll al top
    window.scrollTo({ top: 0, behavior: 'smooth' });

    console.log('✅ Catálogo dinámico cargado');
    console.log(`📦 ${allCards.length} productos disponibles`);
  })();
  </script>
</body>
</html>