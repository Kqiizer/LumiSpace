<?php
/**
 * ============================================
 * CATÁLOGO DE PRODUCTOS - LUMISPACE
 * ============================================
 * Archivo: catalogo.php
 * Descripción: Catálogo completo de productos con filtros y búsqueda
 * ============================================
 */

// ==========================================
// INICIALIZACIÓN
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../config/functions.php";

// Configuración base
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$conn = getDBConnection();

// ==========================================
// OBTENER CATEGORÍAS Y PRODUCTOS
// ==========================================
$categorias_db = getCategorias();

$sql = "SELECT p.*, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = 1
        ORDER BY p.id DESC";
$res = $conn->query($sql);
$todos_productos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Contar productos por categoría
$productos_por_categoria = [];
foreach ($todos_productos as $p) {
    $cat_id = (int)($p['categoria_id'] ?? 0);
    $productos_por_categoria[$cat_id] = ($productos_por_categoria[$cat_id] ?? 0) + 1;
}

// ==========================================
// OBTENER FAVORITOS DEL USUARIO
// ==========================================
$favoritosSet = [];
if ($usuario_id && $conn->query("SHOW TABLES LIKE 'favoritos'")->num_rows > 0) {
    $stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $favoritosSet[(int)$row['producto_id']] = true;
    }
    $stmt->close();
}

// ==========================================
// ESTADÍSTICAS DEL SISTEMA
// ==========================================
$stats = [
    'productos' => count($todos_productos),
    'categorias' => count($categorias_db),
    'clientes' => (int)($conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol!='admin'")->fetch_assoc()['total'] ?? 0),
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
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/responsive.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
    <style>
        /* ==========================================
           VARIABLES Y CONFIGURACIÓN BASE
           ========================================== */
        :root {
            --primary-color: #6D5A42;
            --primary-dark: #A0896B;
            --primary-light: #d6c7b2;
            --secondary-color: #c4ae95;
            --success-color: #6ea37c;
            --warning-color: #d9aa6a;
            --text-primary: #32281c;
            --text-secondary: #6b5b49;
            --text-muted: #9a8b7a;
            --bg-primary: #f9f4ed;
            --bg-white: #ffffff;
            --bg-secondary: #f2e7d9;
            --bg-image: #e9dfd1;
            --border-color: rgba(109, 90, 66, 0.25);
            --shadow-sm: 0 2px 8px rgba(34, 23, 8, 0.08);
            --shadow-md: 0 4px 14px rgba(34, 23, 8, 0.12);
            --shadow-lg: 0 10px 26px rgba(34, 23, 8, 0.16);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s ease;
        }

        /* ==========================================
           MODO OSCURO - Compatible con reset.css
           ========================================== */
        body.dark {
            --primary-color: #bea583;
            --primary-dark: #d7c6ac;
            --primary-light: #8d775d;
            --secondary-color: #b89978;
            --success-color: #80c091;
            --warning-color: #edc082;
            --text-primary: #f6f1e8;
            --text-secondary: #d8ccbd;
            --text-muted: #a69988;
            --bg-primary: #1b1712;
            --bg-white: #262018;
            --bg-secondary: #2d261e;
            --bg-image: #332a20;
            --border-color: rgba(255,255,255,0.15);
            --shadow-sm: 0 2px 8px rgba(0,0,0,.45);
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
            box-shadow: 0 8px 25px rgba(109, 90, 66, 0.4);
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
            box-shadow: 0 0 0 4px rgba(109, 90, 66, 0.1);
        }

        body.dark .search-input:focus {
            box-shadow: 0 0 0 4px rgba(190, 165, 131, 0.2);
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
                        <button class="filter-chip active" data-category="">
                            Todos
                            <span class="chip-count">(<?= count($todos_productos) ?>)</span>
                        </button>
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
                    Mostrando <strong id="visibleCount"><?= count($todos_productos) ?></strong> de <strong><?= count($todos_productos) ?></strong> productos
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
                            <button class="action-btn js-cart" 
                                    title="Agregar al carrito">
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

            <!-- Estado Vacío -->
            <div class="empty-state" id="emptyState" style="display:none;">
                <i class="fas fa-search"></i>
                <h3>No se encontraron productos</h3>
                <p>Intenta con otros filtros o términos de búsqueda</p>
            </div>
        </section>

        <!-- Sección Conoces - Integrantes -->
        <section class="team-section" style="padding: 80px 20px; background: var(--bg-primary);">
            <div class="container" style="max-width: 1200px; margin: 0 auto;">
                <div style="text-align: center; margin-bottom: 50px;">
                    <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 700; color: var(--text-primary); margin-bottom: 15px;">
                        ¿Conoces a nuestro equipo?
                    </h2>
                    <p style="font-size: 1.1rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
                        Conoce a las personas detrás de LumiSpace
                    </p>
                </div>

                <div class="team-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; margin-top: 40px;">
                    <!-- Carta de Ximena Cuevas -->
                    <div class="team-card" style="background: var(--bg-white); border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-md); transition: all 0.3s ease;">
                        <div class="team-image" style="position: relative; height: 300px; overflow: hidden; background: linear-gradient(135deg, #e8ddd4, #d4c4a8);">
                            <img src="<?= $BASE ?>images/imagenes-integrantes/ximena-crv.jpeg" 
                                 alt="Ximena Cuevas" 
                                 style="width: 100%; height: 100%; object-fit: cover; display: block;">
                        </div>
                        <div class="team-info" style="padding: 25px 20px; text-align: center;">
                            <h3 style="font-size: 1.3rem; color: var(--primary-color); margin-bottom: 8px; font-weight: 600;">
                                Ximena Cuevas
                            </h3>
                            <p class="team-role" style="color: var(--primary-dark); font-size: 0.95rem; font-weight: 500; margin-bottom: 12px;">
                                Miembro del Equipo
                            </p>
                        </div>
                    </div>

                    <!-- Puedes agregar más cartas de integrantes aquí -->
                </div>
            </div>
        </section>

        <!-- Footer -->
        <?php include __DIR__ . "/../includes/footer.php"; ?>
    </div>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
    </script>
    <script src="<?= $BASE ?>js/catalogo.js"></script>
</body>
</html>
