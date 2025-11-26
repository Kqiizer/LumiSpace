<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$categorias = getCategorias();

$conn = getDBConnection();
$brandOptions = [];
if (tableExists($conn, 'marcas')) {
    $res = $conn->query("SELECT id, nombre FROM marcas ORDER BY nombre ASC");
    if ($res) {
        $brandOptions = $res->fetch_all(MYSQLI_ASSOC);
    }
} elseif (tableExists($conn, 'proveedores')) {
    $res = $conn->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC");
    if ($res) {
        $brandOptions = $res->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar productos - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/search.css">
</head>
<body class="search-page">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="search-hero">
        <div class="container">
            <h1 class="search-title">Encuentra lo que necesitas</h1>
            <p class="search-subtitle">Busca por nombre, categoría, marca o incluso describe el producto.</p>
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Ej. lámpara moderna, escritorio, Samsung..." autocomplete="off">
                <button type="button" id="searchSubmit">
                    <span>Buscar</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                        <line x1="20" y1="20" x2="16" y2="16" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
                <ul id="searchSuggestions" class="search-suggestions"></ul>
            </div>
        </div>
    </section>

    <section class="search-layout">
        <aside class="filters-card">
            <h3>Filtros</h3>
            <form id="filtersForm">
                <div class="filter-group">
                    <label for="filterCategory">Categoría</label>
                    <select id="filterCategory">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['nombre'] ?? '') ?>">
                                <?= htmlspecialchars($cat['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterBrand">Marca</label>
                    <select id="filterBrand">
                        <option value="">Todas</option>
                        <?php foreach ($brandOptions as $brand): ?>
                            <option value="<?= htmlspecialchars($brand['nombre'] ?? '') ?>">
                                <?= htmlspecialchars($brand['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterColor">Color</label>
                    <select id="filterColor">
                        <option value="">Todos</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="filterSize">Talla / Tamaño</label>
                    <select id="filterSize">
                        <option value="">Todas</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Precio</label>
                    <div style="display:flex; gap:10px;">
                        <input type="number" id="filterMinPrice" placeholder="Mín" min="0">
                        <input type="number" id="filterMaxPrice" placeholder="Máx" min="0">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="filterAvailability">Disponibilidad</label>
                    <select id="filterAvailability">
                        <option value="">Todos</option>
                        <option value="in">En stock</option>
                        <option value="out">Agotado</option>
                    </select>
                </div>
            </form>
        </aside>

        <section class="results-card">
            <div class="results-header">
                <div class="results-summary" id="resultsSummary">Cargando resultados...</div>
                <select id="sortSelect">
                    <option value="relevance">Relevancia</option>
                    <option value="price_asc">Precio: menor a mayor</option>
                    <option value="price_desc">Precio: mayor a menor</option>
                    <option value="newest">Más recientes</option>
                    <option value="popularity">Más populares</option>
                    <option value="rating">Mejor calificados</option>
                </select>
            </div>
            <div class="filter-badges" id="filterBadges">
                <span class="filter-badge">Sin filtros activos</span>
            </div>
            <div class="results-grid" id="resultsGrid">
                <!-- Resultados dinámicos -->
            </div>
            <div class="pagination" id="pagination"></div>
        </section>
    </section>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
    </script>
    <script src="<?= $BASE ?>js/search.js"></script>
</body>
</html>

