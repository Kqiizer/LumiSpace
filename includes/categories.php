<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// Obtener todas las categorías desde la base de datos
$categorias_db = getCategorias();

// Contar productos por categoría
$productos_por_categoria = [];
$conn = getDBConnection();

$check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$has_activo = $check_activo && $check_activo->num_rows > 0;

if ($categorias_db) {
  foreach ($categorias_db as $cat) {
    $cat_id = (int)$cat['id'];
    
    if ($has_activo) {
      $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ? AND activo = 1");
    } else {
      $stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = ?");
    }
    
    if ($stmt) {
      $stmt->bind_param("i", $cat_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $productos_por_categoria[$cat_id] = (int)($row['total'] ?? 0);
      $stmt->close();
    } else {
      $productos_por_categoria[$cat_id] = 0;
    }
  }
}

function getCategoryImage($imagen, $BASE) {
  if (empty($imagen)) return $BASE . 'images/categorias/default.jpg';
  if (preg_match('#^https?://#i', $imagen)) return $imagen;
  if (strpos($imagen, '/') === 0) return $BASE . ltrim($imagen, '/');
  return $BASE . 'images/categorias/' . $imagen;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f5;
      color: #333;
    }

    .products {
      padding: 60px 0;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

    /* Tarjeta de categoría */
    .product-category {
      background: white;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
    }

    .product-category:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }

    /* Header de la categoría */
    .category-header {
      padding: 30px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .item-count {
      display: inline-block;
      font-size: 14px;
      color: #888;
      margin-bottom: 12px;
      font-weight: 500;
    }

    .category-title {
      font-size: 32px;
      font-weight: 700;
      color: #333;
      margin-bottom: 12px;
      line-height: 1.2;
    }

    .category-description {
      font-size: 15px;
      color: #666;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    /* Lista de subcategorías */
    .category-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .category-list li {
      font-size: 14px;
      color: #555;
      line-height: 1.5;
    }

    /* Imagen de la categoría */
    .category-image {
      width: 100%;
      height: 200px;
      background-size: cover;
      background-position: center;
      background-color: #e0e0e0;
      transition: transform 0.3s ease;
    }

    .product-category:hover .category-image {
      transform: scale(1.05);
    }

    /* Estado vacío */
    .empty-state {
      grid-column: 1 / -1;
      text-align: center;
      padding: 80px 20px;
      color: #999;
    }

    .empty-state i {
      font-size: 60px;
      margin-bottom: 20px;
      opacity: 0.3;
    }

    .empty-state h3 {
      font-size: 24px;
      margin-bottom: 10px;
      color: #666;
    }

    /* Skeleton loader */
    .skeleton {
      background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
    }

    @keyframes loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: white;
      color: #333;
      padding: 16px 24px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 10000;
      animation: slideIn 0.3s ease;
    }

    .toast i {
      font-size: 18px;
    }

    .toast.warning i {
      color: #f59e0b;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
      }
    }

    @media (max-width: 768px) {
      .products-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .category-header {
        padding: 24px;
      }

      .category-title {
        font-size: 28px;
      }

      .category-description {
        font-size: 14px;
      }

      .toast {
        left: 20px;
        right: 20px;
      }
    }
  </style>
</head>
<body>

<!-- Categorías de Productos -->
<section class="products">
  <div class="container">
    <div class="products-grid" data-base="<?= htmlspecialchars($BASE) ?>">

      <?php if (!empty($categorias_db)): ?>
        <?php foreach ($categorias_db as $cat): 
          $cat_id = (int)$cat['id'];
          $nombre = htmlspecialchars($cat['nombre']);
          $descripcion = htmlspecialchars($cat['descripcion'] ?? 'Encuentra la mejor selección de productos');
          $imagen = getCategoryImage($cat['imagen'] ?? '', $BASE);
          $total_productos = $productos_por_categoria[$cat_id] ?? 0;
        ?>
          <div class="product-category" 
               data-category-id="<?= $cat_id ?>"
               data-category-name="<?= $nombre ?>"
               data-products-count="<?= $total_productos ?>">
            
            <div class="category-header">
              <div class="item-count"><?= number_format($total_productos) ?>+ Artículos</div>
              <h2 class="category-title"><?= $nombre ?></h2>
              <p class="category-description"><?= $descripcion ?></p>
              
              <?php 
              // Obtener subcategorías si existen
              $subcats = [];
              if (isset($cat['subcategorias']) && !empty($cat['subcategorias'])) {
                $subcats = is_string($cat['subcategorias']) ? json_decode($cat['subcategorias'], true) : $cat['subcategorias'];
              }
              
              if (!empty($subcats) && is_array($subcats)):
              ?>
                <ul class="category-list">
                  <?php foreach (array_slice($subcats, 0, 4) as $sub): ?>
                    <li><?= htmlspecialchars(is_array($sub) ? ($sub['nombre'] ?? $sub) : $sub) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <div class="category-image skeleton" data-bg="<?= htmlspecialchars($imagen) ?>"></div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <i class="fas fa-box-open"></i>
          <h3>No hay categorías disponibles</h3>
          <p>Las categorías aparecerán aquí cuando se agreguen</p>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<script>
(()=>{
  const BASE_URL = document.querySelector('.products-grid')?.dataset.base || '/';
  
  // Toast notification
  const showToast = (message, type = 'info') => {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    
    const icons = {
      info: 'fa-info-circle',
      warning: 'fa-exclamation-triangle',
      success: 'fa-check-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <i class="fas ${icons[type] || icons.info}"></i>
      <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  };
  
  // Lazy loading de imágenes
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
            img.style.backgroundImage = `url('${BASE_URL}images/default.png')`;
            img.classList.remove('skeleton');
          };
          testImg.src = bgUrl;
        }
        
        imageObserver.unobserve(img);
      }
    });
  });

  document.querySelectorAll('.category-image').forEach(img => {
    imageObserver.observe(img);
  });

  // Click en categoría
  document.querySelectorAll('.product-category').forEach(card => {
    const catId = card.dataset.categoryId;
    const catName = card.dataset.categoryName;
    const productsCount = parseInt(card.dataset.productsCount || 0);

    card.addEventListener('click', () => {
      if (productsCount === 0) {
        showToast('Esta categoría no tiene productos disponibles', 'warning');
        return;
      }

      // Redirigir a la página de categoría
      window.location.href = `${BASE_URL}views/categoria.php?id=${catId}`;
    });
  });

  // Eventos para admin
  window.addEventListener('categoryAdded', () => {
    showToast('Nueva categoría agregada', 'success');
    setTimeout(() => location.reload(), 1500);
  });

  window.addEventListener('categoryUpdated', () => {
    showToast('Categoría actualizada', 'success');
    setTimeout(() => location.reload(), 1500);
  });

  window.addEventListener('categoryDeleted', () => {
    showToast('Categoría eliminada', 'success');
    setTimeout(() => location.reload(), 1500);
  });

  console.log('✅ Categorías cargadas:', document.querySelectorAll('.product-category').length);
})();
</script>
</body>
</html>