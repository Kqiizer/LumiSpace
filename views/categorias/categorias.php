<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
  header("Location: ../login.php?error=unauthorized");
  exit();
}

// 🔹 Obtener categorías
$categorias = getCategorias() ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .page-header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff; margin-bottom: 20px;
    }
    .btn-add {
      background: #fff; color: var(--act1);
      font-weight: 600; padding: 8px 16px;
      border-radius: 8px; text-decoration: none;
      transition: all .25s ease;
    }
    .btn-add:hover { background: var(--act1); color:#fff; }

    .alert {
      margin-bottom: 18px; padding: 12px 16px;
      border-radius: 8px; font-weight: 600;
    }
    .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    .search-box { margin-bottom: 16px; }
    .search-box input {
      width: 100%; padding: 10px 14px;
      border: 1px solid #ccc; border-radius: 8px;
      font-size: .95rem;
    }

    .table-wrapper { overflow-x: auto; }
    .table { width:100%; border-collapse: collapse; }
    .table th, .table td {
      padding: 12px 14px;
      border-bottom: 1px solid #eee;
      text-align: left; vertical-align: middle;
    }
    .table th {
      background: var(--card-bg-1); 
      color: var(--text); font-weight: 600;
    }
    .table tbody tr:hover { background: rgba(0,0,0,0.03); }

    .btn-sm {
      padding: 6px 10px; border-radius: 6px;
      font-size: .85rem; text-decoration: none;
      margin-right: 6px; display:inline-block;
      transition: all .2s ease;
    }
    .btn-edit { background: #ffc107; color: #fff; }
    .btn-edit:hover { background: #e0a800; }
    .btn-delete { background: #dc3545; color:#fff; }
    .btn-delete:hover { background: #b02a37; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content wide">
      <div class="page-header">
        <h2>📂 Categorías</h2>
        <a href="categoria-nueva.php" class="btn-add">➕ Nueva Categoría</a>
      </div>

      <!-- Alertas -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert success">✅ <?= htmlspecialchars($_GET['msg']) ?></div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <!-- Buscador -->
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="Buscar categoría por nombre o descripción...">
      </div>

      <div class="card p-16 table-wrapper">
        <table class="table" id="categoriasTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Descripción</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($categorias)): ?>
              <?php foreach($categorias as $c): ?>
                <tr>
                  <td><?= $c['id'] ?></td>
                  <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
                  <td><?= htmlspecialchars($c['descripcion'] ?? '-') ?></td>
                  <td>
                    <a href="categoria-editar.php?id=<?= $c['id'] ?>" class="btn-sm btn-edit">✏️ Editar</a>
                    <a href="categoria-eliminar.php?id=<?= $c['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('¿Eliminar esta categoría?')">🗑️ Eliminar</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;">⚠️ No hay categorías registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    // 🔍 Filtro dinámico
    document.getElementById("searchInput").addEventListener("keyup", function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll("#categoriasTable tbody tr");

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (!row.innerText.includes("No hay categorías")) {
          row.style.display = text.includes(filter) ? "" : "none";
        }
      });
    });
  </script>
</body>
</html>
