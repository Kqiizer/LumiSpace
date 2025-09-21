<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$movs = getMovimientos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Movimientos - Inventario</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .page-header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.15);
      margin-bottom: 18px;
    }
    .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .btn-add { background: #fff; color: var(--act1); font-weight: 600; padding: 8px 16px; border-radius: 8px; text-decoration: none; transition: all .25s ease; }
    .btn-add:hover { background: var(--act1); color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }

    /* Tabla centrada */
    .table-wrapper.centered {
      display: flex;
      justify-content: center;
      margin-top: 16px;
      overflow-x: auto;
    }
    .table-wrapper.centered table {
      width: 95%;
      max-width: 1100px;
      border-collapse: collapse;
      background: var(--card-bg-1);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow);
    }
    .table.full-width th, .table.full-width td {
      padding: 12px 14px; text-align: left; border-bottom: 1px solid #eee;
    }
    .table.full-width th {
      background: var(--card-bg-2);
    }

    /* Badges tipo movimiento */
    .badge { padding: 4px 10px; border-radius: 8px; font-size: .85rem; font-weight: 600; }
    .badge.entrada { background: #d4edda; color: #155724; }
    .badge.salida  { background: #f8d7da; color: #721c24; }
    .badge.ajuste  { background: #fff3cd; color: #856404; }

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
        <h2>📜 Movimientos de Inventario</h2>
        <a href="movimiento-agregar.php" class="btn-add">➕ Nuevo Movimiento</a>
      </div>

      <!-- Tabla -->
      <div class="table-wrapper centered">
        <table class="table full-width" id="movimientosTable">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cantidad</th>
              <th>Usuario</th>
              <th>Motivo</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody id="movimientosBody">
            <?php if (!empty($movs)): ?>
              <?php foreach($movs as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['producto']) ?></td>
                  <td><span class="badge <?= $m['tipo'] ?>"><?= ucfirst($m['tipo']) ?></span></td>
                  <td><?= $m['cantidad'] ?></td>
                  <td><?= htmlspecialchars($m['usuario']) ?></td>
                  <td><?= htmlspecialchars($m['motivo']) ?></td>
                  <td><?= $m['creado_en'] ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No hay movimientos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination" id="pagination"></div>
    </section>
  </main>

<script>
// 📄 Paginación JS
const rowsPerPage = 10;
const rows = document.querySelectorAll("#movimientosBody tr");
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
