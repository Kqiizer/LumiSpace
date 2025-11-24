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
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/footer.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/responsive.css">
  <style>
    :root {
      --brand-dark: #2b241d;
      --brand-primary: #6D5A42;
      --brand-secondary: #A0896B;
      --brand-muted: #c9b8a2;
      --brand-cream: #f9f5ef;
      --brand-white: #ffffff;
      --text-main: #2f2a24;
      --text-soft: #6b6257;
      --shadow: 0 25px 60px rgba(23, 14, 4, 0.12);
      --radius-lg: 28px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(180deg, #fcfbf8 0%, #f3ede4 35%, #f7f2ec 100%);
      color: var(--text-main);
      min-height: 100vh;
    }

    .page-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .main-content {
      flex: 1;
    }

    /* Asegurar que el header y menú sean visibles */
    .header {
      position: sticky !important;
      top: 0 !important;
      z-index: 1000 !important;
    }

    .menu-toggle,
    #menu-btn {
      display: flex !important;
      visibility: visible !important;
      opacity: 1 !important;
    }

    .sidebar {
      z-index: 2000 !important;
    }

    .overlay {
      z-index: 1500 !important;
    }

    .container {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 22px;
    }

    /* HERO */
    .catalog-hero {
      padding: 90px 0 70px;
      position: relative;
      overflow: hidden;
    }

    .hero-bg {
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at top right, rgba(160, 137, 107, 0.35), transparent 55%);
      z-index: 0;
    }

    .hero-grid {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 40px;
      align-items: center;
    }

    .hero-title {
      font-size: clamp(40px, 4.6vw, 64px);
      font-weight: 800;
      line-height: 1.1;
      color: var(--brand-dark);
      letter-spacing: -0.03em;
      margin-bottom: 18px;
    }

    .hero-subtitle {
      font-size: 18px;
      color: var(--text-soft);
      max-width: 520px;
      margin-bottom: 32px;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
    }

    .hero-btn {
      padding: 14px 26px;
      border-radius: 999px;
      border: none;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .hero-btn.primary {
      background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
      color: white;
      box-shadow: 0 12px 30px rgba(109, 90, 66, 0.25);
    }

    .hero-btn.secondary {
      background: transparent;
      border: 1.5px solid rgba(109, 90, 66, 0.3);
      color: var(--brand-primary);
    }

    .hero-btn:hover {
      transform: translateY(-2px);
    }

    .hero-card {
      background: rgba(255, 255, 255, 0.85);
      border-radius: var(--radius-lg);
      padding: 32px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(16px);
    }

    .hero-card h3 {
      font-size: 16px;
      text-transform: uppercase;
      letter-spacing: 3px;
      color: var(--text-soft);
      margin-bottom: 18px;
    }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(2, minmax(120px, 1fr));
      gap: 24px;
    }

    .hero-stat-number {
      font-size: 38px;
      font-weight: 700;
      color: var(--brand-primary);
    }

    .hero-stat-label {
      font-size: 14px;
      color: var(--text-soft);
    }

    /* FILTERS */
    .category-filters {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 40px;
      justify-content: center;
    }

    .filter-chip {
      border: 1px solid transparent;
      border-radius: 999px;
      padding: 8px 18px;
      font-size: 14px;
      font-weight: 600;
      background: rgba(160, 137, 107, 0.12);
      color: var(--brand-primary);
      cursor: pointer;
      transition: all .2s ease;
    }

    .filter-chip.active {
      background: var(--brand-primary);
      color: white;
      box-shadow: 0 12px 25px rgba(109, 90, 66, 0.35);
    }

    .filter-chip:hover {
      opacity: .85;
    }

    /* CATEGORIES */
    .products-section {
      padding: 70px 0 80px;
    }

    .section-header {
      text-align: center;
      margin-bottom: 45px;
    }

    .section-label {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 8px 20px;
      border-radius: 999px;
      background: rgba(109, 90, 66, 0.1);
      color: var(--brand-primary);
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 18px;
    }

    .section-title {
      font-size: clamp(32px, 4vw, 44px);
      font-weight: 800;
      color: var(--brand-dark);
      margin-bottom: 12px;
    }

    .section-description {
      font-size: 18px;
      color: var(--text-soft);
      max-width: 640px;
      margin: 0 auto;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 26px;
    }

    .category-card {
      background: rgba(255,255,255,0.9);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: var(--shadow);
      cursor: pointer;
      display: flex;
      flex-direction: column;
      border: 1px solid rgba(109,90,66,0.08);
      transition: transform .3s ease, box-shadow .3s ease;
    }

    .category-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 35px 70px rgba(36, 25, 12, 0.18);
    }

    .card-media {
      position: relative;
      height: 220px;
      background-size: cover;
      background-position: center;
    }

    .card-media::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent, rgba(0,0,0,0.65));
    }

    .card-badge {
      position: absolute;
      left: 24px;
      bottom: 24px;
      z-index: 2;
      background: rgba(255,255,255,0.88);
      color: var(--brand-primary);
      border-radius: 999px;
      padding: 6px 16px;
      font-size: 13px;
      font-weight: 600;
      box-shadow: 0 12px 24px rgba(0,0,0,0.08);
    }

    .card-body {
      padding: 28px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      flex: 1;
    }

    .card-head {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .card-pill {
      align-self: flex-start;
      font-size: 12px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--brand-secondary);
    }

    .card-title {
      font-size: 28px;
      font-weight: 700;
      color: var(--brand-dark);
      letter-spacing: -0.02em;
    }

    .card-description {
      color: var(--text-soft);
      line-height: 1.6;
      font-size: 15px;
    }

    .tag-list {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .tag-list li {
      font-size: 13px;
      padding: 6px 12px;
      border-radius: 999px;
      background: rgba(160, 137, 107, 0.12);
      color: var(--brand-primary);
    }

    .card-footer {
      padding: 24px 28px 28px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid rgba(109,90,66,0.08);
    }

    .card-meta {
      font-size: 13px;
      color: var(--text-soft);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .ghost-btn {
      padding: 10px 18px;
      border-radius: 999px;
      border: 1.5px solid rgba(109,90,66,0.4);
      background: transparent;
      color: var(--brand-primary);
      font-weight: 600;
      text-decoration: none;
      font-size: 14px;
      transition: all .2s ease;
    }

    .ghost-btn:hover {
      border-color: transparent;
      background: var(--brand-primary);
      color: white;
    }

    /* STATS */
    .statistics {
      padding: 70px 0 90px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 22px;
    }

    .stat-item {
      background: white;
      border-radius: 24px;
      padding: 28px;
      box-shadow: var(--shadow);
      border: 1px solid rgba(109,90,66,0.08);
      text-align: center;
    }

    .stat-icon {
      width: 58px;
      height: 58px;
      margin: 0 auto 18px;
      border-radius: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(160, 137, 107, 0.12);
      color: var(--brand-primary);
      font-size: 20px;
    }

    .stat-number {
      font-size: 34px;
      font-weight: 700;
      color: var(--brand-dark);
    }

    .stat-label {
      font-size: 14px;
      color: var(--text-soft);
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-top: 6px;
    }

    /* CTA */
    .cta-section {
      padding: 110px 0;
      background: linear-gradient(135deg, rgba(109, 90, 66, 0.92), rgba(34, 24, 13, 0.95));
      color: white;
      text-align: center;
    }

    .cta-content h2 {
      font-size: clamp(32px, 4vw, 48px);
      margin-bottom: 16px;
    }

    .cta-content p {
      color: rgba(255,255,255,0.78);
      max-width: 520px;
      margin: 0 auto 40px;
    }

    .cta-buttons {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 18px;
    }

    .cta-btn {
      padding: 14px 30px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 600;
      border: 1px solid transparent;
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .cta-btn.primary {
      background: white;
      color: var(--brand-primary);
    }

    .cta-btn.secondary {
      border-color: rgba(255,255,255,0.4);
      color: white;
    }

    .cta-btn:hover {
      transform: translateY(-3px);
    }

    /* Skeleton */
    .skeleton {
      background: linear-gradient(90deg, rgba(255,255,255,0.2), rgba(255,255,255,0.45), rgba(255,255,255,0.2));
      background-size: 200% 100%;
      animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hero-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .card-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }

      .ghost-btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>">

  <div class="page-wrapper">
    <div class="main-content">
      
      <?php include __DIR__ . "/../includes/header.php"; ?>

      <!-- Hero -->
      <section class="catalog-hero">
        <div class="hero-bg"></div>
        <div class="container">
          <div class="hero-grid">
            <div>
              <h1 class="hero-title">Diseños curados para cada ambiente.</h1>
              <p class="hero-subtitle">
                Navega por nuestras colecciones y encuentra piezas luminosas que combinan artesanía,
                tecnología y calidez en una sola experiencia.
              </p>
              <div class="hero-actions">
                <a href="<?= $BASE ?>views/catalogo.php" class="hero-btn primary">Ver catálogo completo</a>
                <a href="<?= $BASE ?>views/contacto.php" class="hero-btn secondary">Hablar con un especialista</a>
              </div>
            </div>
            <div class="hero-card">
              <h3>Indicadores LumiSpace</h3>
              <div class="hero-stats">
                <div>
                  <div class="hero-stat-number"><?= number_format($stats['productos']) ?></div>
                  <div class="hero-stat-label">Productos en vitrina</div>
                </div>
                <div>
                  <div class="hero-stat-number"><?= number_format($stats['clientes']) ?></div>
                  <div class="hero-stat-label">Clientes felices</div>
                </div>
                <div>
                  <div class="hero-stat-number">72h</div>
                  <div class="hero-stat-label">Promedio de despacho</div>
                </div>
                <div>
                  <div class="hero-stat-number"><?= number_format($stats['pedidos']) ?></div>
                  <div class="hero-stat-label">Pedidos entregados</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Categorías Dinámicas -->
      <section class="products-section">
        <div class="container">
          <div class="section-header">
            <span class="section-label"><i class="fas fa-star"></i> Nuestras Categorías</span>
            <h2 class="section-title">Colecciones pensadas para cada ambiente</h2>
            <p class="section-description">Explora líneas curadas que van desde lo minimalista hasta lo escultórico, con acabados premium y entregas rápidas.</p>
          </div>

          <div class="category-filters">
            <button class="filter-chip active">Todas</button>
            <button class="filter-chip">Interior</button>
            <button class="filter-chip">Exterior</button>
            <button class="filter-chip">Decorativas</button>
            <button class="filter-chip">Smart</button>
          </div>

          <?php
            $collectionImages = [
              $BASE . 'imagenes/lamparas/lampara colgante en cono.jpeg',
              $BASE . 'imagenes/lamparas/lampara colgante plana.jpeg',
              $BASE . 'imagenes/lamparas/lampara de pared verde.jpeg',
            ];
          ?>
          <div class="products-grid" data-base="<?= htmlspecialchars($BASE) ?>">
            <?php if (!empty($categorias_db)): ?>
              <?php foreach ($categorias_db as $cat): 
                $cat_id = (int)$cat['id'];
                $nombre = htmlspecialchars($cat['nombre']);
                $descripcion = htmlspecialchars($cat['descripcion'] ?? 'Encuentra la mejor selección de productos');
                $imagen = getCategoryImage($cat['imagen'] ?? '', $BASE);
                if (!empty($collectionImages)) {
                  $imagen = array_shift($collectionImages);
                }
                $total_productos = $productos_por_categoria[$cat_id] ?? 0;
                $subcats = [];
                if (isset($cat['subcategorias']) && !empty($cat['subcategorias'])) {
                  $subcats = is_string($cat['subcategorias']) ? json_decode($cat['subcategorias'], true) : $cat['subcategorias'];
                }
              ?>
                <div class="category-card" 
                     data-category-id="<?= $cat_id ?>"
                     data-category-name="<?= $nombre ?>"
                     data-products-count="<?= $total_productos ?>">

                  <div class="card-media lazy-bg skeleton" data-bg="<?= htmlspecialchars($imagen) ?>">
                    <span class="card-badge"><?= number_format($total_productos) ?> productos</span>
                  </div>

                  <div class="card-body">
                    <div class="card-head">
                      <span class="card-pill">Colección</span>
                      <h3 class="card-title"><?= $nombre ?></h3>
                    </div>
                    <p class="card-description"><?= $descripcion ?></p>
                    <?php if (!empty($subcats) && is_array($subcats)): ?>
                      <ul class="tag-list">
                        <?php foreach (array_slice($subcats, 0, 4) as $sub): ?>
                          <li><?= htmlspecialchars(is_array($sub) ? ($sub['nombre'] ?? $sub) : $sub) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>

                  <div class="card-footer">
                    <span class="card-meta">
                      <i class="fas fa-truck"></i> 72h despacho promedio
                    </span>
                    <a href="<?= $BASE ?>views/catalogo.php?categoria=<?= $cat_id ?>" class="ghost-btn">Ver colección</a>
                  </div>
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

  <script>
  // Asegurar que el menú funcione correctamente
  document.addEventListener('DOMContentLoaded', function() {
    // Verificar que los elementos del menú existan
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (menuBtn && sidebar && overlay) {
      console.log('✅ Elementos del menú encontrados');
    } else {
      console.warn('⚠️ Algunos elementos del menú no se encontraron:', {
        menuBtn: !!menuBtn,
        sidebar: !!sidebar,
        overlay: !!overlay
      });
    }
  });
  </script>
  <script src="<?= $BASE ?>js/header.js" defer></script>
  <script src="<?= $BASE ?>js/search-overlay.js" defer></script>
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

    document.querySelectorAll('.lazy-bg').forEach(img => {
      imageObserver.observe(img);
    });

    // Click en categoría
    document.querySelectorAll('.category-card').forEach(card => {
      const catId = card.dataset.categoryId;
      const productsCount = parseInt(card.dataset.productsCount || 0);

      if (productsCount === 0) {
        card.style.cursor = 'not-allowed';
        card.style.opacity = 0.6;
        return;
      }

      card.addEventListener('click', (event) => {
        if (event.target.closest('.ghost-btn')) return;
        window.location.href = `${BASE_URL}views/catalogo.php?categoria=${catId}`;
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