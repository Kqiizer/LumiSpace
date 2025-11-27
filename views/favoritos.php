<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$uid = (int)($_SESSION['usuario_id'] ?? 0);

// Validar autenticación
if ($uid <= 0) {
    header("Location: " . $BASE . "views/login.php?next=" . urlencode($BASE . "views/favoritos.php"));
    exit;
}

// Obtener favoritos
$favoritos = getFavoritos($uid);
$favoritosCount = count($favoritos);

require_once __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos | LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/favoritos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body data-base="<?= htmlspecialchars($BASE) ?>" data-user-id="<?= $uid ?>">
    
    <!-- Hero Section -->
    <section class="favorites-hero">
        <div class="hero-background">
            <div class="hero-pattern"></div>
        </div>
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-heart"></i>
                <span>Tu Colección Personal</span>
            </div>
            <h1 class="hero-title">Mis Favoritos</h1>
            <p class="hero-description">
                Guarda las piezas que te inspiran y compártelas cuando estés listo para transformar tu espacio.
            </p>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number" id="favorites-count"><?= $favoritosCount ?></span>
                    <span class="stat-label">Productos guardados</span>
                </div>
            </div>
        </div>
    </section>

    <main class="favorites-main">
        <div class="container">
            <?php if (empty($favoritos)): ?>
                <!-- Empty State -->
                <div class="empty-state-container">
                    <div class="empty-state">
                        <div class="empty-icon-container">
                            <div class="empty-icon-circle">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="empty-ripple"></div>
                            <div class="empty-ripple"></div>
                        </div>
                        <h2 class="empty-title">Tu lista de deseos está vacía</h2>
                        <p class="empty-description">
                            Aún no has guardado productos en tus favoritos.<br>
                            Explora nuestra colección y descubre las piezas perfectas para iluminar tu espacio.
                        </p>
                        <a href="<?= $BASE ?>views/catalogo.php" class="empty-cta">
                            <i class="fas fa-search"></i>
                            <span>Explorar Catálogo</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Toolbar -->
                <div class="favorites-toolbar">
                    <div class="toolbar-left">
                        <h2 class="toolbar-title">
                            <i class="fas fa-heart"></i>
                            <span>Artículos Guardados</span>
                            <span class="toolbar-count"><?= $favoritosCount ?></span>
                        </h2>
                    </div>
                    <div class="toolbar-right">
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid" title="Vista de cuadrícula">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="view-btn" data-view="list" title="Vista de lista">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        <button class="clear-all-btn" id="clearAllFavorites" title="Eliminar todos los favoritos">
                            <i class="fas fa-trash-alt"></i>
                            <span>Limpiar todo</span>
                        </button>
                    </div>
                </div>

                <!-- Filters Bar -->
                <div class="filters-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            id="favorites-search" 
                            class="search-input" 
                            placeholder="Buscar en favoritos..."
                            autocomplete="off"
                        >
                        <button class="search-clear" id="searchClear" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="sort-wrapper">
                        <label for="sortSelect" class="sort-label">
                            <i class="fas fa-sort"></i>
                            Ordenar por:
                        </label>
                        <select id="sortSelect" class="sort-select">
                            <option value="recent">Más recientes</option>
                            <option value="oldest">Más antiguos</option>
                            <option value="price-low">Precio: menor a mayor</option>
                            <option value="price-high">Precio: mayor a menor</option>
                            <option value="name-asc">Nombre: A-Z</option>
                            <option value="name-desc">Nombre: Z-A</option>
                        </select>
                    </div>
                </div>

                <!-- Categories Filter -->
                <div class="categories-filter" id="categoriesFilter">
                    <!-- Se llena dinámicamente con JavaScript -->
                </div>

                <!-- Products Grid -->
                <div class="favorites-grid" id="favoritesGrid" data-view="grid">
                    <?php foreach ($favoritos as $producto): 
                        $producto_id = (int)($producto['id'] ?? $producto['producto_id'] ?? 0);
                        if ($producto_id <= 0) continue;
                        
                        $imagen = !empty($producto['imagen']) ? htmlspecialchars($producto['imagen']) : ($BASE . 'images/default.png');
                        $descuento = isset($producto['descuento']) ? (int)$producto['descuento'] : 0;
                        $stock = isset($producto['stock'] ?? $producto['stock_real'] ?? 0) ? (int)($producto['stock'] ?? $producto['stock_real'] ?? 0) : 0;
                        $categoria = !empty($producto['categoria']) ? htmlspecialchars($producto['categoria']) : 'Sin categoría';
                        $nombre = !empty($producto['nombre']) ? htmlspecialchars($producto['nombre']) : 'Producto sin nombre';
                        $precio = isset($producto['precio']) ? (float)$producto['precio'] : 0.0;
                        $precio_original = (isset($producto['precio_original']) && $producto['precio_original'] !== null && $producto['precio_original'] > 0) ? (float)$producto['precio_original'] : null;
                        
                        // Calcular descuento si no está definido
                        if ($descuento === 0 && $precio_original && $precio_original > $precio) {
                            $descuento = (int)round((($precio_original - $precio) / $precio_original) * 100);
                        }
                    ?>
                        <article class="favorite-card" data-id="<?= $producto_id ?>" data-category="<?= strtolower($categoria) ?>" data-name="<?= strtolower($nombre) ?>" data-price="<?= $precio ?>" data-stock="<?= $stock ?>">
                            <div class="card-image-container">
                                <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $producto_id ?>" class="card-image-link">
                                    <div class="card-image" style="background-image: url('<?= $imagen ?>');">
                                        <div class="image-overlay"></div>
                                    </div>
                                </a>
                                
                                <!-- Badges -->
                                <div class="card-badges">
                                    <?php if ($descuento > 0): ?>
                                        <span class="badge badge-discount">
                                            <i class="fas fa-tag"></i>
                                            <span>-<?= $descuento ?>%</span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($stock <= 0): ?>
                                        <span class="badge badge-out">
                                            <i class="fas fa-times-circle"></i>
                                            <span>Agotado</span>
                                        </span>
                                    <?php elseif ($stock < 10): ?>
                                        <span class="badge badge-low-stock">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Últimos <?= $stock ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Quick Actions -->
                                <div class="card-actions">
                                    <button 
                                        class="action-btn action-remove" 
                                        data-product-id="<?= $producto_id ?>"
                                        title="Eliminar de favoritos"
                                        aria-label="Eliminar de favoritos"
                                    >
                                        <i class="fas fa-heart-broken"></i>
                                    </button>
                                    <button 
                                        class="action-btn action-cart <?= $stock <= 0 ? 'disabled' : '' ?>" 
                                        data-product-id="<?= $producto_id ?>"
                                        title="Agregar al carrito"
                                        aria-label="Agregar al carrito"
                                        <?= $stock <= 0 ? 'disabled' : '' ?>
                                    >
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                    <a 
                                        href="<?= $BASE ?>views/productos-detal.php?id=<?= $producto_id ?>" 
                                        class="action-btn action-view"
                                        title="Ver detalles"
                                        aria-label="Ver detalles del producto"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            
                            <div class="card-content">
                                <div class="card-meta">
                                    <span class="card-category"><?= $categoria ?></span>
                                    <span class="card-stock <?= $stock > 0 ? 'in-stock' : 'out-stock' ?>">
                                        <i class="fas fa-<?= $stock > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                                        <span><?= $stock > 0 ? 'Disponible' : 'Agotado' ?></span>
                                    </span>
                                </div>
                                
                                <h3 class="card-title">
                                    <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $producto_id ?>">
                                        <?= $nombre ?>
                                    </a>
                                </h3>
                                
                                <div class="card-footer">
                                    <div class="card-price">
                                        <span class="price-current">$<?= number_format($precio, 2) ?></span>
                                        <?php if ($precio_original && $precio_original > $precio): ?>
                                            <span class="price-original">$<?= number_format($precio_original, 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <button 
                                        class="card-add-btn <?= $stock <= 0 ? 'disabled' : '' ?>" 
                                        data-product-id="<?= $producto_id ?>"
                                        <?= $stock <= 0 ? 'disabled' : '' ?>
                                    >
                                        <i class="fas fa-shopping-cart"></i>
                                        <span>Agregar</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Empty Search State -->
                <div class="empty-search-state" id="emptySearchState" style="display: none;">
                    <div class="empty-search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="empty-search-title">No se encontraron productos</h3>
                    <p class="empty-search-text">Intenta con otros términos de búsqueda o filtros</p>
                    <button class="empty-search-clear" id="clearFilters">
                        <i class="fas fa-times"></i>
                        <span>Limpiar filtros</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Toast Notification -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Confirmation Modal -->
    <div class="modal-overlay" id="confirmModal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Confirmar acción</h3>
                <button class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="modal-message" id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" id="modalCancel">Cancelar</button>
                <button class="modal-btn modal-btn-confirm" id="modalConfirm">Confirmar</button>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Global variables
        window.FAVORITES_DATA = <?= json_encode($favoritos, JSON_UNESCAPED_UNICODE) ?>;
        window.BASE_URL = <?= json_encode($BASE, JSON_UNESCAPED_UNICODE) ?>;
        window.USER_ID = <?= $uid ?>;
    </script>
    <script src="<?= $BASE ?>js/favoritos.js" defer></script>
</body>
</html>
