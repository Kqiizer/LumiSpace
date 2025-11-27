<?php
/**
 * Editar Rol - Sistema LumiSpace
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.0.2
 */

declare(strict_types=1);

// ====================================
// 🔒 Inicialización y Seguridad
// ====================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dependencias
require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// ✅ BASE URL segura
if (defined('BASE_URL')) {
    $BASE = rtrim(BASE_URL, '/') . '/';
} else {
    $root = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $BASE = ($root === '' ? '/' : $root . '/');
}

// ====================================
// 🔐 Autenticación
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: {$BASE}admin/login.php?error=unauthorized");
    exit();
}

// ====================================
// 🎯 Validar y obtener ID de rol
// ====================================
$rolId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rolId || $rolId <= 0) {
    $_SESSION['error_message'] = "ID de rol inválido.";
    header("Location: {$BASE}admin/roles/roles.php?error=invalid_id");
    exit();
}

// Obtener datos del rol
$rol = getRolById($rolId);
if (!$rol) {
    $_SESSION['error_message'] = "El rol no existe o fue eliminado.";
    header("Location: {$BASE}admin/roles/roles.php?error=not_found");
    exit();
}

// ====================================
// 🧾 Preparar datos del formulario
// ====================================
$errors = [];
$formData = [
    'nombre' => $rol['nombre'],
    'descripcion' => $rol['descripcion'] ?? ''
];

// ====================================
// 📝 Procesamiento del formulario
// ====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['descripcion'] = trim($_POST['descripcion'] ?? '');

    // ✅ Validar nombre
    $validacion = validateRolName($formData['nombre']);
    if (!$validacion['valid']) {
        $errors['nombre'] = $validacion['message'];
    }

    // ✅ Validar longitud de descripción
    if (strlen($formData['descripcion']) > 500) {
        $errors['descripcion'] = "La descripción no puede exceder 500 caracteres.";
    }

    // ✅ No permitir editar nombre de roles protegidos
    $rolesProtegidos = ['Administrador', 'Admin'];
    if (in_array($rol['nombre'], $rolesProtegidos, true) && $formData['nombre'] !== $rol['nombre']) {
        $errors['nombre'] = "No se puede cambiar el nombre del rol de Administrador.";
    }

    // ✅ Si no hay errores, actualizar
    if (empty($errors)) {
        $actualizado = updateRol($rolId, $formData['nombre'], $formData['descripcion']);

        if ($actualizado) {
            $_SESSION['success_message'] = "✅ Rol actualizado correctamente.";
            header("Location: {$BASE}admin/roles/roles.php?msg=" . urlencode("Rol actualizado exitosamente."));
            exit();
        } else {
            $errors['general'] = "No se pudo actualizar. Puede que ya exista otro rol con ese nombre.";
        }
    }
}

// ====================================
// 🔑 Obtener permisos del rol
// ====================================
$permisosRol = getRolPermisos($rolId);
$permisosDisponibles = getAllPermisos();

// ====================================
// 🔢 Contadores adicionales
// ====================================
$rol['usuarios_count'] = $rol['usuarios_count'] ?? contarUsuariosPorRol($rolId);
$rol['creado_en'] = $rol['creado_en'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Rol - LumiSpace</title>
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
<main class="main">
<?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
<section class="content wide">

<div class="page-header">
  <h2><i class="fas fa-edit"></i> Editar Rol</h2>
  <p>Modifica la información y permisos del rol</p>
  <span class="badge"><i class="fas fa-user-shield"></i> <?= htmlspecialchars($rol['nombre']) ?></span>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert--error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<?php if (in_array($rol['nombre'], ['Administrador', 'Admin'])): ?>
<div class="alert alert--warning"><i class="fas fa-shield-alt"></i> Este es un rol protegido. Algunas acciones están restringidas.</div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="info-stats">
  <div class="stat-item"><strong>Usuarios Asignados</strong><span><?= $rol['usuarios_count'] ?></span></div>
  <div class="stat-item"><strong>Permisos Asignados</strong><span><?= count($permisosRol) ?></span></div>
  <div class="stat-item"><strong>Creado</strong><span><?= date('d/m/Y', strtotime($rol['creado_en'])) ?></span></div>
</div>

<!-- Formulario -->
<div class="form-card">
<form method="POST" id="rolForm">
  <div class="form-group <?= isset($errors['nombre']) ? 'has-error' : '' ?>">
    <label for="nombre">Nombre del Rol <span style="color:#dc3545">*</span></label>
    <input type="text" id="nombre" name="nombre" maxlength="50" required
      value="<?= htmlspecialchars($formData['nombre']) ?>"
      <?= in_array($rol['nombre'], ['Administrador','Admin']) ? 'readonly' : '' ?>>
    <?php if(isset($errors['nombre'])): ?><div style="color:#dc3545"><?= htmlspecialchars($errors['nombre']) ?></div><?php endif; ?>
  </div>

  <div class="form-group <?= isset($errors['descripcion']) ? 'has-error' : '' ?>">
    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" maxlength="500"><?= htmlspecialchars($formData['descripcion']) ?></textarea>
    <?php if(isset($errors['descripcion'])): ?><div style="color:#dc3545"><?= htmlspecialchars($errors['descripcion']) ?></div><?php endif; ?>
  </div>

  <div class="form-actions">

    <!-- ✅ BOTÓN CANCELAR CORREGIDO -->
    <a href="<?= $BASE ?>admin/roles/roles.php" class="btn btn-secondary">
      <i class="fas fa-times"></i> Cancelar
    </a>

    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save"></i> Guardar Cambios
    </button>
  </div>
</form>
</div>

<!-- Permisos -->
<div class="permissions-section">
<h3><i class="fas fa-shield-alt"></i> Permisos Asignados</h3>
<?php if ($permisosRol): ?>
  <div class="permissions-grid">
  <?php 
  $agrupados=[];
  foreach($permisosRol as $p){$m=$p['modulo']?:'General';$agrupados[$m][]=$p;}
  foreach($agrupados as $mod=>$arr): ?>
    <div class="permission-module">
      <strong><?= htmlspecialchars($mod) ?></strong>
      <?php foreach($arr as $permiso): ?>
      <div class="permission-item"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($permiso['descripcion'] ?: $permiso['nombre']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  </div>
<?php else: ?>
  <div style="text-align:center;padding:40px;color:#777;">
    <i class="fas fa-lock" style="font-size:3rem;opacity:.3;"></i>
    <p>Este rol aún no tiene permisos asignados.</p>
  </div>
<?php endif; ?>

<div class="quick-actions">
  <a href="roles-permisos.php?id=<?= $rolId ?>" class="btn btn-primary"><i class="fas fa-key"></i> Gestionar Permisos</a>
  <?php if (($rol['usuarios_count'] ?? 0) == 0): ?>
  <a href="roles-eliminar.php?id=<?= $rolId ?>" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Eliminar Rol</a>
  <?php endif; ?>
</div>
</div>

</section>
</main>
<script>
'use strict';
const d=document;
const desc=d.getElementById('descripcion'),char=d.createElement('small');
desc.after(char);
desc.addEventListener('input',()=>{let l=desc.value.length;char.textContent=l+'/500';char.style.color=l>450?'#dc3545':l>400?'#ffc107':'#6c757d';});
</script>
</body>
</html>
