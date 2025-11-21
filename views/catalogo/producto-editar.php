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

$conn = getDBConnection();

// 🔹 Obtenemos stock inicial
$stockInicial = (int)($producto['stock_inicial'] ?? 0);

// 🔹 Calculamos stock real (entradas - salidas)
$sqlMov = "
    SELECT 
      SUM(CASE WHEN tipo='entrada' THEN cantidad ELSE 0 END) AS entradas,
      SUM(CASE WHEN tipo='salida'  THEN cantidad ELSE 0 END) AS salidas
    FROM inventario
    WHERE producto_id = ?
";
$stmt = $conn->prepare($sqlMov);
$stmt->bind_param("i", $producto['id']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$entradas = (int)($res['entradas'] ?? 0);
$salidas  = (int)($res['salidas'] ?? 0);

// 🔹 Stock real = inicial + entradas – salidas
$stockReal = $stockInicial + $entradas - $salidas;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Producto - LumiSpace</title>
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
    .form-card h2 { margin-bottom: 20px; color: var(--act1); }
    form { display: flex; flex-direction: column; gap: 14px; }
    label { font-weight: 600; color: var(--text); }
    input, textarea, select {
      width: 100%; padding: 10px 12px;
      border: 1px solid var(--card-bd); border-radius: 8px;
      background: var(--card-bg-2); font-size: .95rem;
    }
    textarea { resize: vertical; min-height: 90px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
    .btn { padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; border: none; }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
    .prod-img { max-width: 150px; border-radius: 8px; display: block; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);}
    #preview { max-width: 150px; max-height: 150px; border-radius: 8px; border: 1px solid #ccc; margin-top: 10px; display: none; object-fit: cover; }
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
          <input type="number" step="0.01" min="0.01" name="precio" value="<?= $producto['precio'] ?>" required>

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

          <label>Stock Inicial</label>
          <input type="number" value="<?= $stockInicial ?>" readonly style="background:#f3f3f3; cursor:not-allowed;">

          <label>Stock Actual (solo lectura)</label>
          <input type="number" value="<?= $stockReal ?>" readonly style="background:#f3f3f3; cursor:not-allowed;">
          <small style="color:#777;">El stock actual se calcula automáticamente con base en inventario.</small>

          <label>Imagen actual</label>
          <?php if (!empty($producto['imagen'])): ?>
            <img src="<?= BASE_URL ?>images/productos/<?= htmlspecialchars($producto['imagen']) ?>" 
                 alt="Imagen actual" class="prod-img">
          <?php else: ?>
            <p style="color:#aaa;">Sin imagen</p>
          <?php endif; ?>

          <label>Nueva imagen (opcional)</label>
          <input type="file" name="imagen" accept="image/*" onchange="previewImage(event)">
          <img id="preview" alt="Vista previa de nueva imagen">

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Actualizar</button>
            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script>
    // 📌 Previsualización de imagen nueva
    function previewImage(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
          const img = document.getElementById('preview');
          img.src = ev.target.result;
          img.style.display = 'block';
        }
        reader.readAsDataURL(file);
      }
    }
  </script>
</body>
</html>
