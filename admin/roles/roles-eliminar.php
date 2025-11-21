<?php
/**
 * Eliminación de Roles - Sistema LumiSpace
 * @package LumiSpace
 * @subpackage Admin
 * @version 2.1.0
 */

declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// =============================
// 🔐 AUTENTICACIÓN
// =============================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// =============================
// 🧩 VALIDAR ID
// =============================
$rolId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rolId) {
    header("Location: roles.php?error=invalid_id");
    exit();
}

// =============================
// 🔎 OBTENER ROL
// =============================
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT r.id, r.nombre, r.descripcion, COUNT(u.id) AS usuarios_count
    FROM roles r
    LEFT JOIN usuarios u ON u.rol = r.nombre
    WHERE r.id = ?
    GROUP BY r.id, r.nombre, r.descripcion
");
$stmt->bind_param("i", $rolId);
$stmt->execute();
$rol = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rol) {
    header("Location: roles.php?error=not_found");
    exit();
}

// =============================
// 🛡️ VALIDACIONES DE SEGURIDAD
// =============================
$protectedRoles = ['admin', 'administrador'];
if (in_array(strtolower($rol['nombre']), $protectedRoles)) {
    header("Location: roles.php?error=protected_role");
    exit();
}

if ($rol['usuarios_count'] > 0) {
    header("Location: roles.php?error=has_users&msg=" . urlencode("No se puede eliminar el rol '{$rol['nombre']}' porque tiene usuarios asignados."));
    exit();
}

// =============================
// 🗑️ ELIMINACIÓN CONFIRMADA
// =============================
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        $del = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $del->bind_param("i", $rolId);
        $del->execute();

        if ($del->affected_rows > 0) {
            error_log(sprintf("[%s] Rol #%d (%s) eliminado por usuario #%d",
                date('Y-m-d H:i:s'),
                $rolId,
                $rol['nombre'],
                $_SESSION['usuario_id']
            ));
            header("Location: roles.php?msg=" . urlencode("El rol '{$rol['nombre']}' fue eliminado correctamente."));
            exit();
        } else {
            header("Location: roles.php?error=delete_failed");
            exit();
        }
    } catch (Exception $e) {
        error_log("❌ Error eliminando rol {$rolId}: " . $e->getMessage());
        header("Location: roles.php?error=db_error");
        exit();
    }
}

// =============================
// 💡 INTERFAZ DE CONFIRMACIÓN
// =============================
$rolNombre = htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8');
$rolDescripcion = htmlspecialchars($rol['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Eliminar Rol - <?= $rolNombre ?> | LumiSpace</title>
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
  background: linear-gradient(135deg, var(--act1), var(--act2));
  min-height: 100vh; display:flex; align-items:center; justify-content:center;
}
.confirm-card {
  background: #fff; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,.15);
  max-width: 480px; width: 100%; overflow: hidden; animation: fadeIn .4s ease;
}
@keyframes fadeIn { from {opacity:0; transform:translateY(20px);} to {opacity:1; transform:none;} }
.header {
  background: linear-gradient(135deg,#f093fb,#f5576c);
  color:#fff; text-align:center; padding:30px 20px;
}
.header i {
  background: rgba(255,255,255,0.25); border-radius:50%; width:70px; height:70px;
  display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:2rem;
}
.body { padding: 30px; text-align: center; }
.warning { background:#fff3cd; border-left:4px solid #ffc107; padding:15px; border-radius:10px; margin-bottom:20px; color:#856404; }
.role-box { border:1px solid #eee; border-radius:10px; padding:20px; background:#fafafa; margin-bottom:20px; }
.role-box h3 { color:#f5576c; margin-bottom:5px; }
.role-box p { color:#666; font-size:0.9rem; }
.actions { display:flex; gap:10px; justify-content:center; }
.btn {
  padding:12px 24px; border-radius:10px; font-weight:600; text-decoration:none; transition:.3s;
  display:inline-flex; align-items:center; gap:6px;
}
.btn-danger { background:linear-gradient(135deg,#f5576c,#f093fb); color:#fff; }
.btn-danger:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(245,87,108,0.3); }
.btn-secondary { background:#6c757d; color:#fff; }
.btn-secondary:hover { background:#5a6268; transform:translateY(-2px); }
</style>
</head>
<body>
<div class="confirm-card">
  <div class="header">
    <i class="fa fa-exclamation-triangle"></i>
    <h2>Confirmar Eliminación</h2>
    <p>Esta acción es permanente e irreversible.</p>
  </div>
  <div class="body">
    <div class="warning">
      <strong>Advertencia:</strong> Se eliminarán todos los permisos vinculados a este rol.
    </div>
    <div class="role-box">
      <h3><?= $rolNombre ?></h3>
      <?php if ($rolDescripcion): ?><p><?= $rolDescripcion ?></p><?php endif; ?>
    </div>
    <p>¿Deseas continuar con la eliminación?</p>
    <div class="actions">
      <a href="roles.php" class="btn btn-secondary"><i class="fa fa-times"></i> Cancelar</a>
      <a href="?id=<?= $rolId ?>&confirm=yes" class="btn btn-danger" id="deleteBtn"><i class="fa fa-trash-alt"></i> Sí, eliminar</a>
    </div>
  </div>
</div>

<script>
document.getElementById('deleteBtn').addEventListener('click', e => {
  if(!confirm('⚠️ Confirmación final:\n\nEsta acción eliminará permanentemente el rol "<?= $rolNombre ?>".\n\n¿Deseas continuar?')) {
    e.preventDefault();
  } else {
    e.target.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Eliminando...';
    e.target.style.pointerEvents = 'none';
  }
});
</script>
</body>
</html>
