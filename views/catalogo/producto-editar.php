<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$producto = null;

if ($id > 0) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM productos WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
}

if (!$producto) {
    header("Location: productos.php?error=notfound");
    exit();
}

$categorias  = getCategorias();
$proveedores = getProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Producto - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card { max-width: 700px; margin: 0 auto; padding: 24px; border-radius: var(--radius);
      background: var(--card-bg-1); box-shadow: var(--shadow); border: 1px solid var(--card-bd); }
    .form-card h2 { margin-bottom: 20px; color: var(--act1); }
    form { display: flex; flex-direction: column; gap: 14px; }
    label { font-weight: 600; color: var(--text); }
    input, textarea, select {
      width: 100%; padding: 10px 12px; border: 1px solid var(--card-bd); border-radius: 8px;
      background: var(--card-bg-2); font-size: .95rem;
    }
    textarea { resize: vertical; min-height: 90px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
    .btn { padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; border: none; }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
    .prod-img { max-width: 120px; border-radius: 8px; display: block; margin-bottom: 10px; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <div class="form-card">
        <h2>✏️ Editar Producto</h2>
        <form method="POST" action="producto-actualizar.php" enctype="multipart/form-data">
          <input type="hidden" name="id" value="<?= $producto['id'] ?>">

          <label>Nombre</label>
          <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>

          <label>Descripción</label>
          <textarea name="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></textarea>

          <label>Precio</label>
          <input type="number" step="0.01" name="precio" value="<?= $producto['precio'] ?>" required>

          <label>Stock</label>
          <input type="number" name="stock" value="<?= $producto['stock'] ?>" required>

          <label>Categoría</label>
          <select name="categoria_id" required>
            <?php foreach($categorias as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $c['id']==$producto['categoria_id']?'selected':'' ?>>
                <?= htmlspecialchars($c['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Proveedor</label>
          <select name="proveedor_id">
            <option value="">-- Ninguno --</option>
            <?php foreach($proveedores as $pr): ?>
              <option value="<?= $pr['id'] ?>" <?= $pr['id']==($producto['proveedor_id'] ?? '')?'selected':'' ?>>
                <?= htmlspecialchars($pr['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Imagen actual</label>
          <?php if (!empty($producto['imagen'])): ?>
            <img src="<?= BASE_URL ?>images/productos/<?= htmlspecialchars($producto['imagen']) ?>" 
                 alt="Imagen actual" class="prod-img">
          <?php else: ?>
            <p style="color:#aaa;">Sin imagen</p>
          <?php endif; ?>
          <input type="file" name="imagen" accept="image/*">

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Actualizar</button>
            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
