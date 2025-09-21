<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Obtener productos y categorías
$productos   = getProductos();
$categorias  = getCategorias();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Productos - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    section.content.wide { max-width: 100% !important; width: 100% !important; margin: 0; padding: 0 10px; }
    .prod-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid var(--card-bd); box-shadow: var(--shadow); }

    /* Header */
    .page-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2)); color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.15); margin-bottom: 18px; }
    .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .btn-add { background: #fff; color: var(--act1); font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; transition: all .25s ease; }
    .btn-add:hover { background: var(--act1); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }

    /* Alertas */
    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; font-weight: 600; animation: fadeIn .3s ease; }
    .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    @keyframes fadeIn { from {opacity:0;transform:translateY(-6px);} to{opacity:1;transform:translateY(0);} }

    /* Buscador + filtro */
    .filters { margin-top: 10px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .filters input, .filters select {
      padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem;
    }

    /* Tabla */
    .table-wrapper { overflow-x: auto; margin-top: 16px; }
    .table.full-width { width: 100%; border-collapse: collapse; }
    .table.full-width th, .table.full-width td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #eee; }
    .table.full-width th { background: var(--card-bg-1); cursor: pointer; }
    .table.full-width th:hover { text-decoration: underline; }

    /* Stock */
    .badge { padding: 4px 8px; border-radius: 8px; font-size: .85rem; font-weight: 600; }
    .badge.low { background: #ffe0e0; color: #d9534f; }
    .badge.medium { background: #fff3cd; color: #856404; }
    .badge.high { background: #d4edda; color: #155724; }

    /* Paginación */
    .pagination { margin-top: 16px; display: flex; gap: 8px; justify-content: center; }
    .pagination button { padding: 6px 12px; border: 1px solid #ccc; background: #fff; cursor: pointer; border-radius: 6px; }
    .pagination button.active { background: #007bff; color: #fff; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content wide">
      <!-- HEADER -->
      <div class="page-header">
        <h2>📦 Productos</h2>
        <a href="producto-nuevo.php" class="btn-add">➕ Nuevo Producto</a>
      </div>

      <!-- Alertas dinámicas -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <!-- Buscador + filtro -->
      <div class="filters">
        <input type="text" id="searchInput" placeholder="Buscar por nombre, categoría o proveedor...">
        <select id="filterCategoria">
          <option value="">-- Todas las categorías --</option>
          <?php foreach($categorias as $c): ?>
            <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tabla -->
      <div class="card p-16 mt-16 table-wrapper">
        <table class="table full-width table-sortable" id="productosTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Imagen</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Proveedor</th>
              <th>Precio</th>
              <th>Stock</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="productosBody">
            <?php if (!empty($productos)): ?>
              <?php foreach($productos as $p): ?>
                <tr>
                  <td><?= $p['id'] ?></td>
                  <td>
                    <?php if (!empty($p['imagen'])): ?>
                      <img src="<?= htmlspecialchars($p['imagen']) ?>" alt="Imagen de <?= htmlspecialchars($p['nombre']) ?>" class="prod-img">
                    <?php else: ?>
                      <span style="color:#aaa;font-size:.9rem;">Sin imagen</span>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                  <td><?= htmlspecialchars($p['categoria'] ?? 'Sin categoría') ?></td>
                  <td><?= htmlspecialchars($p['proveedor'] ?? 'N/A') ?></td>
                  <td>$<?= number_format($p['precio'],2) ?></td>
                  <td>
                    <?php
                      $stock = (int)$p['stock'];
                      $class = $stock <= 5 ? "low" : ($stock <= 20 ? "medium" : "high");
                    ?>
                    <span class="badge <?= $class ?>"><?= $stock ?></span>
                  </td>
                  <td>
                    <a href="producto-editar.php?id=<?= $p['id'] ?>" class="btn btn-sm">✏️</a>
                    <a href="producto-eliminar.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar producto?')">🗑️</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" style="text-align:center;">No hay productos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="pagination" id="pagination"></div>
      </div>
    </section>
  </main>

<script>
// 🔍 Buscador + filtro categoría
function filtrar() {
  const filterText = document.getElementById("searchInput").value.toLowerCase();
  const filterCat  = document.getElementById("filterCategoria").value.toLowerCase();
  const rows = document.querySelectorAll("#productosBody tr");

  rows.forEach(row => {
    const text = row.innerText.toLowerCase();
    const cat  = row.children[3].innerText.toLowerCase();
    const matchText = text.includes(filterText);
    const matchCat  = filterCat === "" || cat === filterCat;
    row.style.display = (matchText && matchCat) ? "" : "none";
  });
}

document.getElementById("searchInput").addEventListener("keyup", filtrar);
document.getElementById("filterCategoria").addEventListener("change", filtrar);

// 📄 Paginación
const rowsPerPage = 10;
const rows = document.querySelectorAll("#productosBody tr");
const totalPages = Math.ceil(rows.length / rowsPerPage);
const pagination = document.getElementById("pagination");

function showPage(page) {
  rows.forEach((row, i) => {
    row.style.display = (i >= (page-1)*rowsPerPage && i < page*rowsPerPage) ? "" : "none";
  });
  document.querySelectorAll(".pagination button").forEach((btn,i) => {
    btn.classList.toggle("active", i+1 === page);
  });
}

if (totalPages > 1) {
  for (let i=1; i<=totalPages; i++) {
    const btn = document.createElement("button");
    btn.innerText = i;
    btn.addEventListener("click", ()=> showPage(i));
    pagination.appendChild(btn);
  }
  showPage(1);
}
</script>
</body>
</html>
