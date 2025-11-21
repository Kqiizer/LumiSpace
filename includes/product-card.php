<!-- es para no repetir código de tarjetas de productos: -->
<div class="product-card">
  <!-- Imagen del producto -->
  <div class="product-image">
    <!-- Badge dinámico -->
    <?php if (!empty($p['descuento'])): ?>
      <span class="badge discount">-<?php echo (int)$p['descuento']; ?>%</span>
    <?php elseif (!empty($p['nuevo']) && $p['nuevo']): ?>
      <span class="badge new">Nuevo</span>
    <?php endif; ?>

    <img src="<?php echo !empty($p['imagen']) 
                  ? "../uploads/" . htmlspecialchars($p['imagen']) 
                  : "../images/no-image.png"; ?>" 
         alt="<?php echo htmlspecialchars($p['nombre']); ?>">

    <!-- Overlay con acciones -->
    <div class="overlay">
      <a href="detalle-producto.php?id=<?php echo $p['id']; ?>" 
         class="icon-btn" title="Ver detalle">
        <i class="fas fa-eye"></i>
      </a>

      <a href="wishlist.php?add=<?php echo $p['id']; ?>" 
         class="icon-btn" title="Agregar a favoritos">
        <i class="fas fa-heart"></i>
      </a>

      <?php if (($p['stock'] ?? 0) > 0): ?>
        <a href="carrito.php?add=<?php echo $p['id']; ?>" 
           class="icon-btn" title="Agregar al carrito">
          <i class="fas fa-shopping-cart"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Información del producto -->
  <div class="product-content">
    <h3 class="product-title">
      <a href="detalle-producto.php?id=<?php echo $p['id']; ?>">
        <?php echo htmlspecialchars($p['nombre']); ?>
      </a>
    </h3>
    <p class="product-category">
      <?php echo htmlspecialchars($p['categoria'] ?? "General"); ?>
    </p>

    <div class="price-stock">
      <!-- Precio -->
      <?php if (!empty($p['precio_original']) && $p['precio_original'] > $p['precio']): ?>
        <p class="product-price">
          <span class="old-price">$<?php echo number_format($p['precio_original'], 2); ?></span>
          <span class="new-price">$<?php echo number_format($p['precio'], 2); ?></span>
        </p>
      <?php else: ?>
        <p class="product-price">$<?php echo number_format($p['precio'], 2); ?></p>
      <?php endif; ?>

      <!-- Stock -->
      <?php if (($p['stock'] ?? 0) > 10): ?>
        <span class="stock in"><i class="fas fa-check-circle"></i> Disponible</span>
      <?php elseif (($p['stock'] ?? 0) > 0): ?>
        <span class="stock low"><i class="fas fa-exclamation-circle"></i> Pocas unidades</span>
      <?php else: ?>
        <span class="stock out"><i class="fas fa-times-circle"></i> Agotado</span>
      <?php endif; ?>
    </div>
  </div>
</div>
