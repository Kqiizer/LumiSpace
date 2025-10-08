<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Validar permisos
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $cantidad    = (int)($_POST['cantidad'] ?? 0);
    $sucursal    = trim($_POST['sucursal'] ?? 'Principal');

    if ($producto_id > 0 && $cantidad > 0 && $sucursal !== '') {
        $conn = getDBConnection();

        // 🔹 Verificar si ya existe inventario para ese producto y sucursal
        $stmt = $conn->prepare("SELECT id, cantidad FROM inventario WHERE producto_id=? AND sucursal=? LIMIT 1");
        $stmt->bind_param("is", $producto_id, $sucursal);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row) {
            // 🔹 Si ya existe, actualizar stock sumando
            $nuevoStock = $row['cantidad'] + $cantidad;
            $stmt = $conn->prepare("UPDATE inventario SET cantidad=? WHERE id=?");
            $stmt->bind_param("ii", $nuevoStock, $row['id']);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                registrarMovimiento(
                    $producto_id,
                    (int)$_SESSION['usuario_id'],
                    'entrada',
                    $cantidad,
                    "Stock agregado en sucursal {$sucursal}"
                );
                header("Location: inventario-listar.php?msg=" . urlencode("✅ Stock actualizado correctamente."));
                exit();
            } else {
                $error = "❌ No se pudo actualizar el stock.";
            }

        } else {
            // 🔹 Si no existe inventario, lo insertamos
            if (insertarInventario($producto_id, $cantidad, $sucursal)) {
                registrarMovimiento(
                    $producto_id,
                    (int)$_SESSION['usuario_id'],
                    'entrada',
                    $cantidad,
                    "Stock inicial en sucursal {$sucursal}"
                );
                header("Location: inventario-listar.php?msg=" . urlencode("✅ Stock agregado correctamente."));
                exit();
            } else {
                $error = "❌ No se pudo registrar el stock inicial.";
            }
        }
    } else {
        $error = "⚠️ Todos los campos son obligatorios y la cantidad debe ser mayor a 0.";
    }
}

$productos = getProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Stock - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 600px; margin: 20px auto; padding: 24px;
      background: var(--card-bg-1); border:1px solid var(--card-bd);
      border-radius: 10px; box-shadow: var(--shadow);
    }
    h1 { margin-bottom: 16px; }
    label { font-weight: 600; display:block; margin-top: 12px; }
    select, input {
      width: 100%; padding: 10px; margin-top: 4px;
      border:1px solid var(--card-bd); border-radius:6px;
    }
    .form-actions { margin-top: 20px; display:flex; gap:10px; }
    .btn { padding: 10px 16px; border-radius: 6px; font-weight:600;
      border:none; cursor:pointer; text-decoration:none; }
    .btn-primary { background: var(--act1); color:#fff; }
    .btn-primary:hover { filter:brightness(1.1); }
    .btn-secondary { background: var(--card-bg-2); color:var(--text); }
  </style>
</head>
<body>
<?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
<main class="main">
  <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
  <section class="content">
    <div class="form-card">
      <h1>➕ Agregar Stock</h1>

      <?php if (!empty($error)): ?>
        <p style="color:#c00; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST">
        <label for="producto">Producto</label>
        <select id="producto" name="producto_id" required>
          <option value="">-- Selecciona --</option>
          <?php foreach ($productos as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="sucursal">Sucursal</label>
        <input type="text" id="sucursal" name="sucursal" value="Principal" required>

        <label for="cantidad">Cantidad a agregar</label>
        <input type="number" id="cantidad" name="cantidad" min="1" required>

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
