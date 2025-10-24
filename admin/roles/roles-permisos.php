<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// 🚨 Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$rol_id = $_GET['id'] ?? null;
if (!$rol_id) {
    header("Location: roles.php");
    exit();
}

$rol = getRolById((int)$rol_id);
if (!$rol) {
    header("Location: roles.php?error=notfound");
    exit();
}

$permisosPorModulo = getAllPermisos();
$permisosAsignados = array_column(getRolPermisos($rol_id), 'id');

// ✅ Guardar cambios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = getDBConnection();

    // Limpiar permisos anteriores
    $stmt = $conn->prepare("DELETE FROM rol_permisos WHERE rol_id = ?");
    $stmt->bind_param("i", $rol_id);
    $stmt->execute();

    // Asignar nuevos permisos
    foreach ($_POST['permisos'] ?? [] as $permiso_id) {
        assignPermisoToRol($rol_id, (int)$permiso_id);
    }

    header("Location: roles.php?updatedPerms=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Permisos del Rol - <?= htmlspecialchars($rol['nombre']) ?> | LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f7f5f0, #e9e5dd);
      min-height: 100vh;
      color: #2a1f15;
    }
    .card {
      background: rgba(255, 255, 255, 0.7);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      padding: 25px;
      margin-bottom: 20px;
    }
    .module-title {
      font-weight: 600;
      color: #4a3b2c;
      margin-bottom: 10px;
    }
    .permiso-item {
      background: rgba(255,255,255,0.6);
      border-radius: 10px;
      padding: 10px 15px;
      margin-bottom: 8px;
      transition: all .2s ease;
    }
    .permiso-item:hover {
      background: rgba(255,255,255,0.9);
      transform: scale(1.02);
    }
    .permiso-item input {
      margin-right: 10px;
    }
    .btn-primary {
      background-color: #a98c67;
      border: none;
    }
    .btn-primary:hover {
      background-color: #8b7355;
    }
    .btn-secondary {
      background-color: #b3a797;
      border: none;
    }
    .btn-secondary:hover {
      background-color: #9b8d7b;
    }
  </style>
</head>
<body>

  <?php include "../includes/navbar-admin.php"; ?>

  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2><i class="fa-solid fa-lock"></i> Permisos del Rol: <strong><?= htmlspecialchars($rol['nombre']) ?></strong></h2>
      <a href="roles.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
    </div>

    <form method="POST">
      <?php foreach ($permisosPorModulo as $modulo => $permisos): ?>
        <div class="card">
          <h5 class="module-title"><i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars(ucfirst($modulo)) ?></h5>
          <div class="row">
            <?php foreach ($permisos as $permiso): ?>
              <div class="col-md-6 col-lg-4">
                <div class="permiso-item">
                  <label>
                    <input type="checkbox" name="permisos[]" value="<?= $permiso['id'] ?>"
                      <?= in_array($permiso['id'], $permisosAsignados) ? 'checked' : '' ?>>
                    <?= htmlspecialchars($permiso['descripcion'] ?: $permiso['nombre']) ?>
                  </label>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fa fa-save"></i> Guardar Cambios</button>
      </div>
    </form>
  </div>

</body>
</html>
