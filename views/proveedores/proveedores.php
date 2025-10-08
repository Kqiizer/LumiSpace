<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$proveedores = getProveedores();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Proveedores - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    /* Header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 20px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff;
      margin-bottom: 20px;
      box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .page-header h2 { margin: 0; font-size: 1.4rem; font-weight: 700; }
    .btn-add {
      background: #fff;
      color: var(--act1);
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      transition: all .25s ease;
    }
    .btn-add:hover { background: var(--act1); color: #fff; }

    /* Alertas */
    .alert {
      margin-bottom: 16px;
      padding: 12px 16px;
      border-radius: 8px;
      font-weight: 600;
    }
    .alert.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
    .alert.error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

    /* Tabla */
    .table-wrapper { overflow-x: auto; }
    .table { width:100%; border-collapse: collapse; border-radius: 10px; overflow:hidden; }
    .table th, .table td {
      padding: 12px 14px;
      border-bottom: 1px solid #eee;
      text-align: left;
    }
    .table th { background: var(--card-bg-1); font-weight: 600; }
    .table tbody tr:hover { background: rgba(0,0,0,0.03); }

    /* Botones */
    .btn-sm {
      padding: 6px 10px;
      border-radius: 6px;
      font-size: .85rem;
      text-decoration: none;
      margin-right: 6px;
      display:inline-block;
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
        <h2>🏭 Proveedores</h2>
        <a href="proveedor-nuevo.php" class="btn-add">➕ Nuevo Proveedor</a>
      </div>

      <!-- Alertas de mensajes -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <!-- Tabla -->
      <div class="card p-16 table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Contacto</th>
              <th>Teléfono</th>
              <th>Email</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($proveedores)): ?>
              <?php foreach($proveedores as $pr): ?>
              <tr>
                <td><?= $pr['id'] ?></td>
                <td><strong><?= htmlspecialchars($pr['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($pr['contacto'] ?? '-') ?></td>
                <td><?= htmlspecialchars($pr['telefono'] ?? '-') ?></td>
                <td><?= htmlspecialchars($pr['email'] ?? '-') ?></td>
                <td>
                  <a href="proveedor-editar.php?id=<?= $pr['id'] ?>" class="btn-sm btn-edit">✏️ Editar</a>
                  <a href="proveedor-eliminar.php?id=<?= $pr['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('¿Eliminar este proveedor?')">🗑️ Eliminar</a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="6" style="text-align:center;">No hay proveedores registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
