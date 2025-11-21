<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : './';
$conn = getDBConnection();
$categorias = getCategorias();
$stats = [
    'productos'  => (int)($conn->query("SELECT COUNT(*) AS total FROM productos WHERE activo = 1")->fetch_assoc()['total'] ?? 0),
    'categorias' => count($categorias),
    'clientes'   => (int)($conn->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol!='admin'")->fetch_assoc()['total'] ?? 0),
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/catalogo.css">
</head>
<body class="catalog-page">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="catalog-hero">
        <div class="container">
            <h1>Catálogo de productos</h1>
            <p>Explora todas nuestras colecciones, filtra por tus preferencias y encuentra lo que necesitas.</p>
            <div class="catalog-stats">
                <article class="catalog-stat">
                    <strong><?= $stats['productos'] ?></strong>
                    Productos disponibles
                </article>
                <article class="catalog-stat">
                    <strong><?= $stats['categorias'] ?></strong>
                    Categorías
                </article>
                <article class="catalog-stat">
                    <strong><?= $stats['clientes'] ?></strong>
                    Clientes satisfechos
                </article>
            </div>
        </div>
    </section>

    <section class="catalog-layout">
        <aside class="filters-panel">
            <h3>Filtrar por</h3>

            <div class="filter-group">
                <label>Categorías</label>
                <div id="catalogCategories" class="categories-list">
                    <div class="category-item active" data-category="">
                        <span>Todos</span>
                        <small><?= $stats['productos'] ?></small>
                    </div>
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="category-item" data-category="<?= htmlspecialchars($categoria['nombre']) ?>">
                            <span><?= htmlspecialchars($categoria['nombre']) ?></span>
                            <small><?= (int)($categoria['total'] ?? 0) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-group">
                <label for="filterBrand">Marca</label>
                <select id="filterBrand" data-placeholder="Todas las marcas">
                    <option value="">Todas</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterColor">Color</label>
                <select id="filterColor" data-placeholder="Todos los colores">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="filter-group">
                <label for="filterSize">Talla / Tamaño</label>
                <select id="filterSize" data-placeholder="Todas las tallas">
                    <option value="">Todos</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Precio</label>
                <input type="number" id="filterPriceMin" placeholder="Desde $" min="0">
                <input type="number" id="filterPriceMax" placeholder="Hasta $" min="0" style="margin-top:8px;">
            </div>

            <div class="filter-group">
                <label for="filterAvailability">Disponibilidad</label>
                <select id="filterAvailability">
                    <option value="">Todos</option>
                    <option value="in">En stock</option>
                    <option value="out">Agotado</option>
                </select>
            </div>

            <div class="filter-group">
                <label>
                    <input type="checkbox" id="filterDiscount">
                    Solo productos con descuento
                </label>
            </div>
        </aside>

        <section class="catalog-results">
            <header class="results-header">
                <div id="resultsInfo">Mostrando productos...</div>
                <div class="catalog-sort">
                    <label for="catalogSort">Ordenar:</label>
                    <select id="catalogSort">
                        <option value="relevance">Relevancia</option>
                        <option value="price_asc">Precio: menor a mayor</option>
                        <option value="price_desc">Precio: mayor a menor</option>
                        <option value="popularity">Popularidad</option>
                        <option value="rating">Mejor calificación</option>
                        <option value="newest">Novedades</option>
                    </select>
                </div>
            </header>

            <div id="productGrid" class="product-grid"></div>
            <div id="catalogPagination" class="pagination"></div>
        </section>
    </section>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
    </script>
    <script src="<?= $BASE ?>js/catalogo.js"></script>
</body>
</html>

