<?php
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../includes/header.php';

$slug = $_GET['slug'] ?? '';
$post = getBlogPostBySlug($slug);

if (!$post) {
    header("Location: " . BASE_URL . "views/blog.php");
    exit;
}

// Fetch related posts (simple implementation: get latest 3 excluding current)
// In a real app, you'd filter by category/tag
$allPosts = getBlogPosts(4, 0);
$relatedPosts = [];
foreach ($allPosts as $p) {
    if ($p['id'] != $post['id'] && count($relatedPosts) < 3) {
        $relatedPosts[] = $p;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['titulo']) ?> | LumiSpace Blog</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles/reset.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/styles/blog.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="single-post-page">

    <!-- Reading Progress Bar -->
    <div class="reading-progress-container">
        <div class="reading-progress-bar" id="readingProgress"></div>
    </div>

    <!-- Immersive Hero -->
    <header class="post-hero">
        <div class="post-hero-bg"
            style="background-image: url('<?= !empty($post['imagen_portada']) ? $post['imagen_portada'] : BASE_URL . 'images/default-blog.jpg' ?>');">
        </div>
        <div class="post-hero-overlay"></div>
        <div class="container post-hero-content">
            <div class="post-badges">
                <span class="badge-category">Diseño</span>
                <span class="badge-time"><i class="far fa-clock"></i> 5 min de lectura</span>
            </div>
            <h1 class="post-title-hero"><?= htmlspecialchars($post['titulo']) ?></h1>
            <div class="post-meta-hero">
                <div class="author-info">
                    <img src="<?= BASE_URL ?>images/default-avatar.png" alt="Autor" class="author-avatar">
                    <span>Por <strong><?= htmlspecialchars($post['autor_nombre'] ?? 'LumiSpace') ?></strong></span>
                </div>
                <span class="meta-divider">•</span>
                <span class="post-date"><?= date('d M, Y', strtotime($post['fecha_creacion'])) ?></span>
            </div>
        </div>
    </header>

    <div class="container post-layout">
        <!-- Sticky Social Sidebar -->
        <aside class="social-sidebar">
            <div class="sticky-wrapper">
                <span class="share-text">Compartir</span>
                <a href="#" class="share-btn facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="share-btn twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" class="share-btn pinterest"><i class="fab fa-pinterest-p"></i></a>
                <a href="#" class="share-btn linkedin"><i class="fab fa-linkedin-in"></i></a>
                <div class="share-divider"></div>
                <a href="#comments" class="share-btn comments-trigger"><i class="far fa-comment"></i></a>
            </div>
        </aside>

        <!-- Main Content -->
        <article class="post-content-area">
            <div class="post-body typography-elite">
                <?= $post['contenido'] ?>
            </div>

            <!-- Tags -->
            <div class="post-tags">
                <span>Etiquetas:</span>
                <a href="#">#Iluminación</a>
                <a href="#">#Interiorismo</a>
                <a href="#">#LumiSpace</a>
            </div>

            <!-- Author Bio -->
            <div class="author-bio-card">
                <div class="bio-avatar">
                    <img src="<?= BASE_URL ?>images/default-avatar.png" alt="Autor">
                </div>
                <div class="bio-content">
                    <h3>Sobre el Autor</h3>
                    <p>Experto en diseño de iluminación y tendencias de interiorismo. Apasionado por crear espacios que
                        inspiran y transforman la vida cotidiana a través de la luz.</p>
                    <div class="bio-social">
                        <a href="#">Ver más artículos</a>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="post-navigation">
                <a href="#" class="nav-prev">
                    <span class="nav-label">Anterior</span>
                    <span class="nav-title">Tendencias de Iluminación 2024</span>
                </a>
                <a href="#" class="nav-next">
                    <span class="nav-label">Siguiente</span>
                    <span class="nav-title">Cómo elegir la lámpara perfecta</span>
                </a>
            </div>

            <!-- Comments Section (UI Only) -->
            <div id="comments" class="comments-section">
                <h3 class="section-title">Comentarios (3)</h3>

                <div class="comment-list">
                    <!-- Comment 1 -->
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <img src="https://ui-avatars.com/api/?name=Ana+G&background=random" alt="Ana">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <h4>Ana García</h4>
                                <span class="comment-date">Hace 2 días</span>
                            </div>
                            <p>¡Me encantó este artículo! Los consejos sobre iluminación LED son muy útiles para mi
                                renovación.</p>
                            <a href="#" class="reply-link">Responder</a>
                        </div>
                    </div>

                    <!-- Comment 2 -->
                    <div class="comment-item">
                        <div class="comment-avatar">
                            <img src="https://ui-avatars.com/api/?name=Carlos+M&background=random" alt="Carlos">
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <h4>Carlos Méndez</h4>
                                <span class="comment-date">Hace 1 día</span>
                            </div>
                            <p>Excelente análisis. ¿Tienen recomendaciones para iluminación de oficinas en casa?</p>
                            <a href="#" class="reply-link">Responder</a>
                        </div>
                    </div>
                </div>

                <!-- Comment Form -->
                <div class="comment-form-wrapper">
                    <h3>Deja un comentario</h3>
                    <form class="comment-form">
                        <div class="form-row">
                            <input type="text" placeholder="Nombre" class="form-input">
                            <input type="email" placeholder="Email" class="form-input">
                        </div>
                        <textarea placeholder="Escribe tu comentario aquí..." class="form-textarea"></textarea>
                        <button type="button" class="btn-submit-comment">Publicar Comentario</button>
                    </form>
                </div>
            </div>
        </article>
    </div>

    <!-- Related Posts -->
    <section class="related-posts-section">
        <div class="container">
            <h2 class="section-title-center">También te podría interesar</h2>
            <div class="related-grid">
                <?php foreach ($relatedPosts as $rPost): ?>
                    <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $rPost['slug'] ?>" class="related-card">
                        <div class="related-image"
                            style="background-image: url('<?= !empty($rPost['imagen_portada']) ? $rPost['imagen_portada'] : BASE_URL . 'images/default-blog.jpg' ?>');">
                        </div>
                        <div class="related-content">
                            <span class="related-date"><?= date('M d', strtotime($rPost['fecha_creacion'])) ?></span>
                            <h4><?= htmlspecialchars($rPost['titulo']) ?></h4>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Reading Progress Bar
        window.onscroll = function () {
            let winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            let height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            let scrolled = (winScroll / height) * 100;
            document.getElementById("readingProgress").style.width = scrolled + "%";
        };

        // Parallax Effect for Hero
        window.addEventListener('scroll', function () {
            const scrolled = window.pageYOffset;
            const heroBg = document.querySelector('.post-hero-bg');
            if (heroBg) {
                heroBg.style.transform = 'translateY(' + (scrolled * 0.5) + 'px)';
            }
        });
    </script>

</body>

</html>