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

// ==========================================
// FUNCIÓN HELPER PARA IMÁGENES
// ==========================================
function img_url($path, $BASE, $folder = 'productos') {
    $path = trim((string)$path);
    if ($path === '') return $BASE . "images/default.png";
    if (preg_match('#^https?://#i', $path)) return $path;
    return $BASE . "images/{$folder}/" . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Catálogo completo de productos LumiSpace - <?= $stats['productos'] ?> productos disponibles">
    <title>Catálogo de Productos - LumiSpace</title>
    
    <!-- Fonts & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/header.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/footer.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/responsive.css">
    <style>
        /* ==========================================
           VARIABLES Y CONFIGURACIÓN BASE
           ========================================== */
        :root {
            --primary-color: #a1683a;
            --primary-dark: #8f5e4b;
            --primary-light: #c2a98f;
            --secondary-color: #ff6b6b;
            --success-color: #51cf66;
            --warning-color: #ffd43b;
            --text-primary: #333;
            --text-secondary: #666;
            --text-muted: #999;
            --bg-primary: #fafafa;
            --bg-white: #ffffff;
            --bg-secondary: #f7f7f7;
            --bg-image: #f0f0f0;
            --border-color: #e0e0e0;
            --shadow-sm: 0 2px 8px rgba(0,0,0,.1);
            --shadow-md: 0 4px 12px rgba(0,0,0,.12);
            --shadow-lg: 0 8px 18px rgba(0,0,0,.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s ease;
        }

        /* ==========================================
           MODO OSCURO - Compatible con reset.css
           ========================================== */
        body.dark {
            --primary-color: #d4af7f;
            --primary-dark: #c2a98f;
            --primary-light: #8f7a5d;
            --secondary-color: #ff8787;
            --success-color: #69db7c;
            --warning-color: #ffe066;
            --text-primary: #f0f0f0;
            --text-secondary: #b8b8b8;
            --text-muted: #888888;
            --bg-primary: #1e1e1e;
            --bg-white: #2a2a2a;
            --bg-secondary: #2b2b2b;
            --bg-image: #2e2e2e;
            --border-color: #404040;
            --shadow-sm: 0 2px 8px rgba(0,0,0,.4);
            --shadow-md: 0 4px 12px rgba(0,0,0,.5);
            --shadow-lg: 0 8px 18px rgba(0,0,0,.6);
        }

        /* Transición suave al cambiar tema */
        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Asegurar que todos los elementos tengan transición suave */
        *,
        *::before,
        *::after {
            transition-property: background-color, color, border-color, box-shadow;
            transition-duration: 0.3s;
            transition-timing-function: ease;
        }

        /* Excepciones para animaciones específicas */
        .product-image,
        .action-btn,
        .product-card {
            transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* ==========================================
           BOTÓN DE TOGGLE MODO OSCURO
           ========================================== */
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 1000;
        }

        .theme-toggle:hover {
            transform: scale(1.1) rotate(15deg);
            box-shadow: 0 8px 25px rgba(161, 104, 58, 0.4);
        }

        .theme-toggle:active {
            transform: scale(0.95);
        }

        /* Icono animado */
        .theme-toggle i {
            transition: transform 0.5s ease;
        }

        .theme-toggle:hover i {
            transform: rotate(180deg);
        }

        /* ==========================================
           LAYOUT PRINCIPAL
           ========================================== */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ==========================================
           HERO SECTION
           ========================================== */
        .catalog-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
            padding: 80px 20px 60px;
            position: relative;
            overflow: hidden;
        }

        .catalog-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Modo oscuro - hero con menor opacidad */
        body.dark .catalog-hero::before {
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.03) 0%, transparent 50%);
        }

        .catalog-hero-content {
            position: relative;
            z-index: 1;
        }

        .catalog-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }

        .catalog-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            margin-bottom: 30px;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .hero-stat {
            text-align: center;
        }

        .hero-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .hero-stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ==========================================
           BARRA DE FILTROS Y BÚSQUEDA
           ========================================== */
        .filters-bar {
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,.05);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .filters-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px 15px;
        }

        /* Barra de búsqueda */
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 18px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--bg-secondary);
            color: var(--text-primary);
        }

        .search-input::placeholder {
            color: var(--text-muted);
            opacity: 0.7;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--bg-white);
            box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
        }

        body.dark .search-input:focus {
            box-shadow: 0 0 0 4px rgba(212, 175, 127, 0.2);
        }

        /* Asegurar que todos los inputs en modo oscuro se vean bien */
        body.dark input,
        body.dark select,
        body.dark textarea {
            color-scheme: dark;
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-actions {
            display: flex;
            gap: 10px;
        }

        .btn-icon {
            padding: 12px 20px;
            border: 2px solid var(--border-color);
            background: var(--bg-white);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .btn-icon:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--bg-secondary);
        }

        /* Chips de categorías */
        .category-filters {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: var(--primary-light) var(--bg-secondary);
        }

        .category-filters::-webkit-scrollbar {
            height: 6px;
            background: var(--bg-secondary);
        }

        .category-filters::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 3px;
        }

        .category-filters::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }

        .filters-scroll {
            display: flex;
            gap: 12px;
            padding-bottom: 5px;
        }

        .filter-chip {
            background: var(--bg-white);
            border: 2px solid var(--border-color);
            border-radius: 25px;
            padding: 10px 24px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            transition: var(--transition);
            color: var(--text-primary);
        }

        .filter-chip:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .filter-chip.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-chip .chip-count {
            opacity: 0.8;
            font-size: 0.85rem;
            margin-left: 5px;
        }

        /* ==========================================
           GRID DE PRODUCTOS
           ========================================== */
        .products-section {
            flex: 1;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            padding: 40px 15px;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .products-count {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }

        .products-count strong {
            color: var(--primary-color);
            font-weight: 700;
        }

        .view-toggle {
            display: flex;
            gap: 8px;
            background: var(--bg-white);
            padding: 5px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .view-btn {
            padding: 8px 16px;
            border: none;
            background: transparent;
            cursor: pointer;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            color: var(--text-secondary);
        }

        .view-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        /* ==========================================
           TARJETAS DE PRODUCTOS
           ========================================== */
        .product-card {
            background: var(--bg-white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-color);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .product-image-wrapper {
            position: relative;
            height: 280px;
            background: var(--bg-image);
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.08);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--secondary-color);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.4);
        }

        .product-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
            transform: translateX(0);
        }

        .action-btn {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
            color: var(--text-primary);
        }

        /* Modo oscuro - botones de acción */
        body.dark .action-btn {
            background: rgba(42, 42, 42, 0.95);
            color: var(--text-primary);
        }

        .action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .action-btn.active {
            background: var(--secondary-color);
            color: white;
        }

        .catalog-wishlist-btn {
            border: 1px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .catalog-wishlist-btn i {
            font-size: 1rem;
        }

        .catalog-wishlist-btn:hover:not([disabled]) {
            background: var(--primary-color);
            color: #fff;
        }

        .catalog-wishlist-btn.active {
            background: var(--primary-color);
            color: #fff;
        }

        .catalog-wishlist-btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .product-cta {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .product-detail-btn {
            background: var(--primary-color);
            color: #fff;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .product-detail-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(161, 104, 58, 0.25);
        }

        .catalog-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--bg-white);
            color: var(--text-primary);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid var(--primary-color);
            animation: toastIn 0.3s ease;
            z-index: 2000;
        }

        body.dark .catalog-toast {
            background: var(--bg-white);
            color: var(--text-primary);
        }

        .catalog-toast.hide {
            animation: toastOut 0.3s ease forwards;
        }

        .catalog-toast.success {
            border-left-color: #28a745;
        }

        .catalog-toast.error {
            border-left-color: #dc3545;
        }

        .catalog-toast.warning {
            border-left-color: #ffc107;
        }

        @keyframes toastIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes toastOut {
            to { opacity: 0; transform: translateY(-10px); }
        }

        .product-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category-tag {
            font-size: 0.75rem;
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 12px;
            min-height: 2.4em;
            line-height: 1.4;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: auto;
        }

        .product-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .original-price {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 1rem;
        }

        /* ==========================================
           ESTADO VACÍO
           ========================================== */
        .empty-state {
            text-align: center;
            padding: 100px 20px;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .empty-state p {
            color: var(--text-muted);
        }

        /* ==========================================
           TOAST NOTIFICATIONS
           ========================================== */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--bg-white);
            padding: 16px 24px;
            border-radius: var(--radius-md);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
            z-index: 1000;
            max-width: 350px;
            border: 1px solid var(--border-color);
        }

        .toast.success {
            border-left: 4px solid var(--success-color);
        }

        .toast.error {
            border-left: 4px solid var(--secondary-color);
        }

        .toast.warning {
            border-left: 4px solid var(--warning-color);
        }

        .toast i {
            font-size: 1.2rem;
        }

        .toast.success i {
            color: var(--success-color);
        }

        .toast.error i {
            color: var(--secondary-color);
        }

        .toast.warning i {
            color: var(--warning-color);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ==========================================
           LOADING SKELETON
           ========================================== */
        .skeleton {
            animation: skeleton-loading 1s linear infinite alternate;
        }

        @keyframes skeleton-loading {
            0% {
                background-color: hsl(200, 20%, 80%);
            }
            100% {
                background-color: hsl(200, 20%, 95%);
            }
        }

        body.dark .skeleton {
            animation: skeleton-loading-dark 1s linear infinite alternate;
        }

        @keyframes skeleton-loading-dark {
            0% {
                background-color: hsl(0, 0%, 25%);
            }
            100% {
                background-color: hsl(0, 0%, 35%);
            }
        }

        /* ==========================================
           RESPONSIVE DESIGN
           ========================================== */
        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .catalog-hero h1 {
                font-size: 2.2rem;
            }

            .hero-stats {
                gap: 30px;
            }

            .hero-stat-number {
                font-size: 2rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }

            .product-image-wrapper {
                height: 220px;
            }

            .search-bar {
                flex-direction: column;
            }

            .search-input-wrapper {
                width: 100%;
            }

            .search-actions {
                width: 100%;
            }

            .btn-icon {
                flex: 1;
                justify-content: center;
            }

            .toast {
                bottom: 15px;
                right: 15px;
                left: 15px;
                max-width: none;
            }

            .theme-toggle {
                bottom: 100px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .catalog-hero {
                padding: 50px 15px 40px;
            }

            .catalog-hero h1 {
                font-size: 1.8rem;
            }

            .hero-stats {
                gap: 20px;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .product-info {
                padding: 15px;
            }

            .product-name {
                font-size: 0.95rem;
            }

            .product-price {
                font-size: 1.2rem;
            }

            .theme-toggle {
                left: 15px;
                bottom: 80px;
            }
        }
    </style>
</head>

<body>
    <div class="page-wrapper">
        <!-- Botón de Toggle Tema -->
        <button class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
            <i class="fas fa-moon"></i>
        </button>

        <!-- Header -->
        <?php include __DIR__ . "/../includes/header.php"; ?>

        <!-- Hero Section -->
        <section class="catalog-hero">
            <div class="catalog-hero-content">
                <h1>
                    <i class="fas fa-store"></i>
                    Catálogo Completo
                </h1>
                <p>Explora nuestra selección de <?= $stats['productos'] ?> productos en <?= $stats['categorias'] ?> categorías diferentes</p>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats['productos'] ?></span>
                        <span class="hero-stat-label">Productos</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats['categorias'] ?></span>
                        <span class="hero-stat-label">Categorías</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats['clientes'] ?></span>
                        <span class="hero-stat-label">Clientes</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Barra de Filtros y Búsqueda -->
        <div class="filters-bar">
            <div class="filters-container">
                
                <!-- Búsqueda -->
                <div class="search-bar">
                    <div class="search-input-wrapper">
                        <input type="text" 
                               class="search-input" 
                               id="searchInput" 
                               placeholder="Buscar productos por nombre...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                    <div class="search-actions">
                        <button class="btn-icon" id="btnClearFilters" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                            <span>Limpiar</span>
                        </button>
                    </div>
                </div>

                <!-- Filtros de Categorías -->
                <div class="category-filters">
                    <div class="filters-scroll">

                        <!-- Botón TODOS -->
                        <button class="filter-chip active" data-category="">
                            Todos
                            <span class="chip-count">(<?= count($todos_productos) ?>)</span>
                        </button>

                        <!-- Botones de categorías -->
                        <?php foreach ($categorias_db as $cat): 
                            $count = $productos_por_categoria[$cat['id']] ?? 0;

                            if ($count > 0): ?>
                                <button class="filter-chip" data-category="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                    <span class="chip-count">(<?= $count ?>)</span>
                                </button>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productos -->
        <section class="products-section">

            <div class="products-header">
                <div class="products-count">
                    Mostrando <strong id="visibleCount"><?= count($todos_productos) ?></strong> de 
                    <strong><?= count($todos_productos) ?></strong> productos
                </div>
            </div>

            <div class="products-grid" id="productsGrid" data-base="<?= htmlspecialchars($BASE) ?>">
                <?php foreach ($todos_productos as $p): 
            $img = img_url($p['imagen'], $BASE);
            $precio = (float)$p['precio'];
            $precioOriginal = (float)($p['precio_original'] ?? 0);
            $desc = $precioOriginal > $precio ? round(100 - ($precio * 100 / $precioOriginal)) : 0;
            $fav = !empty($favoritosSet[$p['id']]);
        ?>
        <div class="product-card"
             data-id="<?= $p['id'] ?>"
             data-category="<?= $p['categoria_id'] ?>"
             data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">

            <div class="product-image-wrapper">
                <div class="product-image" style="background-image:url('<?= $img ?>')"></div>

                <?php if ($desc): ?>
                    <div class="product-badge">-<?= $desc ?>%</div>
                <?php endif; ?>

                <div class="product-actions">
                    <button class="action-btn js-wish <?= $fav ? 'active' : '' ?>"
                            title="<?= $fav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
                        <i class="fas fa-heart"></i>
                    </button>

                    <a class="action-btn"
                       href="<?= $BASE ?>views/productos-detal.php?id=<?= $p['id'] ?>"
                       title="Ver detalles del producto">
                        <i class="fas fa-eye"></i>
                    </a>

                    <button class="action-btn js-cart" title="Agregar al carrito">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>

            <div class="product-info">
                <div class="product-category-tag">
                    <?= htmlspecialchars($p['categoria']) ?>
                </div>

                <div class="product-name">
                    <?= htmlspecialchars($p['nombre']) ?>
                </div>

                <div class="product-price-wrapper">
                    <span class="product-price">$<?= number_format($precio, 2) ?></span>

                    <?php if ($desc): ?>
                        <span class="original-price">$<?= number_format($precioOriginal, 2) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="empty-state" id="emptyState" style="display:none;">
        <i class="fas fa-search"></i>
        <h3>No se encontraron productos</h3>
        <p>Intenta con otros filtros o términos de búsqueda</p>
    </div>

</section>


<!-- =================================================================== -->
<!--  🔥 JAVASCRIPT INTEGRADO - FILTROS 100% FUNCIONALES -->
<!-- =================================================================== -->

<script>
document.addEventListener("DOMContentLoaded", () => {

    const products = document.querySelectorAll(".product-card");
    const chips = document.querySelectorAll(".filter-chip");
    const searchInput = document.getElementById("searchInput");
    const emptyState = document.getElementById("emptyState");
    const visibleCount = document.getElementById("visibleCount");
    const btnClear = document.getElementById("btnClearFilters");

    function aplicarFiltros() {
        const texto = searchInput.value.toLowerCase();
        const chipActivo = document.querySelector(".filter-chip.active");
        const categoria = chipActivo ? chipActivo.dataset.category : "";

        let visibles = 0;

        products.forEach(p => {
            const nombre = p.dataset.nombre.toLowerCase();
            const cat = p.dataset.category;

            const coincideTexto = nombre.includes(texto);
            const coincideCategoria = categoria === "" || categoria === cat;

            if (coincideTexto && coincideCategoria) {
                p.style.display = "block";
                visibles++;
            } else {
                p.style.display = "none";
            }
        });

        visibleCount.textContent = visibles;
        emptyState.style.display = visibles === 0 ? "block" : "none";
    }

    chips.forEach(chip => {
        chip.addEventListener("click", () => {
            chips.forEach(c => c.classList.remove("active"));
            chip.classList.add("active");
            aplicarFiltros();
        });
    });

    searchInput.addEventListener("keyup", aplicarFiltros);

    btnClear.addEventListener("click", () => {
        searchInput.value = "";
        chips.forEach(c => c.classList.remove("active"));
        chips[0].classList.add("active");
        aplicarFiltros();
    });

});
</script>


        <!-- Footer -->
        <?php include __DIR__ . "/../includes/footer.php"; ?>
    </div>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
    </script>
    <script src="<?= $BASE ?>js/catalogo.js"></script>
</body>
</html>

