<?php
require_once __DIR__ . "/../config/functions.php";

// BASE_URL
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/LumiSpace/';

/**
 * Construye URL absoluta para imágenes con sanitización
 */
function buildImageUrl(?string $raw, string $BASE): string {
    $raw = trim((string)$raw);
    $raw = str_replace('\\', '/', $raw);
    $raw = preg_replace('#\.\./#', '', $raw);

    // URL absoluta
    if ($raw !== '' && preg_match('#^https?://#i', $raw)) {
        return $raw;
    }
    
    // Path absoluto del sistema
    if ($raw !== '' && strpos($raw, '/') === 0) {
        return rtrim($BASE, '/') . $raw;
    }
    
    // Path relativo con images/
    if (stripos($raw, 'images/') === 0) {
        return rtrim($BASE, '/') . '/' . $raw;
    }
    
    // Path con productos/
    if (stripos($raw, 'productos/') === 0) {
        return rtrim($BASE, '/') . '/images/' . $raw;
    }
    
    // Vacío o null → default
    if ($raw === '') {
        return rtrim($BASE, '/') . '/images/default.png';
    }
    
    // Cualquier otro caso
    return rtrim($BASE, '/') . '/images/productos/' . $raw;
}

// Obtener productos destacados
$conn = getDBConnection();
$topProductos = [];

try {
    $sql = "
        SELECT 
            p.id, 
            p.nombre, 
            p.precio, 
            p.imagen,
            COALESCE(SUM(i.cantidad), 0) AS stock_real,
            COALESCE(SUM(dv.cantidad), 0) AS total_vendido
        FROM productos p
        LEFT JOIN inventario i ON p.id = i.producto_id
        LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
        GROUP BY p.id, p.nombre, p.precio, p.imagen
        ORDER BY total_vendido DESC, p.id DESC
        LIMIT 12";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['img_url'] = buildImageUrl($row['imagen'] ?? '', $BASE);
            $row['stock_real'] = max(0, (int)$row['stock_real']);
            $row['precio'] = max(0, (float)$row['precio']);
            $topProductos[] = $row;
        }
        $result->free();
    }
} catch (Exception $e) {
    error_log("Error en hero.php: " . $e->getMessage());
}
?>

<!-- Hero Section -->
<section class="hero" role="banner">
  <div class="container">
    <div class="hero-content">
      <p class="hero-brand">LumiSpace</p>
      <h1 class="hero-title">
  <span class="t" data-i18n="hero.title" data-i18n-es="Ilumina tu espacio. Inspira tu estilo">Ilumina tu espacio. Inspira tu estilo</span>
</h1>
      <p class="hero-subtitle">
  <span class="t" data-i18n="hero.subtitle" data-i18n-es="En Lumispace creemos que la luz no solo transforma ambientes, transforma emociones. Diseñamos lámparas, gadgets y soluciones inteligentes que combinan tecnología, elegancia y funcionalidad.">
    En Lumispace creemos que la luz no solo transforma ambientes, transforma emociones. Diseñamos lámparas, gadgets y soluciones inteligentes que combinan tecnología, elegancia y funcionalidad.
  </span>
</p>


      <div class="hero-buttons">
        <a href="<?= htmlspecialchars($BASE . 'index.php') ?>" class="btn-primary" aria-label="Comprar productos">
  <span class="t" data-i18n="hero.cta_buy" data-i18n-es="Compra Ahora">Compra Ahora</span>
  <i class="fas fa-arrow-right" aria-hidden="true"></i>
</a>
        <a href="<?= htmlspecialchars($BASE . 'index.php') ?>" class="btn-secondary" aria-label="Ver más productos">
  <span class="t" data-i18n="hero.cta_more" data-i18n-es="Ver Más">Ver Más</span>
</a>
      </div>
    </div>

    <!-- Carrusel de Productos -->
    <div class="hero-images" role="region" aria-label="Productos destacados">
      <?php if (!empty($topProductos)): ?>
        <div class="carousel-wrapper" id="heroCarousel">
          <?php foreach ($topProductos as $p): 
            $detalle = $BASE . 'views/productos-detal.php?id=' . (int)$p['id'];
            $precioFormato = number_format($p['precio'], 2);
            $stockTexto = $p['stock_real'] === 1 ? 'disponible' : 'disponibles';
          ?>
            <article class="room-card" 
                     data-url="<?= htmlspecialchars($detalle, ENT_QUOTES) ?>"
                     data-id="<?= (int)$p['id'] ?>">
              <div class="room-image" 
                   style="background-image: url('<?= htmlspecialchars($p['img_url'], ENT_QUOTES) ?>');"
                   role="img"
                   aria-label="<?= htmlspecialchars($p['nombre']) ?>">
                <div class="room-price" aria-label="Precio">$<?= $precioFormato ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= htmlspecialchars(strtoupper($p['nombre'])) ?></h3>
                <p class="room-items">
                  <?= (int)$p['stock_real'] ?> <?= $stockTexto ?>
                </p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <!-- Controles de navegación -->
        <div class="nav-arrows" role="group" aria-label="Controles del carrusel">
          <button class="nav-arrow" 
                  id="prevBtn" 
                  type="button"
                  aria-label="Producto anterior">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
          </button>
          <button class="nav-arrow nav-arrow-next" 
                  id="nextBtn" 
                  type="button"
                  aria-label="Siguiente producto">
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
          </button>
        </div>
      <?php else: ?>
        <div class="no-products">
          <p>No hay productos disponibles en este momento.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
.hero-brand {
  color: #a0896b;
  margin-bottom: 10px;
  font-weight: 600;
  font-size: 14px;
  text-transform: uppercase;
  letter-spacing: 2px;
}

.hero-images {
  position: relative;
  overflow: hidden;
  padding: 20px 0;
}

.carousel-wrapper {
  display: flex;
  gap: 20px;
  will-change: transform;
  transition: transform 0.3s ease-out;
}

.room-card {
  flex: 0 0 calc(33.33% - 14px);
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  transition: all 0.3s ease;
  user-select: none;
}

.room-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 20px rgba(0,0,0,.15);
}

.room-image {
  height: 280px;
  background-size: cover;
  background-position: center;
  position: relative;
}

.room-price {
  position: absolute;
  top: 12px;
  right: 12px;
  background: rgba(255,255,255,0.95);
  padding: 8px 14px;
  border-radius: 8px;
  font-weight: 700;
  color: #8b7355;
  font-size: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

.room-info {
  padding: 16px;
}

.room-title {
  font-size: 15px;
  font-weight: 700;
  color: #8b7355;
  margin: 0 0 8px;
  line-height: 1.4;
}

.room-items {
  font-size: 13px;
  color: #a0896b;
  margin: 0;
}

.nav-arrows {
  position: absolute;
  bottom: 40px;
  right: 20px;
  display: flex;
  gap: 10px;
  z-index: 10;
}

.nav-arrow {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.9);
  color: #8b7355;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

.nav-arrow:hover {
  background: #fff;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0,0,0,.2);
}

.nav-arrow-next {
  background: #d4c4a8 !important;
  color: #fff !important;
}

.no-products {
  text-align: center;
  padding: 60px 20px;
  color: #999;
}

@media (max-width: 768px) {
  .room-card {
    flex: 0 0 calc(50% - 10px);
  }
  
  .room-image {
    height: 220px;
  }
  
  .nav-arrows {
    bottom: 20px;
    right: 50%;
    transform: translateX(50%);
  }
}

@media (max-width: 480px) {
  .room-card {
    flex: 0 0 calc(100% - 20px);
  }
}
</style>

<script>
(function() {
  'use strict';
  
  const carousel = document.getElementById("heroCarousel");
  if (!carousel || !carousel.children.length) return;

  let pos = 0;
  let isPaused = false;
  let isDragging = false;
  let moved = false;
  let startX = 0;
  let initialPos = 0;
  let animationId = null;
  
  const speed = 0.6;

  function getCardWidth() {
    const firstCard = carousel.children[0];
    return firstCard ? firstCard.offsetWidth + 20 : 0;
  }

  function animate() {
    if (!isPaused && !isDragging) {
      pos -= speed;
      const cardWidth = getCardWidth();
      carousel.style.transform = `translateX(${pos}px)`;
      
      // Loop infinito
      if (Math.abs(pos) >= cardWidth) {
        carousel.appendChild(carousel.children[0]);
        pos += cardWidth;
      }
    }
    animationId = requestAnimationFrame(animate);
  }

  // Iniciar animación
  animate();

  // Pausar en hover (desktop)
  carousel.addEventListener("mouseenter", () => { 
    isPaused = true; 
  });
  
  carousel.addEventListener("mouseleave", () => { 
    isPaused = false; 
  });

  // Drag (desktop)
  carousel.addEventListener("mousedown", (e) => {
    isDragging = true;
    moved = false;
    startX = e.pageX;
    initialPos = pos;
    carousel.style.cursor = "grabbing";
    e.preventDefault();
  });

  document.addEventListener("mouseup", () => {
    if (isDragging) {
      isDragging = false;
      carousel.style.cursor = "grab";
    }
  });

  document.addEventListener("mousemove", (e) => {
    if (!isDragging) return;
    const deltaX = e.pageX - startX;
    pos = initialPos + deltaX;
    if (Math.abs(deltaX) > 5) moved = true;
    carousel.style.transform = `translateX(${pos}px)`;
  });

  // Touch (mobile)
  carousel.addEventListener("touchstart", (e) => {
    isDragging = true;
    moved = false;
    startX = e.touches[0].pageX;
    initialPos = pos;
    isPaused = true;
  }, { passive: true });

  carousel.addEventListener("touchend", () => {
    isDragging = false;
    isPaused = false;
  }, { passive: true });

  carousel.addEventListener("touchmove", (e) => {
    if (!isDragging) return;
    const deltaX = e.touches[0].pageX - startX;
    pos = initialPos + deltaX;
    if (Math.abs(deltaX) > 5) moved = true;
    carousel.style.transform = `translateX(${pos}px)`;
  }, { passive: true });

  // Navegación con flechas
  const nextBtn = document.getElementById("nextBtn");
  const prevBtn = document.getElementById("prevBtn");

  if (nextBtn) {
    nextBtn.addEventListener("click", () => {
      const cardWidth = getCardWidth();
      pos -= cardWidth;
      carousel.style.transform = `translateX(${pos}px)`;
      isPaused = true;
      setTimeout(() => { isPaused = false; }, 1000);
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener("click", () => {
      const cardWidth = getCardWidth();
      const lastCard = carousel.lastElementChild;
      if (lastCard) {
        carousel.insertBefore(lastCard, carousel.firstElementChild);
        pos -= cardWidth;
        carousel.style.transform = `translateX(${pos}px)`;
        isPaused = true;
        setTimeout(() => { isPaused = false; }, 1000);
      }
    });
  }

  // Click en cards (solo si no se movió)
  carousel.addEventListener("click", (e) => {
    const card = e.target.closest(".room-card");
    if (!card || moved) {
      moved = false;
      return;
    }
    
    const url = card.dataset.url;
    if (url) {
      window.location.href = url;
    }
  });

  // Limpiar al salir
  window.addEventListener('beforeunload', () => {
    if (animationId) {
      cancelAnimationFrame(animationId);
    }
  });
})();
</script>