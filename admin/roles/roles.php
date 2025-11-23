<?php
/**
 * Panel de Administración - Gestión de Roles
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.3.0
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

// Dependencias principales
require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// ====================================
// 🔐 AUTENTICACIÓN
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// ====================================
// 📊 DATOS DEL USUARIO ACTUAL
// ====================================
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Invitado';
$usuario_rol    = ucfirst($_SESSION['usuario_rol'] ?? 'Sin rol');

// ====================================
// 📊 OBTENER DATOS
// ====================================
try {
    $roles = getRoles();
    $rolesStats = getRolesStats();
} catch (Throwable $e) {
    error_log("Error al obtener roles: " . $e->getMessage());
    $roles = [];
    $rolesStats = [];
}

$totalRoles = count($roles);

// ====================================
// 🎨 FUNCIONES VISUALES
// ====================================
function escape(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

function getRoleBadgeClass(string $role): string {
    $role = strtolower($role);
    return [
        'admin' => 'danger',
        'gestor' => 'info',
        'cajero' => 'primary',
        'inventario' => 'success',
        'usuario' => 'secondary',
    ][$role] ?? 'secondary';
}

function getRoleIcon(string $role): string {
    $role = strtolower($role);
    return [
        'admin' => 'fa-crown',
        'gestor' => 'fa-user-cog',
        'cajero' => 'fa-cash-register',
        'inventario' => 'fa-boxes',
        'usuario' => 'fa-user',
    ][$role] ?? 'fa-user';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestión de Roles - LumiSpace</title>
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --act1:#8b7355;--act2:#cbbca4;--shadow-md:0 4px 16px rgba(0,0,0,.12);
}
.page-header{display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,var(--act1),var(--act2));color:#fff;padding:24px;border-radius:16px;box-shadow:var(--shadow-md);margin-bottom:24px;}
.btn-add{background:#fff;color:var(--act1);border:none;font-weight:600;padding:12px 24px;border-radius:12px;display:flex;align-items:center;gap:8px;transition:.3s;text-decoration:none;}
.btn-add:hover{background:var(--act1);color:#fff;transform:translateY(-2px);}
.table-wrapper{background:#fff;border-radius:12px;box-shadow:var(--shadow-md);overflow:hidden;}
.table{width:100%;border-collapse:collapse;}
.table th,.table td{padding:16px 20px;border-bottom:1px solid #e0e0e0;text-align:left;}
.table th{background:#f8f9fa;text-transform:uppercase;font-size:.85rem;}
.role-badge{display:inline-flex;align-items:center;gap:8px;color:#fff;border-radius:20px;padding:8px 14px;font-size:.9rem;font-weight:600;}
.role-badge--danger{background:linear-gradient(135deg,#f093fb,#f5576c);}
.role-badge--info{background:linear-gradient(135deg,#4facfe,#00f2fe);}
.role-badge--success{background:linear-gradient(135deg,#11998e,#38ef7d);}
.role-badge--primary{background:linear-gradient(135deg,var(--act1),var(--act2));}
.role-badge--secondary{background:linear-gradient(135deg,#868f96,#596164);}
.actions{display:flex;gap:6px;}
.btn-action{width:36px;height:36px;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.3s;text-decoration:none;}
.btn-action--edit{background:#fff3cd;color:#856404;}
.btn-action--edit:hover{background:#ffc107;color:#fff;}
.btn-action--perm{background:#e2f0ff;color:#004085;}
.btn-action--perm:hover{background:#007bff;color:#fff;}
.btn-action--delete{background:#f8d7da;color:#721c24;}
.btn-action--delete:hover{background:#dc3545;color:#fff;}
.count-badge{background:#f4f4f4;padding:4px 10px;border-radius:10px;font-size:.8rem;}
.alert{margin-bottom:15px;padding:15px 20px;border-radius:10px;display:flex;align-items:center;gap:10px;}
.alert--success{background:#d4edda;color:#155724;border-left:4px solid #28a745;}
.alert--error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545;}
.empty-state{text-align:center;padding:80px 20px;color:#888;}
.empty-state i{font-size:3rem;opacity:.3;margin-bottom:10px;}
.current-role{background:#fff3cd;border-left:4px solid #ffc107;padding:14px 20px;border-radius:10px;display:flex;align-items:center;gap:10px;margin-bottom:15px;font-weight:600;color:#856404;}
.current-role i{color:#ffc107;}
</style>
</head>
<body>
<?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
<main class="main">
<?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

<section class="content wide">
  <div class="page-header">
    <div>
      <h2><i class="fa-solid fa-user-shield"></i> Gestión de Roles</h2>
      <p>Administra los roles y permisos del sistema LumiSpace</p>
    </div>
    <a href="roles-agregar.php" class="btn-add"><i class="fa fa-plus-circle"></i> Nuevo Rol</a>
  </div>

  <!-- 🔹 Rol actual -->
  <div class="current-role">
    <i class="fa fa-user-tag"></i> 
    Rol actual: <strong><?= escape($usuario_rol) ?></strong> — Usuario: <?= escape($usuario_nombre) ?>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert--success"><i class="fa fa-check-circle"></i><?= escape($_GET['msg']) ?></div>
  <?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert--error"><i class="fa fa-exclamation-triangle"></i><?= escape($_GET['error']) ?></div>
  <?php endif; ?>

  <div class="table-wrapper">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th><th>Rol</th><th>Descripción</th><th>Usuarios</th><th>Permisos</th><th>Creado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($roles): foreach ($roles as $rol): ?>
          <tr>
            <td>#<?= $rol['id'] ?></td>
            <td>
              <span class="role-badge role-badge--<?= getRoleBadgeClass($rol['nombre']) ?>">
                <i class="fa <?= getRoleIcon($rol['nombre']) ?>"></i> <?= escape($rol['nombre']) ?>
              </span>
            </td>
            <td><?= escape($rol['descripcion']) ?: 'Sin descripción' ?></td>
            <td><span class="count-badge"><i class="fa fa-users"></i> <?= (int)$rol['usuarios_count'] ?></span></td>
            <td><span class="count-badge"><i class="fa fa-key"></i> <?= (int)$rol['permisos_count'] ?></span></td>
            <td><?= $rol['creado_en'] ? date('d/m/Y', strtotime($rol['creado_en'])) : '-' ?></td>
            <td>
              <div class="actions">
                <a href="roles-editar.php?id=<?= $rol['id'] ?>" class="btn-action btn-action--edit" title="Editar"><i class="fa fa-edit"></i></a>
                <a href="roles-permisos.php?id=<?= $rol['id'] ?>" class="btn-action btn-action--perm" title="Permisos"><i class="fa fa-key"></i></a>
                <a href="roles-eliminar.php?id=<?= $rol['id'] ?>" class="btn-action btn-action--delete" title="Eliminar"><i class="fa fa-trash"></i></a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7">
            <div class="empty-state">
              <i class="fa fa-user-shield"></i>
              <h3>No hay roles registrados</h3>
              <p>Crea tu primer rol para comenzar la gestión de usuarios.</p>
              <a href="roles-agregar.php" class="btn-add"><i class="fa fa-plus-circle"></i> Crear Rol</a>
            </div>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</main>
</body>
</html>
