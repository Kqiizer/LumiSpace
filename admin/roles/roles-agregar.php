<?php
/**
 * Agregar Nuevo Rol - Sistema LumiSpace
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.0.1
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
// 📝 PROCESAMIENTO DEL FORMULARIO
// ====================================
$errors = [];
$success = false;
$formData = [
    'nombre' => '',
    'descripcion' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $formData['nombre'] = trim($_POST['nombre'] ?? '');
    $formData['descripcion'] = trim($_POST['descripcion'] ?? '');
    
    // Validar nombre
    if (empty($formData['nombre'])) {
        $errors['nombre'] = 'El nombre del rol es obligatorio';
    } elseif (strlen($formData['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    } elseif (strlen($formData['nombre']) > 50) {
        $errors['nombre'] = 'El nombre no puede exceder 50 caracteres';
    } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $formData['nombre'])) {
        $errors['nombre'] = 'El nombre solo puede contener letras y espacios';
    }
    
    // Validar descripción
    if (!empty($formData['descripcion']) && strlen($formData['descripcion']) > 500) {
        $errors['descripcion'] = 'La descripción no puede exceder 500 caracteres';
    }
    
    // Si no hay errores, crear el rol
    if (empty($errors)) {
        try {
            $conn = getDBConnection();
            
            // Verificar y crear tabla si no existe
            if (!tableExists($conn, 'roles')) {
                createRolesTable();
            }
            
            // Verificar si el rol ya existe
            $checkSql = "SELECT id FROM roles WHERE LOWER(nombre) = LOWER(?)";
            $checkStmt = $conn->prepare($checkSql);
            
            if ($checkStmt === false) {
                error_log("Error en prepare: " . $conn->error);
                $errors['general'] = 'Error al preparar la consulta. Por favor, verifica que la tabla "roles" exista en la base de datos.';
            } else {
                $checkStmt->bind_param('s', $formData['nombre']);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->fetch_assoc()) {
                    $errors['nombre'] = 'Ya existe un rol con ese nombre';
                } else {
                    // Insertar el nuevo rol (sin fecha_creacion si no existe la columna)
                    $sql = "INSERT INTO roles (nombre, descripcion) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    
                    if ($stmt === false) {
                        error_log("Error en prepare insert: " . $conn->error);
                        $errors['general'] = 'Error al preparar la inserción: ' . $conn->error;
                    } else {
                        $stmt->bind_param('ss', $formData['nombre'], $formData['descripcion']);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "El rol '{$formData['nombre']}' fue creado exitosamente";
                            header("Location: roles.php?success=1&msg=" . urlencode("Rol creado exitosamente"));
                            exit();
                        } else {
                            $errors['general'] = 'Error al crear el rol: ' . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
                $checkStmt->close();
            }
        } catch (Exception $e) {
            error_log("Error al crear rol: " . $e->getMessage());
            $errors['general'] = 'Error en la base de datos: ' . $e->getMessage();
        }
    }
}

// Obtener módulos y permisos disponibles para mostrar como referencia
try {
    $conn = getDBConnection();
    
    $permisosQuery = "SELECT DISTINCT modulo, nombre, descripcion 
                      FROM permisos 
                      ORDER BY modulo, nombre";
    $permisosResult = $conn->query($permisosQuery);
    
    // Agrupar permisos por módulo
    $permisosDisponibles = [];
    if ($permisosResult && $permisosResult->num_rows > 0) {
        while ($permiso = $permisosResult->fetch_assoc()) {
            $modulo = $permiso['modulo'] ?: 'General';
            if (!isset($permisosDisponibles[$modulo])) {
                $permisosDisponibles[$modulo] = [];
            }
            $permisosDisponibles[$modulo][] = $permiso;
        }
    }
} catch (Exception $e) {
    error_log("Error al obtener permisos: " . $e->getMessage());
    $permisosDisponibles = [];
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
        :root {
            --primary-gradient: linear-gradient(135deg, var(--act1), var(--act2));
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            background: var(--primary-gradient);
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

        .form-card {
            background: var(--card-bg-1);
            border: 1px solid var(--card-bd);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 32px;
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

        .alert--success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
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
            border: 2px solid var(--card-bd);
            background: var(--card-bg-1);
            color: var(--text);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--act1);
            box-shadow: 0 0 0 3px rgba(161, 104, 58, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group small {
            display: block;
            margin-top: 6px;
            color: var(--muted);
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
            color: var(--muted);
            margin-top: 4px;
        }

        .info-box {
            background: rgba(161, 104, 58, 0.1);
            border-left: 4px solid var(--act1);
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }

        .info-box strong {
            display: block;
            color: var(--text);
            margin-bottom: 8px;
        }

        .info-box ul {
            margin: 8px 0;
            padding-left: 20px;
            color: var(--text);
        }

        .info-box li {
            margin: 4px 0;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 24px;
            border-top: 1px solid var(--card-bd);
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
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: 0 4px 14px rgba(161, 104, 58, 0.3);
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 104, 58, 0.4);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: var(--muted);
            color: #fff;
        }

        .btn-secondary:hover {
            background: var(--text);
        }

        .permissions-preview {
            background: var(--card-bg-2);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-top: 24px;
        }

        .permissions-preview h3 {
            margin: 0 0 16px 0;
            font-size: 1.1rem;
            color: var(--text);
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .permission-module {
            background: var(--card-bg-1);
            padding: 12px;
            border-radius: 8px;
            border-left: 3px solid var(--act1);
        }

        .permission-module strong {
            display: block;
            font-size: 0.9rem;
            color: var(--act1);
            margin-bottom: 6px;
        }

        .permission-module ul {
            margin: 0;
            padding-left: 18px;
            font-size: 0.85rem;
            color: var(--muted);
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                justify-content: center;
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
                    <i class="fas fa-user-plus"></i>
                    Crear Nuevo Rol
                </h2>
                <p>Define un nuevo rol con sus permisos y responsabilidades</p>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($errors['general']) ?>
                </div>
            <?php endif; ?>

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
                        >
                        <small>Nombre único que identifica el rol (3-50 caracteres, solo letras)</small>
                        <?php if (isset($errors['nombre'])): ?>
                            <div class="error-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= htmlspecialchars($errors['nombre']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group <?= isset($errors['descripcion']) ? 'has-error' : '' ?>">
                        <label for="descripcion">
                            Descripción
                            <span style="color: var(--muted); font-weight: normal;">(Recomendado)</span>
                        </label>
                        <textarea 
                            id="descripcion" 
                            name="descripcion" 
                            placeholder="Describe las responsabilidades y alcance de este rol..."
                            maxlength="500"
                        ><?= htmlspecialchars($formData['descripcion']) ?></textarea>
                        <small>Explica las funciones y permisos que tendrá este rol</small>
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

                    <!-- Info Box -->
                    <div class="info-box">
                        <strong><i class="fas fa-lightbulb"></i> Nota Importante</strong>
                        <p>Después de crear el rol, podrás asignarle permisos específicos desde la lista de roles.</p>
                        <p>Los permisos disponibles incluyen:</p>
                        <ul>
                            <li>Gestión de usuarios y roles</li>
                            <li>Administración de productos e inventario</li>
                            <li>Operaciones de venta</li>
                            <li>Visualización y exportación de reportes</li>
                            <li>Configuración del sistema</li>
                        </ul>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="form-actions">
                        <a href="roles.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i>
                            Crear Rol
                        </button>
                    </div>
                </form>

                <!-- Preview de Permisos Disponibles -->
                <?php if (!empty($permisosDisponibles)): ?>
                    <div class="permissions-preview">
                        <h3><i class="fas fa-shield-alt"></i> Permisos Disponibles en el Sistema</h3>
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
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        'use strict';

        // Contador de caracteres para descripción
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
                charCount.style.color = 'var(--muted)';
            }
        }

        descripcionTextarea.addEventListener('input', updateCharCount);
        updateCharCount();

        // Validación en tiempo real del nombre
        const nombreInput = document.getElementById('nombre');
        
        nombreInput.addEventListener('input', function() {
            // Permitir solo letras, espacios y tildes
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            
            // Capitalizar primera letra de cada palabra
            this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
        });

        // Validación antes de enviar
        const form = document.getElementById('rolForm');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];

            // Validar nombre
            const nombre = nombreInput.value.trim();
            if (nombre.length < 3) {
                isValid = false;
                errors.push('El nombre debe tener al menos 3 caracteres');
            }

            if (nombre.length > 50) {
                isValid = false;
                errors.push('El nombre no puede exceder 50 caracteres');
            }

            // Validar descripción
            const descripcion = descripcionTextarea.value.trim();
            if (descripcion.length > 500) {
                isValid = false;
                errors.push('La descripción no puede exceder 500 caracteres');
            }

            if (!isValid) {
                e.preventDefault();
                alert('Por favor corrige los siguientes errores:\n\n' + errors.join('\n'));
                return false;
            }

            // Deshabilitar botón para evitar doble envío
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
        });

        // Auto-focus en el campo de nombre
        nombreInput.focus();

        // Prevenir salida accidental si hay datos
        let formModified = false;

        form.addEventListener('change', () => {
            formModified = true;
        });

        window.addEventListener('beforeunload', (e) => {
            if (formModified && nombreInput.value.trim()) {
                e.preventDefault();
                e.returnValue = '¿Seguro que quieres salir? Los cambios no guardados se perderán.';
            }
        });

        // Remover advertencia al enviar
        form.addEventListener('submit', () => {
            formModified = false;
        });
    </script>
</body>
</html>