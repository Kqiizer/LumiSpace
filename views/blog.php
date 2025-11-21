<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : './';
$blogData = getBlogPostsData();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - LumiSpace</title>
    <link rel="stylesheet" href="<?= $BASE ?>css/blog.css">
</head>
<body class="blog-page">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <section class="blog-hero">
        <div class="container">
            <div>
                <p>Blog LumiSpace</p>
                <h1>Ideas, guías y tendencias</h1>
                <p>Inspiración para iluminar cada espacio con estilo y funcionalidad.</p>
            </div>
            <div class="blog-search">
                <input type="text" id="blogSearchInput" placeholder="Busca por título, categoría o etiqueta...">
                <button type="button" id="blogSearchBtn">Buscar</button>
            </div>
        </div>
    </section>

    <section class="blog-layout">
        <div>
            <div id="postsGrid" class="posts-grid"></div>
            <div id="blogPagination" class="pagination"></div>
        </div>

        <aside class="blog-sidebar">
            <div class="sidebar-card">
                <h3>Categorías</h3>
                <div id="blogCategoryList" class="sidebar-list"></div>
            </div>

            <div class="sidebar-card">
                <h3>Etiquetas</h3>
                <div id="blogTagList" class="sidebar-list"></div>
            </div>

            <div class="sidebar-card">
                <h3>Artículos recientes</h3>
                <div id="recommendedList" class="recommended-list"></div>
            </div>
        </aside>
    </section>

    <script>
        window.BASE_URL = "<?= $BASE ?>";
        window.BLOG_POSTS = <?= json_encode($blogData['posts'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.BLOG_CATEGORIES = <?= json_encode($blogData['categories'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        window.BLOG_TAGS = <?= json_encode($blogData['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="<?= $BASE ?>js/blog.js"></script>
</body>
</html>

