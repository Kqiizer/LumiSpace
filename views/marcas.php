<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : './';
$brands = getBrandsOverview();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/marcas.css">
</head>
<body class="brands-page">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="brands-hero">
        <div class="container">
            <h1>Marcas aliadas</h1>
            <p>Explora nuestras alianzas con las marcas más queridas y descubre colecciones especiales.</p>
            <div id="featuredBrands" class="featured-brands"></div>
        </div>
    </section>

    <section class="brand-grid" id="brandGrid"></section>

    <section class="brand-products">
        <header>
            <div>
                <h2 id="brandProductsTitle">Selecciona una marca</h2>
                <p id="brandProductsDescription">Visualiza aquí los productos de cada marca con filtros avanzados.</p>
            </div>
            <div class="brand-products-controls">
                <select id="brandSort">
                    <option value="relevance">Relevancia</option>
                    <option value="price_asc">Precio: menor a mayor</option>
                    <option value="price_desc">Precio: mayor a menor</option>
                    <option value="popularity">Popularidad</option>
                    <option value="rating">Mejor calificados</option>
                    <option value="newest">Más recientes</option>
                </select>
                <input type="number" id="brandMinPrice" placeholder="Precio mín" min="0">
                <input type="number" id="brandMaxPrice" placeholder="Precio máx" min="0">
                <select id="brandAvailability">
                    <option value="">Disponibilidad</option>
                    <option value="in">En stock</option>
                    <option value="out">Agotado</option>
                </select>
            </div>
        </header>
        <div id="brandProductsGrid" class="brand-products-grid">
            <div class="brand-empty-state">Selecciona una marca para ver sus productos.</div>
        </div>
    </section>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
        window.BRANDS_DATA = <?= json_encode($brands, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?= $BASE ?>js/marcas.js"></script>
</body>
</html>

