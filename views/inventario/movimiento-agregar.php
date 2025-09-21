<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = (int)$_POST['producto_id'];
    $tipo        = $_POST['tipo'];
    $cantidad    = (int)$_POST['cantidad'];
    $motivo      = trim($_POST['motivo']);
    $uid         = $_SESSION['usuario_id'];

    if (registrarMovimiento($producto_id, $uid, $tipo, $cantidad, $motivo)) {
        header("Location: movimientos-listar.php?msg=Movimiento registrado correctamente");
        exit();
    } else {
        $error = "No se pudo registrar el movimiento. Intenta de nuevo.";
    }
}

$productos = getProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar movimiento - Inventario</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .page-header {
      display: flex; justify-content: space-between; align-items: center;
      padding: 14px 20px; border-radius: 12px;
      background: linear-gradient(135deg, var(--act1), var(--act2));
      color: #fff; box-shadow: 0 4px 14px rgba(0,0,0,.15);
      margin-bottom: 18px;
    }
    .page-header h2 { margin: 0; font-size: 1.3rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }

    /* Contenedor centrado */
    .center-wrapper {
      display: flex;
      justify-content: center;
      align-items: flex-start; /* puedes cambiar a center si quieres vertical total */
      padding: 20px;
    }

    form.form-card {
      width: 100%; max-width: 600px;
      background: var(--card-bg-1);
      padding: 20px;
      border-radius: 12px;
      box-shadow: var(--shadow);
    }
    form.form-card label { display: block; margin-top: 12px; font-weight: 600; }
    form.form-card input, form.form-card select {
      width: 100%; padding: 10px 12px;
      border-radius: 8px; border: 1px solid #ccc;
      margin-top: 6px;
    }

    .alert.error {
      margin-bottom: 16px; padding: 12px 16px;
      border-radius: 8px; font-weight: 600;
      background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
    }
    .btn-row { margin-top: 16px; display: flex; gap: 12px; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <!-- HEADER -->
      <div class="page-header">
        <h2>➕ Registrar Movimiento</h2>
      </div>

      <!-- Error -->
      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- Formulario centrado -->
      <div class="center-wrapper">
        <form method="POST" class="form-card">
          <label>Producto</label>
          <select name="producto_id" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($productos as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Tipo de movimiento</label>
          <select name="tipo" required>
            <option value="entrada">Entrada (compra/proveedor)</option>
            <option value="salida">Salida (venta)</option>
            <option value="ajuste">Ajuste manual</option>
          </select>

          <label>Cantidad</label>
          <input type="number" name="cantidad" min="1" required>

          <label>Motivo</label>
          <input type="text" name="motivo" placeholder="Ej. Compra a proveedor, venta, ajuste...">

          <div class="btn-row">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="movimientos-listar.php" class="btn">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
