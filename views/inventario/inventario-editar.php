<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id  = (int)($_GET['id'] ?? 0);
$inv = getInventarioById($id);

if (!$inv) {
    header("Location: inventario-listar.php?error=" . urlencode("Registro de inventario no encontrado."));
    exit();
}

$error = null;
$conn  = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id   = (int)($_POST['producto_id'] ?? 0);
    $cantidadNueva = (int)($_POST['cantidad'] ?? 0);
    $sucursal      = trim($_POST['sucursal'] ?? '');

    if ($producto_id > 0 && $cantidadNueva >= 0 && $sucursal !== '') {
        $cantidadAnterior = (int)$inv['cantidad'];
        $diferencia       = $cantidadNueva - $cantidadAnterior;

        // ✅ Actualizar inventario con el stock real
        $sql  = "UPDATE inventario SET cantidad=?, sucursal=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("isi", $cantidadNueva, $sucursal, $id);
            if ($stmt->execute()) {
                // Registrar solo en historial si hubo diferencia
                if ($diferencia > 0) {
                    registrarMovimiento($producto_id, $_SESSION['usuario_id'], "entrada", $diferencia, "Ajuste manual en $sucursal");
                } elseif ($diferencia < 0) {
                    registrarMovimiento($producto_id, $_SESSION['usuario_id'], "salida", abs($diferencia), "Ajuste manual en $sucursal");
                }

                header("Location: inventario-listar.php?msg=" . urlencode("Inventario actualizado correctamente."));
                exit();
            } else {
                $error = "❌ No se pudo actualizar: " . $stmt->error;
            }
        } else {
            $error = "❌ Error al preparar consulta: " . $conn->error;
        }
    } else {
        $error = "⚠️ Todos los campos son obligatorios.";
    }
}

// 🔹 Sucursales (si existe tabla sucursales)
$sucursales = [];
$chk = $conn->query("SHOW TABLES LIKE 'sucursales'");
if ($chk && $chk->num_rows > 0) {
    $res = $conn->query("SELECT nombre FROM sucursales ORDER BY nombre ASC");
    if ($res) $sucursales = array_column($res->fetch_all(MYSQLI_ASSOC), 'nombre');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Inventario - LumiSpace</title>
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
    input, select {
      width: 100%; padding: 10px 12px;
      border: 1px solid var(--card-bd); border-radius: 8px;
      background: var(--card-bg-2); font-size: .95rem;
    }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
    .btn { padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; border: none; }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
    .alert { margin-bottom: 16px; padding: 12px; border-radius: 8px; font-weight: 600; }
    .alert.error { background: #f8d7da; color: #721c24; border:1px solid #f5c6cb; }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <div class="form-card">
        <h2>✏️ Editar Inventario</h2>

        <?php if (!empty($error)): ?>
          <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="producto_id" value="<?= $inv['producto_id'] ?>">

          <!-- Producto -->
          <label>Producto</label>
          <input type="text" value="<?= htmlspecialchars($inv['producto']) ?>" readonly style="background:#f3f3f3; cursor:not-allowed;">

          <!-- Sucursal -->
          <label for="sucursal">Sucursal</label>
          <?php if (!empty($sucursales)): ?>
            <select id="sucursal" name="sucursal" required>
              <?php foreach ($sucursales as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= ($s === $inv['sucursal']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($s) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php else: ?>
            <input type="text" id="sucursal" name="sucursal" value="<?= htmlspecialchars($inv['sucursal']) ?>" required>
          <?php endif; ?>

          <!-- Cantidad actual -->
          <label for="cantidad">Cantidad en stock</label>
          <input type="number" id="cantidad" name="cantidad" min="0" value="<?= (int)$inv['cantidad'] ?>" required>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <a href="inventario-listar.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
