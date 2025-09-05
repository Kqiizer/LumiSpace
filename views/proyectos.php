<?php $activePage = "proyectos"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LumiSpace - Proyectos</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/carousel.css">
</head>
<body>
  <!-- HEADER -->
 <?php 
  $activePage = "proyectos"; 
  include("../includes/header.php"); 
  include("../includes/sidebar.php"); 
?>

  <!-- CONTENIDO -->
  <section class="hero">
    <div class="hero-text">
      <h1 class="brand">Proyectos</h1>
      <p class="tagline">Inspírate con nuestros proyectos innovadores</p>
    </div>
  </section>

  <!-- CARRUSEL -->
  <h2 class="section-title">🏗️ Proyectos Destacados</h2>
  <?php include("../includes/carousel-proyectos.php"); ?>

  <!-- FOOTER -->
  <?php include("../includes/footer.php"); ?>
  <script src="../js/main.js"></script>
  <script src="../js/carousel.js"></script>
</body>
</html>
