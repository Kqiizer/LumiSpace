<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM proveedores WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$proveedor = $stmt->get_result()->fetch_assoc();

if (!$proveedor) {
    header("Location: proveedores.php?error=notfound");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Proveedor - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 700px;
      margin: 0 auto;
      padding: 24px;
      border-radius: var(--radius);
      background: var(--card-bg-1);
      border: 1px solid var(--card-bd);
      box-shadow: var(--shadow);
    }
    .form-card h2 {
      margin-bottom: 20px;
      color: var(--act1);
    }
    form { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    form label { font-weight: 600; margin-bottom: 6px; display: block; color: var(--text); }
    form input, form textarea {
      width: 100%; padding: 10px 12px;
      border: 1px solid var(--card-bd); border-radius: 8px;
      background: var(--card-bg-2); font-size: .95rem;
    }
    form textarea { resize: vertical; min-height: 90px; grid-column: span 2; }
    .form-actions { grid-column: span 2; display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
    .btn { padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: all .25s ease; text-decoration: none; display: inline-block; text-align: center; }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
    
    <section class="content wide">
      <div class="form-card">
        <h2>✏️ Editar Proveedor</h2>
        <form method="POST" action="proveedor-actualizar.php">
          <input type="hidden" name="id" value="<?= $proveedor['id'] ?>">

          <div>
            <label>Nombre</label>
            <input type="text" 
                   name="nombre" 
                   value="<?= htmlspecialchars($proveedor['nombre']) ?>" 
                   required
                   pattern="[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+" 
                   title="Solo se permiten letras y espacios">
          </div>

          <div>
            <label>Contacto</label>
            <input type="text" name="contacto" value="<?= htmlspecialchars($proveedor['contacto'] ?? '') ?>">
          </div>

          <div>
            <label>Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>">
          </div>

          <div>
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email'] ?? '') ?>">
          </div>

          <div style="grid-column: span 2;">
            <label>Dirección</label>
            <textarea name="direccion"><?= htmlspecialchars($proveedor['direccion'] ?? '') ?></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Actualizar</button>
            <a href="proveedores.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
