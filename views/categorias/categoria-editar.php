<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$categoria = null;

if ($id > 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $categoria = $res->fetch_assoc();
    $stmt->close();
}

if (!$categoria) {
    header("Location: categorias.php?error=" . urlencode("Categoría no encontrada"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Categoría - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 700px;
      margin: 0 auto;
      padding: 24px;
      border-radius: var(--radius);
      background: var(--card-bg-1);
      box-shadow: var(--shadow);
      border: 1px solid var(--card-bd);
    }
    .form-card h2 {
      margin-bottom: 20px;
      color: var(--act1);
      font-size: 1.4rem;
      font-weight: 700;
    }
    form { display: grid; gap: 18px; }
    label { font-weight: 600; color: var(--text); margin-bottom: 6px; display: block; }
    input, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--card-bd);
      border-radius: 8px;
      background: var(--card-bg-2);
      font-size: .95rem;
    }
    textarea { resize: vertical; min-height: 100px; }
    .preview-img { margin-top: 6px; display: flex; align-items: center; gap: 12px; }
    .preview-img img {
      width: 80px; border-radius: 6px;
      border: 1px solid var(--card-bd); box-shadow: var(--shadow);
    }
    .form-actions {
      display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;
    }
    .btn { padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer;
      font-weight: 600; text-decoration: none; text-align: center; transition: all .25s ease; }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
    
    <section class="content wide">
      <div class="form-card">
        <h2>✏️ Editar Categoría</h2>
        <form method="POST" action="categoria-guardar.php" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= (int)$categoria['id'] ?>">

          <div>
            <label for="nombre">Nombre <span style="color:red">*</span></label>
            <input type="text" id="nombre" name="nombre" 
              value="<?= htmlspecialchars($categoria['nombre'] ?? '') ?>" required>
          </div>
          
          <div>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($categoria['descripcion'] ?? '') ?></textarea>
          </div>
          
          <div>
            <label for="imagen">Imagen (opcional)</label>
            <?php if (!empty($categoria['imagen'])): ?>
              <div class="preview-img">
                <img src="../../<?= htmlspecialchars($categoria['imagen']) ?>" alt="Imagen actual">
                <small>Se reemplazará si subes una nueva</small>
              </div>
            <?php endif; ?>
            <input type="file" id="imagen" name="imagen" accept="image/*">
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar cambios</button>
            <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
