<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/header.php';

$uid = $_SESSION['usuario_id'] ?? 0;
$favoritos = getFavoritos($uid);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Favoritos | LumiSpace</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles/reset.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles/favoritos.css">
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
                    <a href="<?= BASE_URL ?>views/catalogo.php" class="btn-hero">
                        Explorar Catálogo <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="favorites-grid">
                    <?php foreach ($favoritos as $producto): ?>
                        <article class="fav-card reveal-on-scroll">
                            <div class="card-image-wrapper">
                                <div class="fav-image" style="background-image: url('<?= $producto['imagen'] ?>');"></div>
                                <div class="card-overlay">
                                    <button class="btn-action remove" onclick="toggleFavorito(<?= $producto['id'] ?>, this, true)" title="Eliminar de favoritos">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn-action cart" onclick="agregarAlCarrito(<?= $producto['id'] ?>)" <?= $producto['stock'] <= 0 ? 'disabled' : '' ?> title="Agregar al carrito">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                                <?php if ($producto['descuento'] > 0): ?>
                                    <span class="badge badge-discount">-<?= $producto['descuento'] ?>%</span>
                                <?php endif; ?>
                                <?php if ($producto['stock'] <= 0): ?>
                                    <span class="badge badge-out">Agotado</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="fav-content">
                                <div class="fav-meta">
                                    <span class="category"><?= htmlspecialchars($producto['categoria']) ?></span>
                                    <?php if ($producto['stock'] > 0): ?>
                                        <span class="stock-status in-stock"><i class="fas fa-check-circle"></i> Disponible</span>
                                    <?php else: ?>
                                        <span class="stock-status out-stock"><i class="fas fa-times-circle"></i> Agotado</span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="fav-title">
                                    <a href="<?= BASE_URL ?>views/productos-detal.php?id=<?= $producto['id'] ?>">
                                        <?= htmlspecialchars($producto['nombre']) ?>
                                    </a>
                                </h3>
                                
                                <div class="fav-footer">
                                    <div class="price-wrapper">
                                        <span class="price-current">$<?= number_format($producto['precio'], 2) ?></span>
                                        <?php if ($producto['precio_original']): ?>
                                            <span class="price-original">$<?= number_format($producto['precio_original'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= BASE_URL ?>views/productos-detal.php?id=<?= $producto['id'] ?>" class="details-link">
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
                const formData = new FormData();
                formData.append('producto_id', pid);

                const res = await fetch('<?= BASE_URL ?>includes/favoritos-toggle.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.ok) {
                    // Update header badge
                    const badge = document.getElementById('fav-badge');
                    if (badge) {
                        badge.innerText = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    }

                    // Update section count
                    const countBadge = document.querySelector('.count-badge');
                    if (countBadge) {
                        countBadge.innerText = data.count;
                    }

                    if (removeCard) {
                        const card = btn.closest('.fav-card');
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
            } catch (e) {
                console.error(e);
            }
        }

        // Add to Cart Logic
        async function agregarAlCarrito(id) {
            try {
                const res = await fetch('<?= BASE_URL ?>api/carrito/add.php', {
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