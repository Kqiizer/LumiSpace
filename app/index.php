<?php
// Inicia sesión y valida
session_start();

// Si quieres proteger con login descomenta esto:
// if (!isset($_SESSION['usuario_id'])) {
//     header("Location: views/login.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LumiSpace</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/carousel.css"> 
</head>
<body>
  <!-- HEADER -->
  <?php 
    $activePage = "home"; 
    include("includes/header.php"); 
    include("includes/sidebar.php");
  ?>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-text">
      <h1 class="brand">Lumi<span>Space</span></h1>
      <p class="tagline">Inspiración y diseño para tus espacios ✨</p>
      <a href="views/login.php" class="btn-primary">Empieza ahora</a>
    </div>
    <div class="hero-img">
      <img src="images/hero-lamps.png" alt="Decoración LumiSpace">
    </div>
  </section>

  <!-- CARRUSEL DE PRODUCTOS -->
  <section class="carousel-section">
    <h2 class="section-title">✨ Productos en Venta</h2>
    <div class="carousel-container">
      <div class="carousel-track" id="carouselTrack">

        <!-- Producto 1 -->
        <div class="card">
          <img src="images/producto1.jpg" alt="Silla Moderna">
          <h3>Silla Moderna</h3>
          <p class="price">$1200 MXN</p>
          <a href="producto.php?id=1" class="btn-light">Comprar</a>
        </div>

        <!-- Producto 2 -->
        <div class="card">
          <img src="images/producto2.jpg" alt="Lámpara Vintage">
          <h3>Lámpara Vintage</h3>
          <p class="price">$850 MXN</p>
          <a href="producto.php?id=2" class="btn-light">Comprar</a>
        </div>

        <!-- Producto 3 -->
        <div class="card">
          <img src="images/producto3.jpg" alt="Mesa Minimalista">
          <h3>Mesa Minimalista</h3>
          <p class="price">$1800 MXN</p>
          <a href="producto.php?id=3" class="btn-light">Comprar</a>
        </div>

      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>© <?php echo date("Y"); ?> LumiSpace. Todos los derechos reservados.</p>
  </footer>

  <script src="js/carousel.js"></script>
</body>
</html>
