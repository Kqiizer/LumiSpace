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

// ✅ BASE URL segura
if (defined('BASE_URL')) {
  $BASE = rtrim(BASE_URL, '/') . '/';
} else {
  $root = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  $BASE = ($root === '' ? '/' : $root . '/');
}

// ====================================
// 🔐 AUTENTICACIÓN
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: {$BASE}views/login.php?error=unauthorized");
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

<!-- ✅ FIX para asegurar que el botón sea clickeable -->
<style>
  .btn-add {
    position: relative;
    z-index: 10;
    pointer-events: auto !important;
    cursor: pointer !important;
  }
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

    <!-- ✅ MISMO DISEÑO, PERO FUNCIONAL -->
    <a href="<?= $BASE ?>admin/roles/roles-agregar.php" class="btn-add">
      <i class="fa fa-plus-circle"></i> Nuevo Rol
    </a>
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
              <a href="<?= $BASE ?>views/roles/roles-agregar.php" class="btn-add">
                <i class="fa fa-plus-circle"></i> Crear Rol
              </a>
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
