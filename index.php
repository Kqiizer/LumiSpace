<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();

// Definir BASE igual que en header.php
$root = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
$BASE = ($root === '' || $root === '/') ? '/' : $root . '/';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LumiSpace</title>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <!-- Estilos -->
  <link rel="stylesheet" href="css/styles/header.css">
  <link rel="stylesheet" href="css/styles/sidebar.css">
  <link rel="stylesheet" href="css/styles/reset.css">
  <link rel="stylesheet" href="css/styles/hero.css">
  <link rel="stylesheet" href="css/styles/features.css">
  <link rel="stylesheet" href="css/styles/products.css">
  <link rel="stylesheet" href="css/styles/statistics.css">
  <link rel="stylesheet" href="css/styles/collection.css">
  <link rel="stylesheet" href="css/styles/footer.css">
  <link rel="stylesheet" href="css/styles/responsive.css">
</head>

<body>
  <div class="page-wrapper">
    <div class="main-content">

      <!-- ✅ Este include ya contiene el header, el sidebar y overlay -->
      <?php include "includes/header.php"; ?>

      <?php include "includes/hero.php"; ?>
      <?php include "includes/features.php"; ?>
      <?php include "includes/categories.php"; ?>
      <?php include "includes/statistics.php"; ?>
      <?php include "includes/collection.php"; ?>

    </div>

    <?php include "includes/footer.php"; ?>
  </div>

  <!-- ✅ Scripts (deben ir al final del body) -->
  <script>
    // Definir BASE_URL para product-actions.js (igual que en catálogo)
    window.BASE_URL = "<?= $BASE ?>";
  </script>
  <script src="<?= $BASE ?>js/product-actions.js"></script>
  <script src="<?= $BASE ?>js/header.js" defer></script>
  <script src="<?= $BASE ?>js/script.js" defer></script>
</body>

</html>