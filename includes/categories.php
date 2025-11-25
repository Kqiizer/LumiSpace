<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// Obtener todas las categorías desde la base de datos
$categorias_db = getCategorias();

// Contar productos por categoría de manera más eficiente
$productos_por_categoria = [];
$conn = getDBConnection();

// Verificar si existe la columna 'activo'
$check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$has_activo = $check_activo && $check_activo->num_rows > 0;

if ($categorias_db && is_array($categorias_db)) {
  // Obtener todos los conteos en una sola consulta para mejor rendimiento
  $cat_ids = array_map(function ($cat) {
    return (int) $cat['id']; }, $categorias_db);
  $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));

  $where_clause = $has_activo ? "AND activo = 1" : "";
  $sql = "SELECT categoria_id, COUNT(*) as total FROM productos 
          WHERE categoria_id IN ($placeholders) $where_clause 
          GROUP BY categoria_id";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $types = str_repeat('i', count($cat_ids));
    $stmt->bind_param($types, ...$cat_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
      $productos_por_categoria[(int) $row['categoria_id']] = (int) $row['total'];
    }
    $stmt->close();
  }

  // Inicializar en 0 las categorías sin productos
  foreach ($cat_ids as $cat_id) {
    if (!isset($productos_por_categoria[$cat_id])) {
      $productos_por_categoria[$cat_id] = 0;
    }
  }
}

/**
 * Obtiene la URL de la imagen de categoría
 * Maneja diferentes formatos de rutas de imágenes
 */
function getCategoryImage($imagen, $BASE)
{
  $defaultImage = $BASE . 'images/categorias/default.jpg';

  if (empty($imagen) || trim($imagen) === '') {
    return $defaultImage;
  }

  $imagen = trim($imagen);

  // Si ya es una URL completa (http o https), devolverla tal cual
  if (preg_match('#^https?://#i', $imagen)) {
    return $imagen;
  }

  // Si empieza con /, es una ruta absoluta desde la raíz
  if (strpos($imagen, '/') === 0) {
    return $BASE . ltrim($imagen, '/');
  }

  // Si la imagen ya contiene una ruta relativa completa (ej: imagenes/lamparas/deco2.jpg)
  // o empieza con imagenes/ o images/, usarla directamente
  if (strpos($imagen, 'imagenes/') === 0 || strpos($imagen, 'images/') === 0) {
    return $BASE . $imagen;
  }

  // Si es solo un nombre de archivo, asumir que está en images/categorias/
  return $BASE . 'images/categorias/' . $imagen;
}

/**
 * Procesa las subcategorías
 */
function getSubcategorias($subcats_data)
{
  if (empty($subcats_data)) {
    return [];
  }

  if (is_string($subcats_data)) {
    $decoded = json_decode($subcats_data, true);
    return is_array($decoded) ? $decoded : [];
  }

  return is_array($subcats_data) ? $subcats_data : [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categorías - Tienda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #6D5A42;
      --primary-dark: #4f4433;
      --primary-light: #8A7458;
      --accent: #A0896B;
      --accent-light: #c4b59b;
      --bg-light: #F7F2EA;
      --bg-dark: #1e1912;
      --text-light: #2b241b;
      --text-dark: #fdfaf5;
      --text-muted: #7a6b58;
      --border-color: rgba(109, 90, 66, 0.15);
      --card-bg: #ffffff;
      --glass: rgba(255, 255, 255, 0.7);
      --shadow-sm: 0 4px 16px rgba(34, 23, 8, 0.08);
      --shadow-md: 0 12px 30px rgba(34, 23, 8, 0.12);
      --shadow-lg: 0 24px 60px rgba(34, 23, 8, 0.14);
      --shadow-xl: 0 40px 80px rgba(34, 23, 8, 0.18);
      --radius-sm: 10px;
      --radius-md: 20px;
      --radius-lg: 30px;
      --transition-smooth: all 0.45s cubic-bezier(0.4, 0, 0.2, 1);
      --transition-spring: all 0.7s cubic-bezier(0.34, 1.56, 0.64, 1);
      --gradient-sand: linear-gradient(135deg, #fdf7f0 0%, #f3ebe0 40%, #efe4d7 100%);
      --gradient-primary: linear-gradient(135deg, #6D5A42 0%, #A0896B 100%);
      --gradient-dark: linear-gradient(135deg, rgba(109, 90, 66, 0.85), rgba(46, 37, 26, 0.95));
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', sans-serif;
      background: var(--gradient-sand);
      color: var(--text-light);
      line-height: 1.7;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    .products {
      padding: 60px 0;
      min-height: 60vh;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
      position: relative;
      padding: 20px 0 40px;
    }

    .section-header::before {
      content: '';
      position: absolute;
      inset: 0;
      margin: auto;
      width: 260px;
      height: 260px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(160, 137, 107, 0.12), transparent 65%);
      filter: blur(8px);
      z-index: -1;
    }

    .section-header h1 {
      font-size: clamp(36px, 5vw, 50px);
      font-weight: 800;
      color: var(--text-light);
      margin-bottom: 18px;
      letter-spacing: -0.02em;
      position: relative;
      display: inline-flex;
      gap: 14px;
      align-items: center;
    }

    .section-header h1::after {
      content: '';
      width: 90px;
      height: 4px;
      border-radius: 999px;
      background: var(--gradient-primary);
      display: inline-block;
    }

    .section-header p {
      font-size: 18px;
      color: var(--text-muted);
      max-width: 640px;
      margin: 0 auto;
      margin-top: 18px;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
      gap: 30px;
    }

    /* Grid de 3 columnas para la página de inicio */
    .products-grid.home-categories {
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
      max-width: 1400px;
      margin: 0 auto;
    }

    @media (max-width: 1024px) {
      .products-grid.home-categories {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .products-grid.home-categories {
        grid-template-columns: 1fr;
      }
    }

    /* Tarjeta de categoría */
    .product-category {
      background: var(--card-bg);
      border-radius: var(--radius-md);
      overflow: hidden;
      cursor: pointer;
      transition: var(--transition-smooth);
      box-shadow: var(--shadow-sm);
      display: flex;
      flex-direction: column;
      position: relative;
      border: 1px solid var(--border-color);
      backdrop-filter: blur(6px);
    }

    .product-category::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(160, 137, 107, 0.08), transparent 50%);
      opacity: 0;
      transition: var(--transition-smooth);
    }

    .product-category:hover {
      transform: translateY(-10px) scale(1.01);
      box-shadow: var(--shadow-lg);
      border-color: rgba(160, 137, 107, 0.45);
    }

    .product-category:hover::before {
      opacity: 1;
    }

    .product-category.no-products {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .product-category.no-products:hover {
      transform: none;
      box-shadow: var(--shadow-sm);
      border-color: var(--border-color);
    }

    /* Badge de productos */
    .products-badge {
      position: absolute;
      top: 20px;
      right: 20px;
      background: var(--glass);
      backdrop-filter: blur(14px);
      padding: 10px 18px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      color: var(--text-light);
      box-shadow: var(--shadow-md);
      z-index: 10;
      border: 1px solid rgba(109, 90, 66, 0.25);
      transition: var(--transition-smooth);
    }

    .product-category:hover .products-badge {
      background: var(--primary);
      color: white;
      border-color: transparent;
    }

    .products-badge.empty {
      background: rgba(239, 68, 68, 0.1);
      color: #dc2626;
      border-color: rgba(239, 68, 68, 0.3);
    }

    .product-category:hover .products-badge.empty {
      background: rgba(239, 68, 68, 0.15);
      color: #dc2626;
      border-color: rgba(239, 68, 68, 0.4);
    }

    /* Imagen de la categoría */
    .category-image-wrapper {
      width: 100%;
      height: 260px;
      overflow: hidden;
      position: relative;
      background: var(--gradient-dark);
    }

    .category-image {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    /* Clase lazy-bg para compatibilidad con la página de categorías */
    .lazy-bg {
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    .product-category:hover .category-image {
      transform: scale(1.15) rotate(2deg);
    }

    /* Overlay gradient */
    .category-image-wrapper::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 65%;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
      pointer-events: none;
      transition: var(--transition-smooth);
    }

    .product-category:hover .category-image-wrapper::after {
      background: linear-gradient(to top, rgba(160, 137, 107, 0.65), transparent);
    }

    /* Header de la categoría */
    .category-header {
      padding: 32px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .category-title {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 30px;
      font-weight: 700;
      color: var(--text-light);
      margin-bottom: 12px;
      line-height: 1.3;
      letter-spacing: -0.01em;
    }

    .category-description {
      font-size: 15px;
      color: var(--text-muted);
      margin-bottom: 24px;
      line-height: 1.6;
      flex: 1;
    }

    /* Lista de subcategorías */
    .category-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .category-list li {
      font-size: 14px;
      color: var(--text-muted);
      line-height: 1.5;
      padding-left: 24px;
      position: relative;
      transition: var(--transition-smooth);
    }

    .product-category:hover .category-list li {
      color: var(--text-light);
    }

    .category-list li::before {
      content: '→';
      position: absolute;
      left: 0;
      color: var(--primary);
      font-weight: bold;
      transition: var(--transition-smooth);
    }

    .product-category:hover .category-list li::before {
      left: 4px;
      color: var(--accent);
    }

    /* Footer de la categoría */
    .category-footer {
      padding: 24px 32px;
      background: rgba(247, 242, 234, 0.85);
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1px solid var(--border-color);
      transition: var(--transition-smooth);
    }

    .product-category:hover .category-footer {
      background: linear-gradient(135deg, rgba(109, 90, 66, 0.1), rgba(160, 137, 107, 0.12));
    }

    .view-products-btn {
      color: var(--primary);
      font-weight: 600;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: var(--transition-smooth);
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .product-category:hover .view-products-btn {
      color: var(--primary-dark);
      gap: 16px;
    }

    .view-products-btn i {
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .product-category:hover .view-products-btn i {
      transform: translateX(6px) scale(1.1);
    }

    /* Estado vacío */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 120px 20px;
      color: var(--text-muted);
    }

    .empty-state i {
      font-size: 80px;
      margin-bottom: 30px;
      opacity: 0.15;
      color: var(--primary-light);
    }

    .empty-state h3 {
      font-family: 'Playfair Display', Georgia, serif;
      font-size: 32px;
      margin-bottom: 16px;
      color: var(--text-light);
      font-weight: 700;
    }

    .empty-state p {
      font-size: 16px;
      color: var(--text-muted);
    }

    /* Skeleton loader */
    .skeleton {
      background: linear-gradient(90deg,
          var(--border-color) 25%,
          var(--bg-light) 50%,
          var(--border-color) 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
      position: relative;
    }

    .skeleton::after {
      content: '';
      position: absolute;
      inset: 0;
      background: rgba(161, 104, 58, 0.02);
    }

    @keyframes loading {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    /* Toast mejorado */
    .toast {
      position: fixed;
      bottom: 32px;
      right: 32px;
      background: white;
      color: var(--text-light);
      padding: 20px 26px;
      border-radius: var(--radius-sm);
      box-shadow: var(--shadow-xl);
      display: flex;
      align-items: center;
      gap: 16px;
      z-index: 10000;
      animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      min-width: 320px;
      border: 1px solid var(--border-color);
      border-left: 4px solid var(--primary);
    }

    .toast.warning {
      border-left-color: #f59e0b;
    }

    .toast.success {
      border-left-color: #10b981;
    }

    .toast.error {
      border-left-color: #ef4444;
    }

    .toast i {
      font-size: 20px;
      flex-shrink: 0;
    }

    .toast.warning i {
      color: #f59e0b;
    }

    .toast.success i {
      color: #10b981;
    }

    .toast.error i {
      color: #ef4444;
    }

    .toast-message {
      flex: 1;
      font-size: 15px;
      font-weight: 500;
      line-height: 1.5;
    }

    .toast-close {
      cursor: pointer;
      color: var(--text-muted);
      font-size: 18px;
      padding: 4px;
      transition: var(--transition-smooth);
      border-radius: 4px;
    }

    .toast-close:hover {
      color: var(--text-light);
      background: var(--bg-light);
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    /* Loading spinner */
    .loading-overlay {
      position: fixed;
      inset: 0;
      background: rgba(250, 250, 248, 0.98);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }

    .loading-overlay.active {
      opacity: 1;
      pointer-events: all;
    }

    .spinner {
      width: 60px;
      height: 60px;
      border: 4px solid var(--border-color);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
      }
    }

    @media (max-width: 768px) {
      .products {
        padding: 40px 0;
      }

      .section-header h1 {
        font-size: 32px;
      }

      .section-header p {
        font-size: 16px;
      }

      .products-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .category-image-wrapper {
        height: 200px;
      }

      .category-header {
        padding: 24px;
      }

      .category-title {
        font-size: 24px;
      }

      .category-description {
        font-size: 14px;
      }

      .toast {
        left: 20px;
        right: 20px;
        min-width: auto;
      }
    }

    @media (max-width: 480px) {
      .section-header h1 {
        font-size: 28px;
      }

      .products-badge {
        top: 15px;
        right: 15px;
        padding: 6px 12px;
        font-size: 12px;
      }
    }
  </style>
</head>

<body>

  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
    <div class="spinner"></div>
  </div>

  <!-- Categorías de Productos -->
  <section class="products">
    <div class="container">

      <div class="section-header">
        <h1>Nuestras Categorías</h1>
        <p>Explora nuestra amplia selección de productos</p>
      </div>

      <?php
      // Detectar si estamos en la página de inicio
      $isHomePage = basename($_SERVER['SCRIPT_NAME']) === 'index.php';

      // Limitar a 3 categorías en la página de inicio
      if ($isHomePage && is_array($categorias_db)) {
        $categorias_db = array_slice($categorias_db, 0, 3);
      }
      ?>
      <div class="products-grid <?= $isHomePage ? 'home-categories' : '' ?>" data-base="<?= htmlspecialchars($BASE) ?>">

        <?php if (!empty($categorias_db) && is_array($categorias_db)): ?>
          <?php foreach ($categorias_db as $cat):
            $cat_id = (int) $cat['id'];
            $nombre = htmlspecialchars($cat['nombre'] ?? 'Sin nombre');
            $descripcion = htmlspecialchars($cat['descripcion'] ?? 'Encuentra la mejor selección de productos');

            // Obtener la imagen de la categoría (exactamente como en views/categorias.php)
            // Usar 'imagen' o 'featured_image' según lo que exista en la BD
            $categoryImage = $cat['imagen'] ?? $cat['featured_image'] ?? '';
            $imagen = getCategoryImage($categoryImage, $BASE);

            $total_productos = $productos_por_categoria[$cat_id] ?? 0;
            $subcats = getSubcategorias($cat['subcategorias'] ?? null);
            ?>
            <div class="product-category <?= $total_productos === 0 ? 'no-products' : '' ?>"
              data-category-id="<?= $cat_id ?>" data-category-name="<?= $nombre ?>"
              data-products-count="<?= $total_productos ?>" role="button" tabindex="0"
              aria-label="Ver productos de <?= $nombre ?>">

              <div class="category-image-wrapper">
                <div class="products-badge <?= $total_productos === 0 ? 'empty' : '' ?>">
                  <?= $total_productos === 0 ? 'Sin productos' : number_format($total_productos) . ' producto' . ($total_productos !== 1 ? 's' : '') ?>
                </div>
                <div class="category-image lazy-bg skeleton" data-bg="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>"
                  style="background-image: url('<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>');"></div>
              </div>

              <div class="category-header">
                <h2 class="category-title"><?= $nombre ?></h2>
                <p class="category-description"><?= $descripcion ?></p>

                <?php if (!empty($subcats) && is_array($subcats)): ?>
                  <ul class="category-list">
                    <?php
                    $display_subcats = array_slice($subcats, 0, 4);
                    foreach ($display_subcats as $sub):
                      $sub_name = is_array($sub) ? ($sub['nombre'] ?? $sub) : $sub;
                      ?>
                      <li><?= htmlspecialchars($sub_name) ?></li>
                    <?php endforeach; ?>

                    <?php if (count($subcats) > 4): ?>
                      <li style="color: var(--primary); font-weight: 600; letter-spacing: 0.3px;">
                        +<?= count($subcats) - 4 ?> más...
                      </li>
                    <?php endif; ?>
                  </ul>
                <?php endif; ?>
              </div>

              <?php if ($total_productos > 0): ?>
                <div class="category-footer">
                  <span class="view-products-btn">
                    Ver productos <i class="fas fa-arrow-right"></i>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No hay categorías disponibles</h3>
            <p>Las categorías aparecerán aquí cuando se agreguen al sistema</p>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <script>
    (function () {
      'use strict';

      const BASE_URL = document.querySelector('.products-grid')?.dataset.base || '/';
      const loadingOverlay = document.getElementById('loadingOverlay');

      /**
       * Muestra un toast de notificación
       */
      function showToast(message, type = 'info', duration = 3000) {
        // Remover toast existente
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const icons = {
          info: 'fa-info-circle',
          warning: 'fa-exclamation-triangle',
          success: 'fa-check-circle',
          error: 'fa-times-circle'
        };

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
      <i class="fas ${icons[type] || icons.info}"></i>
      <div class="toast-message">${message}</div>
      <i class="fas fa-times toast-close"></i>
    `;

        document.body.appendChild(toast);

        // Click en cerrar
        toast.querySelector('.toast-close').addEventListener('click', () => {
          hideToast(toast);
        });

        // Auto ocultar
        const timeoutId = setTimeout(() => {
          hideToast(toast);
        }, duration);

        // Guardar timeout para poder cancelarlo
        toast.dataset.timeoutId = timeoutId;
      }

      /**
       * Oculta el toast con animación
       */
      function hideToast(toast) {
        if (toast.dataset.timeoutId) {
          clearTimeout(parseInt(toast.dataset.timeoutId));
        }

        toast.style.opacity = '0';
        toast.style.transform = 'translateY(20px) scale(0.95)';

        setTimeout(() => {
          toast.remove();
        }, 300);
      }

      /**
       * Muestra/oculta el overlay de carga
       */
      function toggleLoading(show = true) {
        if (loadingOverlay) {
          if (show) {
            loadingOverlay.classList.add('active');
          } else {
            loadingOverlay.classList.remove('active');
          }
        }
      }

      /**
       * Lazy loading de imágenes con IntersectionObserver
       */
      function initImageLazyLoading() {
        const imageObserver = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const img = entry.target;
              const bgUrl = img.dataset.bg;

              if (bgUrl) {
                const testImg = new Image();

                testImg.onload = () => {
                  img.style.backgroundImage = `url('${bgUrl}')`;
                  img.classList.remove('skeleton');
                };

                testImg.onerror = () => {
                  // Imagen por defecto si falla la carga
                  img.style.backgroundImage = `url('${BASE_URL}images/categorias/default.jpg')`;
                  img.classList.remove('skeleton');
                  console.warn('Error cargando imagen:', bgUrl);
                };

                testImg.src = bgUrl;
              } else {
                img.classList.remove('skeleton');
              }

              imageObserver.unobserve(img);
            }
          });
        }, {
          rootMargin: '50px' // Cargar imágenes 50px antes de entrar en viewport
        });

        // Observar tanto .category-image como .lazy-bg
        document.querySelectorAll('.category-image, .lazy-bg').forEach(img => {
          // Si estamos en la página de inicio, cargar imágenes inmediatamente
          const isHomePage = document.querySelector('.products-grid.home-categories') !== null;
          if (isHomePage) {
            const bgUrl = img.dataset.bg;
            if (bgUrl) {
              const testImg = new Image();
              testImg.onload = () => {
                img.style.backgroundImage = `url('${bgUrl}')`;
                img.classList.remove('skeleton');
              };
              testImg.onerror = () => {
                img.style.backgroundImage = `url('${BASE_URL}images/categorias/default.jpg')`;
                img.classList.remove('skeleton');
              };
              testImg.src = bgUrl;
            } else {
              img.classList.remove('skeleton');
            }
          } else {
            // En otras páginas, usar lazy loading
            imageObserver.observe(img);
          }
        });
      }

      /**
       * Navegar a la página de categoría
       */
      function navigateToCategory(categoryId, categoryName, productsCount) {
        if (productsCount === 0) {
          showToast('Esta categoría aún no tiene productos disponibles', 'warning');
          return;
        }

        toggleLoading(true);

        // Agregar pequeño delay para mejor UX
        setTimeout(() => {
          window.location.href = BASE_URL + "views/categoria.php?id=" + categoryId;
        }, 200);
      }

      /**
       * Inicializar clicks en categorías
       */
      function initCategoryClicks() {
        document.querySelectorAll('.product-category').forEach(card => {
          const catId = card.dataset.categoryId;
          const catName = card.dataset.categoryName;
          const productsCount = parseInt(card.dataset.productsCount || 0);

          // Click con mouse
          card.addEventListener('click', (e) => {
            // Prevenir navegación si se clickea en elementos específicos
            if (e.target.closest('.toast-close')) return;

            navigateToCategory(catId, catName, productsCount);
          });

          // Soporte para teclado (accesibilidad)
          card.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              navigateToCategory(catId, catName, productsCount);
            }
          });
        });
      }

      /**
       * Eventos personalizados para admin
       */
      function initCustomEvents() {
        window.addEventListener('categoryAdded', () => {
          showToast('Nueva categoría agregada correctamente', 'success');
          setTimeout(() => location.reload(), 1500);
        });

        window.addEventListener('categoryUpdated', () => {
          showToast('Categoría actualizada correctamente', 'success');
          setTimeout(() => location.reload(), 1500);
        });

        window.addEventListener('categoryDeleted', () => {
          showToast('Categoría eliminada correctamente', 'success');
          setTimeout(() => location.reload(), 1500);
        });
      }

      /**
       * Inicialización
       */
      function init() {
        initImageLazyLoading();
        initCategoryClicks();
        initCustomEvents();

        const categoryCount = document.querySelectorAll('.product-category').length;
        console.log('✅ Sistema de categorías inicializado');
        console.log(`📦 Categorías cargadas: ${categoryCount}`);

        // Ocultar loading si estaba visible
        toggleLoading(false);
      }

      // Ejecutar cuando el DOM esté listo
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
      } else {
        init();
      }

    })();
  </script>
</body>

</html>