<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
require_once __DIR__ . '/../../config/functions.php';

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    // header("Location: ../../views/login.php?error=unauthorized");
    // exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$post = null;
$titulo = '';
$contenido = '';
$imagen_portada = '';
$estado = 'borrador';
$isEditing = false;

if ($id > 0) {
    // Fetch existing post
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $post = $res->fetch_assoc();

    if ($post) {
        $isEditing = true;
        $titulo = $post['titulo'];
        $contenido = $post['contenido'];
        $imagen_portada = $post['imagen_portada'];
        $estado = $post['estado'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = $_POST['titulo'] ?? '';
    $contenido = $_POST['contenido'] ?? '';
    $imagen_portada = $_POST['imagen_portada'] ?? '';
    $estado = $_POST['estado'] ?? 'borrador';

    // Basic validation
    if (empty($titulo)) {
        $error = "El título es obligatorio.";
    } else {
        $data = [
            'titulo' => $titulo,
            'contenido' => $contenido,
            'imagen_portada' => $imagen_portada,
            'estado' => $estado,
            'autor_id' => $_SESSION['usuario_id'] ?? 1 // Fallback to 1 if session not set (should be set in admin)
        ];

        if ($isEditing) {
            if (updateBlogPost($id, $data)) {
                echo "<script>alert('Post actualizado correctamente'); window.location.href='index.php';</script>";
            } else {
                $error = "Error al actualizar el post.";
            }
        } else {
            if (createBlogPost($data)) {
                echo "<script>alert('Post creado correctamente'); window.location.href='index.php';</script>";
            } else {
                $error = "Error al crear el post.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditing ? 'Editar Post' : 'Nuevo Post' ?> - LumiSpace</title>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    <main class="main">
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content">



            <div class="container mt-4 mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><?= $isEditing ? 'Editar Post' : 'Nuevo Post' ?></h2>
                    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="titulo" name="titulo"
                                    value="<?= htmlspecialchars($titulo) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="imagen_portada" class="form-label">URL Imagen de Portada</label>
                                <input type="text" class="form-control" id="imagen_portada" name="imagen_portada"
                                    value="<?= htmlspecialchars($imagen_portada) ?>"
                                    placeholder="https://ejemplo.com/imagen.jpg">
                                <div class="form-text">Puedes usar una URL externa o una ruta relativa.</div>
                            </div>

                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borrador
                                    </option>
                                    <option value="publicado" <?= $estado === 'publicado' ? 'selected' : '' ?>>Publicado
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="contenido" class="form-label">Contenido</label>
                                <textarea class="form-control" id="contenido" name="contenido"
                                    rows="10"><?= htmlspecialchars($contenido) ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> <?= $isEditing ? 'Actualizar Post' : 'Guardar Post' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TinyMCE -->
            <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
            <script>
                tinymce.init({
                    selector: '#contenido',
                    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
                });
            </script>

        </section>
    </main>
</body>

</html>