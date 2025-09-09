<?php $activePage = "categorias"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LumiSpace - Categorías</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/carousel.css">
</head>
<body>
  <!-- HEADER -->
<?php 
  $activePage = "categorias"; 
  include("../includes/header.php"); 
  include("../includes/sidebar.php"); 
?>

  <!-- CONTENIDO -->
  <section class="hero">
    <div class="hero-text">
      <h1 class="brand">Categorías</h1>
      <p class="tagline">Explora nuestras diferentes categorías de productos</p>
    </div>
  </section>

  <!-- CARRUSEL -->
  <h2 class="section-title">📂 Categorías Destacadas</h2>
  <?php include("../includes/carousel-categorias.php"); ?>

  <!-- FOOTER -->
  <?php include("../includes/footer.php"); ?>
  <script src="../js/main.js"></script>
  <script src="../js/carousel.js"></script>
</body>
</html>
