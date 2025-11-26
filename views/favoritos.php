<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$uid = $_SESSION['usuario_id'] ?? 0;
$favoritos = getFavoritos($uid);

// Debug: verificar que los favoritos se obtengan correctamente
if (empty($favoritos) && $uid > 0) {
    error_log("⚠️ No se encontraron favoritos para usuario ID: " . $uid);
}

require_once __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos | LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
    <link rel="stylesheet" href="<?= $BASE ?>css/styles/favoritos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Hero Section -->
    <section class="fav-hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <span class="hero-tag">Tu Colección Personal</span>
            <h1 class="hero-title">Mis Favoritos</h1>
            <p class="hero-excerpt">
                Una selección curada de las piezas que te inspiran. <br>
                Guarda, compara y decide cuando estés listo para transformar tu espacio.
            </p>
        </div>
    </section>

    <main class="container main-layout">
        <div class="content-area">
            <div class="section-header">
                <h2>Artículos Guardados <span class="count-badge"><?= count($favoritos) ?></span></h2>
                <div class="line-accent"></div>
                <?php if (!empty($favoritos)): ?>
                    <div class="view-options">
                        <button class="view-btn active" title="Vista Cuadrícula"><i class="fas fa-th-large"></i></button>
                        <button class="view-btn" title="Vista Lista"><i class="fas fa-list"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($favoritos)): ?>
                <div class="empty-state reveal-on-scroll">
                    <div class="empty-icon-wrapper">
                        <i class="far fa-heart empty-icon"></i>
                        <div class="empty-pulse"></div>
                    </div>
                    <h2 class="empty-title">Tu lista de deseos espera</h2>
                    <p class="empty-text">
                        Aún no has agregado productos a tus favoritos. 
                        Explora nuestra colección y guarda las piezas que iluminarán tu vida.
                    </p>
                    <a href="<?= $BASE ?>views/catalogo.php" class="btn-hero">
                        Explorar Catálogo <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="favorites-grid">
                    <?php foreach ($favoritos as $producto): 
                        // Asegurar que tenemos el ID correcto
                        $producto_id = (int)($producto['id'] ?? $producto['producto_id'] ?? 0);
                        if ($producto_id <= 0) continue; // Saltar si no hay ID válido
                        
                        $imagen = !empty($producto['imagen']) ? $producto['imagen'] : ($BASE . 'images/default.png');
                        $descuento = isset($producto['descuento']) ? (int)$producto['descuento'] : 0;
                        $stock = isset($producto['stock']) ? (int)$producto['stock'] : 0;
                        $categoria = !empty($producto['categoria']) ? $producto['categoria'] : 'Sin categoría';
                        $nombre = !empty($producto['nombre']) ? $producto['nombre'] : 'Producto sin nombre';
                        $precio = isset($producto['precio']) ? (float)$producto['precio'] : 0.0;
                        $precio_original = (isset($producto['precio_original']) && $producto['precio_original'] !== null && $producto['precio_original'] > 0) ? (float)$producto['precio_original'] : null;
                    ?>
                        <article class="fav-card reveal-on-scroll">
                            <div class="card-image-wrapper">
                                <div class="fav-image" style="background-image: url('<?= htmlspecialchars($imagen) ?>');"></div>
                                <div class="card-overlay">
                                    <button class="btn-action remove" onclick="toggleFavorito(<?= $producto_id ?>, this, true)" title="Eliminar de favoritos">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn-action cart" onclick="agregarAlCarrito(<?= $producto_id ?>)" <?= $stock <= 0 ? 'disabled' : '' ?> title="Agregar al carrito">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                                <?php if ($descuento > 0): ?>
                                    <span class="badge badge-discount">-<?= $descuento ?>%</span>
                                <?php endif; ?>
                                <?php if ($stock <= 0): ?>
                                    <span class="badge badge-out">Agotado</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fav-content">
                                <div class="fav-meta">
                                    <span class="category"><?= htmlspecialchars($categoria) ?></span>
                                    <?php if ($stock > 0): ?>
                                        <span class="stock-status in-stock"><i class="fas fa-check-circle"></i> Disponible</span>
                                    <?php else: ?>
                                        <span class="stock-status out-stock"><i class="fas fa-times-circle"></i> Agotado</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="fav-title">
                                    <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $producto_id ?>">
                                        <?= htmlspecialchars($nombre) ?>
                                    </a>
                                </h3>
                                
                                <div class="fav-footer">
                                    <div class="price-wrapper">
                                        <span class="price-current">$<?= number_format($precio, 2) ?></span>
                                        <?php if ($precio_original && $precio_original > $precio): ?>
                                            <span class="price-original">$<?= number_format($precio_original, 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= $BASE ?>views/productos-detal.php?id=<?= $producto_id ?>" class="details-link">
                                        Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Animation on scroll
        document.addEventListener('DOMContentLoaded', () => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
        });

        // Toggle Favorite Logic
        async function toggleFavorito(pid, btn, removeCard = false) {
            try {
                const res = await fetch('<?= $BASE ?>api/wishlist/toggle.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ producto_id: pid })
                });

                if (res.status === 401) {
                    alert('Debes iniciar sesión para gestionar favoritos');
                    window.location.href = '<?= $BASE ?>views/login.php';
                    return;
                }

                const data = await res.json();

                if (data.ok) {
                    // Update header badge
                    const badge = document.getElementById('fav-badge');
                    if (badge) {
                        badge.innerText = data.count || 0;
                        badge.style.display = (data.count || 0) > 0 ? 'flex' : 'none';
                    }

                    // Update section count
                    const countBadge = document.querySelector('.count-badge');
                    if (countBadge) {
                        countBadge.innerText = data.count || 0;
                    }

                    if (removeCard) {
                        const card = btn.closest('.fav-card');
                        if (card) {
                            card.style.transition = 'all 0.4s ease';
                            card.style.transform = 'scale(0.9) translateY(20px)';
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.remove();
                                if (document.querySelectorAll('.fav-card').length === 0) {
                                    location.reload();
                                }
                            }, 400);
                        }
                    }
                } else {
                    alert(data.msg || 'Error al actualizar favoritos');
                }
            } catch (e) {
                console.error('Error en toggleFavorito:', e);
                alert('Error al actualizar favoritos. Por favor, intenta de nuevo.');
            }
        }

        // Add to Cart Logic
        async function agregarAlCarrito(id) {
            try {
                const res = await fetch('<?= $BASE ?>api/carrito/add.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ producto_id: id, cantidad: 1 })
                });
                const data = await res.json();
                if (data.ok) {
                    const cartBadge = document.getElementById('cart-badge');
                    if (cartBadge) {
                        const currentCount = parseInt(cartBadge.innerText || '0');
                        cartBadge.innerText = currentCount + 1;
                        cartBadge.style.display = 'flex';
                    }
                    
                    // Visual feedback on the button
                    const btn = document.querySelector(`button[onclick="agregarAlCarrito(${id})"]`);
                    if(btn) {
                        const originalHTML = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-check"></i>';
                        btn.classList.add('success');
                        setTimeout(() => {
                            btn.innerHTML = originalHTML;
                            btn.classList.remove('success');
                        }, 2000);
                    }
                    
                    // Show toast
                    if (typeof showToast === 'function') {
                        showToast('✓ Producto agregado al carrito', 'success');
                    }
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>