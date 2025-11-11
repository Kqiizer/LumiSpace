<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// Obtener todas las categorías dinámicamente desde la BD
$categorias_db = getCategorias();
$productos_por_categoria = [];

// Contar productos por categoría
$conn = getDBConnection();
$check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$has_activo = $check_activo && $check_activo->num_rows > 0;

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
  }
}

// Obtener estadísticas
$stats = [
  'productos' => 0,
  'clientes' => 0,
  'pedidos' => 0
];

$result = $conn->query("SELECT COUNT(*) as total FROM productos");
if ($result) $stats['productos'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol != 'admin'");
if ($result) $stats['clientes'] = $result->fetch_assoc()['total'];

$check_pedidos = $conn->query("SHOW TABLES LIKE 'pedidos'");
if ($check_pedidos && $check_pedidos->num_rows > 0) {
  $result = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'entregado'");
  if ($result) $stats['pedidos'] = $result->fetch_assoc()['total'];
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
  <title>Catálogo - LumiSpace</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/footer.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/responsive.css">

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

    .page-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .main-content {
      flex: 1;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Hero Section */
    .catalog-hero {
      background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
      padding: 100px 0 80px;
      color: white;
      text-align: center;
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
    }

    .hero-title {
      font-size: 56px;
      font-weight: 800;
      margin-bottom: 20px;
      letter-spacing: -1px;
    }

    .hero-subtitle {
      font-size: 20px;
      opacity: 0.95;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Categorías Grid */
    .products-section {
      padding: 80px 0;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-label {
      display: inline-block;
      padding: 8px 20px;
      background: rgba(161, 104, 58, 0.1);
      color: #a1683a;
      border-radius: 50px;
      font-weight: 700;
      font-size: 14px;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 16px;
    }

    .section-title {
      font-size: 42px;
      font-weight: 800;
      color: #333;
      margin-bottom: 16px;
    }

    .section-description {
      font-size: 18px;
      color: #666;
      max-width: 600px;
      margin: 0 auto;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 30px;
    }

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

    .category-header {
      padding: 30px;
      flex: 1;
    }

    .item-count {
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
    }

    .category-description {
      font-size: 15px;
      color: #666;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    .category-list {
      list-style: none;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .category-list li {
      font-size: 14px;
      color: #555;
    }

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

    .skeleton {
      background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%);
      background-size: 200% 100%;
      animation: loading 1.5s infinite;
    }

    @keyframes loading {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    /* Estadísticas */
    .statistics {
      padding: 80px 0;
      background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
      position: relative;
      overflow: hidden;
    }

    .statistics::before {
      content: '';
      position: absolute;
      bottom: -50%;
      left: -10%;
      width: 600px;
      height: 600px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 40px;
      position: relative;
      z-index: 1;
    }

    .stat-item {
      text-align: center;
      color: white;
      padding: 30px 20px;
      transition: transform 0.3s ease;
    }

    .stat-item:hover {
      transform: translateY(-10px);
    }

    .stat-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .stat-item:hover .stat-icon {
      background: rgba(255, 255, 255, 0.25);
      transform: scale(1.1);
    }

    .stat-icon i {
      font-size: 36px;
    }

    .stat-number {
      font-size: 56px;
      font-weight: 800;
      line-height: 1;
      margin-bottom: 8px;
    }

    .stat-label {
      font-size: 18px;
      font-weight: 500;
      opacity: 0.95;
      margin-top: 12px;
    }

    /* CTA Section */
    .cta-section {
      padding: 100px 0;
      background: #f8f6f3;
      text-align: center;
    }

    .cta-content h2 {
      font-size: 42px;
      font-weight: 800;
      margin-bottom: 20px;
      color: #333;
    }

    .cta-content p {
      font-size: 18px;
      color: #666;
      margin-bottom: 40px;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .cta-buttons {
      display: flex;
      gap: 20px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .cta-btn {
      padding: 16px 36px;
      border-radius: 8px;
      font-weight: 700;
      font-size: 16px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
    }

    .cta-btn.primary {
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: white;
    }

    .cta-btn.primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(161, 104, 58, 0.3);
    }

    .cta-btn.secondary {
      background: white;
      color: #a1683a;
      border: 2px solid #a1683a;
    }

    .cta-btn.secondary:hover {
      background: #a1683a;
      color: white;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .products-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
      }
    }

    @media (max-width: 768px) {
      .hero-title {
        font-size: 42px;
      }

      .hero-subtitle {
        font-size: 18px;
      }

      .products-grid {
        grid-template-columns: 1fr;
      }

      .stats-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .section-title {
        font-size: 32px;
      }

      .category-title {
        font-size: 28px;
      }

      .cta-content h2 {
        font-size: 32px;
      }

      .cta-buttons {
        flex-direction: column;
        align-items: stretch;
      }

      .cta-btn {
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <div class="page-wrapper">
    <div class="main-content">
      
      <?php include __DIR__ . "/../includes/header.php"; ?>

      <!-- Hero -->
      <section class="catalog-hero">
        <div class="container">
          <div class="hero-content">
            <h1 class="hero-title">Ilumina Tu Mundo</h1>
            <p class="hero-subtitle">Descubre nuestra colección de luminarias diseñadas para transformar cada espacio</p>
          </div>
        </div>
      </section>

      <!-- Categorías Dinámicas -->
      <section class="products-section">
        <div class="container">
          <div class="section-header">
            <span class="section-label">Nuestras Categorías</span>
            <h2 class="section-title">Explora Por Categoría</h2>
            <p class="section-description">Encuentra exactamente lo que necesitas navegando por nuestras categorías especializadas</p>
          </div>

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
              <p style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #999;">
                No hay categorías disponibles
              </p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- Estadísticas -->
      <section class="statistics">
        <div class="container">
          <div class="stats-grid">
            
            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-box"></i>
              </div>
              <div class="stat-number" data-target="<?= $stats['productos'] ?>">0</div>
              <div class="stat-label">Productos Disponibles</div>
            </div>

            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="stat-number" data-target="<?= $stats['clientes'] ?>">0</div>
              <div class="stat-label">Clientes Satisfechos</div>
            </div>

            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-award"></i>
              </div>
              <div class="stat-number" data-target="15">0</div>
              <div class="stat-label">Años de Experiencia</div>
            </div>

            <div class="stat-item">
              <div class="stat-icon">
                <i class="fas fa-shipping-fast"></i>
              </div>
              <div class="stat-number" data-target="<?= $stats['pedidos'] ?>">0</div>
              <div class="stat-label">Pedidos Entregados</div>
            </div>

          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="cta-section">
        <div class="container">
          <div class="cta-content">
            <h2>¿Listo para Iluminar Tu Espacio?</h2>
            <p>Descubre nuestras ofertas exclusivas y transforma tu hogar hoy mismo</p>
            <div class="cta-buttons">
              <a href="<?= $BASE ?>index.php" class="cta-btn primary">
                <i class="fas fa-shopping-bag"></i> Ver Todos los Productos
              </a>
              <a href="<?= $BASE ?>views/contacto.php" class="cta-btn secondary">
                <i class="fas fa-phone"></i> Contactar Asesor
              </a>
            </div>
          </div>
        </div>
      </section>

    </div>

    <?php include __DIR__ . "/../includes/footer.php"; ?>
  </div>

  <script src="<?= $BASE ?>js/header.js"></script>
  <script>
  (()=>{
    const BASE_URL = document.querySelector('.products-grid')?.dataset.base || '/';
    
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
      const productsCount = parseInt(card.dataset.productsCount || 0);

      card.addEventListener('click', () => {
        if (productsCount === 0) return;
        window.location.href = `${BASE_URL}views/categoria.php?id=${catId}`;
      });
    });

    // Animación de contadores
    const animateCounter = (element, target) => {
      const duration = 2000;
      const increment = target / (duration / 16);
      let current = 0;

      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          element.textContent = target.toLocaleString();
          clearInterval(timer);
        } else {
          element.textContent = Math.floor(current).toLocaleString();
        }
      }, 16);
    };

    const statsObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const numberElement = entry.target.querySelector('.stat-number');
          const target = parseInt(numberElement.dataset.target);
          animateCounter(numberElement, target);
          statsObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.3 });

    document.querySelectorAll('.stat-item').forEach(item => {
      statsObserver.observe(item);
    });

    console.log('✅ Catálogo cargado');
  })();
  </script>
  <script src="<?= $BASE ?>js/translator.js" defer></script>
</body>
</html>