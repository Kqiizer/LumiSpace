<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/header.php';

// Paginación
$page = isset($_GET['page']) ? (int) $page : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

$posts = getBlogPosts($limit, $offset);

// Featured Post (For demo purposes, take the first one or a specific one)
$featuredPost = !empty($posts) ? $posts[0] : null;
// Remove featured from main list if you want, or keep it. Let's keep it for now but maybe skip it in the grid loop if we wanted.
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Blog | LumiSpace</title>
    <link rel="stylesheet" href="css/styles/reset.css">
    <link rel="stylesheet" href="css/styles/blog.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Hero Section -->
    <section class="blog-hero">
        <div class="hero-bg"
            style="background-image: url('<?= !empty($featuredPost['imagen_portada']) ? $featuredPost['imagen_portada'] : BASE_URL . 'images/default-blog.jpg' ?>');">
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <span class="hero-tag">Destacado</span>
            <h1 class="hero-title">
                <?= $featuredPost ? htmlspecialchars($featuredPost['titulo']) : 'Bienvenido al Blog' ?>
            </h1>
            <p class="hero-excerpt">
                <?= $featuredPost ? substr(strip_tags($featuredPost['contenido']), 0, 150) . '...' : 'Explora las últimas tendencias en iluminación y diseño.' ?>
            </p>
            <?php if ($featuredPost): ?>
                <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $featuredPost['slug'] ?>" class="btn-hero">
                    Leer Artículo <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="container main-layout">
        <!-- Main Content -->
        <div class="content-area">
            <div class="section-header">
                <h2>Últimas Publicaciones</h2>
                <div class="line-accent"></div>
            </div>

            <div class="blog-grid">
                <?php if (empty($posts)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No hay publicaciones disponibles por el momento.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <article class="blog-card reveal-on-scroll">
                            <div class="card-image-wrapper">
                                <div class="blog-image"
                                    style="background-image: url('<?= !empty($post['imagen_portada']) ? $post['imagen_portada'] : BASE_URL . 'images/default-blog.jpg' ?>');">
                                </div>
                                <div class="card-overlay">
                                    <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $post['slug'] ?>" class="btn-icon">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="blog-content">
                                <div class="blog-meta">
                                    <span><i class="far fa-calendar"></i>
                                        <?= date('M d, Y', strtotime($post['fecha_creacion'])) ?></span>
                                    <span><i class="far fa-user"></i>
                                        <?= htmlspecialchars($post['autor_nombre'] ?? 'LumiSpace') ?></span>
                                </div>
                                <h3 class="blog-title">
                                    <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $post['slug'] ?>">
                                        <?= htmlspecialchars($post['titulo']) ?>
                                    </a>
                                </h3>
                                <div class="blog-excerpt">
                                    <?= substr(strip_tags($post['contenido']), 0, 100) ?>...
                                </div>
                                <div class="blog-footer">
                                    <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $post['slug'] ?>"
                                        class="read-more-link">
                                        Leer más
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination-wrapper">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>" class="page-btn prev"><i class="fas fa-chevron-left"></i> Anterior</a>
                <?php endif; ?>
                <?php if (count($posts) == $limit): ?>
                    <a href="?page=<?= $page + 1 ?>" class="page-btn next">Siguiente <i
                            class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <!-- Search Widget -->
            <div class="widget search-widget">
                <form action="" method="GET" class="search-form">
                    <input type="text" placeholder="Buscar artículos..." name="q">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Categories Widget -->
            <div class="widget categories-widget">
                <h3 class="widget-title">Categorías</h3>
                <ul class="category-list">
                    <li><a href="#">Tendencias <span>(3)</span></a></li>
                    <li><a href="#">Iluminación LED <span>(5)</span></a></li>
                    <li><a href="#">Diseño Interior <span>(2)</span></a></li>
                    <li><a href="#">Tecnología Smart <span>(4)</span></a></li>
                </ul>
            </div>

            <!-- Newsletter Widget -->
            <div class="widget newsletter-widget">
                <h3 class="widget-title">Newsletter</h3>
                <p>Suscríbete para recibir las últimas novedades y ofertas exclusivas.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Tu correo electrónico">
                    <button type="button">Suscribirse</button>
                </form>
            </div>

            <!-- Tags Widget -->
            <div class="widget tags-widget">
                <h3 class="widget-title">Etiquetas Populares</h3>
                <div class="tag-cloud">
                    <a href="#">Minimalismo</a>
                    <a href="#">Vintage</a>
                    <a href="#">Industrial</a>
                    <a href="#">Cocina</a>
                    <a href="#">Baño</a>
                    <a href="#">Exterior</a>
                </div>
            </div>
        </aside>
    </div>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Simple reveal animation on scroll
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
    </script>
</body>

</html>