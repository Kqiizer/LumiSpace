<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

// Traer productos dinámicos por categoría
$productosExteriores   = getProductosCatalogo("Exteriores");
$productosDecorativo   = getProductosCatalogo("Decorativo");
$productosIluminacion  = getProductosCatalogo("Iluminación");
$productosTodos        = getProductosCatalogo(null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogo - LumiSpace</title>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- ✅ Estilos globales -->
  <link rel="stylesheet" href="../css/styles/reset.css">
  <link rel="stylesheet" href="../css/styles/header.css">
  <link rel="stylesheet" href="../css/styles/sidebar.css">
  <link rel="stylesheet" href="../css/styles/footer.css">
  <link rel="stylesheet" href="../css/styles/responsive.css">

  <!-- ✅ Estilos propios del catálogo -->
  <link rel="stylesheet" href="../css/styles/categorias.css">
</head>
<body>

  <div class="page-wrapper">
    <div class="main-content">
      
      <!-- ✅ Header dinámico -->
      <?php include "../includes/header.php"; ?>

      <!-- 🎨 Hero -->
      <section class="catalog-hero">
        <div class="container">
          <div class="hero-content">
            <h1 class="hero-title">Ilumina Tu Mundo</h1>
            <p class="hero-subtitle">Descubre nuestra colección de luminarias diseñadas para transformar cada espacio</p>
          </div>
        </div>
      </section>

      <!-- 🎨 Categorías principales -->
      <section class="main-categories">
        <div class="container">
          <div class="categories-grid">

            <!-- Exteriores -->
            <div class="category-card large">
              <div class="category-image">
                <div class="image-placeholder"><i class="fas fa-home"></i></div>
                <div class="category-overlay">
                  <div class="category-info">
                    <div class="category-icon"><i class="fas fa-umbrella-beach"></i></div>
                    <h2 class="category-title">Exteriores</h2>
                    <p class="category-count"><?php echo count($productosExteriores); ?> Productos</p>
                    <a href="#exteriores" class="explore-btn">
                      Explorar Colección <i class="fas fa-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Decorativo -->
            <div class="category-card">
              <div class="category-image">
                <div class="image-placeholder decorative"><i class="fas fa-star"></i></div>
                <div class="category-overlay">
                  <div class="category-info">
                    <div class="category-icon"><i class="fas fa-palette"></i></div>
                    <h2 class="category-title">Decorativo</h2>
                    <p class="category-count"><?php echo count($productosDecorativo); ?> Productos</p>
                    <a href="#decorativo" class="explore-btn">
                      Explorar <i class="fas fa-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Iluminación -->
            <div class="category-card">
              <div class="category-image">
                <div class="image-placeholder lighting"><i class="fas fa-lightbulb"></i></div>
                <div class="category-overlay">
                  <div class="category-info">
                    <div class="category-icon"><i class="fas fa-sun"></i></div>
                    <h2 class="category-title">Iluminación</h2>
                    <p class="category-count"><?php echo count($productosIluminacion); ?> Productos</p>
                    <a href="#iluminacion" class="explore-btn">
                      Explorar <i class="fas fa-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 🎨 Sección Exteriores -->
      <section class="catalog-products" id="exteriores">
        <div class="container">
          <h2 class="section-title">Productos de Exteriores</h2>
          <div class="product-grid">
            <?php if (empty($productosExteriores)): ?>
              <p class="no-products">No hay productos en esta categoría.</p>
            <?php else: ?>
              <?php foreach ($productosExteriores as $p): ?>
                <?php include "../includes/product-card.php"; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- 🎨 Sección Decorativo -->
      <section class="catalog-products" id="decorativo">
        <div class="container">
          <h2 class="section-title">Productos Decorativos</h2>
          <div class="product-grid">
            <?php if (empty($productosDecorativo)): ?>
              <p class="no-products">No hay productos en esta categoría.</p>
            <?php else: ?>
              <?php foreach ($productosDecorativo as $p): ?>
                <?php include "../includes/product-card.php"; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- 🎨 Sección Iluminación -->
      <section class="catalog-products" id="iluminacion">
        <div class="container">
          <h2 class="section-title">Productos de Iluminación</h2>
          <div class="product-grid">
            <?php if (empty($productosIluminacion)): ?>
              <p class="no-products">No hay productos en esta categoría.</p>
            <?php else: ?>
              <?php foreach ($productosIluminacion as $p): ?>
                <?php include "../includes/product-card.php"; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- 🎨 Lighting Section extra -->
      <section class="lighting-section">
        <div class="container">
          <div class="section-header">
            <span class="section-label">Tecnología de Iluminación</span>
            <h2 class="section-title">Soluciones Inteligentes</h2>
            <p class="section-description">Iluminación eficiente y sustentable para tu hogar</p>
          </div>
          <div class="lighting-showcase">

            <div class="lighting-category">
              <div class="lighting-visual">
                <div class="light-placeholder"><i class="fas fa-lightbulb"></i></div>
              </div>
              <div class="lighting-info">
                <h3>LED Inteligente</h3>
                <ul>
                  <li>Control por voz</li>
                  <li>Cambio de color</li>
                  <li>Ahorro de energía 90%</li>
                  <li>Vida útil 25,000 hrs</li>
                </ul>
              </div>
            </div>

            <div class="lighting-category">
              <div class="lighting-visual">
                <div class="light-placeholder"><i class="fas fa-sun"></i></div>
              </div>
              <div class="lighting-info">
                <h3>Solar</h3>
                <ul>
                  <li>100% energía renovable</li>
                  <li>Instalación sin cables</li>
                  <li>Sensor crepuscular</li>
                  <li>Resistente al agua</li>
                </ul>
              </div>
            </div>

            <div class="lighting-category">
              <div class="lighting-visual">
                <div class="light-placeholder"><i class="fas fa-wifi"></i></div>
              </div>
              <div class="lighting-info">
                <h3>Smart Home</h3>
                <ul>
                  <li>App móvil incluida</li>
                  <li>Programación horaria</li>
                  <li>Compatible Alexa/Google</li>
                  <li>Escenas personalizadas</li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- 🎨 CTA -->
      <section class="cta-section">
        <div class="container">
          <div class="cta-content">
            <h2>¿Listo para Iluminar Tu Espacio?</h2>
            <p>Descubre nuestras ofertas exclusivas y transforma tu hogar hoy mismo</p>
            <div class="cta-buttons">
              <a href="catalogo.php" class="cta-btn primary">
                <i class="fas fa-shopping-bag"></i> Ver Todo
              </a>
              <a href="contacto.php" class="cta-btn secondary">
                <i class="fas fa-phone"></i> Contactar Asesor
              </a>
            </div>
          </div>
        </div>
      </section>

    </div>

    <!-- ✅ Footer -->
    <?php include "../includes/footer.php"; ?>
  </div>

  <!-- Scripts -->
  <script src="../js/header.js"></script>
  <script src="../js/script.js"></script>
</body>
</html>
