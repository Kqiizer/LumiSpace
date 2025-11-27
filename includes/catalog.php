<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$conn = getDBConnection();

// Obtener categorías con sus imágenes (usando el mismo sistema que categories.php)
$categorias_db = getCategorias();

// La función getCategoryImage() ya está declarada en includes/categories.php
// Si no existe, la declaramos aquí como fallback
if (!function_exists('getCategoryImage')) {
  function getCategoryImage($imagen, $BASE) {
    if (empty($imagen) || trim($imagen) === '') {
      return $BASE . 'images/categorias/default.jpg';
    }
    
    $imagen = trim($imagen);
    
    // Si ya es una URL completa (http o https), devolverla tal cual
    if (preg_match('#^https?://#i', $imagen)) {
      return $imagen;
    }
    
    // Si empieza con /, es una ruta absoluta desde la raíz
    if (strpos($imagen, '/') === 0) {
      return $BASE . ltrim($imagen, '/');
    }
    
    // Si la imagen ya contiene una ruta relativa completa (ej: imagenes/lamparas/deco2.jpg)
    // o empieza con imagenes/ o images/, usarla directamente
    if (strpos($imagen, 'imagenes/') === 0 || strpos($imagen, 'images/') === 0) {
      return $BASE . $imagen;
    }
    
    // Si es solo un nombre de archivo, asumir que está en images/categorias/
    return $BASE . 'images/categorias/' . $imagen;
  }
}

// Obtener productos destacados de las primeras 2 categorías
$productos = [];
if (!empty($categorias_db) && is_array($categorias_db)) {
  // Verificar qué columnas existen en la tabla productos
  $check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
  $has_activo = $check_activo && $check_activo->num_rows > 0;
  $check_estado = $conn->query("SHOW COLUMNS FROM productos LIKE 'estado'");
  $has_estado = $check_estado && $check_estado->num_rows > 0;
  
  // Tomar las primeras 2 categorías
  $categorias_mostrar = array_slice($categorias_db, 0, 2);
  
  foreach ($categorias_mostrar as $cat) {
    $cat_id = (int)($cat['id'] ?? 0);
    
    // Construir la consulta SQL dinámicamente según las columnas disponibles
    $where_conditions = ["p.categoria_id = ?"];
    $types = "i";
    $params = [$cat_id];
    
    if ($has_activo) {
      $where_conditions[] = "p.activo = 1";
    } elseif ($has_estado) {
      $where_conditions[] = "p.estado = 'activo'";
    }
    
    $sql = "SELECT p.*, c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE " . implode(" AND ", $where_conditions) . "
            ORDER BY p.id DESC
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      if ($params) {
        $stmt->bind_param($types, ...$params);
      }
      $stmt->execute();
      $result = $stmt->get_result();
      $producto = $result->fetch_assoc();
      
      if ($producto) {
        // Usar la imagen de la categoría si el producto no tiene imagen
        $categoryImage = $cat['imagen'] ?? $cat['featured_image'] ?? '';
        if (empty($producto['imagen']) && !empty($categoryImage)) {
          $producto['imagen'] = $categoryImage;
          $producto['usar_imagen_categoria'] = true;
        }
        $productos[] = $producto;
      }
      $stmt->close();
    }
  }
}

// Si no hay productos de categorías específicas, obtener productos destacados generales
if (empty($productos)) {
  // Verificar qué columnas existen
  $check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
  $has_activo = $check_activo && $check_activo->num_rows > 0;
  $check_estado = $conn->query("SHOW COLUMNS FROM productos LIKE 'estado'");
  $has_estado = $check_estado && $check_estado->num_rows > 0;
  
  $where_conditions = [];
  if ($has_activo) {
    $where_conditions[] = "p.activo = 1";
  } elseif ($has_estado) {
    $where_conditions[] = "p.estado = 'activo'";
  }
  
  $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
  
  $sql = "SELECT p.*, c.nombre AS categoria
          FROM productos p
          LEFT JOIN categorias c ON p.categoria_id = c.id
          $where_sql
          ORDER BY p.id DESC
          LIMIT 2";
  
  $res = $conn->query($sql);
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $productos[] = $row;
    }
  }
}

// Si aún no hay productos, usar las categorías directamente como productos
if (empty($productos) && !empty($categorias_db)) {
  $categorias_mostrar = array_slice($categorias_db, 0, 2);
  foreach ($categorias_mostrar as $cat) {
    $productos[] = [
      'id' => $cat['id'],
      'nombre' => $cat['nombre'],
      'precio' => 0,
      'imagen' => $cat['imagen'] ?? $cat['featured_image'] ?? '',
      'usar_imagen_categoria' => true,
      'categoria' => $cat['nombre']
    ];
  }
}
?>

<!-- Catálogo Section -->
<section class="catalog-section">
  <div class="catalog-container">
    <h2 class="catalog-title">CATÁLOGO</h2>
    
    <div class="catalog-grid">
      <?php if (!empty($productos)): ?>
        <?php foreach ($productos as $producto): 
          $nombre = htmlspecialchars($producto['nombre'] ?? 'Producto sin nombre');
          $precio = (float)($producto['precio'] ?? 0);
          $producto_id = (int)($producto['id'] ?? 0);
          
          // Usar la misma función getCategoryImage que categories.php
          $categoryImage = $producto['imagen'] ?? '';
          $imagen = getCategoryImage($categoryImage, $BASE);
        ?>
          <div class="catalog-card product-card" data-id="<?= $producto_id ?>">
            <div class="catalog-card-image">
              <img src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>" 
                   alt="<?= $nombre ?>"
                   loading="lazy">
            </div>
            <h3 class="catalog-card-title"><?= $nombre ?></h3>
            <?php if ($precio > 0): ?>
              <button class="catalog-card-price js-cart" data-product-id="<?= $producto_id ?>" data-id="<?= $producto_id ?>" title="Agregar al carrito">
                $<?= number_format($precio, 2) ?>
              </button>
            <?php else: ?>
              <button class="catalog-card-price" onclick="window.location.href='<?= $BASE ?>views/catalogo.php'">
                Ver catálogo
              </button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <!-- Placeholder si no hay productos -->
        <div class="catalog-card">
          <div class="catalog-card-image">
            <img src="<?= $BASE ?>images/productos/default.png" alt="Producto">
          </div>
          <h3 class="catalog-card-title">Producto de ejemplo</h3>
          <button class="catalog-card-price">$0.00</button>
        </div>
        <div class="catalog-card">
          <div class="catalog-card-image">
            <img src="<?= $BASE ?>images/productos/default.png" alt="Producto">
          </div>
          <h3 class="catalog-card-title">Producto de ejemplo</h3>
          <button class="catalog-card-price">$0.00</button>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<style>
/* ==========================================
   CATÁLOGO SECTION
   ========================================== */
.catalog-section {
  padding: 100px 20px;
  background: #faf8f6;
  position: relative;
}

.catalog-container {
  max-width: 1200px;
  margin: 0 auto;
}

.catalog-title {
  font-family: 'Inter', sans-serif;
  font-size: 2.5rem;
  font-weight: 700;
  color: #8b7355;
  text-align: center;
  margin-bottom: 50px;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

/* ==========================================
   CATALOG GRID
   ========================================== */
.catalog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  max-width: 1000px;
  margin: 0 auto;
}

/* ==========================================
   CATALOG CARD
   ========================================== */
.catalog-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
  border: 1px solid rgba(139, 115, 85, 0.1);
  box-shadow: 0 4px 12px rgba(109, 90, 66, 0.08);
}

.catalog-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 32px rgba(109, 90, 66, 0.15);
  border-color: rgba(139, 115, 85, 0.2);
}

.catalog-card-image {
  width: 100%;
  height: 320px;
  overflow: hidden;
  background: linear-gradient(135deg, #f4f1ec 0%, #e9e4dd 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.catalog-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.catalog-card:hover .catalog-card-image img {
  transform: scale(1.08);
}

.catalog-card-title {
  font-family: 'Inter', sans-serif;
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d2520;
  text-align: center;
  padding: 20px 20px 16px;
  margin: 0;
  line-height: 1.4;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.catalog-card-price {
  background: linear-gradient(135deg, #8b7355 0%, #a0896b 100%);
  color: #ffffff;
  border: none;
  padding: 14px 28px;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  margin: 0 auto 24px;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  width: auto;
  min-width: 140px;
  display: block;
  box-shadow: 0 4px 12px rgba(139, 115, 85, 0.2);
}

.catalog-card-price:hover {
  background: linear-gradient(135deg, #a0896b 0%, #8b7355 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(139, 115, 85, 0.3);
}

/* ==========================================
   RESPONSIVE
   ========================================== */
@media (max-width: 768px) {
  .catalog-section {
    padding: 80px 20px;
  }
  
  .catalog-title {
    font-size: 2rem;
    margin-bottom: 40px;
  }
  
  .catalog-grid {
    grid-template-columns: 1fr;
    gap: 24px;
    max-width: 500px;
  }
  
  .catalog-card-image {
    height: 280px;
  }
  
  .catalog-card-title {
    font-size: 1.0625rem;
    padding: 18px 16px 14px;
    min-height: 56px;
  }
  
  .catalog-card-price {
    font-size: 0.9375rem;
    padding: 12px 24px;
    min-width: 130px;
  }
}

@media (max-width: 480px) {
  .catalog-section {
    padding: 60px 15px;
  }
  
  .catalog-title {
    font-size: 1.75rem;
    margin-bottom: 30px;
  }
  
  .catalog-card-image {
    height: 240px;
  }
  
  .catalog-card-title {
    font-size: 1rem;
    padding: 16px 12px 12px;
    min-height: 52px;
  }
  
  .catalog-card-price {
    font-size: 0.875rem;
    padding: 10px 20px;
    min-width: 120px;
  }
}

/* ==========================================
   DARK MODE
   ========================================== */
body.dark .catalog-section {
  background: #1b1712;
}

body.dark .catalog-title {
  color: #f6f1e8;
}

body.dark .catalog-card {
  background: #6D5A42;
}

body.dark .catalog-card-image {
  background: #8A7458;
}

body.dark .catalog-card-price {
  background: #ffffff;
  color: #32281c;
}

body.dark .catalog-card-price:hover {
  background: #f2e7d9;
}
</style>


