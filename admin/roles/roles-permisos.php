<?php
/**
 * Permisos del Rol - Sistema LumiSpace
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.0.2
 */

declare(strict_types=1);

// =====================================
// 🔒 Seguridad y dependencias
// =====================================
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// =====================================
// 📦 Obtener rol
// =====================================
$rol_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rol_id) {
    header("Location: roles.php?error=invalid_id");
    exit();
}

$rol = getRolById($rol_id);
if (!$rol) {
    header("Location: roles.php?error=notfound");
    exit();
}

// =====================================
// 🔑 Obtener permisos
// =====================================
$permisosPorModulo = getAllPermisos();
$permisosAsignados = array_column(getRolPermisos($rol_id), 'id');

// =====================================
// 💾 Guardar cambios
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // Limpiar permisos anteriores
        $stmt = $conn->prepare("DELETE FROM rol_permisos WHERE rol_id = ?");
        $stmt->bind_param("i", $rol_id);
        $stmt->execute();
        $stmt->close();

        // Asignar nuevos
        foreach ($_POST['permisos'] ?? [] as $permiso_id) {
            assignPermisoToRol($rol_id, (int)$permiso_id);
        }

        $conn->commit();
        header("Location: roles.php?msg=" . urlencode("Permisos actualizados correctamente"));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error al actualizar permisos: " . $e->getMessage());
        header("Location: roles.php?error=permisos_update_failed");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Permisos del Rol - <?= htmlspecialchars($rol['nombre']) ?> | LumiSpace</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  font-family: 'Roboto', 'Segoe UI', system-ui, -apple-system, sans-serif;
  background: linear-gradient(135deg, #f7f5f0, #e9e5dd);
  min-height: 100vh;
  color: #2a1f15;
}
.container {
  max-width: 1200px;
  margin: 40px auto;
  padding: 0 20px;
}
.header-bar {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:30px;
}
.header-bar h2 {
  font-size:1.8rem;
  display:flex;
  align-items:center;
  gap:10px;
  color:#4a3b2c;
}
.card {
  background: rgba(255,255,255,0.7);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  padding: 25px;
  margin-bottom: 25px;
  transition: transform .3s ease;
}
.card:hover { transform: scale(1.01); }
.module-title {
  font-weight:600;
  font-size:1.1rem;
  color:#4a3b2c;
  margin-bottom:15px;
  border-left:4px solid #a1683a;
  padding-left:10px;
}
.permiso-item {
  background: rgba(255,255,255,0.6);
  border-radius: 10px;
  padding: 10px 15px;
  margin-bottom: 10px;
  display:flex;
  align-items:center;
  transition: all .2s ease;
}
.permiso-item:hover {
  background: rgba(255,255,255,0.9);
  transform: translateX(3px);
}
.permiso-item input {
  margin-right: 10px;
  accent-color: #a1683a;
}
.btn {
  padding: 12px 24px;
  border-radius: 10px;
  font-weight:600;
  cursor:pointer;
  border:none;
  font-size:1rem;
  display:inline-flex;
  align-items:center;
  gap:8px;
  transition:all .3s ease;
}
.btn-primary {
  background: linear-gradient(135deg, #a1683a, #8b7355);
  color:#fff;
}
.btn-primary:hover {
  transform:translateY(-2px);
  box-shadow:0 6px 12px rgba(161,104,58,0.3);
}
.btn-secondary {
  background:#cbbca4;
  color:#fff;
}
.btn-secondary:hover {
  background:#a4937b;
}
footer {
  text-align:center;
  margin:40px 0 20px;
  color:#7a6c50;
  font-size:0.9rem;
}
</style>
</head>
<body>

<?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
<main class="main">
<?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

<div class="container">
  <div class="header-bar">
    <h2><i class="fa-solid fa-key"></i> Permisos del Rol: <strong><?= htmlspecialchars($rol['nombre']) ?></strong></h2>
    <a href="roles.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
  </div>

  <form method="POST">
    <?php foreach ($permisosPorModulo as $modulo => $permisos): ?>
      <div class="card">
        <h5 class="module-title"><i class="fa-solid fa-folder-open"></i> <?= htmlspecialchars(ucfirst($modulo)) ?></h5>
        <div class="row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;">
          <?php foreach ($permisos as $permiso): ?>
            <div class="permiso-item">
              <label style="cursor:pointer;">
                <input type="checkbox" name="permisos[]" value="<?= $permiso['id'] ?>"
                  <?= in_array($permiso['id'], $permisosAsignados) ? 'checked' : '' ?>>
                <?= htmlspecialchars($permiso['descripcion'] ?: $permiso['nombre']) ?>
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="text-center" style="text-align:center;margin-top:40px;">
      <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-save"></i> Guardar Cambios</button>
    </div>
  </form>
</div>

<footer>
  <p>LumiSpace © <?= date('Y') ?> | Gestión avanzada de roles y permisos</p>
</footer>

</main>
</body>
</html>
