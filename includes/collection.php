<?php
require_once __DIR__ . "/../config/functions.php";

// Traer categorías
$categorias = getCategorias();

// Saber qué categoría está seleccionada
$categoriaSeleccionada = isset($_GET['cat']) ? (int)$_GET['cat'] : null;

// Traer productos según categoría
$productos = getProductosPorCategoria($categoriaSeleccionada, 8);
?>

<!-- Products Collection -->
<section class="collection">
  <div class="container">
    <div class="section-header">
      <div class="section-subtitle">Our Products</div>
      <h2 class="section-title">Our Products Collections</h2>

      <!-- Filtros -->
      <div class="filter-tabs">
        <a href="index.php" class="filter-tab <?= !$categoriaSeleccionada ? 'active' : '' ?>">All Products</a>
        <?php foreach ($categorias as $c): ?>
          <a href="index.php?cat=<?= $c['id'] ?>" 
             class="filter-tab <?= ($categoriaSeleccionada === (int)$c['id']) ? 'active' : '' ?>">
            <?= htmlspecialchars($c['nombre']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Productos -->
    <div class="products-showcase">
      <?php if (count($productos) > 0): ?>
        <?php foreach ($productos as $p): ?>
          <div class="product-card">
            <!-- Imagen del producto -->
            <div class="product-image" 
                 style="background-image: url('<?= BASE_URL ?>images/productos/<?= htmlspecialchars($p['imagen']) ?>'); 
                        background-size: cover; 
                        background-position: center;">
              <?php if (!empty($p['descuento']) && $p['descuento'] > 0): ?>
                <div class="discount-badge"><?= (int)$p['descuento'] ?>% off</div>
              <?php endif; ?>

              <div class="product-actions">
                <button class="action-btn"><i class="fas fa-heart"></i></button>
                <button class="action-btn"><i class="fas fa-expand"></i></button>
                <button class="action-btn"><i class="fas fa-sync-alt"></i></button>
              </div>
            </div>

            <!-- Info -->
            <div class="product-info">
              <div class="product-brand"><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></div>
              <div class="product-name"><?= htmlspecialchars($p['nombre']) ?></div>
              <div class="product-rating">
                <div class="stars">⭐</div>
                <span class="rating-number"><?= number_format($p['rating'] ?? 4.5, 1) ?></span>
              </div>
              <div class="product-price">
                <span class="current-price">$<?= number_format($p['precio'], 2) ?></span>
                <?php if (!empty($p['precio_original'])): ?>
                  <span class="original-price">$<?= number_format($p['precio_original'], 2) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No hay productos en esta categoría.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
