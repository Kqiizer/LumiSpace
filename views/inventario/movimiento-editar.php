<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Validar permisos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// 🔹 Obtener ID del movimiento
$id = (int)($_GET['id'] ?? 0);
$mov = getMovimientoById($id); // ✅ debe apuntar a movimientos_inventario

if (!$mov) {
    header("Location: movimientos-listar.php?error=" . urlencode("Movimiento no encontrado."));
    exit();
}

$error = null;

// 🔹 Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoNuevo     = $_POST['tipo'] ?? '';
    $cantidadNueva = (int)($_POST['cantidad'] ?? 0);
    $motivoNuevo   = trim($_POST['motivo'] ?? '');

    if ($cantidadNueva > 0 && in_array($tipoNuevo, ['entrada','salida','ajuste'])) {
        $productoId = (int)$mov['producto_id'];
        $usuarioId  = (int)$_SESSION['usuario_id'];

        // ✅ actualizarMovimiento debe apuntar a movimientos_inventario
        if (actualizarMovimiento($id, $productoId, $tipoNuevo, $cantidadNueva, $motivoNuevo, $usuarioId)) {
            header("Location: movimientos-listar.php?msg=" . urlencode("✅ Movimiento actualizado correctamente."));
            exit();
        } else {
            $error = "❌ No se pudo actualizar el movimiento.";
        }
    } else {
        $error = "⚠️ Datos inválidos: revisa tipo, cantidad y motivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Movimiento - Inventario</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 600px; margin: 20px auto; padding: 20px;
      background: var(--card-bg-1); border-radius: 12px; box-shadow: var(--shadow);
    }
    h2 { margin-bottom: 16px; font-size: 1.4rem; font-weight: 700; }
    label { font-weight: 600; display: block; margin-top: 12px; }
    input, select {
      width: 100%; padding: 10px 12px; margin-top: 6px;
      border: 1px solid #ccc; border-radius: 8px;
    }
    .btn-row { margin-top: 18px; display: flex; gap: 12px; }
    .btn { padding: 10px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; }
    .btn-primary { background: var(--act1); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-secondary { background: #ddd; color: #333; }
    .alert.error {
      background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;
      padding:12px; border-radius:8px; margin-bottom:16px; font-weight:600;
    }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <h2>✏️ Editar Movimiento</h2>

      <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" class="form-card">
        <label>Producto</label>
        <input type="text" value="<?= htmlspecialchars($mov['producto'] ?? '') ?>" disabled>

        <label>Tipo</label>
        <select name="tipo" required>
          <option value="entrada" <?= $mov['tipo']=='entrada'?'selected':'' ?>>Entrada</option>
          <option value="salida" <?= $mov['tipo']=='salida'?'selected':'' ?>>Salida</option>
          <option value="ajuste" <?= $mov['tipo']=='ajuste'?'selected':'' ?>>Ajuste</option>
        </select>

        <label>Cantidad</label>
        <input type="number" name="cantidad" value="<?= (int)$mov['cantidad'] ?>" min="1" required>

        <label>Motivo</label>
        <input type="text" name="motivo" value="<?= htmlspecialchars($mov['motivo'] ?? '') ?>">

        <div class="btn-row">
          <button type="submit" class="btn btn-primary">💾 Guardar</button>
          <a href="movimientos-listar.php" class="btn btn-secondary">Cancelar</a>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
