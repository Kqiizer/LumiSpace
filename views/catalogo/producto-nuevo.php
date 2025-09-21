<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Obtener categorías y proveedores
$categorias  = getCategorias(); 
$proveedores = getProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Producto - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 900px;
      margin: 0 auto;
      padding: 24px;
      border-radius: var(--radius);
      background: var(--card-bg-1);
      box-shadow: var(--shadow);
      border: 1px solid var(--card-bd);
    }
    .form-card h2 { margin-bottom: 20px; color: var(--act1); }

    form { 
      display: grid; 
      grid-template-columns: 1fr 1fr; 
      gap: 18px; 
    }
    form label { 
      font-weight: 600; 
      margin-bottom: 6px; 
      display: block; 
      color: var(--text); 
    }
    form input, form textarea, form select {
      width: 100%; 
      padding: 10px 12px;
      border: 1px solid var(--card-bd); 
      border-radius: 8px;
      background: var(--card-bg-2); 
      font-size: .95rem;
      transition: all .2s ease;
    }
    form input:focus, form textarea:focus, form select:focus {
      border-color: var(--act1);
      outline: none;
      box-shadow: 0 0 0 2px rgba(161, 104, 58, .3);
    }
    form textarea { 
      resize: vertical; 
      min-height: 90px; 
      grid-column: span 2; 
    }

    .form-actions { 
      grid-column: span 2; 
      display: flex; 
      justify-content: flex-end; 
      gap: 12px; 
      margin-top: 10px; 
    }

    .btn { 
      padding: 10px 18px; 
      border-radius: 8px; 
      border: none; 
      cursor: pointer; 
      font-weight: 600; 
      transition: all .25s ease; 
      text-decoration: none; 
      display: inline-block; 
      text-align: center; 
    }
    .btn-primary { 
      background: linear-gradient(90deg, var(--act1), var(--act2)); 
      color: #fff; 
    }
    .btn-primary:hover { 
      filter: brightness(1.1); 
      transform: translateY(-2px); 
    }
    .btn-secondary { 
      background: var(--card-bg-2); 
      color: var(--text); 
    }
    .btn-secondary:hover { 
      background: var(--card-bg-1); 
    }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content wide">
      <div class="form-card">
        <h2>➕ Nuevo Producto</h2>

        <form method="POST" action="producto-guardar.php" enctype="multipart/form-data">
          
          <!-- Nombre -->
          <div>
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Foco LED 12W" required>
          </div>

          <!-- Precio -->
          <div>
            <label for="precio">Precio ($)</label>
            <input type="number" step="0.01" min="0" id="precio" name="precio" placeholder="0.00" required>
          </div>

          <!-- Stock -->
          <div>
            <label for="stock">Stock inicial</label>
            <input type="number" min="0" id="stock" name="stock" placeholder="0" required>
          </div>

          <!-- Categoría -->
          <div>
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria_id" required>
              <option value="">-- Seleccionar categoría --</option>
              <?php if (!empty($categorias)): ?>
                <?php foreach($categorias as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="">(No hay categorías registradas)</option>
              <?php endif; ?>
            </select>
          </div>

          <!-- Proveedor -->
          <div>
            <label for="proveedor">Proveedor</label>
            <select id="proveedor" name="proveedor_id">
              <option value="">-- Ninguno --</option>
              <?php if (!empty($proveedores)): ?>
                <?php foreach($proveedores as $pr): ?>
                  <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nombre']) ?></option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="">(No hay proveedores registrados)</option>
              <?php endif; ?>
            </select>
          </div>

          <!-- Imagen -->
          <div style="grid-column: span 2;">
            <label for="imagen">Imagen del producto (opcional)</label>
            <input type="file" id="imagen" name="imagen" accept="image/*">
          </div>

          <!-- Descripción -->
          <div style="grid-column: span 2;">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" placeholder="Ej. Bombilla LED de bajo consumo, 12W, luz cálida."></textarea>
          </div>

          <!-- Acciones -->
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
