<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = rtrim(BASE_URL, '/') . '/';
$favoritesUrl = $baseUrl . 'index/favoritos.php';
$usuarioId = (int)($_SESSION['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    header('Location: ' . $baseUrl . 'views/login.php?next=' . urlencode($favoritesUrl));
    exit();
}

$favoritosData = getFavoritos($usuarioId);
$exploreRaw = getProductosPublicos(8);

$mapProduct = static function (array $product) {
    $price = isset($product['precio']) ? (float)$product['precio'] : (float)($product['price'] ?? 0);
    $originalPrice = null;
    if (array_key_exists('precio_original', $product) && $product['precio_original'] !== null) {
        $originalPrice = (float)$product['precio_original'];
    } elseif (isset($product['originalPrice'])) {
        $originalPrice = (float)$product['originalPrice'];
    }
    $discount = isset($product['descuento']) ? (float)$product['descuento'] : (float)($product['discount'] ?? 0);
    if (!$discount && $originalPrice && $originalPrice > $price) {
        $discount = round((($originalPrice - $price) / $originalPrice) * 100);
    }
    $stock = isset($product['stock']) ? (int)$product['stock'] : (int)($product['stock_real'] ?? 0);
    $image = publicImageUrl($product['imagen'] ?? $product['image'] ?? '');

    return [
        'id' => (int)$product['id'],
        'name' => $product['nombre'] ?? $product['name'] ?? 'Producto sin nombre',
        'category' => $product['categoria'] ?? $product['category'] ?? 'Otros',
        'price' => $price,
        'originalPrice' => $originalPrice,
        'discount' => $discount,
        'image' => $image,
        'stock' => $stock,
        'rating' => isset($product['rating']) ? (float)$product['rating'] : 4.8,
        'reviews' => isset($product['reviews']) ? (int)$product['reviews'] : 0,
        'description' => $product['descripcion'] ?? $product['description'] ?? '',
        'added_at' => $product['agregado_en'] ?? $product['added_at'] ?? null,
    ];
};

$favoritesForJS = array_map($mapProduct, $favoritosData);
$exploreForJS = array_map($mapProduct, $exploreRaw);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - Luminarias</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . 'css/styles/favoritos.css', ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    
    <div id="notification" class="notification hidden">
        <svg class="notification-icon" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        <span id="notificationMessage"></span>
    </div>

    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="back-button">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Inicio</span>
                </a>
                <div class="header-left">
                    <h1 class="header-title">
                        <svg class="header-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        Mis Favoritos
                    </h1>
                    <p class="header-subtitle">
                        <span id="favoritesCount">0</span> productos guardados
                    </p>
                </div>
                <div class="header-right">
                    <button class="header-btn" id="cartBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Carrito
                        <span id="cartCount" class="cart-badge hidden">0</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

   
    <main class="main">
        <div class="container">
           
            <div class="filters-section">
                <div class="search-sort-container">
                    
                    <div class="search-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input" 
                            placeholder="Buscar en favoritos..."
                        >
                    </div>

                   
                    <select id="sortSelect" class="sort-select">
                        <option value="recent">Más recientes</option>
                        <option value="price-low">Menor precio</option>
                        <option value="price-high">Mayor precio</option>
                        <option value="discount">Mayor descuento</option>
                    </select>
                </div>

                
                <div class="categories" id="categories">
                   
                </div>
            </div>

            
            <div id="productsGrid" class="products-grid">
                
            </div>

            
            <div id="emptyState" class="empty-state hidden">
                <svg class="empty-icon" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <h2 class="empty-title">Aún no tienes favoritos</h2>
                <p class="empty-text">Comienza a explorar y guarda tus luminarias favoritas</p>
                <a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" class="empty-cta">
                    <i class="fas fa-home"></i>
                    <span>Ir al Inicio</span>
                </a>
            </div>

            
            <div class="explore-section">
                <h2 class="explore-title">Explorar más productos</h2>
                <div id="exploreGrid" class="explore-grid">
                 
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Confirmación -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h3 class="confirm-modal-title" id="confirmModalTitle">Confirmar acción</h3>
            <p class="confirm-modal-message" id="confirmModalMessage">¿Estás seguro de realizar esta acción?</p>
            <div class="confirm-modal-buttons">
                <button class="confirm-btn confirm-btn-cancel" id="confirmModalCancel">Cancelar</button>
                <button class="confirm-btn confirm-btn-confirm" id="confirmModalConfirm">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        window.BASE_URL = "<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>";
        window.USER_LOGGED = true;
        window.FAVORITES_ENDPOINT = "<?= htmlspecialchars($baseUrl . 'api/wishlist/toggle.php', ENT_QUOTES, 'UTF-8'); ?>";
        window.FAVORITES_DATA = <?= json_encode($favoritesForJS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.EXPLORE_DATA = <?= json_encode($exploreForJS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.LOGIN_URL = "<?= htmlspecialchars($baseUrl . 'views/login.php?next=' . urlencode($favoritesUrl), ENT_QUOTES, 'UTF-8'); ?>";
    </script>
    <script src="<?= htmlspecialchars($baseUrl . 'js/favoritos.js', ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>