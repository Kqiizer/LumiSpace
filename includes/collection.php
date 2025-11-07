<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Obtener todos los productos y categorías
$categorias = getCategorias();
$todosProductos = [];

// Obtener productos de todas las fuentes
$prods1 = getProductosCatalogo(null, 200);
$prods2 = getProductosPublicos(200);
$todosProductos = array_merge($prods1 ?: [], $prods2 ?: []);

// Eliminar duplicados por ID
$productosUnicos = [];
$idsVistos = [];
foreach ($todosProductos as $p) {
  $id = (int)($p['id'] ?? 0);
  if ($id && !in_array($id, $idsVistos)) {
    $idsVistos[] = $id;
    $productosUnicos[] = $p;
  }
}
$todosProductos = $productosUnicos;

// Obtener cantidades desde la tabla inventario para todos los productos
$conn = getDBConnection();
$inventarioMap = [];
if ($conn) {
  $productIds = array_map(function($p) { return (int)$p['id']; }, $todosProductos);
  if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmtInv = $conn->prepare("
      SELECT producto_id, cantidad 
      FROM inventario 
      WHERE producto_id IN ($placeholders)
    ");
    
    if ($stmtInv) {
      $types = str_repeat('i', count($productIds));
      $stmtInv->bind_param($types, ...$productIds);
      if ($stmtInv->execute()) {
        $resultInv = $stmtInv->get_result();
        while ($rowInv = $resultInv->fetch_assoc()) {
          $inventarioMap[(int)$rowInv['producto_id']] = (int)$rowInv['cantidad'];
        }
        $resultInv->free();
      }
      $stmtInv->close();
    }
  }
}

// Agregar cantidad del inventario a cada producto
foreach ($todosProductos as &$producto) {
  $prodId = (int)$producto['id'];
  $producto['cantidad_inventario'] = $inventarioMap[$prodId] ?? 0;
}
unset($producto);

// Favoritos del usuario
$favoritosSet = [];
if ($usuario_id) {
  $conn = getDBConnection();
  $chk = $conn->query("SHOW TABLES LIKE 'favoritos'");
  if ($chk && $chk->num_rows > 0) {
    if ($stmt = $conn->prepare("SELECT producto_id FROM favoritos WHERE usuario_id=?")) {
      $stmt->bind_param("i", $usuario_id);
      if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) $favoritosSet[(int)$row['producto_id']] = true;
        $res->free();
      }
      $stmt->close();
    }
  }
}

function prod_img_url($raw, $BASE) {
  $raw = trim((string)$raw);
  if ($raw === '') return $BASE . 'images/default.png';
  $raw = str_replace('\\', '/', $raw);
  if (preg_match('#^https?://#i', $raw)) return $raw;
  if (stripos($raw, '/images/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'images/productos/') === 0) return $BASE . $raw;
  if (stripos($raw, '/uploads/productos/') === 0) return $BASE . ltrim($raw, '/');
  if (stripos($raw, 'uploads/productos/') === 0) return $BASE . $raw;
  if (strpos($raw, '/') !== false) return $BASE . ltrim($raw, '/');
  return $BASE . 'images/productos/' . $raw;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Catálogo premium de productos - La mejor calidad al mejor precio">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #a1683a;
      --primary-dark: #7d4e2a;
      --primary-light: #c08552;
      --accent: #d4a574;
      --bg-light: #fafaf8;
      --bg-dark: #0f0e0d;
      --text-light: #1a1816;
      --text-dark: #f8f7f5;
      --text-muted: #6b6966;
      --border-color: #e6e4e0;
      --card-bg: #ffffff;
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
      --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
      --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
      --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.16);
      --radius-sm: 8px;
      --radius-md: 16px;
      --radius-lg: 24px;
      --transition-smooth: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      --transition-spring: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--bg-light);
      color: var(--text-light);
      line-height: 1.7;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }

    body.dark {
      background: var(--bg-dark);
      color: var(--text-dark);
      --card-bg: #1a1816;
      --border-color: #2d2a28;
      --text-muted: #9a9795;
    }

    /* Hero Banner Premium */
    .hero-banner {
      position: relative;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      padding: clamp(60px, 10vh, 120px) 20px;
      margin-bottom: 60px;
      overflow: hidden;
      min-height: 400px;
      display: flex;
      align-items: center;
    }

    .hero-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 50%, rgba(212, 165, 116, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(212, 165, 116, 0.25) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 40%);
      animation: heroGlow 8s ease-in-out infinite;
    }

    .hero-banner::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
      background-size: 30px 30px;
      opacity: 0.3;
      animation: dotMove 20s linear infinite;
    }

    @keyframes heroGlow {
      0%, 100% { 
        opacity: 0.5;
        transform: scale(1);
      }
      50% { 
        opacity: 1;
        transform: scale(1.1);
      }
    }

    @keyframes dotMove {
      0% { background-position: 0 0; }
      100% { background-position: 30px 30px; }
    }

    .hero-shapes {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
    }

    .shape {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(10px);
      animation: float 20s ease-in-out infinite;
    }

    .shape:nth-child(1) {
      width: 300px;
      height: 300px;
      top: -100px;
      left: -100px;
      animation-delay: 0s;
    }

    .shape:nth-child(2) {
      width: 200px;
      height: 200px;
      top: 50%;
      right: -50px;
      animation-delay: 2s;
      animation-duration: 15s;
    }

    .shape:nth-child(3) {
      width: 150px;
      height: 150px;
      bottom: -50px;
      left: 30%;
      animation-delay: 4s;
      animation-duration: 18s;
    }

    @keyframes float {
      0%, 100% {
        transform: translate(0, 0) rotate(0deg);
      }
      25% {
        transform: translate(30px, -30px) rotate(90deg);
      }
      50% {
        transform: translate(-20px, -50px) rotate(180deg);
      }
      75% {
        transform: translate(-30px, -20px) rotate(270deg);
      }
    }

    .hero-content {
      position: relative;
      max-width: 1400px;
      margin: 0 auto;
      text-align: center;
      z-index: 1;
      width: 100%;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 24px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 50px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      font-size: clamp(0.75rem, 2vw, 0.9rem);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 24px;
      animation: fadeInDown 0.6s ease 0.2s both;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      transition: var(--transition-smooth);
    }

    .hero-badge:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.25);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }

    .hero-badge i {
      font-size: 1.2em;
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.2); }
    }

    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 6vw, 4.5rem);
      font-weight: 900;
      color: white;
      margin-bottom: 20px;
      line-height: 1.1;
      animation: fadeInUp 0.6s ease 0.4s both;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      position: relative;
    }

    .hero-title::after {
      content: attr(data-text);
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        90deg,
        transparent 0%,
        rgba(255, 255, 255, 0.4) 50%,
        transparent 100%
      );
      background-clip: text;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      animation: shine 3s ease-in-out infinite;
      z-index: 1;
    }

    @keyframes shine {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(200%); }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .hero-description {
      font-size: clamp(1rem, 2vw, 1.3rem);
      color: rgba(255, 255, 255, 0.95);
      max-width: 700px;
      margin: 0 auto 40px;
      animation: fadeInUp 0.6s ease 0.6s both;
      line-height: 1.6;
      font-weight: 400;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      gap: 24px;
      max-width: 800px;
      margin: 0 auto;
      animation: fadeInUp 0.6s ease 0.8s both;
    }

    .stat-item {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: var(--radius-md);
      padding: 24px 20px;
      text-align: center;
      transition: var(--transition-smooth);
      position: relative;
      overflow: hidden;
    }

    .stat-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
      );
      transition: left 0.5s ease;
    }

    .stat-item:hover::before {
      left: 100%;
    }

    .stat-item:hover {
      transform: translateY(-8px) scale(1.05);
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.3);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
    }

    .stat-number {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 900;
      color: white;
      display: block;
      margin-bottom: 8px;
      font-family: 'Inter', sans-serif;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      line-height: 1;
    }

    .stat-icon {
      font-size: 1.5rem;
      margin-bottom: 8px;
      color: rgba(255, 255, 255, 0.8);
      animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
    }

    .stat-label {
      font-size: clamp(0.8rem, 1.5vw, 0.95rem);
      color: rgba(255, 255, 255, 0.9);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      font-weight: 600;
    }

    .scroll-indicator {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      animation: fadeIn 0.6s ease 1.2s both, floatUpDown 2s ease-in-out infinite 1.8s;
      cursor: pointer;
      z-index: 10;
      transition: var(--transition-smooth);
    }

    .scroll-indicator i {
      font-size: 1.5rem;
      animation: arrowBounce 2s ease-in-out infinite;
    }

    @keyframes floatUpDown {
      0%, 100% { transform: translateX(-50%) translateY(0); }
      50% { transform: translateX(-50%) translateY(-10px); }
    }

    @keyframes arrowBounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(5px); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .hero-banner.loaded {
      animation: heroSlideIn 0.8s ease-out;
    }

    @keyframes heroSlideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Container */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px 60px;
    }

    /* Enhanced Toolbar */
    .toolbar-wrapper {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      padding: 24px;
      margin-bottom: 40px;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border-color);
      animation: slideInScale 0.6s ease;
    }

    @keyframes slideInScale {
      from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .toolbar {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
    }

    .search-container {
      flex: 1;
      min-width: 280px;
      max-width: 500px;
    }

    .search-box {
      position: relative;
    }

    .search-box input {
      width: 100%;
      padding: 14px 50px 14px 50px;
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      font-size: 0.95rem;
      transition: var(--transition-smooth);
      background: var(--card-bg);
      color: var(--text-light);
      font-weight: 500;
    }

    body.dark .search-box input {
      color: var(--text-dark);
    }

    .search-box input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
      transform: translateY(-2px);
    }

    .search-box input::placeholder {
      color: var(--text-muted);
    }

    .search-icon {
      position: absolute;
      left: 18px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary);
      font-size: 1.1rem;
      pointer-events: none;
      transition: var(--transition-smooth);
    }

    .search-box input:focus ~ .search-icon {
      transform: translateY(-50%) scale(1.1);
    }

    .clear-search {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(161, 104, 58, 0.1);
      border: none;
      color: var(--primary);
      cursor: pointer;
      padding: 8px;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      display: none;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
    }

    .clear-search:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-50%) rotate(90deg);
    }

    .clear-search.visible {
      display: flex;
    }

    .toolbar-controls {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }

    .sort-box {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 18px;
      background: var(--card-bg);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      transition: var(--transition-smooth);
    }

    .sort-box:hover {
      border-color: var(--primary);
    }

    .sort-box label {
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 8px;
      white-space: nowrap;
    }

    .sort-box select {
      border: none;
      background: transparent;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      color: var(--text-light);
      outline: none;
      padding: 0;
    }

    body.dark .sort-box select {
      color: var(--text-dark);
    }

    .sort-box select option {
      background: var(--card-bg);
      color: var(--text-light);
      padding: 8px;
    }

    body.dark .sort-box select option {
      background: var(--card-bg);
      color: var(--text-dark);
    }

    .toolbar-btn {
      padding: 12px 20px;
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      background: var(--card-bg);
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: var(--transition-smooth);
      color: var(--text-light);
      white-space: nowrap;
    }

    body.dark .toolbar-btn {
      color: var(--text-dark);
    }

    .toolbar-btn:hover {
      border-color: var(--primary);
      background: rgba(161, 104, 58, 0.05);
      transform: translateY(-2px);
      box-shadow: var(--shadow-sm);
    }

    .toolbar-btn.active {
      background: var(--primary);
      border-color: var(--primary);
      color: white;
    }

    .results-count {
      padding: 12px 24px;
      background: linear-gradient(135deg, rgba(161, 104, 58, 0.1), rgba(161, 104, 58, 0.05));
      border-radius: 50px;
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--primary);
      border: 2px solid rgba(161, 104, 58, 0.2);
      white-space: nowrap;
    }

    /* Enhanced Filter Tabs */
    .filter-section {
      margin-bottom: 40px;
    }

    .filter-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .filter-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-light);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    body.dark .filter-title {
      color: var(--text-dark);
    }

    .filter-tabs {
      display: flex;
      gap: 12px;
      overflow-x: auto;
      padding: 16px 0;
      scrollbar-width: thin;
      scrollbar-color: var(--primary) transparent;
      scroll-behavior: smooth;
      animation: fadeIn 0.6s ease 0.4s both;
    }

    .filter-tabs::-webkit-scrollbar {
      height: 8px;
    }

    .filter-tabs::-webkit-scrollbar-track {
      background: rgba(161, 104, 58, 0.05);
      border-radius: 4px;
    }

    .filter-tabs::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 4px;
      transition: var(--transition-smooth);
    }

    .filter-tabs::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }

    .filter-tab {
      padding: 14px 28px;
      border: 2px solid var(--border-color);
      border-radius: 50px;
      cursor: pointer;
      white-space: nowrap;
      transition: var(--transition-spring);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--card-bg);
      text-decoration: none;
      color: var(--text-light);
      font-size: 0.9rem;
      position: relative;
      overflow: hidden;
    }

    body.dark .filter-tab {
      color: var(--text-dark);
    }

    .filter-tab::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      opacity: 0;
      transition: var(--transition-smooth);
    }

    .filter-tab:hover {
      border-color: var(--primary);
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .filter-tab.active {
      border-color: var(--primary);
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      transform: translateY(-3px) scale(1.05);
      box-shadow: var(--shadow-lg);
    }

    .filter-tab .badge {
      background: rgba(255, 255, 255, 0.2);
      padding: 4px 10px;
      border-radius: 50px;
      font-size: 0.8rem;
      font-weight: 700;
      min-width: 24px;
      text-align: center;
      transition: var(--transition-smooth);
    }

    .filter-tab.active .badge {
      background: rgba(255, 255, 255, 0.3);
    }

    /* Premium Product Grid */
    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 32px;
      animation: fadeIn 0.6s ease 0.6s both;
    }

    .product-card {
      background: var(--card-bg);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: var(--transition-smooth);
      cursor: pointer;
      position: relative;
      display: flex;
      flex-direction: column;
      border: 1px solid var(--border-color);
      transform-origin: center;
    }

    .product-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(161, 104, 58, 0.05), transparent);
      opacity: 0;
      transition: var(--transition-smooth);
      pointer-events: none;
      z-index: 1;
    }

    .product-card:hover {
      transform: translateY(-12px);
      box-shadow: var(--shadow-xl);
      border-color: var(--primary);
    }

    .product-card:hover::before {
      opacity: 1;
    }

    .product-card.hidden {
      display: none;
    }

    .product-image-container {
      position: relative;
      width: 100%;
      height: 320px;
      overflow: hidden;
      background: linear-gradient(135deg, #f5f3f0, #e6e4e0);
    }

    .product-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: var(--transition-smooth);
    }

    .product-card:hover .product-image {
      transform: scale(1.08);
    }

    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);
      opacity: 0;
      transition: var(--transition-smooth);
      display: flex;
      align-items: flex-end;
      padding: 20px;
    }

    .product-card:hover .image-overlay {
      opacity: 1;
    }

    .quick-view-btn {
      padding: 12px 24px;
      background: white;
      color: var(--primary);
      border: none;
      border-radius: var(--radius-md);
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transform: translateY(20px);
      opacity: 0;
      transition: var(--transition-smooth);
      font-size: 0.9rem;
    }

    .product-card:hover .quick-view-btn {
      transform: translateY(0);
      opacity: 1;
    }

    .quick-view-btn:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-2px);
    }

    .product-badges {
      position: absolute;
      top: 16px;
      left: 16px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      z-index: 2;
    }

    .product-badge {
      padding: 8px 16px;
      border-radius: var(--radius-md);
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      backdrop-filter: blur(10px);
      animation: badgePulse 2s ease-in-out infinite;
    }

    @keyframes badgePulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    .badge-new {
      background: linear-gradient(135deg, #28a745, #20c997);
      color: white;
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    }

    .badge-sale {
      background: linear-gradient(135deg, #dc3545, #e83e8c);
      color: white;
      box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }

    .badge-featured {
      background: linear-gradient(135deg, #ffc107, #ff9800);
      color: #1a1816;
      box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    }

    .badge-stock-low {
      background: linear-gradient(135deg, #ff9800, #ff6b6b);
      color: white;
      box-shadow: 0 4px 12px rgba(255, 152, 0, 0.4);
    }

    .quick-actions {
      position: absolute;
      top: 16px;
      right: 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      z-index: 2;
    }

    .quick-action {
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
      box-shadow: var(--shadow-sm);
      color: var(--text-light);
      opacity: 0;
      transform: translateX(20px);
    }

    .product-card:hover .quick-action {
      opacity: 1;
      transform: translateX(0);
    }

    .quick-action:nth-child(1) {
      transition-delay: 0.05s;
    }

    .quick-action:nth-child(2) {
      transition-delay: 0.1s;
    }

    .quick-action:hover {
      background: var(--primary);
      color: white;
      transform: scale(1.15);
      box-shadow: var(--shadow-md);
    }

    .quick-action.active {
      background: var(--primary);
      color: white;
    }

    .product-body {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
      position: relative;
      z-index: 2;
    }

    .product-category {
      font-size: 0.8rem;
      color: var(--primary);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .product-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-bottom: 12px;
      color: var(--text-light);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.4;
      min-height: 2.8em;
    }

    body.dark .product-title {
      color: var(--text-dark);
    }

    .product-description {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: 16px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      flex: 1;
      line-height: 1.6;
    }

    .product-rating {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
    }

    .stars {
      display: flex;
      gap: 3px;
    }

    .star {
      color: #ddd;
      font-size: 14px;
      transition: var(--transition-smooth);
    }

    .star.filled {
      color: #ffc107;
      animation: starShine 1.5s ease-in-out infinite;
    }

    @keyframes starShine {
      0%, 100% {
        filter: drop-shadow(0 0 2px rgba(255, 193, 7, 0.5));
      }
      50% {
        filter: drop-shadow(0 0 6px rgba(255, 193, 7, 0.8));
      }
    }

    .rating-info {
      font-size: 0.85rem;
      color: var(--text-muted);
      font-weight: 600;
    }

    .rating-count {
      color: var(--primary);
      cursor: pointer;
      transition: var(--transition-smooth);
      text-decoration: none;
    }

    .rating-count:hover {
      color: var(--primary-dark);
      text-decoration: underline;
    }

    .product-stock {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 14px;
      padding: 10px 14px;
      background: rgba(161, 104, 58, 0.05);
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      font-weight: 600;
      border-left: 3px solid;
    }

    .stock-available {
      color: #28a745;
      border-color: #28a745;
      background: rgba(40, 167, 69, 0.05);
    }

    .stock-low {
      color: #ff9800;
      border-color: #ff9800;
      background: rgba(255, 152, 0, 0.05);
      animation: stockWarning 2s ease-in-out infinite;
    }

    @keyframes stockWarning {
      0%, 100% {
        opacity: 1;
      }
      50% {
        opacity: 0.7;
      }
    }

    .stock-out {
      color: #dc3545;
      border-color: #dc3545;
      background: rgba(220, 53, 69, 0.05);
    }

    .stock-icon {
      font-size: 16px;
    }

    .product-price-section {
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .product-price {
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--primary);
      font-family: 'Playfair Display', serif;
    }

    .price-original {
      font-size: 1.2rem;
      color: var(--text-muted);
      text-decoration: line-through;
      font-weight: 500;
    }

    .price-discount {
      padding: 4px 10px;
      background: linear-gradient(135deg, #dc3545, #e83e8c);
      color: white;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      font-weight: 700;
    }

    .product-actions {
      display: flex;
      gap: 10px;
      margin-top: auto;
    }

    .action-btn {
      flex: 1;
      padding: 14px;
      border: none;
      border-radius: var(--radius-md);
      cursor: pointer;
      transition: var(--transition-smooth);
      font-weight: 700;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      position: relative;
      overflow: hidden;
    }

    .action-btn::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .action-btn:active::before {
      width: 300px;
      height: 300px;
    }

    .action-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Botón de carrito tipo e-commerce moderno */
    .action-btn.cart-icon-btn {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      padding: 14px;
      box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
    }

    .action-btn.cart-icon-btn i {
      font-size: 1.3rem;
      transition: var(--transition-smooth);
    }

    .action-btn.cart-icon-btn:hover:not(:disabled) {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(161, 104, 58, 0.4);
    }

    .action-btn.cart-icon-btn:hover:not(:disabled) i {
      transform: scale(1.2);
    }

    .action-btn.cart-icon-btn.added {
      background: linear-gradient(135deg, #28a745, #20c997);
      animation: successPulse 0.6s ease;
    }

    @keyframes successPulse {
      0%, 100% {
        transform: scale(1);
      }
      50% {
        transform: scale(1.05);
      }
    }

    .action-btn.cart-icon-btn:disabled {
      background: linear-gradient(135deg, #dc3545, #c82333);
      cursor: not-allowed;
      opacity: 0.7;
    }

    /* Botón de favoritos */
    .action-btn.wishlist-btn {
      background: var(--card-bg);
      color: var(--primary);
      border: 2px solid var(--primary);
      padding: 14px;
      flex: 0;
      min-width: 54px;
    }

    .action-btn.wishlist-btn i {
      font-size: 1.3rem;
      transition: var(--transition-smooth);
    }

    .action-btn.wishlist-btn:hover:not(:disabled) {
      background: rgba(161, 104, 58, 0.1);
      border-color: var(--primary-dark);
      transform: translateY(-3px);
    }

    .action-btn.wishlist-btn:hover:not(:disabled) i {
      transform: scale(1.2);
    }

    .action-btn.wishlist-btn.active {
      background: linear-gradient(135deg, #dc3545, #e83e8c);
      border-color: #dc3545;
      color: white;
    }

    .action-btn.wishlist-btn.active:hover {
      background: linear-gradient(135deg, #c82333, #d63384);
      border-color: #c82333;
    }

    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 100px 20px;
      animation: fadeIn 0.6s ease;
    }

    .empty-state-icon {
      font-size: 5rem;
      color: var(--text-muted);
      opacity: 0.3;
      margin-bottom: 24px;
      animation: float 3s ease-in-out infinite;
    }

    .empty-state h3 {
      font-size: 1.8rem;
      color: var(--text-light);
      margin-bottom: 12px;
      font-weight: 700;
    }

    body.dark .empty-state h3 {
      color: var(--text-dark);
    }

    .empty-state p {
      color: var(--text-muted);
      font-size: 1.1rem;
    }

    /* Toast Notifications */
    .toast {
      position: fixed;
      bottom: 32px;
      right: 32px;
      padding: 18px 28px;
      background: var(--card-bg);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-xl);
      display: flex;
      align-items: center;
      gap: 14px;
      z-index: 10001;
      animation: toastSlideIn 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      max-width: 400px;
      border: 1px solid var(--border-color);
      backdrop-filter: blur(10px);
    }

    @keyframes toastSlideIn {
      from {
        opacity: 0;
        transform: translateX(400px) scale(0.8);
      }
      to {
        opacity: 1;
        transform: translateX(0) scale(1);
      }
    }

    .toast.removing {
      animation: toastSlideOut 0.3s ease forwards;
    }

    @keyframes toastSlideOut {
      to {
        opacity: 0;
        transform: translateX(400px) scale(0.8);
      }
    }

    .toast-icon {
      font-size: 1.6rem;
      flex-shrink: 0;
    }

    .toast-message {
      flex: 1;
      font-weight: 600;
      font-size: 0.95rem;
    }

    .toast.success {
      border-left: 4px solid #28a745;
    }

    .toast.success .toast-icon {
      color: #28a745;
    }

    .toast.error {
      border-left: 4px solid #dc3545;
    }

    .toast.error .toast-icon {
      color: #dc3545;
    }

    .toast.warning {
      border-left: 4px solid #ff9800;
    }

    .toast.warning .toast-icon {
      color: #ff9800;
    }

    .toast.info {
      border-left: 4px solid #2196f3;
    }

    .toast.info .toast-icon {
      color: #2196f3;
    }

    /* Animations */
    @keyframes heartbeat {
      0%, 100% {
        transform: scale(1);
      }
      25% {
        transform: scale(1.1);
      }
      50% {
        transform: scale(1);
      }
      75% {
        transform: scale(1.05);
      }
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .hero-banner {
        padding: clamp(50px, 8vh, 100px) 20px;
        min-height: 350px;
      }

      .hero-stats {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 16px;
      }

      .shape:nth-child(1) {
        width: 200px;
        height: 200px;
      }

      .shape:nth-child(2) {
        width: 150px;
        height: 150px;
      }
    }

    @media (max-width: 768px) {
      .hero-banner {
        padding: 60px 16px;
        min-height: 300px;
      }

      .hero-banner::after {
        background-size: 20px 20px;
      }

      .hero-badge {
        padding: 8px 20px;
        font-size: 0.75rem;
        letter-spacing: 1.5px;
      }

      .hero-title {
        margin-bottom: 16px;
      }

      .hero-description {
        margin-bottom: 32px;
        font-size: 1rem;
      }

      .hero-stats {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
      }

      .stat-item {
        padding: 20px 12px;
      }

      .stat-number {
        font-size: 2rem;
      }

      .stat-label {
        font-size: 0.75rem;
        letter-spacing: 1px;
      }

      .scroll-indicator {
        bottom: 20px;
        font-size: 0.75rem;
      }

      .shape {
        display: none;
      }

      .toolbar-wrapper {
        padding: 20px;
      }

      .toolbar {
        flex-direction: column;
      }

      .search-container {
        width: 100%;
        max-width: none;
      }

      .toolbar-controls {
        width: 100%;
        justify-content: space-between;
      }

      .sort-box {
        flex: 1;
      }

      .filter-tab {
        padding: 12px 20px;
      }

      .product-image-container {
        height: 250px;
      }

      .toast {
        bottom: 20px;
        right: 20px;
        left: 20px;
        max-width: none;
      }

      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
      }
    }

    @media (max-width: 480px) {
      .hero-banner {
        padding: 50px 12px;
        min-height: 280px;
      }

      .hero-stats {
        grid-template-columns: 1fr;
        gap: 12px;
        max-width: 300px;
      }

      .stat-item {
        padding: 16px;
      }

      .stat-number {
        font-size: 1.8rem;
      }

      .stat-icon {
        font-size: 1.2rem;
      }

      .scroll-indicator {
        display: none;
      }

      .products-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .product-title {
        font-size: 1.1rem;
      }

      .product-price {
        font-size: 1.5rem;
      }
    }

    body.dark .hero-banner {
      background: linear-gradient(135deg, #1a1612 0%, #0f0e0d 100%);
    }

    body.dark .hero-badge {
      background: rgba(161, 104, 58, 0.3);
      border-color: rgba(161, 104, 58, 0.4);
    }

    body.dark .stat-item {
      background: rgba(161, 104, 58, 0.15);
      border-color: rgba(161, 104, 58, 0.3);
    }

    /* ============================================
       CARRITO LATERAL PREMIUM
       ============================================ */
    
    .cart-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      z-index: 9998;
      opacity: 0;
      visibility: hidden;
      transition: var(--transition-smooth);
    }

    .cart-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .cart-sidebar {
      position: fixed;
      top: 0;
      right: -450px;
      width: 100%;
      max-width: 450px;
      height: 100%;
      background: var(--card-bg);
      box-shadow: var(--shadow-xl);
      z-index: 9999;
      display: flex;
      flex-direction: column;
      transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .cart-sidebar.active {
      right: 0;
    }

    .cart-header {
      padding: 24px;
      border-bottom: 2px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
    }

    .cart-title {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 1.4rem;
      font-weight: 800;
      font-family: 'Playfair Display', serif;
    }

    .cart-count-badge {
      background: rgba(255, 255, 255, 0.3);
      padding: 4px 12px;
      border-radius: 50px;
      font-size: 0.9rem;
      font-weight: 700;
      min-width: 32px;
      text-align: center;
    }

    .cart-close {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
      font-size: 1.2rem;
    }

    .cart-close:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: rotate(90deg);
    }

    .cart-body {
      flex: 1;
      overflow-y: auto;
      padding: 20px;
      scrollbar-width: thin;
      scrollbar-color: var(--primary) transparent;
    }

    .cart-body::-webkit-scrollbar {
      width: 8px;
    }

    .cart-body::-webkit-scrollbar-track {
      background: rgba(161, 104, 58, 0.05);
    }

    .cart-body::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 4px;
    }

    .cart-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      text-align: center;
      padding: 40px;
    }

    .cart-empty-icon {
      font-size: 5rem;
      color: var(--text-muted);
      opacity: 0.3;
      margin-bottom: 20px;
      animation: float 3s ease-in-out infinite;
    }

    .cart-empty-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--text-light);
      margin-bottom: 12px;
    }

    body.dark .cart-empty-title {
      color: var(--text-dark);
    }

    .cart-empty-text {
      color: var(--text-muted);
      font-size: 1rem;
      margin-bottom: 24px;
    }

    .cart-empty-btn {
      padding: 14px 32px;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      border-radius: var(--radius-md);
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cart-empty-btn:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .cart-items {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .cart-item {
      background: var(--card-bg);
      border: 2px solid var(--border-color);
      border-radius: var(--radius-md);
      padding: 16px;
      display: flex;
      gap: 16px;
      transition: var(--transition-smooth);
      position: relative;
      overflow: hidden;
    }

    .cart-item::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 4px;
      height: 100%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      opacity: 0;
      transition: var(--transition-smooth);
    }

    .cart-item:hover {
      border-color: var(--primary);
      transform: translateX(4px);
      box-shadow: var(--shadow-md);
    }

    .cart-item:hover::before {
      opacity: 1;
    }

    .cart-item-image {
      width: 80px;
      height: 80px;
      border-radius: var(--radius-sm);
      object-fit: cover;
      background: linear-gradient(135deg, #f5f3f0, #e6e4e0);
      flex-shrink: 0;
    }

    .cart-item-details {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .cart-item-name {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-light);
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.3;
    }

    body.dark .cart-item-name {
      color: var(--text-dark);
    }

    .cart-item-price {
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--primary);
      font-family: 'Playfair Display', serif;
    }

    .cart-item-controls {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(161, 104, 58, 0.1);
      padding: 4px;
      border-radius: var(--radius-sm);
    }

    .quantity-btn {
      width: 32px;
      height: 32px;
      border-radius: var(--radius-sm);
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      color: var(--primary);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
      font-weight: 700;
      font-size: 1rem;
    }

    .quantity-btn:hover:not(:disabled) {
      background: var(--primary);
      color: white;
      transform: scale(1.1);
    }

    .quantity-btn:disabled {
      opacity: 0.3;
      cursor: not-allowed;
    }

    .quantity-display {
      min-width: 40px;
      text-align: center;
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--text-light);
    }

    body.dark .quantity-display {
      color: var(--text-dark);
    }

    .cart-item-remove {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: rgba(220, 53, 69, 0.1);
      border: none;
      color: #dc3545;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition-smooth);
      margin-left: auto;
    }

    .cart-item-remove:hover {
      background: #dc3545;
      color: white;
      transform: rotate(90deg) scale(1.1);
    }

    .cart-footer {
      padding: 20px 24px;
      border-top: 2px solid var(--border-color);
      background: var(--card-bg);
    }

    .cart-summary {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 20px;
    }

    .cart-summary-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 1rem;
    }

    .cart-summary-label {
      color: var(--text-muted);
      font-weight: 600;
    }

    .cart-summary-value {
      font-weight: 700;
      color: var(--text-light);
    }

    body.dark .cart-summary-value {
      color: var(--text-dark);
    }

    .cart-summary-row.total {
      padding-top: 12px;
      border-top: 2px solid var(--border-color);
      font-size: 1.3rem;
    }

    .cart-summary-row.total .cart-summary-label {
      color: var(--text-light);
      font-weight: 800;
    }

    body.dark .cart-summary-row.total .cart-summary-label {
      color: var(--text-dark);
    }

    .cart-summary-row.total .cart-summary-value {
      color: var(--primary);
      font-size: 1.8rem;
      font-family: 'Playfair Display', serif;
    }

    .cart-actions {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .cart-btn {
      padding: 16px;
      border-radius: var(--radius-md);
      border: none;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-decoration: none;
    }

    .cart-btn.primary {
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
    }

    .cart-btn.primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(161, 104, 58, 0.4);
    }

    .cart-btn.secondary {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }

    .cart-btn.secondary:hover {
      background: rgba(161, 104, 58, 0.1);
      transform: translateY(-2px);
    }

    .clear-cart-btn {
      padding: 12px;
      background: rgba(220, 53, 69, 0.1);
      color: #dc3545;
      border: 2px solid rgba(220, 53, 69, 0.2);
      border-radius: var(--radius-md);
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 12px;
    }

    .clear-cart-btn:hover {
      background: #dc3545;
      color: white;
      border-color: #dc3545;
    }

    /* Botón flotante del carrito */
    .cart-float-btn {
      position: fixed;
      bottom: 32px;
      right: 32px;
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 8px 24px rgba(161, 104, 58, 0.4);
      transition: var(--transition-smooth);
      z-index: 1000;
    }

    .cart-float-btn:hover {
      transform: translateY(-4px) scale(1.1);
      box-shadow: 0 12px 32px rgba(161, 104, 58, 0.5);
    }

    .cart-float-badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #dc3545;
      color: white;
      width: 28px;
      height: 28px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      border: 3px solid var(--bg-light);
      animation: pulse 2s ease-in-out infinite;
    }

    body.dark .cart-float-badge {
      border-color: var(--bg-dark);
    }

    @media (max-width: 768px) {
      .cart-sidebar {
        max-width: 100%;
      }

      .cart-float-btn {
        width: 56px;
        height: 56px;
        bottom: 20px;
        right: 20px;
        font-size: 1.3rem;
      }

      .cart-float-badge {
        width: 24px;
        height: 24px;
        font-size: 0.7rem;
      }
    }
  </style>
</head>
<body>
  <div class="hero-banner loaded">
    <div class="hero-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>

    <div class="hero-content">
      <div class="hero-badge">
        <i class="fas fa-star"></i>
        Productos Premium
      </div>

      <h1 class="hero-title" data-text="Catálogo Exclusivo">
        Catálogo Exclusivo
      </h1>

      <p class="hero-description">
        Descubre nuestra colección cuidadosamente seleccionada de productos de la más alta calidad con los mejores precios del mercado
      </p>

      <div class="hero-stats">
        <div class="stat-item">
          <i class="fas fa-box-open stat-icon"></i>
          <span class="stat-number" id="totalProducts">0</span>
          <span class="stat-label">Productos</span>
        </div>
        
        <div class="stat-item">
          <i class="fas fa-layer-group stat-icon"></i>
          <span class="stat-number"><?= count($categorias) ?></span>
          <span class="stat-label">Categorías</span>
        </div>
        
        <div class="stat-item">
          <i class="fas fa-star stat-icon"></i>
          <span class="stat-number">4.8</span>
          <span class="stat-label">Calificación</span>
        </div>
      </div>
    </div>

    <div class="scroll-indicator" onclick="document.getElementById('productsGrid')?.scrollIntoView({behavior: 'smooth'})">
      <span>Explorar</span>
      <i class="fas fa-chevron-down"></i>
    </div>
  </div>

  <div class="container">
    <div class="toolbar-wrapper">
      <div class="toolbar">
        <div class="search-container">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Buscar productos, categorías...">
            <button class="clear-search" id="btnClearSearch">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="toolbar-controls">
          <div class="sort-box">
            <label>
              <i class="fas fa-sort-amount-down"></i>
            </label>
            <select id="sortSelect">
              <option value="default">Recomendados</option>
              <option value="name-asc">A → Z</option>
              <option value="name-desc">Z → A</option>
              <option value="price-asc">Precio ↑</option>
              <option value="price-desc">Precio ↓</option>
              <option value="rating-desc">Mejor Valorados</option>
              <option value="newest">Más Recientes</option>
            </select>
          </div>

          <button class="toolbar-btn" id="btnViewToggle" title="Cambiar vista">
            <i class="fas fa-th"></i>
          </button>

          <button class="toolbar-btn" id="btnResetFilters" style="display:none;" title="Limpiar filtros">
            <i class="fas fa-redo-alt"></i>
          </button>

          <span class="results-count" id="resultsCount">
            <i class="fas fa-box-open"></i> 0 productos
          </span>
        </div>
      </div>
    </div>

    <?php if (!empty($categorias)): ?>
    <div class="filter-section">
      <div class="filter-header">
        <div class="filter-title">
          <i class="fas fa-filter"></i>
          Categorías
        </div>
      </div>
      <div class="filter-tabs" id="filterTabs">
        <?php 
        $productosCount = [];
        foreach ($todosProductos as $p) {
          $catId = (int)($p['categoria_id'] ?? 0);
          if (!isset($productosCount[$catId])) {
            $productosCount[$catId] = 0;
          }
          $productosCount[$catId]++;
        }
        
        foreach ($categorias as $cat): 
          $catId = (int)$cat['id'];
          $catNombre = htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8');
          $count = $productosCount[$catId] ?? 0;
          
          if ($count === 0) continue;
        ?>
        <a href="#" class="filter-tab" data-cat="<?= $catId ?>" data-count="<?= $count ?>">
          <span><?= $catNombre ?></span>
          <span class="badge" id="badge-<?= $catId ?>"><?= $count ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="products-grid" id="productsGrid">
      <?php foreach ($todosProductos as $producto): 
        $id = (int)$producto['id'];
        $nombre = htmlspecialchars($producto['nombre'] ?? 'Sin nombre', ENT_QUOTES, 'UTF-8');
        $descripcion = htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
        $precio = (float)($producto['precio'] ?? 0);
        $categoria_id = (int)($producto['categoria_id'] ?? 0);
        $categoria_nombre = 'General';
        foreach ($categorias as $cat) {
          if ((int)$cat['id'] === $categoria_id) {
            $categoria_nombre = htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8');
            break;
          }
        }
        $imagen = prod_img_url($producto['imagen'] ?? '', $BASE);
        $isFav = isset($favoritosSet[$id]);
        
        $cantidad = (int)($producto['cantidad_inventario'] ?? 0);
        
        $stockClass = $cantidad > 10 ? 'stock-available' : ($cantidad > 0 ? 'stock-low' : 'stock-out');
        $stockText = $cantidad > 10 ? "En stock ($cantidad disponibles)" : ($cantidad > 0 ? "¡Últimas $cantidad unidades!" : "Agotado");
        $stockIcon = $cantidad > 10 ? 'fa-check-circle' : ($cantidad > 0 ? 'fa-exclamation-triangle' : 'fa-times-circle');
        
        $avgRating = 0;
        $totalReviews = 0;
        
        $conn = getDBConnection();
        if ($conn) {
          $stmtRating = $conn->prepare("
            SELECT 
              COALESCE(AVG(rating), 0) as avg_rating,
              COUNT(*) as total_reviews
            FROM opiniones 
            WHERE producto_id = ?
          ");
          
          if ($stmtRating) {
            $stmtRating->bind_param("i", $id);
            if ($stmtRating->execute()) {
              $resultRating = $stmtRating->get_result();
              if ($ratingData = $resultRating->fetch_assoc()) {
                $avgRating = (float)$ratingData['avg_rating'];
                $totalReviews = (int)$ratingData['total_reviews'];
              }
              $resultRating->free();
            }
            $stmtRating->close();
          }
        }
        
        $precioOriginal = (float)($producto['precio_original'] ?? 0);
        $hasDiscount = $precioOriginal > 0 && $precioOriginal > $precio;
        $discountPercent = $hasDiscount ? round((($precioOriginal - $precio) / $precioOriginal) * 100) : 0;
        $originalPrice = $hasDiscount ? $precioOriginal : $precio;
      ?>
      <div class="product-card" 
           data-id="<?= $id ?>" 
           data-nombre="<?= $nombre ?>" 
           data-precio="<?= $precio ?>" 
           data-cat="<?= $categoria_id ?>"
           data-img="<?= $imagen ?>"
           data-rating="<?= $avgRating ?>"
           data-cantidad="<?= $cantidad ?>">
        
        <div class="product-image-container">
          <img src="<?= $imagen ?>" alt="<?= $nombre ?>" class="product-image" loading="lazy">
          
          <div class="image-overlay">
            <button class="quick-view-btn" onclick="window.location.href='<?= $BASE ?>views/productos-detal.php?id=<?= $id ?>'">
              <i class="fas fa-eye"></i>
              <span>Vista Rápida</span>
            </button>
          </div>

          <div class="product-badges">
            <?php if ($cantidad === 0): ?>
              <div class="product-badge badge-sale">Agotado</div>
            <?php elseif ($cantidad <= 5): ?>
              <div class="product-badge badge-stock-low">¡Últimas unidades!</div>
            <?php endif; ?>
            
            <?php if ($hasDiscount): ?>
              <div class="product-badge badge-featured">-<?= $discountPercent ?>%</div>
            <?php endif; ?>

            <?php if ($avgRating >= 4.5): ?>
              <div class="product-badge badge-new">⭐ Destacado</div>
            <?php endif; ?>
          </div>

          <div class="quick-actions">
            <button class="quick-action js-compare" title="Comparar producto">
              <i class="fas fa-balance-scale"></i>
            </button>
          </div>
        </div>
        
        <div class="product-body">
          <div class="product-category">
            <i class="fas fa-tag"></i>
            <?= $categoria_nombre ?>
          </div>
          
          <h3 class="product-title"><?= $nombre ?></h3>
          <p class="product-description"><?= $descripcion ?></p>
          
          <div class="product-rating" data-product-id="<?= $id ?>">
            <div class="stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star star <?= $i <= floor($avgRating) ? 'filled' : '' ?>"></i>
              <?php endfor; ?>
            </div>
            <span class="rating-info">
              <?= number_format($avgRating, 1) ?> 
              (<span class="rating-count" data-product-id="<?= $id ?>"><?= number_format($totalReviews) ?> opiniones</span>)
            </span>
          </div>

          <div class="product-stock <?= $stockClass ?>">
            <i class="fas <?= $stockIcon ?> stock-icon"></i>
            <span><?= $stockText ?></span>
          </div>

          <div class="product-price-section">
            <div class="product-price">$<?= number_format($precio, 2) ?></div>
            <?php if ($hasDiscount): ?>
              <div class="price-original">$<?= number_format($originalPrice, 2) ?></div>
              <div class="price-discount">-<?= $discountPercent ?>%</div>
            <?php endif; ?>
          </div>
          
          <div class="product-actions">
            <button class="action-btn cart-icon-btn js-cart" <?= $cantidad === 0 ? 'disabled' : '' ?> title="<?= $cantidad === 0 ? 'Agotado' : 'Agregar al carrito' ?>">
              <i class="fas fa-shopping-cart"></i>
              <?= $cantidad === 0 ? 'Agotado' : 'Agregar al Carrito' ?>
            </button>
            <button class="action-btn wishlist-btn js-wish <?= $isFav ? 'active' : '' ?>" title="<?= $isFav ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>">
              <i class="fas fa-heart"></i>
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="empty-state" id="emptyState" style="display:none;">
      <div class="empty-state-icon">
        <i class="fas fa-search"></i>
      </div>
      <h3>No encontramos productos</h3>
      <p>Intenta ajustar los filtros o búsqueda para encontrar lo que necesitas</p>
    </div>
  </div>

  <!-- ============================================
       CARRITO LATERAL PREMIUM
       ============================================ -->
  <div class="cart-overlay" id="cartOverlay"></div>
  
  <div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
      <div class="cart-title">
        <i class="fas fa-shopping-cart"></i>
        <span>Mi Carrito</span>
        <span class="cart-count-badge" id="cartCountBadge">0</span>
      </div>
      <button class="cart-close" id="cartCloseBtn">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="cart-body" id="cartBody">
      <!-- Contenido dinámico del carrito -->
    </div>

    <div class="cart-footer" id="cartFooter" style="display: none;">
      <div class="cart-summary">
        <div class="cart-summary-row">
          <span class="cart-summary-label">Subtotal</span>
          <span class="cart-summary-value" id="cartSubtotal">$0.00</span>
        </div>
        <div class="cart-summary-row">
          <span class="cart-summary-label">Envío</span>
          <span class="cart-summary-value" id="cartShipping">Gratis</span>
        </div>
        <div class="cart-summary-row total">
          <span class="cart-summary-label">Total</span>
          <span class="cart-summary-value" id="cartTotal">$0.00</span>
        </div>
      </div>

      <div class="cart-actions">
        <a href="<?= $BASE ?>views/checkout.php" class="cart-btn primary">
          <i class="fas fa-lock"></i>
          Proceder al Pago
        </a>
        <button class="cart-btn secondary" id="cartContinueBtn">
          <i class="fas fa-arrow-left"></i>
          Seguir Comprando
        </button>
        <button class="clear-cart-btn" id="clearCartBtn">
          <i class="fas fa-trash-alt"></i>
          Vaciar Carrito
        </button>
      </div>
    </div>
  </div>

  <!-- Botón flotante del carrito -->
  <button class="cart-float-btn" id="cartFloatBtn" title="Ver carrito">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-float-badge" id="cartFloatBadge" style="display: none;">0</span>
  </button>

<script>
  const BASE = '<?= $BASE ?>';
  const USER_ID = <?= $usuario_id ?>;
  const LS_CART = 'lumispace_cart';
  const LS_COMP = 'lumispace_compare';

  const getCart = () => JSON.parse(localStorage.getItem(LS_CART) || '[]');
  const saveCart = cart => localStorage.setItem(LS_CART, JSON.stringify(cart));
  const getSet = key => new Set(JSON.parse(localStorage.getItem(key) || '[]'));
  const saveSet = (key, set) => localStorage.setItem(key, JSON.stringify([...set]));

  function toast(msg, type = 'info') {
    const icons = {
      success: 'fa-check-circle',
      error: 'fa-times-circle',
      warning: 'fa-exclamation-triangle',
      info: 'fa-info-circle'
    };

    const div = document.createElement('div');
    div.className = `toast ${type}`;
    div.innerHTML = `
      <i class="fas ${icons[type]} toast-icon"></i>
      <span class="toast-message">${msg}</span>
    `;

    document.body.appendChild(div);

    setTimeout(() => {
      div.classList.add('removing');
      setTimeout(() => div.remove(), 300);
    }, 3500);
  }

  function updateBadge() {
    const cart = getCart();
    const total = cart.reduce((sum, item) => sum + (item.cantidad || 1), 0);
    
    // Actualizar badge del carrito lateral
    const cartCountBadge = document.getElementById('cartCountBadge');
    if (cartCountBadge) cartCountBadge.textContent = total;
    
    // Actualizar badge flotante
    const cartFloatBadge = document.getElementById('cartFloatBadge');
    if (cartFloatBadge) {
      cartFloatBadge.textContent = total;
      cartFloatBadge.style.display = total > 0 ? 'flex' : 'none';
    }
    
    // Actualizar badge del navbar (si existe)
    const navBadge = document.querySelector('.cart-badge');
    if (navBadge) navBadge.textContent = total;
  }

  // Renderizar carrito
  function renderCart() {
    const cart = getCart();
    const cartBody = document.getElementById('cartBody');
    const cartFooter = document.getElementById('cartFooter');
    
    if (cart.length === 0) {
      cartBody.innerHTML = `
        <div class="cart-empty">
          <div class="cart-empty-icon">
            <i class="fas fa-shopping-cart"></i>
          </div>
          <h3 class="cart-empty-title">Tu carrito está vacío</h3>
          <p class="cart-empty-text">¡Agrega productos y comienza a comprar!</p>
          <button class="cart-empty-btn" onclick="closeCart()">
            <i class="fas fa-shopping-bag"></i>
            Explorar Productos
          </button>
        </div>
      `;
      cartFooter.style.display = 'none';
      return;
    }

    cartFooter.style.display = 'block';
    
    let html = '<div class="cart-items">';
    let subtotal = 0;

    cart.forEach(item => {
      const itemTotal = item.precio * item.cantidad;
      subtotal += itemTotal;
      
      html += `
        <div class="cart-item" data-id="${item.id}">
          <img src="${item.imagen}" alt="${item.nombre}" class="cart-item-image">
          <div class="cart-item-details">
            <div class="cart-item-name">${item.nombre}</div>
            <div class="cart-item-price">$${item.precio.toFixed(2)}</div>
            <div class="cart-item-controls">
              <div class="quantity-controls">
                <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">
                  <i class="fas fa-minus"></i>
                </button>
                <span class="quantity-display">${item.cantidad}</span>
                <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
              <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Eliminar">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    });

    html += '</div>';
    cartBody.innerHTML = html;

    // Actualizar totales
    const shipping = subtotal >= 500 ? 0 : 50; // Envío gratis sobre $500
    const total = subtotal + shipping;

    document.getElementById('cartSubtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('cartShipping').textContent = shipping === 0 ? 'Gratis' : `$${shipping.toFixed(2)}`;
    document.getElementById('cartTotal').textContent = `$${total.toFixed(2)}`;
  }

  // Abrir carrito
  function openCart() {
    document.getElementById('cartOverlay').classList.add('active');
    document.getElementById('cartSidebar').classList.add('active');
    document.body.style.overflow = 'hidden';
    renderCart();
  }

  // Cerrar carrito
  function closeCart() {
    document.getElementById('cartOverlay').classList.remove('active');
    document.getElementById('cartSidebar').classList.remove('active');
    document.body.style.overflow = '';
  }

  // Actualizar cantidad
  function updateQuantity(productId, change) {
    const cart = getCart();
    const item = cart.find(i => i.id === productId);
    
    if (item) {
      item.cantidad += change;
      
      if (item.cantidad <= 0) {
        removeFromCart(productId);
        return;
      }
      
      saveCart(cart);
      renderCart();
      updateBadge();
      toast(`Cantidad actualizada: ${item.cantidad}`, 'info');
    }
  }

  // Eliminar del carrito
  function removeFromCart(productId) {
    let cart = getCart();
    const item = cart.find(i => i.id === productId);
    
    if (item) {
      cart = cart.filter(i => i.id !== productId);
      saveCart(cart);
      renderCart();
      updateBadge();
      toast('Producto eliminado del carrito', 'info');
    }
  }

  // Vaciar carrito
  function clearCart() {
    if (confirm('¿Estás seguro de que deseas vaciar el carrito?')) {
      saveCart([]);
      renderCart();
      updateBadge();
      toast('Carrito vaciado', 'success');
    }
  }

  // Event Listeners
  document.addEventListener('DOMContentLoaded', function() {
    updateBadge();
    
    // Botón flotante
    document.getElementById('cartFloatBtn')?.addEventListener('click', openCart);
    
    // Cerrar carrito
    document.getElementById('cartCloseBtn')?.addEventListener('click', closeCart);
    document.getElementById('cartOverlay')?.addEventListener('click', closeCart);
    document.getElementById('cartContinueBtn')?.addEventListener('click', closeCart);
    
    // Vaciar carrito
    document.getElementById('clearCartBtn')?.addEventListener('click', clearCart);
  });

  (function() {
    const $grid = document.getElementById('productsGrid');
    const $search = document.getElementById('searchInput');
    const $clearSearch = document.getElementById('btnClearSearch');
    const $sort = document.getElementById('sortSelect');
    const $resultsCount = document.getElementById('resultsCount');
    const $emptyState = document.getElementById('emptyState');
    const $totalProducts = document.getElementById('totalProducts');

    let allCards = [];
    let currentCat = '';
    let currentSearch = '';
    let searchTimeout = null;
    let isListView = false;

    const filterAndSort = () => {
      let visible = allCards.filter(card => {
        const catMatch = !currentCat || card.dataset.cat === currentCat;
        const searchMatch = !currentSearch || 
          card.dataset.nombre.toLowerCase().includes(currentSearch.toLowerCase());
        return catMatch && searchMatch;
      });

      const sortVal = $sort.value;
      if (sortVal === 'name-asc') {
        visible.sort((a, b) => a.dataset.nombre.localeCompare(b.dataset.nombre));
      } else if (sortVal === 'name-desc') {
        visible.sort((a, b) => b.dataset.nombre.localeCompare(a.dataset.nombre));
      } else if (sortVal === 'price-asc') {
        visible.sort((a, b) => parseFloat(a.dataset.precio) - parseFloat(b.dataset.precio));
      } else if (sortVal === 'price-desc') {
        visible.sort((a, b) => parseFloat(b.dataset.precio) - parseFloat(a.dataset.precio));
      } else if (sortVal === 'rating-desc') {
        visible.sort((a, b) => parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0));
      }

      allCards.forEach(card => {
        const shouldShow = visible.includes(card);
        card.classList.toggle('hidden', !shouldShow);
      });

      visible.forEach(card => $grid.appendChild(card));

      $resultsCount.innerHTML = `<i class="fas fa-box-open"></i> ${visible.length} producto${visible.length !== 1 ? 's' : ''}`;
      
      updateCategoryBadges(visible);
      
      $emptyState.style.display = visible.length === 0 ? 'block' : 'none';
      $grid.style.display = visible.length === 0 ? 'none' : 'grid';
    };

    const updateCategoryBadges = (visibleCards) => {
      const categoryCounts = {};
      
      visibleCards.forEach(card => {
        const catId = card.dataset.cat;
        if (!categoryCounts[catId]) {
          categoryCounts[catId] = 0;
        }
        categoryCounts[catId]++;
      });
      
      document.querySelectorAll('.filter-tab').forEach(tab => {
        const catId = tab.dataset.cat;
        const badge = tab.querySelector('.badge');
        const count = categoryCounts[catId] || 0;
        
        if (badge) {
          const currentCount = parseInt(badge.textContent) || 0;
          if (currentCount !== count) {
            badge.style.transform = 'scale(1.2)';
            setTimeout(() => {
              badge.textContent = count;
              badge.style.transform = 'scale(1)';
            }, 150);
          }
          
          if (currentSearch && count === 0) {
            tab.style.opacity = '0.4';
            tab.style.pointerEvents = 'none';
          } else {
            tab.style.opacity = '1';
            tab.style.pointerEvents = 'auto';
          }
        }
      });
    };

    const resetFilters = () => {
      currentCat = '';
      currentSearch = '';
      $search.value = '';
      $clearSearch.classList.remove('visible');
      document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
      $sort.value = 'default';
      filterAndSort();
      updateResetButton();
      toast('✨ Filtros restablecidos', 'info');
    };

    const updateResetButton = () => {
      const btn = document.getElementById('btnResetFilters');
      const shouldShow = currentCat !== '' || currentSearch !== '';
      btn.style.display = shouldShow ? 'flex' : 'none';
    };

    const toggleView = () => {
      isListView = !isListView;
      $grid.classList.toggle('list-view', isListView);
      
      const btn = document.getElementById('btnViewToggle');
      const icon = btn.querySelector('i');
      
      if (isListView) {
        icon.className = 'fas fa-list';
        btn.title = 'Vista Lista';
      } else {
        icon.className = 'fas fa-th';
        btn.title = 'Vista Grid';
      }
      
      localStorage.setItem('productViewMode', isListView ? 'list' : 'grid');
      toast(`Vista ${isListView ? 'Lista' : 'Grid'} activada`, 'info');
    };

    $search.addEventListener('input', e => {
      currentSearch = e.target.value.trim();
      $clearSearch.classList.toggle('visible', currentSearch !== '');
      
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        filterAndSort();
        updateResetButton();
      }, 300);
    });

    $clearSearch.addEventListener('click', () => {
      currentSearch = '';
      $search.value = '';
      $clearSearch.classList.remove('visible');
      filterAndSort();
      updateResetButton();
      toast('🔍 Búsqueda limpiada', 'info');
    });

    document.getElementById('btnResetFilters').addEventListener('click', resetFilters);
    document.getElementById('btnViewToggle').addEventListener('click', toggleView);
    $sort.addEventListener('change', () => {
      filterAndSort();
      toast('📊 Orden actualizado', 'info');
    });

    document.querySelectorAll('.filter-tab').forEach(tab => {
      tab.addEventListener('click', e => {
        e.preventDefault();
        const catId = tab.dataset.cat;
        
        if (tab.classList.contains('active')) {
          tab.classList.remove('active');
          currentCat = '';
          toast('📦 Mostrando todos los productos', 'info');
        } else {
          document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
          tab.classList.add('active');
          currentCat = catId;
          const catName = tab.textContent.trim().split(/\d+/)[0].trim();
          toast(`🏷️ Categoría: ${catName}`, 'info');
          tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        
        filterAndSort();
        updateResetButton();
      });
    });

    // ========================================
    // EVENT LISTENER UNIFICADO Y OPTIMIZADO
    // ========================================
    $grid.addEventListener('click', async e => {
      const card = e.target.closest('.product-card');
      if (!card) return;
      
      // Detectar si se clickeó un elemento de acción específico
      const actionBtn = e.target.closest('.action-btn, .quick-action');
      const isOtherAction = e.target.closest('.rating-count, .quick-view-btn');
      
      // Si NO es una acción, navegar al detalle del producto
      if (!actionBtn && !isOtherAction) {
        const prodId = card.dataset.id;
        window.location.href = `${BASE}views/productos-detal.php?id=${prodId}`;
        return;
      }
      
      // Si es un botón de acción, prevenir navegación y procesar
      if (actionBtn) {
        e.preventDefault();
        e.stopPropagation();

      const prodId = parseInt(card.dataset.id, 10);
      const cantidad = parseInt(card.dataset.cantidad, 10);

      if (actionBtn.classList.contains('js-wish')) {
        if (!USER_ID) {
          toast('⚠️ Debes iniciar sesión para agregar favoritos', 'warning');
          setTimeout(() => location.href = BASE + 'views/login.php?next=' + encodeURIComponent(location.pathname), 1500);
          return;
        }

        actionBtn.disabled = true;
        const icon = actionBtn.querySelector('i');
        const originalIcon = icon.className;
        icon.className = 'fas fa-spinner fa-spin';

        try {
          const res = await fetch(BASE + 'api/wishlist/toggle.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: prodId })
          });

          const data = await res.json();
          if (!data.ok) throw new Error(data.msg || 'Error');

          const isInWishlist = data.in_wishlist;
          actionBtn.classList.toggle('active', isInWishlist);
          icon.className = 'fas fa-heart';
          actionBtn.title = isInWishlist ? 'Quitar de favoritos' : 'Agregar a favoritos';
          
          // Efecto visual
          if (isInWishlist) {
            actionBtn.style.animation = 'heartbeat 0.6s ease';
            setTimeout(() => actionBtn.style.animation = '', 600);
          }
          
          toast(isInWishlist ? '❤️ Agregado a favoritos' : '💔 Eliminado de favoritos', 'success');
        } catch (err) {
          console.error(err);
          icon.className = originalIcon;
          toast('❌ Error al actualizar favoritos', 'error');
        } finally {
          actionBtn.disabled = false;
        }
        return;
      }

      if (actionBtn.classList.contains('js-compare')) {
        const id = String(prodId);
        const s = getSet(LS_COMP);
        const wasActive = s.has(id);
        
        if (!wasActive && s.size >= 4) {
          toast('⚠️ Máximo 4 productos para comparar', 'warning');
          return;
        }
        
        wasActive ? s.delete(id) : s.add(id);
        saveSet(LS_COMP, s);
        
        actionBtn.classList.toggle('active', !wasActive);
        toast(wasActive ? '➖ Removido de comparación' : `➕ Agregado a comparación (${s.size}/4)`, 'info');
        return;
      }

      if (actionBtn.classList.contains('js-cart')) {
        if (cantidad === 0) {
          toast('❌ Producto agotado', 'error');
          return;
        }

        const cart = getCart();
        const existing = cart.find(item => item.id === prodId);
        
        if (existing) {
          if (existing.cantidad >= cantidad) {
            toast(`⚠️ Stock máximo alcanzado (${cantidad} unidades)`, 'warning');
            return;
          }
          existing.cantidad++;
          toast(`🔄 Cantidad actualizada: ${existing.cantidad} unidades`, 'success');
        } else {
          cart.push({
            id: prodId,
            nombre: card.dataset.nombre,
            precio: parseFloat(card.dataset.precio),
            imagen: card.dataset.img,
            cantidad: 1
          });
          toast('🛒 ¡Agregado al carrito!', 'success');
        }
        
        saveCart(cart);
        updateBadge();
        
        // Efecto visual en el botón - cambio de icono
        actionBtn.classList.add('added');
        const icon = actionBtn.querySelector('i');
        const originalIcon = icon.className;
        icon.className = 'fas fa-check';
        
        setTimeout(() => {
          actionBtn.classList.remove('added');
          icon.className = originalIcon;
        }, 1000);
        
        // Animación de producto volando al carrito
        const productRect = card.getBoundingClientRect();
        const cartBtn = document.getElementById('cartFloatBtn');
        if (cartBtn) {
          const cartRect = cartBtn.getBoundingClientRect();
          
          const flyingProduct = document.createElement('div');
          flyingProduct.style.cssText = `
            position: fixed;
            left: ${productRect.left + productRect.width / 2}px;
            top: ${productRect.top + productRect.height / 2}px;
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            z-index: 10000;
            pointer-events: none;
            transition: all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
          `;
          flyingProduct.innerHTML = '<i class="fas fa-shopping-cart"></i>';
          document.body.appendChild(flyingProduct);
          
          setTimeout(() => {
            flyingProduct.style.left = `${cartRect.left + cartRect.width / 2}px`;
            flyingProduct.style.top = `${cartRect.top + cartRect.height / 2}px`;
            flyingProduct.style.transform = 'scale(0)';
            flyingProduct.style.opacity = '0';
          }, 50);
          
          setTimeout(() => {
            flyingProduct.remove();
            cartBtn.style.animation = 'heartbeat 0.6s ease';
            setTimeout(() => cartBtn.style.animation = '', 600);
          }, 850);
        }
        
        const currentQuantity = parseInt(card.dataset.cantidad);
        const totalInCart = existing ? existing.cantidad : 1;
        const remainingStock = currentQuantity - totalInCart;
        
        if (remainingStock >= 0) {
          const stockElement = card.querySelector('.product-stock');
          const stockSpan = stockElement.querySelector('span');
          const stockIcon = stockElement.querySelector('.stock-icon');
          
          stockElement.classList.remove('stock-available', 'stock-low', 'stock-out');
          
          if (remainingStock > 10) {
            stockElement.classList.add('stock-available');
            stockIcon.className = 'fas fa-check-circle stock-icon';
            stockSpan.textContent = `En stock (${remainingStock} disponibles)`;
          } else if (remainingStock > 0) {
            stockElement.classList.add('stock-low');
            stockIcon.className = 'fas fa-exclamation-triangle stock-icon';
            stockSpan.textContent = `¡Últimas ${remainingStock} unidades!`;
          } else {
            stockElement.classList.add('stock-out');
            stockIcon.className = 'fas fa-times-circle stock-icon';
            stockSpan.textContent = 'Agotado';
            
            actionBtn.disabled = true;
            actionBtn.title = 'Agotado';
            
            const stockBadge = card.querySelector('.badge-stock-low');
            if (stockBadge) {
              stockBadge.textContent = 'Agotado';
              stockBadge.className = 'product-badge badge-sale';
            }
          }
        }
        
        return;
      }
      } // Fin del if (actionBtn)
    });

    allCards = Array.from($grid.querySelectorAll('.product-card'));
    updateBadge();

    $totalProducts.textContent = allCards.length;
    
    let count = 0;
    const target = allCards.length;
    const duration = 1000;
    const increment = target / (duration / 16);
    
    const animate = () => {
      count += increment;
      if (count < target) {
        $totalProducts.textContent = Math.floor(count);
        requestAnimationFrame(animate);
      } else {
        $totalProducts.textContent = target;
      }
    };
    animate();

    const compSet = getSet(LS_COMP);
    allCards.forEach(card => {
      const id = String(card.dataset.id);
      const compBtn = card.querySelector('.js-compare');
      if (compBtn && compSet.has(id)) {
        compBtn.classList.add('active');
      }
    });

    const savedView = localStorage.getItem('productViewMode');
    if (savedView === 'list') {
      toggleView();
    }

    currentCat = '';
    currentSearch = '';
    
    console.log(`✅ Sistema de productos cargado: ${allCards.length} productos disponibles`);
    
    setTimeout(() => {
      allCards.forEach(card => card.classList.remove('hidden'));
      filterAndSort();
    }, 100);

    document.addEventListener('DOMContentLoaded', function() {
      const heroBanner = document.querySelector('.hero-banner');
      heroBanner.classList.add('loaded');

      let ticking = false;
      window.addEventListener('scroll', function() {
        if (!ticking) {
          window.requestAnimationFrame(function() {
            const scrolled = window.pageYOffset;
            const heroContent = document.querySelector('.hero-content');
            const heroShapes = document.querySelectorAll('.shape');
            
            if (heroContent && scrolled < 500) {
              heroContent.style.transform = `translateY(${scrolled * 0.3}px)`;
              heroContent.style.opacity = 1 - (scrolled / 500);
            }

            heroShapes.forEach((shape, index) => {
              const speed = (index + 1) * 0.15;
              shape.style.transform = `translateY(${scrolled * speed}px)`;
            });

            ticking = false;
          });
          ticking = true;
        }
      });

      window.addEventListener('scroll', function() {
        const scrollIndicator = document.querySelector('.scroll-indicator');
        if (scrollIndicator) {
          if (window.pageYOffset > 100) {
            scrollIndicator.style.opacity = '0';
            scrollIndicator.style.pointerEvents = 'none';
          } else {
            scrollIndicator.style.opacity = '1';
            scrollIndicator.style.pointerEvents = 'auto';
          }
        }
      });
    });

    function animateValue(element, start, end, duration) {
      let startTimestamp = null;
      const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.textContent = value;
        if (progress < 1) {
          window.requestAnimationFrame(step);
        } else {
          element.textContent = end;
        }
      };
      window.requestAnimationFrame(step);
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const statNumbers = document.querySelectorAll('.stat-number');
          statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent) || 0;
            if (finalValue > 0 && stat.id !== 'totalProducts') {
              animateValue(stat, 0, finalValue, 1500);
            }
          });
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    const heroBanner = document.querySelector('.hero-banner');
    if (heroBanner) {
      observer.observe(heroBanner);
    }
  })();
</script>
</body>
</html>