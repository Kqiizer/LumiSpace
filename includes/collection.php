<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener todos los productos y categorías
$categorias = getCategorias();
$todosProductos = [];

// Obtener productos de todas las fuentes
$prods1 = getProductosCatalogo(null, 200);
$prods2 = getProductosPublicos(200);
$todosProductos = array_merge($prods1 ?: [], $prods2 ?: []);

// Eliminar duplicados por ID
$productosUnicos = [];
$idsVistos = [];
foreach ($todosProductos as $p) {
  $id = (int)($p['id'] ?? 0);
  if ($id && !in_array($id, $idsVistos)) {
    $idsVistos[] = $id;
    $productosUnicos[] = $p;
  }
}
$todosProductos = $productosUnicos;

// Favoritos del usuario
$favoritosSet = [];
if ($usuario_id) {
  $conn = getDBConnection();
  $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
  if ($chk && $chk->num_rows > 0) {
    if ($stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?")) {
      $stmt->bind_param("i", $usuario_id);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $favoritosSet[(int)$row['producto_id']] = true;
        $res->free();
      }
      $stmt->close();
    }
  }
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
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #a1683a;
      --primary-dark: #8f5e4b;
      --bg-light: #f8f6f3;
      --bg-dark: #1a1612;
      --text-light: #2a1f15;
      --text-dark: #f5f3f0;
      --shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
      --radius: 12px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Inter', -apple-system, sans-serif;
      background: var(--bg-light);
      color: var(--text-light);
      line-height: 1.6;
    }

    body.dark { background: var(--bg-dark); color: var(--text-dark); }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .section-header {
      text-align: center;
      margin-bottom: 40px;
      animation: fadeInDown 0.6s ease;
    }

    @keyframes fadeInDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .section-subtitle {
      color: var(--primary);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-size: 0.9rem;
      margin-bottom: 8px;
    }

    .section-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 32px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .toolbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      margin-bottom: 32px;
      flex-wrap: wrap;
      animation: fadeIn 0.6s ease 0.1s both;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .search-box {
      flex: 1;
      min-width: 250px;
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 12px 16px 12px 44px;
      border: 2px solid #e0d9cf;
      border-radius: var(--radius);
      font-size: 0.95rem;
      transition: var(--transition);
      background: white;
    }

    body.dark .search-box input {
      background: #2d2520;
      border-color: #3d3530;
      color: var(--text-dark);
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
      transform: translateY(-2px);
    }

    .search-box i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary);
      font-size: 1.1rem;
      pointer-events: none;
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
      border-radius: 4px;
      transition: var(--transition);
      display: none;
    }

    .clear-search:hover {
      color: var(--primary);
      background: rgba(161, 104, 58, 0.1);
    }

    .clear-search.visible { display: block; }

    .sort-box {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .sort-box label {
      font-weight: 600;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sort-box select {
      padding: 10px 16px;
      border: 2px solid #e0d9cf;
      border-radius: var(--radius);
      font-size: 0.9rem;
      cursor: pointer;
      background: white;
      transition: var(--transition);
    }

    body.dark .sort-box select {
      background: #2d2520;
      border-color: #3d3530;
      color: var(--text-dark);
    }

    .sort-box select:hover {
      border-color: var(--primary);
    }

    .sort-box select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
    }

    .results-count {
      color: #666;
      font-size: 0.9rem;
      font-weight: 600;
      padding: 8px 16px;
      background: rgba(161, 104, 58, 0.1);
      border-radius: 50px;
      transition: var(--transition);
    }

    body.dark .results-count {
      color: #aaa;
      background: rgba(161, 104, 58, 0.2);
    }

    .filter-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 32px;
      overflow-x: auto;
      padding: 12px 0;
      scrollbar-width: thin;
      animation: fadeIn 0.6s ease 0.2s both;
      scroll-behavior: smooth;
      position: relative;
    }

    .filter-tabs::-webkit-scrollbar { height: 8px; }
    .filter-tabs::-webkit-scrollbar-track {
      background: rgba(161, 104, 58, 0.1);
      border-radius: 4px;
    }
    .filter-tabs::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      border-radius: 4px;
    }
    .filter-tabs::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }

    .filter-controls {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      gap: 12px;
      flex-wrap: wrap;
    }

    .filter-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--text-light);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    body.dark .filter-title { color: var(--text-dark); }

    .filter-actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    .btn-reset, .btn-view-toggle {
      padding: 8px 16px;
      border: 2px solid #e0d9cf;
      border-radius: 8px;
      background: white;
      color: var(--text-light);
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    body.dark .btn-reset, body.dark .btn-view-toggle {
      background: #2d2520;
      border-color: #3d3530;
      color: var(--text-dark);
    }

    .btn-reset:hover, .btn-view-toggle:hover {
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(161, 104, 58, 0.2);
    }

    .btn-reset:active, .btn-view-toggle:active {
      transform: translateY(0);
    }

    .filter-tab {
      padding: 12px 24px;
      border: 2px solid #e0d9cf;
      border-radius: 50px;
      color: var(--text-light);
      font-weight: 600;
      white-space: nowrap;
      transition: var(--transition);
      background: white;
      cursor: pointer;
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-tab::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      opacity: 0;
      transition: var(--transition);
      z-index: 0;
    }

    .filter-tab span {
      position: relative;
      z-index: 1;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-tab .icon {
      font-size: 1rem;
      transition: var(--transition);
    }

    body.dark .filter-tab {
      background: #2d2520;
      border-color: #3d3530;
      color: var(--text-dark);
    }

    .filter-tab:hover {
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(161, 104, 58, 0.25);
    }

    .filter-tab:hover::before {
      opacity: 0.15;
    }

    .filter-tab:hover .icon {
      transform: scale(1.15) rotate(5deg);
    }

    .filter-tab.active {
      border-color: var(--primary);
      color: white;
      box-shadow: 0 4px 16px rgba(161, 104, 58, 0.35);
      transform: translateY(-2px);
    }

    .filter-tab.active::before {
      opacity: 1;
    }

    .filter-tab.active .icon {
      animation: bounce 0.6s ease;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-4px); }
    }

    .filter-tab .count {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 24px;
      height: 24px;
      padding: 0 8px;
      background: rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 700;
      transition: var(--transition);
    }

    .filter-tab.active .count {
      background: rgba(255, 255, 255, 0.25);
      animation: countPulse 0.4s ease;
    }

    @keyframes countPulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }

    /* Vista de grid/lista */
    .products-showcase.list-view {
      grid-template-columns: 1fr;
      gap: 16px;
    }

    .products-showcase.list-view .product-card {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 20px;
    }

    .products-showcase.list-view .product-image {
      height: 100%;
      min-height: 200px;
    }

    .products-showcase.list-view .product-info {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .products-showcase.list-view .product-name {
      font-size: 1.3rem;
      -webkit-line-clamp: 3;
      min-height: auto;
    }

    .products-showcase {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
      animation: fadeIn 0.6s ease 0.3s both;
      min-height: 400px;
    }

    .product-card {
      background: white;
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: var(--transition);
      cursor: pointer;
      position: relative;
      will-change: transform;
      animation: scaleIn 0.4s ease both;
    }

    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes scaleOut {
      from { opacity: 1; transform: scale(1); }
      to { opacity: 0; transform: scale(0.8); }
    }

    body.dark .product-card { background: #2d2520; }

    .product-card:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
      z-index: 10;
    }

    .product-card.hidden {
      display: none;
    }

    .product-card.removing {
      animation: scaleOut 0.3s ease forwards;
    }

    .product-image {
      position: relative;
      width: 100%;
      height: 320px;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      background-color: #f0f0f0;
      overflow: hidden;
    }

    .product-image::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.4));
      opacity: 0;
      transition: var(--transition);
    }

    .product-card:hover .product-image::before {
      opacity: 1;
    }

    .discount-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      padding: 6px 12px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 0.85rem;
      box-shadow: 0 4px 12px rgba(238, 90, 82, 0.4);
      animation: pulse 2s infinite;
      z-index: 2;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
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
      transition: var(--transition);
      z-index: 2;
    }

    .product-card:hover .product-actions {
      opacity: 1;
      transform: translateX(0);
    }

    .action-btn {
      width: 42px;
      height: 42px;
      border: none;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      text-decoration: none;
      color: var(--text-light);
    }

    body.dark .action-btn {
      background: rgba(45, 37, 32, 0.95);
      color: var(--text-dark);
    }

    .action-btn:hover {
      transform: scale(1.15) rotate(5deg);
      background: var(--primary);
      color: white;
      box-shadow: 0 6px 20px rgba(161, 104, 58, 0.4);
    }

    .action-btn.active {
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      animation: heartbeat 1s infinite;
    }

    @keyframes heartbeat {
      0%, 100% { transform: scale(1); }
      25% { transform: scale(1.1); }
      50% { transform: scale(1); }
    }

    .action-btn i { font-size: 1.1rem; }

    .product-info {
      padding: 20px;
    }

    .product-brand {
      color: var(--primary);
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }

    .product-name {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--text-light);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 2.6em;
    }

    body.dark .product-name { color: var(--text-dark); }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
    }

    .stars {
      color: #ffc107;
      font-size: 1rem;
      letter-spacing: 2px;
    }

    .rating-number {
      font-weight: 600;
      font-size: 0.9rem;
      color: #666;
    }

    body.dark .rating-number { color: #aaa; }

    .product-price {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .current-price {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--primary);
    }

    .original-price {
      font-size: 1.1rem;
      color: #999;
      text-decoration: line-through;
    }

    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 80px 20px;
      color: #999;
      animation: fadeIn 0.6s ease;
    }

    .empty-state i {
      font-size: 4rem;
      margin-bottom: 20px;
      opacity: 0.3;
    }

    .empty-state h3 {
      font-size: 1.5rem;
      margin-bottom: 12px;
    }

    .empty-state p {
      font-size: 1rem;
      margin-bottom: 24px;
    }

    .empty-state button {
      padding: 12px 32px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      border-radius: 50px;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
    }

    .empty-state button:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(161, 104, 58, 0.3);
    }

    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      padding: 14px 20px;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.3);
      font-weight: 600;
      z-index: 10000;
      animation: slideInUp 0.3s ease;
      pointer-events: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    @keyframes slideInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideOutDown {
      from { opacity: 1; transform: translateY(0); }
      to { opacity: 0; transform: translateY(20px); }
    }

    .toast.success { background: #51cf66; color: white; }
    .toast.error { background: #ff6b6b; color: white; }
    .toast.warning { background: #ffd43b; color: #000; }
    .toast.info { background: #4dabf7; color: white; }

    .loading-spinner {
      grid-column: 1 / -1;
      text-align: center;
      padding: 60px 20px;
    }

    .spinner {
      width: 50px;
      height: 50px;
      border: 4px solid #e0d9cf;
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 20px;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 1024px) {
      .toolbar {
        gap: 16px;
      }
      
      .results-count {
        order: -1;
        width: 100%;
        text-align: center;
      }

      .products-showcase.list-view .product-card {
        grid-template-columns: 200px 1fr;
      }
    }

    @media (max-width: 768px) {
      .container {
        padding: 20px 16px;
      }

      .section-title { 
        font-size: 1.8rem; 
      }

      .toolbar { 
        flex-direction: column; 
        align-items: stretch;
        gap: 12px;
      }

      .search-box {
        order: 1;
      }

      .results-count {
        order: 2;
        padding: 10px 16px;
        font-size: 0.95rem;
      }

      .sort-box {
        order: 3;
        justify-content: space-between;
      }

      .sort-box label {
        font-size: 0.85rem;
      }

      .sort-box select {
        flex: 1;
        padding: 12px 16px;
      }

      .filter-controls {
        flex-direction: column;
        align-items: stretch;
      }

      .filter-title {
        font-size: 1rem;
      }

      .filter-actions {
        width: 100%;
        justify-content: space-between;
      }

      .btn-reset, .btn-view-toggle {
        flex: 1;
        justify-content: center;
      }

      .filter-tabs {
        gap: 8px;
        padding: 8px 0;
      }

      .filter-tab { 
        padding: 10px 20px; 
        font-size: 0.85rem;
      }

      .filter-tab .icon {
        font-size: 0.9rem;
      }

      .filter-tab .count {
        min-width: 20px;
        height: 20px;
        font-size: 0.7rem;
      }

      .products-showcase {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
      }

      .products-showcase.list-view {
        grid-template-columns: 1fr;
      }

      .products-showcase.list-view .product-card {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .products-showcase.list-view .product-image {
        height: 200px;
        min-height: 200px;
      }

      .product-image { 
        height: 200px; 
      }

      .product-info {
        padding: 16px;
      }

      .product-name {
        font-size: 1rem;
      }

      .current-price {
        font-size: 1.3rem;
      }

      .product-actions {
        opacity: 1;
        transform: translateX(0);
      }

      .action-btn {
        width: 38px;
        height: 38px;
      }

      .action-btn i {
        font-size: 1rem;
      }

      .empty-state {
        padding: 60px 20px;
      }

      .empty-state i {
        font-size: 3rem;
      }

      .empty-state h3 {
        font-size: 1.2rem;
      }

      .toast {
        left: 16px;
        right: 16px;
        bottom: 16px;
        font-size: 0.9rem;
      }
    }

    @media (max-width: 480px) {
      .section-title {
        font-size: 1.5rem;
      }

      .products-showcase {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .product-image {
        height: 250px;
      }

      .filter-tab {
        padding: 8px 16px;
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>

<section class="collection" data-base="<?= htmlspecialchars($BASE) ?>" data-user="<?= (int)$usuario_id ?>">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">Descubre Nuestros</div>
      <h2 class="section-title">Productos Exclusivos</h2>
    </div>

    <div class="toolbar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Buscar productos..." autocomplete="off">
        <button class="clear-search" id="clearSearch">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="results-count">
        <i class="fas fa-box"></i>
        <strong id="resultsCount"><?= count($todosProductos) ?></strong> productos
      </div>
      <div class="sort-box">
        <label for="sortSelect">
          <i class="fas fa-sort-amount-down"></i>
          <span>Ordenar:</span>
        </label>
        <select id="sortSelect">
          <option value="recientes">Más recientes</option>
          <option value="precio_asc">Precio: Menor a Mayor</option>
          <option value="precio_desc">Precio: Mayor a Menor</option>
          <option value="nombre">Nombre: A-Z</option>
          <option value="rating">Mejor valorados</option>
        </select>
      </div>
    </div>

    <div class="filter-controls">
      <div class="filter-title">
        <i class="fas fa-filter"></i>
        <span>Filtrar por categoría</span>
      </div>
      <div class="filter-actions">
        <button class="btn-reset" id="btnResetFilters" style="display: none;">
          <i class="fas fa-times"></i>
          <span>Limpiar</span>
        </button>
        <button class="btn-view-toggle" id="btnViewToggle" title="Cambiar vista">
          <i class="fas fa-th"></i>
          <span class="view-text">Grid</span>
        </button>
      </div>
    </div>

    <div class="filter-tabs" id="filterTabs">
      <?php 
      // Contar productos por categoría
      $conteoCateg = [];
      foreach ($todosProductos as $p) {
        $cid = isset($p['categoria_id']) ? (int)$p['categoria_id'] : 0;
        $conteoCateg[$cid] = ($conteoCateg[$cid] ?? 0) + 1;
      }
      
      foreach ($categorias as $c): 
        $catId = (int)$c['id'];
        $count = $conteoCateg[$catId] ?? 0;
        if ($count > 0): // Solo mostrar categorías con productos
      ?>
        <button class="filter-tab" data-cat="<?= $catId ?>">
          <span>
            <i class="fas fa-tag icon"></i>
            <?= htmlspecialchars($c['nombre']) ?>
            <span class="count"><?= $count ?></span>
          </span>
        </button>
      <?php 
        endif;
      endforeach; 
      ?>
    </div>

    <div class="products-showcase" id="productsGrid">
      <?php if (!empty($todosProductos)): ?>
        <?php foreach ($todosProductos as $p): 
          $img = prod_img_url($p['imagen'] ?? '', $BASE);
          $precio = (float)($p['precio'] ?? 0);
          $precioOriginal = isset($p['precio_original']) ? (float)$p['precio_original'] : 0;
          $mostrarOriginal = $precioOriginal > 0 && $precioOriginal > $precio;
          $descuento = !empty($p['descuento']) ? (int)$p['descuento'] : ($mostrarOriginal ? (int)round(100 - ($precio*100/max(0.01,$precioOriginal))) : 0);
          $prodId = (int)($p['id'] ?? 0);
          $categoriaNombre = $p['categoria'] ?? 'Sin categoría';
          $categoriaId = isset($p['categoria_id']) ? (int)$p['categoria_id'] : 0;
          $enFav = !empty($favoritosSet[$prodId]);
          $rating = $p['rating'] ?? 4.5;
        ?>
          <div class="product-card"
               data-id="<?= $prodId ?>"
               data-cat-id="<?= $categoriaId ?>"
               data-nombre="<?= htmlspecialchars($p["nombre"] ?? "", ENT_QUOTES) ?>"
               data-precio="<?= number_format($precio, 2, '.', '') ?>"
               data-img="<?= htmlspecialchars($img) ?>"
               data-categoria="<?= htmlspecialchars($categoriaNombre, ENT_QUOTES) ?>"
               data-rating="<?= number_format($rating, 1) ?>"
               style="animation-delay: <?= rand(0, 20) * 0.02 ?>s">

            <div class="product-image" style="background-image: url('<?= htmlspecialchars($img) ?>');">
              <?php if ($descuento > 0): ?>
                <div class="discount-badge">-<?= $descuento ?>%</div>
              <?php endif; ?>

              <div class="product-actions">
                <button class="action-btn js-wish <?= $enFav ? 'active' : '' ?>" 
                        title="<?= $enFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
                        data-product-id="<?= $prodId ?>">
                  <i class="fas fa-heart"></i>
                </button>
                <a class="action-btn" 
                   title="Ver detalle" 
                   href="<?= $BASE ?>views/productos-detal.php?id=<?= $prodId ?>">
                  <i class="fas fa-eye"></i>
                </a>
                <button class="action-btn js-compare" 
                        title="Comparar producto"
                        data-product-id="<?= $prodId ?>">
                  <i class="fas fa-sync-alt"></i>
                </button>
                <button class="action-btn js-cart" 
                        title="Agregar al carrito"
                        data-product-id="<?= $prodId ?>">
                  <i class="fas fa-shopping-cart"></i>
                </button>
              </div>
            </div>

            <div class="product-info">
              <div class="product-brand"><?= htmlspecialchars($categoriaNombre) ?></div>
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
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          <h3>No hay productos disponibles</h3>
          <p>Vuelve más tarde para ver nuevos productos</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(()=>{
  const $s = document.querySelector('section.collection');
  if (!$s) return;

  const BASE = $s.dataset.base || '/';
  const USER_ID = parseInt($s.dataset.user || '0', 10);
  const $grid = document.getElementById('productsGrid');
  const $search = document.getElementById('searchInput');
  const $clearSearch = document.getElementById('clearSearch');
  const $sort = document.getElementById('sortSelect');
  const $count = document.getElementById('resultsCount');
  
  const LS_COMP = 'ls_compare';
  const LS_CART = 'ls_cart';
  
  let allCards = [];
  let searchTimeout;
  let currentCat = '';
  let currentSearch = '';
  let isListView = false;

  // Helpers localStorage
  const getSet = k => new Set(JSON.parse(localStorage.getItem(k) || '[]'));
  const saveSet = (k, s) => localStorage.setItem(k, JSON.stringify([...s]));
  const getCart = () => JSON.parse(localStorage.getItem(LS_CART) || '[]');
  const saveCart = c => localStorage.setItem(LS_CART, JSON.stringify(c));
  
  // Toast notifications
  const toast = (msg, type = 'info') => {
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => {
      t.style.animation = 'slideOutDown 0.3s ease';
      setTimeout(() => t.remove(), 300);
    }, 3000);
  };

  // Actualizar badge del carrito
  const updateBadge = () => {
    const total = getCart().reduce((sum, item) => sum + item.cantidad, 0);
    const badge = document.querySelector('.cart-badge');
    if (badge) {
      badge.textContent = total;
      badge.style.display = total > 0 ? 'flex' : 'none';
    }
  };

  // Filtrado y ordenamiento dinámico
  const filterAndSort = () => {
    const query = currentSearch.toLowerCase();
    
    let visible = allCards.filter(card => {
      // Filtro de categoría
      const catMatch = currentCat === '' || card.dataset.catId === currentCat;
      if (!catMatch) return false;
      
      // Filtro de búsqueda
      if (!query) return true;
      
      const nombre = card.dataset.nombre.toLowerCase();
      const cat = card.dataset.categoria.toLowerCase();
      return nombre.includes(query) || cat.includes(query);
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

    // Aplicar visibilidad con animación
    allCards.forEach(card => {
      card.classList.add('hidden');
      card.style.order = '';
    });
    
    visible.forEach((card, i) => {
      setTimeout(() => {
        card.classList.remove('hidden');
        card.style.order = i;
      }, i * 20); // Animación escalonada
    });

    // Actualizar contador
    $count.textContent = visible.length;

    // Actualizar contadores de categorías
    updateCategoryCount();

    // Estado vacío
    setTimeout(() => {
      const empty = $grid.querySelector('.empty-state');
      if (visible.length === 0 && !empty) {
        $grid.insertAdjacentHTML('beforeend', `
          <div class="empty-state">
            <i class="fas fa-search"></i>
            <h3>No se encontraron productos</h3>
            <p>Intenta con otra búsqueda o categoría</p>
            <button id="resetFilters">Limpiar filtros</button>
          </div>
        `);
        document.getElementById('resetFilters')?.addEventListener('click', resetFilters);
      } else if (visible.length > 0 && empty) {
        empty.remove();
      }
    }, visible.length * 20 + 100);
  };

  // Actualizar contador de categorías
  const updateCategoryCount = () => {
    const query = currentSearch.toLowerCase();
    document.querySelectorAll('.filter-tab').forEach(tab => {
      const catId = tab.dataset.cat;
      const count = allCards.filter(card => {
        const catMatch = card.dataset.catId === catId;
        if (!query) return catMatch;
        
        const nombre = card.dataset.nombre.toLowerCase();
        const cat = card.dataset.categoria.toLowerCase();
        return catMatch && (nombre.includes(query) || cat.includes(query));
      }).length;
      
      const countEl = tab.querySelector('.count');
      if (countEl) countEl.textContent = count;
    });
  };

  // Reset filtros
  const resetFilters = () => {
    currentCat = '';
    currentSearch = '';
    $search.value = '';
    $clearSearch.classList.remove('visible');
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('btnResetFilters').style.display = 'none';
    filterAndSort();
    toast('Filtros limpiados', 'info');
  };

  // Mostrar/ocultar botón de reset
  const updateResetButton = () => {
    const btn = document.getElementById('btnResetFilters');
    const shouldShow = currentCat !== '' || currentSearch !== '';
    btn.style.display = shouldShow ? 'flex' : 'none';
  };

  // Toggle vista grid/lista
  const toggleView = () => {
    isListView = !isListView;
    $grid.classList.toggle('list-view', isListView);
    
    const btn = document.getElementById('btnViewToggle');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('.view-text');
    
    if (isListView) {
      icon.className = 'fas fa-list';
      text.textContent = 'Lista';
    } else {
      icon.className = 'fas fa-th';
      text.textContent = 'Grid';
    }
    
    localStorage.setItem('productViewMode', isListView ? 'list' : 'grid');
    toast(`Vista ${isListView ? 'Lista' : 'Grid'} activada`, 'info');
  };

  // Búsqueda con debounce
  $search.addEventListener('input', e => {
    currentSearch = e.target.value.trim();
    $clearSearch.classList.toggle('visible', currentSearch !== '');
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      filterAndSort();
      updateResetButton();
    }, 300);
  });

  // Limpiar búsqueda
  $clearSearch.addEventListener('click', () => {
    currentSearch = '';
    $search.value = '';
    $clearSearch.classList.remove('visible');
    filterAndSort();
    updateResetButton();
  });

  // Botón reset filtros
  document.getElementById('btnResetFilters').addEventListener('click', resetFilters);

  // Botón toggle vista
  document.getElementById('btnViewToggle').addEventListener('click', toggleView);

  // Ordenamiento
  $sort.addEventListener('change', filterAndSort);

  // Filtros de categoría
  document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', e => {
      e.preventDefault();
      
      const catId = tab.dataset.cat;
      
      // Si ya está activa, desactivar (mostrar todos)
      if (tab.classList.contains('active')) {
        tab.classList.remove('active');
        currentCat = '';
        toast('Mostrando todos los productos', 'info');
      } else {
        // Activar nueva categoría
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentCat = catId;
        
        const catName = tab.textContent.trim().split(/\d+/)[0].trim();
        toast(`📂 ${catName}`, 'info');
        
        // Scroll suave hacia el tab activo
        tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
      
      filterAndSort();
      updateResetButton();
    });
  });

  // Click en tarjeta para ver detalles
  $grid.addEventListener('click', e => {
    const card = e.target.closest('.product-card');
    if (!card) return;
    
    // Si NO es un botón de acción, ir a detalle
    const isAction = e.target.closest('.action-btn');
    if (!isAction) {
      const prodId = card.dataset.id;
      window.location.href = `${BASE}views/productos-detal.php?id=${prodId}`;
    }
  }, true);

  // Acciones de productos
  $grid.addEventListener('click', async e => {
    const actionBtn = e.target.closest('.action-btn');
    if (!actionBtn) return;

    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const card = actionBtn.closest('.product-card');
    if (!card) return;

    const prodId = parseInt(card.dataset.id, 10);

    // ❤️ Wishlist
    if (actionBtn.classList.contains('js-wish')) {
      if (!USER_ID) {
        toast('Debes iniciar sesión para agregar favoritos', 'warning');
        setTimeout(() => location.href = BASE + 'views/login.php?next=' + encodeURIComponent(location.pathname), 1500);
        return;
      }

      actionBtn.disabled = true;
      const icon = actionBtn.querySelector('i');
      icon.className = 'fas fa-spinner fa-spin';

      try {
        const res = await fetch(BASE + 'api/wishlist/toggle.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ producto_id: prodId })
        });

        const data = await res.json();
        if (!data.ok) throw new Error(data.msg || 'Error');

        actionBtn.classList.toggle('active', data.in_wishlist);
        icon.className = 'fas fa-heart';
        toast(data.in_wishlist ? 'Agregado a favoritos' : 'Eliminado de favoritos', 'success');
      } catch (err) {
        console.error(err);
        icon.className = 'fas fa-heart';
        toast('Error al actualizar favoritos', 'error');
      } finally {
        actionBtn.disabled = false;
      }
      return;
    }

    // 🔁 Comparar
    if (actionBtn.classList.contains('js-compare')) {
      const id = String(prodId);
      const s = getSet(LS_COMP);
      const wasActive = s.has(id);
      
      if (!wasActive && s.size >= 4) {
        toast('Máximo 4 productos para comparar', 'warning');
        return;
      }
      
      wasActive ? s.delete(id) : s.add(id);
      saveSet(LS_COMP, s);
      
      actionBtn.classList.toggle('active', !wasActive);
      toast(wasActive ? 'Removido de comparación' : `Agregado a comparación (${s.size}/4)`, 'info');
      return;
    }

    // 🛒 Carrito
    if (actionBtn.classList.contains('js-cart')) {
      const cart = getCart();
      const existing = cart.find(item => item.id === prodId);
      
      if (existing) {
        existing.cantidad++;
        toast(`Cantidad actualizada (${existing.cantidad})`, 'success');
      } else {
        cart.push({
          id: prodId,
          nombre: card.dataset.nombre,
          precio: parseFloat(card.dataset.precio),
          imagen: card.dataset.img,
          cantidad: 1
        });
        toast('Agregado al carrito', 'success');
      }
      
      saveCart(cart);
      updateBadge();
      
      // Animación visual
      actionBtn.style.animation = 'heartbeat 0.5s';
      setTimeout(() => actionBtn.style.animation = '', 500);
      return;
    }
  });

  // Escuchar eliminación de productos
  window.addEventListener('productDeleted', e => {
    const productId = e.detail?.productId;
    if (!productId) return;
    
    const card = document.querySelector(`.product-card[data-id="${productId}"]`);
    if (card) {
      card.classList.add('removing');
      setTimeout(() => {
        card.remove();
        allCards = Array.from($grid.querySelectorAll('.product-card'));
        filterAndSort();
        toast('Producto eliminado correctamente', 'success');
      }, 300);
    }
  });

  // Escuchar actualización de productos
  window.addEventListener('productUpdated', e => {
    const productData = e.detail;
    if (!productData?.id) return;
    
    const card = document.querySelector(`.product-card[data-id="${productData.id}"]`);
    if (card) {
      // Actualizar datos de la tarjeta
      if (productData.nombre) card.dataset.nombre = productData.nombre;
      if (productData.precio) card.dataset.precio = productData.precio;
      if (productData.imagen) card.dataset.img = productData.imagen;
      
      // Animación de actualización
      card.style.animation = 'pulse 0.5s';
      setTimeout(() => card.style.animation = '', 500);
      
      toast('Producto actualizado', 'success');
      filterAndSort();
    }
  });

  // Inicializar
  allCards = Array.from($grid.querySelectorAll('.product-card'));
  updateBadge();

  // Cargar estados de comparación
  const compSet = getSet(LS_COMP);
  allCards.forEach(card => {
    const id = String(card.dataset.id);
    const compBtn = card.querySelector('.js-compare');
    if (compBtn && compSet.has(id)) {
      compBtn.classList.add('active');
    }
  });

  // Restaurar vista preferida
  const savedView = localStorage.getItem('productViewMode');
  if (savedView === 'list') {
    isListView = true;
    $grid.classList.add('list-view');
    const btn = document.getElementById('btnViewToggle');
    btn.querySelector('i').className = 'fas fa-list';
    btn.querySelector('.view-text').textContent = 'Lista';
  }

  // Mostrar todos los productos al inicio
  currentCat = '';
  currentSearch = '';
  
  // Log de inicio
  console.log(`✅ Sistema de productos cargado: ${allCards.length} productos disponibles`);
  
  // Trigger inicial para asegurar visibilidad
  setTimeout(() => {
    allCards.forEach(card => card.classList.remove('hidden'));
  }, 100);
})();
</script>
</body>
</html>