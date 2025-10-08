<?php
require_once __DIR__ . "/../config/functions.php";

// BASE_URL
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/LumiSpace/';

// Normalizador de imágenes
function buildImageUrl(?string $raw, string $BASE): string {
    $raw = trim((string)$raw);
    $raw = str_replace('\\', '/', $raw);
    $raw = preg_replace('#\.\./#', '', $raw);

    if ($raw !== '' && preg_match('#^https?://#i', $raw)) return $raw;
    if ($raw !== '' && strpos($raw, '/') === 0) return rtrim($BASE, '/') . '/' . ltrim($raw, '/');
    if (stripos($raw, 'images/') === 0) return rtrim($BASE, '/') . '/' . $raw;
    if (stripos($raw, 'productos/') === 0) return rtrim($BASE, '/') . '/images/' . $raw;
    if ($raw === '' || $raw === null) return rtrim($BASE, '/') . '/images/default.png';
    return rtrim($BASE, '/') . '/images/productos/' . $raw;
}

// 🚀 Productos top (ventas + stock real)
$conn = getDBConnection();
$sql = "
    SELECT 
        p.id, p.nombre, p.precio, p.imagen,
        COALESCE(SUM(i.cantidad),0) AS stock_real,
        COALESCE(SUM(dv.cantidad),0) AS total_vendido
    FROM productos p
    LEFT JOIN inventario i ON p.id = i.producto_id
    LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
    GROUP BY p.id, p.nombre, p.precio, p.imagen
    ORDER BY total_vendido DESC, p.id DESC
    LIMIT 12";
$result = $conn->query($sql);

$topProductos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['img_url'] = buildImageUrl($row['imagen'] ?? '', $BASE);
        $topProductos[] = $row;
    }
}
?>

<!-- Hero Section -->
<section class="hero">
  <div class="container">
    <div class="hero-content">
      <p style="color: #a0896b; margin-bottom: 10px;">LumiSpace</p>
      <h1 class="hero-title">Ilumina tu espacio. Inspira tu estilo</h1>
      <p class="hero-subtitle">
        En Lumispace creemos que la luz no solo transforma ambientes, transforma emociones.
        Diseñamos lámparas, gadgets y soluciones inteligentes que combinan tecnología, elegancia y funcionalidad.
      </p>

      <div class="hero-buttons">
        <a href="<?= htmlspecialchars($BASE . 'index.php') ?>" class="btn-primary">
          Shop Now <i class="fas fa-arrow-right"></i>
        </a>
        <a href="<?= htmlspecialchars($BASE . 'index.php') ?>" class="btn-secondary">
          View All Products
        </a>
      </div>
    </div>

    <!-- Carrusel -->
    <div class="hero-images" style="position: relative; overflow: hidden;">
      <div class="carousel-wrapper" id="heroCarousel" 
           style="display:flex; gap:20px; will-change:transform;">
        <?php if (!empty($topProductos)): ?>
          <?php foreach ($topProductos as $p): 
            $detalle = $BASE . 'views/productos-detal.php?id=' . (int)$p['id'];
          ?>
            <div class="room-card" 
                 data-url="<?= htmlspecialchars($detalle, ENT_QUOTES) ?>"
                 style="flex:0 0 calc(33.33% - 14px); cursor:pointer;">
              <div class="room-image" 
                   style="background-image:url('<?= htmlspecialchars($p['img_url'], ENT_QUOTES) ?>');
                          background-size:cover; background-position:center;">
                <div class="room-price">$<?= number_format((float)$p['precio'], 2) ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= strtoupper(htmlspecialchars($p['nombre'])) ?></h3>
                <p class="room-items"><?= (int)$p['stock_real'] ?> disponibles</p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:2rem">No hay productos para mostrar.</div>
        <?php endif; ?>
      </div>

      <!-- Flechas -->
      <div class="nav-arrows">
        <button class="nav-arrow" id="prevBtn"><i class="fas fa-arrow-left"></i></button>
        <button class="nav-arrow" style="background-color:#d4c4a8;" id="nextBtn"><i class="fas fa-arrow-right"></i></button>
      </div>
    </div>
  </div>
</section>

<script>
// 🎠 Carrusel dinámico con autoplay + hover + drag + swipe + flechas
const carousel = document.getElementById("heroCarousel");
if (carousel && carousel.children.length) {
  let pos = 0;
  let isPaused = false;
  let isDragging = false;
  let moved = false;
  let startX = 0;
  let initialPos = 0;
  const speed = 0.6;

  function cardW() {
    const c = carousel.children[0];
    return c ? c.offsetWidth + 20 : 0;
  }

  function animate() {
    if (!isPaused && !isDragging) {
      pos -= speed;
      const w = cardW();
      carousel.style.transform = `translateX(${pos}px)`;
      if (Math.abs(pos) >= w) {
        carousel.appendChild(carousel.children[0]);
        pos += w;
      }
    }
    requestAnimationFrame(animate);
  }
  animate();

  // Hover (desktop)
  carousel.addEventListener("mouseenter", () => { isPaused = true; });
  carousel.addEventListener("mouseleave", () => { isPaused = false; });

  // Drag (desktop)
  carousel.addEventListener("mousedown", (e) => {
    isDragging = true; moved = false;
    startX = e.pageX;
    initialPos = pos;
    carousel.style.cursor = "grabbing";
  });
  document.addEventListener("mouseup", () => {
    if (isDragging) {
      isDragging = false;
      carousel.style.cursor = "auto";
    }
  });
  document.addEventListener("mousemove", (e) => {
    if (!isDragging) return;
    const dx = e.pageX - startX;
    pos = initialPos + dx;
    if (Math.abs(dx) > 5) moved = true;
    carousel.style.transform = `translateX(${pos}px)`;
  });

  // Swipe táctil (mobile)
  carousel.addEventListener("touchstart", (e) => {
    isDragging = true; moved = false;
    startX = e.touches[0].pageX;
    initialPos = pos;
    isPaused = true;
  }, {passive:true});
  carousel.addEventListener("touchend", () => {
    isDragging = false; isPaused = false;
  }, {passive:true});
  carousel.addEventListener("touchmove", (e) => {
    if (!isDragging) return;
    const dx = e.touches[0].pageX - startX;
    pos = initialPos + dx;
    if (Math.abs(dx) > 5) moved = true;
    carousel.style.transform = `translateX(${pos}px)`;
  }, {passive:true});

  // Flechas
  document.getElementById("nextBtn").addEventListener("click", () => {
    const w = cardW();
    pos -= w;
    carousel.style.transform = `translateX(${pos}px)`;
  });
  document.getElementById("prevBtn").addEventListener("click", () => {
    const w = cardW();
    const last = carousel.lastElementChild;
    carousel.insertBefore(last, carousel.firstElementChild);
    pos -= w;
    carousel.style.transform = `translateX(${pos}px)`;
  });

  // Click seguro
  carousel.addEventListener("click", (e) => {
    const card = e.target.closest(".room-card");
    if (!card || moved) { moved = false; return; }
    const url = card.dataset.url;
    if (url) window.location.href = url;
  });
}
</script>
