<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// 🔹 Obtener movimientos desde funciones
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
      display:flex; justify-content:space-between; align-items:center;
      padding:14px 20px; border-radius:12px;
      background:linear-gradient(135deg,var(--act1),var(--act2));
      color:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15);
      margin-bottom:18px;
    }
    .btn-add { background:#fff; color:var(--act1); font-weight:600; padding:8px 16px; border-radius:8px; text-decoration:none; transition:.25s ease; }
    .btn-add:hover { background:var(--act1); color:#fff; transform:translateY(-2px); }

    .table-wrapper.centered { display:flex; justify-content:center; margin-top:16px; overflow-x:auto; }
    .table-wrapper.centered table { width:95%; max-width:1100px; border-collapse:collapse; background:var(--card-bg-1); border-radius:12px; box-shadow:var(--shadow); }
    .table.full-width th, .table.full-width td { padding:12px 14px; border-bottom:1px solid #eee; text-align:left; }
    .table.full-width th { background:var(--card-bg-2); }

    .badge { padding:4px 10px; border-radius:8px; font-size:.85rem; font-weight:600; }
    .badge.entrada { background:#d4edda; color:#155724; }
    .badge.salida  { background:#f8d7da; color:#721c24; }
    .badge.ajuste  { background:#fff3cd; color:#856404; }

    .btn-sm { padding:6px 10px; border-radius:6px; font-size:.85rem; cursor:pointer; border:none; }
    .btn-delete { background:#dc3545; color:#fff; }
    .btn-sm:hover { opacity:0.85; }

    .pagination { margin-top:16px; display:flex; gap:6px; justify-content:center; flex-wrap:wrap; }
    .pagination button { padding:6px 12px; border:1px solid #ccc; background:#fff; cursor:pointer; border-radius:6px; }
    .pagination button.active { background:#007bff; color:#fff; }
    .pagination button:disabled { background:#f1f1f1; color:#999; cursor:not-allowed; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content wide">
      <div class="page-header">
        <h2>📜 Movimientos de Inventario</h2>
        <div style="display:flex; gap:10px;">
          <a href="movimiento-agregar.php" class="btn-add">➕ Nuevo Movimiento</a>
          <a href="../inventario/inventario-listar.php" class="btn-add">📦 Ver Inventario</a>
        </div>
      </div>

      <!-- Tabla -->
      <div class="table-wrapper centered">
        <table class="table full-width" id="movimientosTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cantidad</th>
              <th>Usuario</th>
              <th>Motivo</th>
              <th>Fecha</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody id="movimientosBody">
            <?php if (!empty($movs)): ?>
              <?php foreach($movs as $m): ?>
                <tr data-id="<?= $m['id'] ?>">
                  <td><?= $m['id'] ?></td>
                  <td><?= htmlspecialchars($m['producto']) ?></td>
                  <td><span class="badge <?= $m['tipo'] ?>"><?= ucfirst($m['tipo']) ?></span></td>
                  <td><?= number_format((int)$m['cantidad']) ?></td>
                  <td><?= htmlspecialchars($m['usuario']) ?></td>
                  <td><?= htmlspecialchars($m['motivo']) ?></td>
                  <td><?= $m['creado_en'] ? date("d/m/Y H:i", strtotime($m['creado_en'])) : "-" ?></td>
                  <td><button class="btn-sm btn-delete" onclick="eliminarMovimiento(<?= $m['id'] ?>, this)">🗑️ Eliminar</button></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="8" style="text-align:center;">No hay movimientos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="pagination" id="pagination"></div>
    </section>
  </main>

<script>
let rowsPerPage = 10;
let rows = document.querySelectorAll("#movimientosBody tr");
let pagination = document.getElementById("pagination");
let currentPage = 1;

// 👉 Eliminar con AJAX
function eliminarMovimiento(id, btn) {
  if (!confirm("¿Eliminar este movimiento?")) return;

  fetch("movimiento-eliminar.php?id=" + id, { method: "GET" })
    .then(r => r.text())
    .then(resp => {
      if (resp.includes("ok") || resp.trim() === "") {
        btn.closest("tr").remove();
        rows = document.querySelectorAll("#movimientosBody tr");
        updatePagination();
      } else {
        alert("❌ Error al eliminar: " + resp);
      }
    })
    .catch(err => {
      console.error("Error AJAX:", err);
      alert("⚠️ No se pudo eliminar.");
    });
}

// 👉 Mostrar página
function showPage(page) {
  currentPage = page;
  let totalRows = rows.length;
  rows.forEach((row, i) => {
    row.style.display = (i >= (page-1)*rowsPerPage && i < page*rowsPerPage) ? "" : "none";
  });

  // actualizar botones activos
  document.querySelectorAll(".pagination button.page-num").forEach((btn,i) => {
    btn.classList.toggle("active", i+1 === page);
  });

  // habilitar/deshabilitar flechas
  document.getElementById("prevBtn").disabled = (page === 1);
  document.getElementById("nextBtn").disabled = (page === Math.ceil(totalRows / rowsPerPage));
}

// 👉 Crear paginación
function updatePagination() {
  pagination.innerHTML = "";
  let totalRows = rows.length;
  let totalPages = Math.ceil(totalRows / rowsPerPage);

  if (totalPages > 1) {
    const prev = document.createElement("button");
    prev.id = "prevBtn";
    prev.innerText = "⏮ Anterior";
    prev.addEventListener("click", ()=> showPage(currentPage-1));
    pagination.appendChild(prev);

    for (let i=1; i<=totalPages; i++) {
      const btn = document.createElement("button");
      btn.innerText = i;
      btn.classList.add("page-num");
      if (i === currentPage) btn.classList.add("active");
      btn.addEventListener("click", ()=> showPage(i));
      pagination.appendChild(btn);
    }

    const next = document.createElement("button");
    next.id = "nextBtn";
    next.innerText = "Siguiente ⏭";
    next.addEventListener("click", ()=> showPage(currentPage+1));
    pagination.appendChild(next);
  }

  showPage(currentPage > totalPages ? 1 : currentPage);
}

// Inicializar
updatePagination();
</script>
</body>
</html>
