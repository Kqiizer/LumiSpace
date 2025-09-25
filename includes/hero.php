<?php
require_once __DIR__ . "/../config/functions.php";

// 🚀 Sacar productos (aunque no tengan ventas)
$conn = getDBConnection();
$sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, 
               COALESCE(SUM(dv.cantidad),0) as total_vendido
        FROM productos p
        LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
        GROUP BY p.id
        ORDER BY total_vendido DESC, p.id DESC
        LIMIT 15";
$result = $conn->query($sql);
$topProductos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
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
        <a href="index.php" class="btn-primary">Shop Now <i class="fas fa-arrow-right"></i></a>
        <a href="index.php" class="btn-secondary">View All Products</a>
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
          <?php foreach ($topProductos as $p): ?>
            <div class="room-card" style="flex:0 0 calc(33.33% - 14px);">
              <div class="room-image" 
                   style="background-image: url('<?= htmlspecialchars($p['imagen']) ?>'); background-size: cover; background-position: center;">
                <div class="room-price">$<?= number_format($p['precio'], 2) ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= strtoupper(htmlspecialchars($p['nombre'])) ?></h3>
                <p class="room-items"><?= (int)$p['stock'] ?> disponibles</p>
              </div>
            </div>
          <?php endforeach; ?>

          <!-- 🔁 Clonamos algunos para loop infinito -->
          <?php foreach (array_slice($topProductos, 0, min(3,count($topProductos))) as $p): ?>
            <div class="room-card" style="flex:0 0 calc(33.33% - 14px); opacity:.7;">
              <div class="room-image" 
                   style="background-image: url('<?= htmlspecialchars($p['imagen']) ?>'); background-size: cover; background-position: center;">
                <div class="room-price">$<?= number_format($p['precio'], 2) ?></div>
              </div>
              <div class="room-info">
                <h3 class="room-title"><?= strtoupper(htmlspecialchars($p['nombre'])) ?></h3>
                <p class="room-items"><?= (int)$p['stock'] ?> disponibles</p>
              </div>
            </div>
          <?php endforeach; ?>
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
// 🎠 Carrusel con movimiento constante + arrastre
const carousel = document.getElementById("heroCarousel");
let isDown = false;
let startX;
let scrollLeft;
let position = 0;
let speed = 0.5; // velocidad constante

// Movimiento automático estilo marquee
function animate() {
  position -= speed;
  carousel.style.transform = `translateX(${position}px)`;

  const firstCard = carousel.children[0];
  const cardWidth = firstCard.offsetWidth + 20;

  // Loop infinito
  if (Math.abs(position) >= cardWidth) {
    carousel.appendChild(firstCard); // manda la primera card al final
    position += cardWidth;
  }
  requestAnimationFrame(animate);
}
animate();

// Drag con el mouse
carousel.addEventListener("mousedown", (e) => {
  isDown = true;
  carousel.style.cursor = "grabbing";
  startX = e.pageX - carousel.offsetLeft;
  scrollLeft = position;
});
carousel.addEventListener("mouseleave", () => {
  isDown = false;
  carousel.style.cursor = "grab";
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
  position = scrollLeft + walk;
  carousel.style.transform = `translateX(${position}px)`;
});

// Flechas manuales
document.getElementById("nextBtn").addEventListener("click", () => {
  position -= (carousel.children[0].offsetWidth + 20);
});
document.getElementById("prevBtn").addEventListener("click", () => {
  const lastCard = carousel.lastElementChild;
  const cardWidth = lastCard.offsetWidth + 20;
  carousel.insertBefore(lastCard, carousel.firstElementChild);
  position -= cardWidth;
});
</script>
