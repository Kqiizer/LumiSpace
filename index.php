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
<?php $activePage = "home"; ?>
<?php include("includes/header.php"); ?>
  <?php include("includes/sidebar.php");?>

  <!-- HERO -->
  <section class="hero">
    <div class="hero-text">
      <h1 class="brand">Lumi<span>Space</span></h1>
      <p class="tagline">Inspiración y diseño para tus espacios</p>
      <p>Explora categorías, marcas, proyectos y servicios con estilo dinámico.</p>
      <a href="#productos" class="btn-light">Explorar</a>
    </div>
  </section>

  <!-- CARRUSEL DE PRODUCTOS -->
  <section id="productos" class="carousel-section">
    <h2 class="section-title">✨ Productos Destacados</h2>
    <?php include("includes/carousel-productos.php"); ?>
  </section>

  <!-- CARRUSEL DE MARCAS -->
  <section id="marcas" class="carousel-section">
    <h2 class="section-title">🏷️ Marcas Populares</h2>
    <?php include("includes/carousel-marcas.php"); ?>
  </section>

  <!-- CARRUSEL DE PROMOCIONES -->
  <section id="promociones" class="carousel-section">
    <h2 class="section-title">🔥 Ofertas Especiales</h2>
    <?php include("includes/carousel-promos.php"); ?>
  </section>

  <!-- FOOTER -->
  <footer id="footer">
    <p>&copy; 2025 LumiSpace - Todos los derechos reservados</p>
  </footer>

  <script src="js/main.js"></script>
  <script src="js/carousel.js"></script>
</body>
</html>
