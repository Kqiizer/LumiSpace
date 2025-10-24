<?php
/**
 * Editar Rol - Sistema LumiSpace
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.0.0
 */

declare(strict_types=1);

// Inicialización de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dependencias
require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/funciones/roles.php";


// ====================================
// 🔐 AUTENTICACIÓN
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// ====================================
// 🎯 OBTENER Y VALIDAR ID
// ====================================
$rolId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$rolId || $rolId <= 0) {
    $_SESSION['error_message'] = 'ID de rol inválido';
    header("Location: roles.php?error=invalid_id");
    exit();
}

// Obtener datos del rol
$rol = getRolById($rolId);

if (!$rol) {
    $_SESSION['error_message'] = 'El rol no existe';
    header("Location: roles.php?error=not_found");
    exit();
}

// ====================================
// 📝 PROCESAMIENTO DEL FORMULARIO
// ====================================
$errors = [];
$formData = [
    'nombre' => $rol['nombre'],
    'descripcion' => $rol['descripcion']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['descripcion'] = trim($_POST['descripcion'] ?? '');
    
    // Validar nombre
    $validation = validateRolName($formData['nombre']);
    if (!$validation['valid']) {
        $errors['nombre'] = $validation['message'];
    }
    
    // Validar descripción
    if (strlen($formData['descripcion']) > 500) {
        $errors['descripcion'] = 'La descripción no puede exceder 500 caracteres';
    }
    
    // Verificar si es un rol protegido
    $protectedRoles = ['Administrador', 'Admin'];
    if (in_array($rol['nombre'], $protectedRoles) && $formData['nombre'] !== $rol['nombre']) {
        $errors['nombre'] = 'No se puede cambiar el nombre del rol de Administrador';
    }
    
    // Si no hay errores, actualizar el rol
    if (empty($errors)) {
        $updated = updateRol($rolId, $formData['nombre'], $formData['descripcion']);
        
        if ($updated) {
            $_SESSION['success_message'] = "El rol fue actualizado exitosamente";
            header("Location: roles.php?msg=" . urlencode("Rol actualizado exitosamente"));
            exit();
        } else {
            $errors['general'] = 'Error al actualizar el rol. Es posible que ya exista otro rol con ese nombre.';
        }
    }
}

// Obtener permisos del rol
$permisosRol = getRolPermisos($rolId);
$permisosDisponibles = getAllPermisos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rol - LumiSpace</title>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --warning-gradient: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            --shadow-md: 0 4px 16px rgba(0,0,0,.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,.15);
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        section.content.wide {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: var(--warning-gradient);
            color: #fff;
            padding: 24px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            box-shadow: var(--shadow-lg);
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .page-header .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .form-card {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 32px;
            margin-bottom: 24px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert--error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert--warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d3748;
            font-size: 0.95rem;
        }

        .form-group label .required {
            color: #dc3545;
            margin-left: 4px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f6d365;
            box-shadow: 0 0 0 3px rgba(246, 211, 101, 0.2);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .form-group.has-error input,
        .form-group.has-error textarea {
            border-color: #dc3545;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .char-counter {
            text-align: right;
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 4px;
        }

        .info-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-item {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 16px;
            border-radius: var(--radius-md);
            border-left: 4px solid #667eea;
        }

        .stat-item strong {
            display: block;
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .stat-item span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
        }

        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--warning-gradient);
            color: #fff;
            box-shadow: 0 4px 14px rgba(246, 211, 101, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(246, 211, 101, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .permissions-section {
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 32px;
        }

        .permissions-section h3 {
            margin: 0 0 20px 0;
            font-size: 1.3rem;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .permission-module {
            background: #f8f9fa;
            padding: 16px;
            border-radius: var(--radius-md);
            border-left: 3px solid #667eea;
        }

        .permission-module strong {
            display: block;
            font-size: 1rem;
            color: #667eea;
            margin-bottom: 12px;
        }

        .permission-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background: #fff;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .permission-item i {
            color: #28a745;
        }

        .permission-item.inactive {
            opacity: 0.4;
        }

        .permission-item.inactive i {
            color: #6c757d;
        }

        .quick-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
        }

        @media (max-width: 768px) {
            .form-actions,
            .quick-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .info-stats {
                grid-template-columns: 1fr;
            }

            .permissions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    
    <main class="main">
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content wide">
            <div class="page-header">
                <h2>
                    <i class="fas fa-edit"></i>
                    Editar Rol
                </h2>
                <p>Modifica la información y permisos del rol</p>
                <span class="badge">
                    <i class="fas fa-user-shield"></i>
                    <?= htmlspecialchars($rol['nombre']) ?>
                </span>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

            <?php if (in_array($rol['nombre'], ['Administrador', 'Admin'])): ?>
                <div class="alert alert--warning">
                    <i class="fas fa-shield-alt"></i>
                    Este es un rol protegido del sistema. Algunas opciones están limitadas por seguridad.
                </div>
            <?php endif; ?>

            <!-- Estadísticas del Rol -->
            <div class="info-stats">
                <div class="stat-item">
                    <strong><i class="fas fa-users"></i> Usuarios Asignados</strong>
                    <span><?= $rol['usuarios_count'] ?></span>
                </div>
                <div class="stat-item">
                    <strong><i class="fas fa-shield-alt"></i> Permisos Asignados</strong>
                    <span><?= count($permisosRol) ?></span>
                </div>
                <div class="stat-item">
                    <strong><i class="fas fa-calendar"></i> Creado</strong>
                    <span style="font-size: 1rem;"><?= date('d/m/Y', strtotime($rol['creado_en'])) ?></span>
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="form-card">
                <form method="POST" action="" id="rolForm">
                    <!-- Nombre del Rol -->
                    <div class="form-group <?= isset($errors['nombre']) ? 'has-error' : '' ?>">
                        <label for="nombre">
                            Nombre del Rol
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            value="<?= htmlspecialchars($formData['nombre']) ?>"
                            placeholder="Ej: Supervisor de Ventas"
                            required
                            maxlength="50"
                            autocomplete="off"
                            <?= in_array($rol['nombre'], ['Administrador', 'Admin']) ? 'readonly' : '' ?>
                        >
                        <small>Nombre único que identifica el rol</small>
                        <?php if (isset($errors['nombre'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= htmlspecialchars($errors['nombre']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group <?= isset($errors['descripcion']) ? 'has-error' : '' ?>">
                        <label for="descripcion">Descripción</label>
                        <textarea 
                            id="descripcion" 
                            name="descripcion" 
                            placeholder="Describe las responsabilidades y alcance de este rol..."
                            maxlength="500"
                        ><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        <small>Explica las funciones y permisos que tiene este rol</small>
                        <div class="char-counter">
                            <span id="charCount">0</span> / 500 caracteres
                        </div>
                        <?php if (isset($errors['descripcion'])): ?>
                            <div class="error-message">
                                <i class="fas fa-info-circle"></i>
                                <?= htmlspecialchars($errors['descripcion']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <a href="roles.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sección de Permisos -->
            <div class="permissions-section">
                <h3>
                    <i class="fas fa-shield-alt"></i>
                    Permisos Asignados
                </h3>

                <?php if (!empty($permisosRol)): ?>
                    <div class="permissions-grid">
                        <?php 
                        // Agrupar permisos por módulo
                        $permisosPorModulo = [];
                        foreach ($permisosRol as $permiso) {
                            $modulo = $permiso['modulo'] ?? 'General';
                            if (!isset($permisosPorModulo[$modulo])) {
                                $permisosPorModulo[$modulo] = [];
                            }
                            $permisosPorModulo[$modulo][] = $permiso;
                        }
                        ?>

                        <?php foreach ($permisosPorModulo as $modulo => $permisos): ?>
                            <div class="permission-module">
                                <strong><?= htmlspecialchars($modulo) ?></strong>
                                <?php foreach ($permisos as $permiso): ?>
                                    <div class="permission-item">
                                        <i class="fas fa-check-circle"></i>
                                        <?= htmlspecialchars($permiso['descripcion'] ?: $permiso['nombre']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fas fa-shield-alt" style="font-size: 3rem; opacity: 0.3; margin-bottom: 16px;"></i>
                        <p>Este rol aún no tiene permisos asignados</p>
                    </div>
                <?php endif; ?>

                <!-- Acciones Rápidas -->
                <div class="quick-actions">
                    <a href="roles-permisos.php?id=<?= $rolId ?>" class="btn btn-primary">
                        <i class="fas fa-key"></i>
                        Gestionar Permisos
                    </a>
                    <?php if ($rol['usuarios_count'] == 0): ?>
                        <a href="roles-eliminar.php?id=<?= $rolId ?>" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i>
                            Eliminar Rol
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
        'use strict';

        // Contador de caracteres
        const descripcionTextarea = document.getElementById('descripcion');
        const charCount = document.getElementById('charCount');

        function updateCharCount() {
            const length = descripcionTextarea.value.length;
            charCount.textContent = length;
            
            if (length > 450) {
                charCount.style.color = '#dc3545';
            } else if (length > 400) {
                charCount.style.color = '#ffc107';
            } else {
                charCount.style.color = '#6c757d';
            }
        }

        descripcionTextarea.addEventListener('input', updateCharCount);
        updateCharCount();

        // Validación del nombre
        const nombreInput = document.getElementById('nombre');
        
        if (!nombreInput.hasAttribute('readonly')) {
            nombreInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
                this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
            });
        }

        // Validación antes de enviar
        const form = document.getElementById('rolForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        });

        // Detectar cambios
        let originalData = {
            nombre: nombreInput.value,
            descripcion: descripcionTextarea.value
        };

        let hasChanges = false;

        form.addEventListener('input', () => {
            hasChanges = (
                nombreInput.value !== originalData.nombre ||
                descripcionTextarea.value !== originalData.descripcion
            );
        });

        // Advertencia al salir
        window.addEventListener('beforeunload', (e) => {
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '¿Seguro que quieres salir? Los cambios no guardados se perderán.';
            }
        });

        form.addEventListener('submit', () => {
            hasChanges = false;
        });
    </script>
</body>
</html>