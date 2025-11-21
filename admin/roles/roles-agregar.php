<?php
/**
 * Agregar Nuevo Rol - Sistema LumiSpace
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

// ====================================
// 🔐 Autenticación
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// ====================================
// 📝 Procesamiento del formulario
// ====================================
$errors = [];
$formData = [
    'nombre' => '',
    'descripcion' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['descripcion'] = trim($_POST['descripcion'] ?? '');

    // Validar nombre
    if ($formData['nombre'] === '') {
        $errors['nombre'] = 'El nombre del rol es obligatorio';
    } elseif (strlen($formData['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    } elseif (strlen($formData['nombre']) > 50) {
        $errors['nombre'] = 'El nombre no puede exceder 50 caracteres';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u', $formData['nombre'])) {
        $errors['nombre'] = 'El nombre solo puede contener letras y espacios';
    }

    // Validar descripción
    if (!empty($formData['descripcion']) && strlen($formData['descripcion']) > 500) {
        $errors['descripcion'] = 'La descripción no puede exceder 500 caracteres';
    }

    // Si no hay errores → crear rol
    if (empty($errors)) {
        try {
            $conn = getDBConnection();

            // Verificar si la tabla existe, si no, crearla
            if (!function_exists('tableExists') || !tableExists($conn, 'roles')) {
                if (function_exists('createRolesTable')) {
                    createRolesTable();
                }
            }

            // Verificar si ya existe
            $stmt = $conn->prepare("SELECT id FROM roles WHERE LOWER(nombre) = LOWER(?)");
            $stmt->bind_param("s", $formData['nombre']);
            $stmt->execute();
            $existe = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existe) {
                $errors['nombre'] = "Ya existe un rol con ese nombre.";
            } else {
                // Insertar
                $sql = "INSERT INTO roles (nombre, descripcion, activo, fecha_creacion)
                        VALUES (?, ?, 1, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $formData['nombre'], $formData['descripcion']);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "✅ Rol '{$formData['nombre']}' creado exitosamente.";
                    header("Location: roles.php?msg=" . urlencode("Rol creado exitosamente"));
                    exit();
                } else {
                    $errors['general'] = "Error al crear el rol: " . $stmt->error;
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error al crear rol: " . $e->getMessage());
            $errors['general'] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// ====================================
// 🔎 Permisos disponibles (vista previa)
// ====================================
$permisosDisponibles = [];
try {
    $conn = getDBConnection();
    $query = "SELECT DISTINCT modulo, nombre, descripcion FROM permisos ORDER BY modulo, nombre";
    $result = $conn->query($query);
    while ($permiso = $result->fetch_assoc()) {
        $mod = $permiso['modulo'] ?: 'General';
        $permisosDisponibles[$mod][] = $permiso;
    }
} catch (Exception $e) {
    error_log("Error al obtener permisos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nuevo Rol - LumiSpace</title>
<link rel="stylesheet" href="../../css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* 🎨 Estilos simplificados */
section.content.wide{max-width:900px;margin:0 auto;padding:20px}
.page-header{background:linear-gradient(135deg,#a1683a,#8f5e4b);color:#fff;padding:24px;border-radius:16px;margin-bottom:24px;box-shadow:0 8px 24px rgba(0,0,0,.15)}
.page-header h2{margin:0;font-size:1.8rem;display:flex;align-items:center;gap:12px}
.page-header p{margin:8px 0 0;opacity:.9;font-size:.95rem}
.form-card{background:#fff;border-radius:16px;padding:32px;box-shadow:0 4px 16px rgba(0,0,0,.1)}
.alert{padding:16px 20px;border-radius:10px;margin-bottom:20px;display:flex;align-items:center;gap:12px}
.alert--error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
.form-group{margin-bottom:24px}
.form-group label{font-weight:600;margin-bottom:8px;display:block}
input,textarea{width:100%;padding:12px 16px;border:2px solid #e0d9cf;border-radius:10px;font-size:1rem;background:#fafaf8}
input:focus,textarea:focus{border-color:#a1683a;box-shadow:0 0 0 3px rgba(161,104,58,.15);outline:none}
textarea{resize:vertical;min-height:100px}
.form-actions{display:flex;gap:12px;justify-content:flex-end;padding-top:24px;border-top:1px solid #eee}
.btn{padding:12px 24px;border-radius:10px;font-weight:600;cursor:pointer;border:none;transition:all .3s ease}
.btn-primary{background:linear-gradient(135deg,#a1683a,#8f5e4b);color:#fff}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(161,104,58,.3)}
.btn-secondary{background:#ccc;color:#333}
.permissions-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:24px}
.permission-module{background:#fafaf8;padding:12px;border-radius:10px;border-left:4px solid #a1683a}
.permission-module strong{display:block;color:#a1683a;margin-bottom:6px}
.permission-module ul{margin:0;padding-left:18px;font-size:.9rem;color:#555}
</style>
</head>
<body>
<?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
<main class="main">
<?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
<section class="content wide">
<div class="page-header">
<h2><i class="fas fa-user-shield"></i> Crear Nuevo Rol</h2>
<p>Define un nuevo rol y sus responsabilidades</p>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert--error"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="form-card">
<form method="POST">
<div class="form-group">
<label for="nombre">Nombre del Rol <span style="color:#dc3545">*</span></label>
<input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($formData['nombre']) ?>" maxlength="50" placeholder="Ej: Supervisor de Ventas">
<small>Solo letras, mínimo 3 caracteres.</small>
<?php if(isset($errors['nombre'])): ?><div style="color:#dc3545"><?= htmlspecialchars($errors['nombre']) ?></div><?php endif; ?>
</div>

<div class="form-group">
<label for="descripcion">Descripción</label>
<textarea name="descripcion" id="descripcion" maxlength="500" placeholder="Describe las responsabilidades..."><?= htmlspecialchars($formData['descripcion']) ?></textarea>
<?php if(isset($errors['descripcion'])): ?><div style="color:#dc3545"><?= htmlspecialchars($errors['descripcion']) ?></div><?php endif; ?>
</div>

<div class="form-actions">
<a href="roles.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear Rol</button>
</div>
</form>

<?php if (!empty($permisosDisponibles)): ?>
<div class="permissions-grid">
<?php foreach ($permisosDisponibles as $modulo => $permisos): ?>
<div class="permission-module">
<strong><?= htmlspecialchars($modulo) ?></strong>
<ul>
<?php foreach ($permisos as $permiso): ?>
<li><?= htmlspecialchars($permiso['descripcion'] ?: $permiso['nombre']) ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
</main>
</body>
</html>
