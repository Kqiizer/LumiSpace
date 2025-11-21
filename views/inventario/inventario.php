<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Datos de inventario
$inventario = getInventario();
$sucursales = array_unique(array_column($inventario, 'sucursal'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Inventario - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    section.content.wide { max-width: 100%; width: 100%; margin: 0; padding: 0 10px; }
    .page-header { display: flex; justify-content: space-between; align-items: center;
      padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.15); margin-bottom: 18px; }
    .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .btn-add { background: #fff; color: var(--act1); font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; transition: all .25s ease; }
    .btn-add:hover { background: var(--act1); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }

    .alert { margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; font-weight: 600; animation: fadeIn .3s ease; }
    .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    @keyframes fadeIn { from {opacity:0;transform:translateY(-6px);} to{opacity:1;transform:translateY(0);} }

    .filters { margin: 10px 0; display: flex; gap: 10px; flex-wrap: wrap; }
    .filters input, .filters select {
      padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem;
    }

    .table-wrapper { overflow-x: auto; margin-top: 16px; }
    .table.full-width { width: 100%; border-collapse: collapse; }
    .table.full-width th, .table.full-width td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #eee; }
    .table.full-width th { background: var(--card-bg-1); cursor: pointer; }
    .table.full-width th:hover { text-decoration: underline; }

    .badge { padding: 4px 8px; border-radius: 8px; font-size: .85rem; font-weight: 600; }
    .badge.low { background: #ffe0e0; color: #d9534f; }
    .badge.medium { background: #fff3cd; color: #856404; }
    .badge.high { background: #d4edda; color: #155724; }

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
      <!-- Header -->
      <div class="page-header">
        <h2>📦 Inventario</h2>
        <div style="display:flex; gap:10px;">
          <a href="movimiento-agregar.php" class="btn-add">➕ Registrar Movimiento</a>
          <a href="movimientos-listar.php" class="btn-add">📜 Ver Movimientos</a>
        </div>
      </div>

      <!-- Alertas -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <!-- Filtros -->
      <div class="filters">
        <input type="text" id="searchInput" placeholder="Buscar producto o sucursal...">
        <select id="filterSucursal">
          <option value="">-- Todas las sucursales --</option>
          <?php foreach($sucursales as $suc): ?>
            <option value="<?= htmlspecialchars($suc) ?>"><?= htmlspecialchars($suc) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Tabla -->
      <div class="card p-16 mt-16 table-wrapper">
        <table class="table full-width" id="inventarioTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Producto</th>
              <th>Sucursal</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Valor total</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody id="inventarioBody">
            <?php if (!empty($inventario)): ?>
              <?php foreach($inventario as $i): 
                $stock = (int)$i['cantidad'];
                $class = $stock <= 5 ? "low" : ($stock <= 20 ? "medium" : "high");
              ?>
                <tr>
                  <td><?= $i['id'] ?></td>
                  <td><strong><?= htmlspecialchars($i['producto']) ?></strong></td>
                  <td><?= htmlspecialchars($i['sucursal']) ?></td>
                  <td><span class="badge <?= $class ?>"><?= $stock ?></span></td>
                  <td>$<?= number_format($i['precio'], 2) ?></td>
                  <td>$<?= number_format($i['cantidad'] * $i['precio'], 2) ?></td>
                  <td>
                    <a href="inventario-eliminar.php?id=<?= $i['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar registro de inventario?')">🗑️</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" style="text-align:center;">No hay registros en inventario.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <div class="pagination" id="pagination"></div>
      </div>
    </section>
  </main>

<script>
// 🔍 Filtros
function filtrar() {
  const text = document.getElementById("searchInput").value.toLowerCase();
  const suc  = document.getElementById("filterSucursal").value.toLowerCase();
  const rows = document.querySelectorAll("#inventarioBody tr");

  rows.forEach(row => {
    const rowText = row.innerText.toLowerCase();
    const sucursal = row.children[2].innerText.toLowerCase();
    const matchText = rowText.includes(text);
    const matchSuc = suc === "" || sucursal === suc;
    row.style.display = (matchText && matchSuc) ? "" : "none";
  });
}
document.getElementById("searchInput").addEventListener("keyup", filtrar);
document.getElementById("filterSucursal").addEventListener("change", filtrar);

// 📄 Paginación
const rowsPerPage = 10;
const rows = document.querySelectorAll("#inventarioBody tr");
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
