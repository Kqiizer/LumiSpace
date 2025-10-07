<?php
require_once __DIR__ . "/../config/functions.php";

// BASE_URL (usa la que tengas en tu config; aquí doy fallback)
$BASE = defined('BASE_URL') ? BASE_URL : '/LumiSpace/';

/**
 * Normaliza la URL de la imagen del producto para que siempre cargue.
 */
function buildImageUrl(?string $raw, string $BASE): string {
    $raw = trim((string)$raw);
    $raw = str_replace('\\', '/', $raw);       // separadores
    $raw = preg_replace('#\.\./#', '', $raw);  // limpia ../

    // URL absoluta ya válida
    if (preg_match('#^https?://#i', $raw)) return $raw;

    // Comienza con slash => absoluta del dominio
    if (strpos($raw, '/') === 0) return $raw;

    // Si trae 'uploads/...'
    if (stripos($raw, 'uploads/') === 0) {
        return rtrim($BASE, '/') . '/' . $raw;
    }

    // Vacío => fallback
    if ($raw === '' || $raw === null) {
        return rtrim($BASE, '/') . '/images/default.png';
    }

    // Nombre de archivo => a uploads/productos/
    return rtrim($BASE, '/') . '/uploads/productos/' . $raw;
}

// 🚀 Top productos por ventas (incluye productos sin ventas)
$conn = getDBConnection();
$sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, 
               COALESCE(SUM(dv.cantidad),0) as total_vendido
        FROM productos p
        LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
        GROUP BY p.id
        ORDER BY total_vendido DESC, p.id DESC
        LIMIT 15";
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
        Por eso diseñamos lámparas, gadgets y soluciones inteligentes que combinan tecnología, elegancia y funcionalidad.
      </p>

      <div class="hero-buttons">
        <a href="<?= htmlspecialchars(rtrim($BASE,'/').'/index.php') ?>" class="btn-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
        <a href="<?= htmlspecialchars(rtrim($BASE,'/').'/index.php') ?>" class="btn-secondary">View All Products</a>
      </div>

      <div class="hero-rating">
        <div class="rating-avatars">
          <div class="avatar avatar-1"></div>
          <div class="avatar avatar-2"></div>
          <div class="avatar avatar-3"></div>
          <div class="avatar avatar-4"></div>
          <div style="background-color: #d4c4a8; color: #8b7355; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-left: -10px;">+</div>
        </div>
        <div class="rating-text">
          <div style="font-weight: bold;">4.9 Ratings+</div>
          <div style="font-size: 12px;">Trusted by 300+ Customers</div>
        </div>
      </div>
    </div>

    <div class="hero-images" style="position: relative; overflow: hidden;">
      <div class="carousel-wrapper" id="heroCarousel" style="display:flex; gap:20px; will-change: transform;">
        <?php if (!empty($topProductos)): ?>
          <?php foreach ($topProductos as $p): 
            $detalle = rtrim($BASE,'/').'/producto.php?id='.((int)$p['id']);
          ?>
            <div class="room-card"
                 data-id="<?= (int)$p['id'] ?>"
                 data-url="<?= htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8') ?>"
                 style="flex:0 0 calc(33.33% - 14px);">
              <div class="room-image" 
                   style="background-image: url('<?= htmlspecialchars($p['img_url'], ENT_QUOTES, 'UTF-8') ?>'); background-size: cover; background-position: center;">
                <div class="room-price">$<?= number_format((float)$p['precio'], 2) ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= strtoupper(htmlspecialchars($p['nombre'])) ?></h3>
                <p class="room-items"><?= (int)$p['stock'] ?> disponibles</p>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- 🔁 Clones para loop infinito suave -->
          <?php foreach (array_slice($topProductos, 0, min(3,count($topProductos))) as $p): 
            $detalle = rtrim($BASE,'/').'/producto.php?id='.((int)$p['id']);
          ?>
            <div class="room-card"
                 data-id="<?= (int)$p['id'] ?>"
                 data-url="<?= htmlspecialchars($detalle, ENT_QUOTES, 'UTF-8') ?>"
                 style="flex:0 0 calc(33.33% - 14px); opacity:.7;">
              <div class="room-image" 
                   style="background-image: url('<?= htmlspecialchars($p['img_url'], ENT_QUOTES, 'UTF-8') ?>'); background-size: cover; background-position: center;">
                <div class="room-price">$<?= number_format((float)$p['precio'], 2) ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= strtoupper(htmlspecialchars($p['nombre'])) ?></h3>
                <p class="room-items"><?= (int)$p['stock'] ?> disponibles</p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:2rem">No hay productos para mostrar.</div>
        <?php endif; ?>
      </div>

      <div class="nav-arrows">
        <button class="nav-arrow" id="prevBtn"><i class="fas fa-arrow-left"></i></button>
        <button class="nav-arrow" style="background-color: #d4c4a8;" id="nextBtn"><i class="fas fa-arrow-right"></i></button>
      </div>
    </div>
  </div>
</section>

<script>
// 🎠 Carrusel con movimiento constante + arrastre + pausa en hover + click a detalle
const carousel = document.getElementById("heroCarousel");
if (carousel && carousel.children.length) {
  let isDown = false;
  let isPaused = false;      // pausa por hover o drag
  let dragged = false;       // evita click tras un drag
  let startX;
  let scrollLeft;
  let position = 0;
  const speed = 0.5; // velocidad constante

  function cardW() {
    const firstCard = carousel.children[0];
    return firstCard ? firstCard.offsetWidth + 20 : 0;
  }

  function animate() {
    const w = cardW();
    if (!w) return requestAnimationFrame(animate);

    if (!isPaused) {
      position -= speed;
      carousel.style.transform = `translateX(${position}px)`;
      if (Math.abs(position) >= w) {
        carousel.appendChild(carousel.children[0]);
        position += w;
      }
    }
    requestAnimationFrame(animate);
  }
  animate();

  // Pausa por hover
  carousel.addEventListener("mouseenter", () => {
    isPaused = true;
    carousel.style.cursor = "grab";
  });
  carousel.addEventListener("mouseleave", () => {
    isPaused = false;
    isDown = false;
    dragged = false;
    carousel.style.cursor = "auto";
  });

  // Drag con el mouse
  carousel.addEventListener("mousedown", (e) => {
    isDown = true;
    isPaused = true;
    dragged = false;
    carousel.style.cursor = "grabbing";
    startX = e.pageX - carousel.offsetLeft;
    scrollLeft = position;
  });
  carousel.addEventListener("mouseup", () => {
    isDown = false;
    carousel.style.cursor = "grab";
  });
  carousel.addEventListener("mousemove", (e) => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - carousel.offsetLeft;
    const walk = x - startX;
    if (Math.abs(walk) > 3) dragged = true; // umbral para considerar drag
    position = scrollLeft + walk;
    carousel.style.transform = `translateX(${position}px)`;
  });

  // Flechas manuales
  const nextBtn = document.getElementById("nextBtn");
  const prevBtn = document.getElementById("prevBtn");

  nextBtn && nextBtn.addEventListener("click", () => {
    const w = cardW();
    if (!w) return;
    position -= w;
    carousel.style.transform = `translateX(${position}px)`;
  });

  prevBtn && prevBtn.addEventListener("click", () => {
    const w = cardW();
    if (!w) return;
    const lastCard = carousel.lastElementChild;
    carousel.insertBefore(lastCard, carousel.firstElementChild);
    position -= w;
    carousel.style.transform = `translateX(${position}px)`;
  });

  // 🔗 Click a detalle (si no se arrastró)
  carousel.addEventListener("click", (e) => {
    const card = e.target.closest(".room-card");
    if (!card) return;
    if (dragged) { dragged = false; return; } // no navegar si fue drag
    const url = card.dataset.url;
    if (url) window.location.href = url;
  });
}
</script>
