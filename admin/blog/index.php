<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../../config/functions.php';

// 🚨 Solo Admin (Basic check, improve as needed)
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    // header("Location: ../../views/login.php?error=unauthorized");
    // exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if (deleteBlogPost($id)) {
        echo "<script>alert('Post eliminado correctamente'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar el post');</script>";
    }
}

// Fetch all posts
$conn = getDBConnection();
$sql = "SELECT b.*, u.nombre as autor_nombre FROM blog_posts b LEFT JOIN usuarios u ON b.autor_id = u.id ORDER BY b.fecha_creacion DESC";
$res = $conn->query($sql);
$posts = $res->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Blog - LumiSpace</title>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    <main class="main">
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content">

            <div class="container-fluid mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Gestión de Blog</h2>
                    <a href="crear.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Post</a>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Imagen</th>
                                        <th>Título</th>
                                        <th>Autor</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><?= $post['id'] ?></td>
                                            <td>
                                                <?php if (!empty($post['imagen_portada'])): ?>
                                                    <img src="<?= $post['imagen_portada'] ?>" alt="Cover"
                                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                <?php else: ?>
                                                    <span class="text-muted"><i class="fas fa-image"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>views/blog-post.php?slug=<?= $post['slug'] ?>"
                                                    target="_blank" class="text-decoration-none fw-bold">
                                                    <?= htmlspecialchars($post['titulo']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($post['autor_nombre'] ?? 'Desconocido') ?></td>
                                            <td>
                                                <?php if ($post['estado'] === 'publicado'): ?>
                                                    <span class="badge bg-success">Publicado</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Borrador</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($post['fecha_creacion'])) ?></td>
                                            <td>
                                                <a href="crear.php?id=<?= $post['id'] ?>"
                                                    class="btn btn-sm btn-warning me-1" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?delete=<?= $post['id'] ?>" class="btn btn-sm btn-danger"
                                                    title="Eliminar"
                                                    onclick="return confirm('¿Estás seguro de eliminar este post?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main>
</body>

</html>