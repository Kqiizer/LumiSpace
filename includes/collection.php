<?php
/**
 * Catálogo de Productos - LumiSpace
 * Componente completo y optimizado
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// ========================================
// OBTENER DATOS
// ========================================
$categorias = getCategorias();
$productos = [];

$prods1 = getProductosCatalogo(null, 200);
$prods2 = getProductosPublicos(200);
$productos = array_merge($prods1 ?: [], $prods2 ?: []);

// Eliminar duplicados
$productosUnicos = [];
$idsVistos = [];
foreach ($productos as $p) {
    $id = (int)($p['id'] ?? 0);
    if ($id && !in_array($id, $idsVistos)) {
        $idsVistos[] = $id;
        $productosUnicos[] = $p;
    }
}
$productos = $productosUnicos;

// Obtener inventario
$conn = getDBConnection();
$inventarioMap = [];
if ($conn && !empty($productos)) {
    $productIds = array_column($productos, 'id');
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $conn->prepare("SELECT producto_id, cantidad FROM inventario WHERE producto_id IN ($placeholders)");
    if ($stmt) {
        $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $inventarioMap[(int)$row['producto_id']] = (int)$row['cantidad'];
        }
        $stmt->close();
    }
}

foreach ($productos as &$producto) {
    $producto['cantidad_inventario'] = $inventarioMap[(int)$producto['id']] ?? 0;
}
unset($producto);

// Favoritos
$favoritosSet = [];
if ($usuario_id && $conn) {
    $stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $favoritosSet[(int)$row['producto_id']] = true;
        }
        $stmt->close();
    }
}

// Conteo por categoría
$conteoCategoria = [];
foreach ($productos as $p) {
    $catId = (int)($p['categoria_id'] ?? 0);
    $conteoCategoria[$catId] = ($conteoCategoria[$catId] ?? 0) + 1;
}

/**
 * Genera URL de imagen del producto
 * Maneja: URLs completas, rutas relativas, uploads, solo nombre de archivo
 */
function getImagenProducto($img, $base) {
    $img = trim((string)$img);
    
    // Si está vacía, devolver imagen por defecto
    if (empty($img) || $img === 'null' || $img === 'undefined') {
        return $base . 'images/productos/default.png';
    }
    
    // Normalizar barras
    $img = str_replace('\\', '/', $img);
    
    // Si ya es una URL completa (http o https)
    if (preg_match('#^https?://#i', $img)) {
        return $img;
    }
    
    // Si empieza con // (protocolo relativo)
    if (strpos($img, '//') === 0) {
        return 'https:' . $img;
    }
    
    // Quitar BASE duplicado si existe
    $img = preg_replace('#^' . preg_quote($base, '#') . '#', '', $img);
    
    // Quitar slash inicial
    $img = ltrim($img, '/');
    
    // Si viene de uploads/
    if (strpos($img, 'uploads/') === 0 || strpos($img, 'uploads\\') === 0) {
        return $base . $img;
    }
    
    // Si viene de images/productos/ (limpiar rutas duplicadas)
    if (strpos($img, 'images/productos/') !== false) {
        $img = preg_replace('#.*images/productos/#', 'images/productos/', $img);
        return $base . $img;
    }
    
    // Si viene de images/ pero no de productos
    if (strpos($img, 'images/') === 0) {
        return $base . $img;
    }
    
    // Si tiene alguna carpeta en la ruta, extraer solo el nombre del archivo
    if (strpos($img, '/') !== false) {
        $img = basename($img);
    }
    
    // Agregar extensión por defecto si no tiene
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $img)) {
        // Verificar si existe con diferentes extensiones
        $extensiones = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        foreach ($extensiones as $ext) {
            $testPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $base . 'images/productos/' . $img . '.' . $ext;
            if (file_exists($testPath)) {
                $img .= '.' . $ext;
                break;
            }
        }
    }
    
    // Ruta por defecto: images/productos/
    return $base . 'images/productos/' . $img;
}

/**
 * Obtiene rating de producto
 */
function getRating($prodId, $conn) {
    if (!$conn) return ['avg' => 0, 'total' => 0];
    
    // Verificar si la tabla opiniones existe
    $check_table = $conn->query("SHOW TABLES LIKE 'opiniones'");
    if (!$check_table || $check_table->num_rows === 0) {
        return ['avg' => 0, 'total' => 0];
    }
    
    $stmt = $conn->prepare("SELECT COALESCE(AVG(rating),0) as avg, COUNT(*) as total FROM opiniones WHERE producto_id=?");
    if (!$stmt) return ['avg' => 0, 'total' => 0];
    $stmt->bind_param("i", $prodId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['avg' => (float)$data['avg'], 'total' => (int)$data['total']];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - LumiSpace</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #a1683a;
            --primary-dark: #7d4e2a;
            --success: #28a745;
            --warning: #ff9800;
            --danger: #dc3545;
            --bg: #fafaf8;
            --card: #ffffff;
            --text: #1a1816;
            --muted: #6b6966;
            --border: #e6e4e0;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --radius: 16px;
        }
        
        body.dark {
            --bg: #0f0e0d;
            --card: #1a1816;
            --text: #f8f7f5;
            --muted: #9a9795;
            --border: #2d2a28;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .catalogo {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Toolbar */
        .toolbar {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
            padding: 20px;
            background: var(--card);
            border-radius: var(--radius);
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 44px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            background: var(--card);
            color: var(--text);
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }
        
        .toolbar-right {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .sort-select {
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: var(--card);
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
        }
        
        .results-count {
            padding: 10px 20px;
            background: rgba(161,104,58,0.1);
            border-radius: 50px;
            font-weight: 700;
            color: var(--primary);
            font-size: 0.9rem;
        }
        
        /* Filtros */
        .filtros {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0 20px;
            scrollbar-width: thin;
        }
        
        .filtro-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
            color: var(--text);
        }
        
        .filtro-btn:hover { border-color: var(--primary); }
        
        .filtro-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .filtro-count {
            padding: 2px 8px;
            background: rgba(255,255,255,0.2);
            border-radius: 50px;
            font-size: 0.8rem;
        }
        
        .filtro-btn:not(.active) .filtro-count {
            background: rgba(161,104,58,0.15);
            color: var(--primary);
        }
        
        /* Grid */
        .productos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        
        /* Card */
        .producto {
            background: var(--card);
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }
        
        .producto:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            border-color: var(--primary);
        }
        
        .producto.hidden { display: none; }
        
        .producto-img {
            position: relative;
            height: 260px;
            background: linear-gradient(135deg, #f5f3f0, #e6e4e0);
            overflow: hidden;
        }
        
        .producto-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s, opacity 0.3s;
            opacity: 0;
        }
        
        .producto-img img.loaded {
            opacity: 1;
        }
        
        .producto-img img.error {
            opacity: 1;
            object-fit: contain;
            padding: 20px;
            background: #f5f3f0;
        }
        
        .img-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f3f0, #e6e4e0);
            z-index: 0;
        }
        
        .img-placeholder i {
            font-size: 3rem;
            color: #ccc;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        .producto-img img.loaded + .img-placeholder,
        .producto-img img.error + .img-placeholder {
            display: none;
        }
        
        .producto:hover .producto-img img { transform: scale(1.08); }
        
        .badges {
            position: absolute;
            top: 12px;
            left: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-agotado { background: var(--danger); color: white; }
        .badge-ultimo { background: var(--warning); color: white; }
        .badge-descuento { background: var(--success); color: white; }
        
        .acciones-hover {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s;
        }
        
        .producto:hover .acciones-hover {
            opacity: 1;
            transform: translateX(0);
        }
        
        .accion-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: var(--text);
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .accion-btn:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }
        
        .accion-btn.fav-active {
            background: var(--danger);
            color: white;
        }
        
        .producto-info {
            padding: 20px;
        }
        
        .producto-cat {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--primary);
            margin-bottom: 6px;
        }
        
        .producto-nombre {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .producto-nombre a {
            color: var(--text);
            text-decoration: none;
        }
        
        .producto-nombre a:hover { color: var(--primary); }
        
        .producto-desc {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }
        
        .stars { display: flex; gap: 2px; }
        .stars i { font-size: 0.75rem; color: #ddd; }
        .stars i.llena { color: #ffc107; }
        .rating-text { font-size: 0.8rem; color: var(--muted); }
        
        .stock {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .stock.disponible { background: rgba(40,167,69,0.1); color: var(--success); }
        .stock.poco { background: rgba(255,152,0,0.1); color: var(--warning); }
        .stock.agotado { background: rgba(220,53,69,0.1); color: var(--danger); }
        
        .precio-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        
        .precio {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            font-family: 'Playfair Display', serif;
        }
        
        .precio-original {
            font-size: 0.95rem;
            color: var(--muted);
            text-decoration: line-through;
        }
        
        .btn-carrito {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-carrito:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161,104,58,0.4);
        }
        
        .btn-carrito:disabled {
            background: var(--muted);
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-carrito.added { background: var(--success); }
        
        /* Empty */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            display: none;
        }
        
        .empty-state i { font-size: 4rem; color: var(--muted); opacity: 0.3; }
        .empty-state h3 { margin: 20px 0 10px; }
        .empty-state p { color: var(--muted); }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        .toast.warning { background: var(--warning); }
        .toast.info { background: #2196f3; }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .toolbar { flex-direction: column; }
            .search-box { width: 100%; }
            .productos-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
            .producto-img { height: 220px; }
            .acciones-hover { opacity: 1; transform: none; }
        }
        
        @media (max-width: 480px) {
            .productos-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="catalogo">
    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="buscar" placeholder="Buscar productos...">
        </div>
        <div class="toolbar-right">
            <select id="ordenar" class="sort-select">
                <option value="default">Recomendados</option>
                <option value="name-asc">A - Z</option>
                <option value="name-desc">Z - A</option>
                <option value="price-asc">Menor precio</option>
                <option value="price-desc">Mayor precio</option>
                <option value="rating">Mejor valorados</option>
            </select>
            <span class="results-count" id="contador"><?= count($productos) ?> productos</span>
        </div>
    </div>
    
    <!-- Filtros -->
    <?php if (!empty($categorias)): ?>
    <div class="filtros">
        <button class="filtro-btn active" data-cat="all">
            Todos <span class="filtro-count"><?= count($productos) ?></span>
        </button>
        <?php foreach ($categorias as $cat): 
            $catId = (int)$cat['id'];
            $count = $conteoCategoria[$catId] ?? 0;
            if ($count === 0) continue;
        ?>
        <button class="filtro-btn" data-cat="<?= $catId ?>">
            <?= htmlspecialchars($cat['nombre']) ?> <span class="filtro-count"><?= $count ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Grid -->
    <div class="productos-grid" id="grid">
        <?php foreach ($productos as $prod): 
            $id = (int)$prod['id'];
            $nombre = htmlspecialchars($prod['nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars($prod['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
            $precio = (float)($prod['precio'] ?? 0);
            $precioOrig = (float)($prod['precio_original'] ?? 0);
            $catId = (int)($prod['categoria_id'] ?? 0);
            $cantidad = (int)($prod['cantidad_inventario'] ?? 0);
            $img = getImagenProducto($prod['imagen'] ?? '', $BASE);
            
            $rating = getRating($id, $conn);
            $tieneDesc = $precioOrig > 0 && $precioOrig > $precio;
            $descuento = $tieneDesc ? round((($precioOrig - $precio) / $precioOrig) * 100) : 0;
            $esFav = isset($favoritosSet[$id]);
            
            $catNombre = 'General';
            foreach ($categorias as $c) {
                if ((int)$c['id'] === $catId) { $catNombre = htmlspecialchars($c['nombre']); break; }
            }
        ?>
        <article class="producto" 
                 data-id="<?= $id ?>" 
                 data-nombre="<?= $nombre ?>" 
                 data-precio="<?= $precio ?>" 
                 data-cat="<?= $catId ?>"
                 data-stock="<?= $cantidad ?>"
                 data-rating="<?= $rating['avg'] ?>">
            
            <div class="producto-img">
                <img src="<?= $img ?>" 
                     alt="<?= $nombre ?>" 
                     loading="lazy"
                     onload="this.classList.add('loaded')"
                     onerror="this.onerror=null; this.src='<?= $BASE ?>images/productos/default.png'; this.classList.add('error');">
                <div class="img-placeholder">
                    <i class="fas fa-image"></i>
                </div>
                
                <div class="badges">
                    <?php if ($cantidad === 0): ?>
                        <span class="badge badge-agotado">Agotado</span>
                    <?php elseif ($cantidad <= 5): ?>
                        <span class="badge badge-ultimo">¡Últimos!</span>
                    <?php endif; ?>
                    <?php if ($tieneDesc): ?>
                        <span class="badge badge-descuento">-<?= $descuento ?>%</span>
                    <?php endif; ?>
                </div>
                
                <div class="acciones-hover">
                    <button class="accion-btn btn-fav js-wish <?= $esFav ? 'fav-active active' : '' ?>" 
                            data-id="<?= $id ?>" 
                            title="<?= $esFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
                            aria-label="Agregar a favoritos"
                            aria-pressed="<?= $esFav ? 'true' : 'false' ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $id ?>" class="accion-btn" title="Ver">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
            
            <div class="producto-info">
                <div class="producto-cat"><?= $catNombre ?></div>
                <h3 class="producto-nombre">
                    <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $id ?>"><?= $nombre ?></a>
                </h3>
                <?php if ($desc): ?>
                    <p class="producto-desc"><?= $desc ?></p>
                <?php endif; ?>
                
                <div class="rating">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($rating['avg']) ? 'llena' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?= number_format($rating['avg'], 1) ?> (<?= $rating['total'] ?>)</span>
                </div>
                
                <div class="stock <?= $cantidad > 10 ? 'disponible' : ($cantidad > 0 ? 'poco' : 'agotado') ?>">
                    <i class="fas fa-<?= $cantidad > 0 ? 'check-circle' : 'times-circle' ?>"></i>
                    <?= $cantidad > 10 ? 'Disponible' : ($cantidad > 0 ? "¡Últimos $cantidad!" : 'Agotado') ?>
                </div>
                
                <div class="precio-box">
                    <span class="precio">$<?= number_format($precio, 2) ?></span>
                    <?php if ($tieneDesc): ?>
                        <span class="precio-original">$<?= number_format($precioOrig, 2) ?></span>
                    <?php endif; ?>
                </div>
                
                <button class="btn-carrito js-cart" 
                        data-id="<?= $id ?>" 
                        data-product-id="<?= $id ?>"
                        data-nombre="<?= $nombre ?>" 
                        data-precio="<?= $precio ?>" 
                        data-img="<?= $img ?>"
                        <?= $cantidad === 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shopping-cart"></i>
                    <?= $cantidad === 0 ? 'Agotado' : 'Agregar al carrito' ?>
                </button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    
    <div class="empty-state" id="empty">
        <i class="fas fa-search"></i>
        <h3>No se encontraron productos</h3>
        <p>Intenta con otros filtros o búsqueda</p>
    </div>
</div>

<script>
const BASE = '<?= $BASE ?>';
const USER_ID = <?= $usuario_id ?>;

// Utilidades
const updateCartBadge = () => {
    // Actualizar desde la API del servidor
    fetch(BASE + 'api/carrito/count.php')
        .then(res => res.json())
        .then(data => {
            const count = data.count || 0;
            document.querySelectorAll('.cart-badge, [data-cart-count], #cart-badge').forEach(b => {
                b.textContent = count;
                b.style.display = count > 0 ? '' : 'none';
            });
        })
        .catch(err => {
            console.error('Error actualizando badge del carrito:', err);
            // Fallback: intentar desde localStorage si existe (solo como último recurso)
            try {
                const CART_KEY = 'lumispace_cart';
                const localCart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
                const count = localCart.reduce((s, i) => s + (i.cantidad || 1), 0);
                if (count > 0) {
                    document.querySelectorAll('.cart-badge, [data-cart-count], #cart-badge').forEach(b => {
                        b.textContent = count;
                        b.style.display = count > 0 ? '' : 'none';
                    });
                }
            } catch (e) {
                // Ignorar errores de localStorage
            }
        });
};

function toast(msg, type = 'info') {
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 300); }, 3000);
}

// Catálogo
const grid = document.getElementById('grid');
const cards = Array.from(grid.querySelectorAll('.producto'));
const empty = document.getElementById('empty');
const contador = document.getElementById('contador');
let catActual = 'all', busqueda = '';

function filtrar() {
    const orden = document.getElementById('ordenar').value;
    
    let visibles = cards.filter(c => {
        const catOk = catActual === 'all' || c.dataset.cat === catActual;
        const busqOk = !busqueda || c.dataset.nombre.toLowerCase().includes(busqueda);
        return catOk && busqOk;
    });
    
    // Ordenar
    if (orden === 'name-asc') visibles.sort((a, b) => a.dataset.nombre.localeCompare(b.dataset.nombre));
    else if (orden === 'name-desc') visibles.sort((a, b) => b.dataset.nombre.localeCompare(a.dataset.nombre));
    else if (orden === 'price-asc') visibles.sort((a, b) => parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio));
    else if (orden === 'price-desc') visibles.sort((a, b) => parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio));
    else if (orden === 'rating') visibles.sort((a, b) => parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating));
    
    cards.forEach(c => c.classList.toggle('hidden', !visibles.includes(c)));
    visibles.forEach(c => grid.appendChild(c));
    
    contador.textContent = `${visibles.length} producto${visibles.length !== 1 ? 's' : ''}`;
    empty.style.display = visibles.length === 0 ? 'block' : 'none';
    grid.style.display = visibles.length === 0 ? 'none' : 'grid';
}

// Eventos
let timeout;
document.getElementById('buscar').addEventListener('input', e => {
    clearTimeout(timeout);
    timeout = setTimeout(() => { busqueda = e.target.value.trim().toLowerCase(); filtrar(); }, 300);
});

document.getElementById('ordenar').addEventListener('change', filtrar);

document.querySelectorAll('.filtro-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        catActual = btn.dataset.cat;
        filtrar();
    });
});

// Click en productos
grid.addEventListener('click', async e => {
    const card = e.target.closest('.producto');
    if (!card) return;
    
    // Favoritos - Manejar tanto .btn-fav como .js-wish
    const favBtn = e.target.closest('.btn-fav, .js-wish');
    if (favBtn) {
        e.preventDefault();
        e.stopPropagation();
        if (!USER_ID) { 
            toast('Inicia sesión para favoritos', 'warning'); 
            setTimeout(() => {
                const next = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `${BASE}views/login.php?next=${next}`;
            }, 1500);
            return; 
        }
        
        favBtn.disabled = true;
        try {
            const res = await fetch(BASE + 'api/favoritos/toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: parseInt(card.dataset.id) })
            });
            
            if (res.status === 401) {
                toast('Debes iniciar sesión para guardar favoritos', 'warning');
                setTimeout(() => {
                    const next = encodeURIComponent(window.location.pathname + window.location.search);
                    window.location.href = `${BASE}views/login.php?next=${next}`;
                }, 1500);
                favBtn.disabled = false;
                return;
            }
            
            const data = await res.json();
            if (data.ok) {
                favBtn.classList.toggle('fav-active', data.in_wishlist);
                favBtn.classList.toggle('active', data.in_wishlist);
                
                // Actualizar icono
                const icon = favBtn.querySelector('i');
                if (icon) {
                    icon.className = data.in_wishlist ? 'fas fa-heart' : 'far fa-heart';
                }
                
                // Actualizar atributos ARIA
                favBtn.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                favBtn.title = data.in_wishlist ? 'Quitar de favoritos' : 'Agregar a favoritos';
                
                toast(data.in_wishlist ? 'Agregado a favoritos' : 'Eliminado de favoritos', 'success');
            } else {
                throw new Error(data.msg || 'Error al actualizar favoritos');
            }
        } catch (err) { 
            console.error('Error en favoritos:', err);
            toast('Error al actualizar favoritos', 'error'); 
        }
        favBtn.disabled = false;
        return;
    }
    
    // Carrito - Usar API del servidor en lugar de localStorage
    const cartBtn = e.target.closest('.btn-carrito');
    if (cartBtn && !cartBtn.disabled) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = parseInt(cartBtn.dataset.id || cartBtn.dataset.productId || '0', 10);
        if (!id) {
            toast('Producto no válido', 'error');
            return;
        }
        
        const stock = parseInt(card.dataset.stock || '0', 10);
        if (stock <= 0) {
            toast('Producto agotado', 'warning');
            return;
        }
        
        // Mostrar estado de carga
        const originalHTML = cartBtn.innerHTML;
        cartBtn.disabled = true;
        cartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';
        
        try {
            const response = await fetch(BASE + 'api/carrito/add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    producto_id: id,
                    product_id: id,
                    cantidad: 1,
                    qty: 1
                })
            });
            
            // Verificar si la respuesta es JSON válido
            let data;
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                console.error('Respuesta no JSON:', text);
                throw new Error('Error del servidor: La respuesta no es válida');
            }
            
            if (response.ok && data.ok !== false) {
                toast('¡Producto agregado al carrito!', 'success');
                
                // Actualizar contador del carrito
                updateCartBadge();
                
                // Efecto visual
                cartBtn.classList.add('added');
                const icon = cartBtn.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-check';
                }
                setTimeout(() => { 
                    cartBtn.classList.remove('added'); 
                    if (icon) icon.className = 'fas fa-shopping-cart';
                    cartBtn.innerHTML = originalHTML;
                    cartBtn.disabled = false;
                }, 1500);
            } else {
                throw new Error(data.msg || 'Error al agregar al carrito');
            }
        } catch (error) {
            console.error('Error al agregar al carrito:', error);
            toast('Error al agregar al carrito. Intenta de nuevo.', 'error');
            cartBtn.innerHTML = originalHTML;
            cartBtn.disabled = false;
        }
        return;
    }
});

updateCartBadge();

// Debug de imágenes - descomentar si hay problemas
// console.log('Imágenes del catálogo:');
// document.querySelectorAll('.producto-img img').forEach(img => {
//     console.log(img.alt + ': ' + img.src);
// });

// Verificar imágenes rotas
document.querySelectorAll('.producto-img img').forEach(img => {
    if (img.complete && img.naturalHeight === 0) {
        img.classList.add('error');
        img.src = BASE + 'images/productos/default.png';
    }
});
</script>
</body>
</html>