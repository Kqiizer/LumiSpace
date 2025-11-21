<?php
/**
 * ============================================
 * FORMULARIO DE REGISTRO DE EMPLEADOS
 * ============================================
 * Archivo: usuario-agregar.php
 * Descripción: Formulario para dar de alta nuevos empleados
 * Permisos: Solo administradores
 * ============================================
 */

// ==========================================
// INICIALIZACIÓN Y SEGURIDAD
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../config/functions.php";

// 🔒 Verificación de acceso - Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// ==========================================
// CARGAR DATOS DINÁMICOS
// ==========================================
require_once __DIR__ . "/../../config/db.php";

// Valores por defecto en caso de error
$roles = ['admin', 'gestor', 'cajero', 'usuario'];
$sucursales = ['Sucursal Principal'];

try {
    // Obtener conexión PDO
    $pdo = class_exists('Database') 
        ? (new Database())->getConnection() 
        : new PDO(
            "mysql:host=localhost;dbname=lumispace;charset=utf8mb4",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    
    // Cargar roles activos desde la base de datos
    $stmtRoles = $pdo->query("SELECT nombre FROM roles WHERE activo = 1 ORDER BY nombre ASC");
    $rolesDB = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($rolesDB)) {
        $roles = $rolesDB;
    }
    
    // Cargar sucursales activas desde la base de datos
    $stmtSucursales = $pdo->query("SELECT nombre FROM sucursales WHERE activo = 1 ORDER BY nombre ASC");
    $sucursalesDB = $stmtSucursales->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($sucursalesDB)) {
        $sucursales = $sucursalesDB;
    }
    
} catch (PDOException $e) {
    // Log del error sin exponer detalles al usuario
    error_log("Error al cargar datos del formulario: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Formulario de registro de nuevos empleados - LumiSpace">
    <title>Registrar Empleado - LumiSpace</title>
    
    <!-- Estilos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* ==========================================
           ESTILOS GENERALES
           ========================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8f6f3 0%, #ebe8e3 100%);
            color: #2a1f15;
            line-height: 1.6;
        }
        
        /* ==========================================
           CONTENEDOR PRINCIPAL
           ========================================== */
        .main {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .content {
            max-width: 1100px;
            margin: 0 auto;
        }
        
        /* ==========================================
           ENCABEZADO DE PÁGINA
           ========================================== */
        .page-header {
            background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
            padding: 28px 35px;
            border-radius: 16px;
            color: #ffffff;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 6px 20px rgba(161, 104, 58, 0.25);
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header h1 i {
            font-size: 1.5rem;
        }
        
        /* ==========================================
           TARJETA DEL FORMULARIO
           ========================================== */
        .form-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(161, 104, 58, 0.1);
        }
        
        /* ==========================================
           SECCIONES DEL FORMULARIO
           ========================================== */
        .form-section {
            margin-bottom: 35px;
            padding-bottom: 30px;
            border-bottom: 2px solid rgba(161, 104, 58, 0.08);
        }
        
        .form-section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h3 {
            color: #a1683a;
            font-size: 1.3rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
        }
        
        .form-section h3 i {
            font-size: 1.2rem;
        }
        
        /* ==========================================
           GRUPOS DE FORMULARIO
           ========================================== */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 20px;
        }
        
        /* ==========================================
           LABELS
           ========================================== */
        label {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 8px;
            display: block;
            color: #2a1f15;
        }
        
        label .required {
            color: #dc3545;
            margin-left: 3px;
        }
        
        /* ==========================================
           INPUTS, SELECTS Y TEXTAREAS
           ========================================== */
        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0d9cf;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: #fafaf8;
            color: #2a1f15;
            transition: all 0.3s ease;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #a1683a;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
        }
        
        input::placeholder,
        textarea::placeholder {
            color: #a0a0a0;
        }
        
        input:disabled,
        input:read-only {
            background: #f0f0f0;
            cursor: not-allowed;
            color: #666;
        }
        
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        /* ==========================================
           VISTA PREVIA DE FOTO
           ========================================== */
        .photo-upload-container {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            margin-top: 10px;
        }
        
        .photo-preview {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            object-fit: cover;
            display: none;
            border: 4px solid #a1683a;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .photo-preview.active {
            display: block;
        }
        
        .upload-info {
            flex: 1;
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        
        .upload-info i {
            color: #a1683a;
            margin-right: 5px;
        }
        
        /* ==========================================
           BOTONES
           ========================================== */
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 35px;
            padding-top: 30px;
            border-top: 2px solid rgba(161, 104, 58, 0.1);
        }
        
        .btn-primary {
            flex: 1;
            background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
            color: #ffffff;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(161, 104, 58, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 104, 58, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            padding: 15px 30px;
            background: #e9ecef;
            color: #495057;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 2px solid transparent;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
            border-color: #adb5bd;
        }
        
        /* ==========================================
           ALERTAS Y MENSAJES
           ========================================== */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }
        
        .alert-info {
            background: #e7f3ff;
            color: #004085;
            border-left: 4px solid #0056b3;
        }
        
        .alert-info i {
            font-size: 1.2rem;
        }
        
        /* ==========================================
           RESPONSIVE
           ========================================== */
        @media (max-width: 992px) {
            .main {
                margin-left: 0;
                padding: 15px;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        
        @media (max-width: 576px) {
            .page-header h1 {
                font-size: 1.4rem;
            }
            
            .photo-upload-container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <!-- ==========================================
         SIDEBAR DE ADMINISTRACIÓN
         ========================================== -->
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    
    <!-- ==========================================
         CONTENIDO PRINCIPAL
         ========================================== -->
    <main class="main">
        <!-- Header de administración -->
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content">
            <!-- ==========================================
                 ENCABEZADO DE PÁGINA
                 ========================================== -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-user-plus"></i>
                    Registrar Nuevo Empleado
                </h1>
                <a href="usuarios.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>

            <!-- ==========================================
                 TARJETA DEL FORMULARIO
                 ========================================== -->
            <div class="form-card">
                <!-- Información adicional -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>
                        La contraseña inicial será el correo electrónico del empleado. 
                        Se recomienda cambiarla en el primer inicio de sesión.
                    </span>
                </div>

                <!-- Formulario principal -->
                <form action="usuario-agregar-procesar.php" 
                      method="POST" 
                      enctype="multipart/form-data" 
                      id="formUsuario"
                      novalidate>

                    <!-- ==========================================
                         SECCIÓN: INFORMACIÓN PERSONAL
                         ========================================== -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-user"></i>
                            Información Personal
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">
                                    Nombre completo
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       id="nombre" 
                                       required 
                                       pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$"
                                       placeholder="Ej: Juan Pérez García"
                                       autocomplete="name">
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">
                                    Teléfono
                                    <span class="required">*</span>
                                </label>
                                <input type="tel" 
                                       name="telefono" 
                                       id="telefono" 
                                       required 
                                       pattern="[0-9]{10}" 
                                       maxlength="10"
                                       placeholder="Ej: 5551234567"
                                       autocomplete="tel">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="direccion">
                                Dirección completa
                                <span class="required">*</span>
                            </label>
                            <textarea name="direccion" 
                                      id="direccion" 
                                      required 
                                      placeholder="Ej: Calle Principal #123, Colonia Centro, Ciudad, Estado"
                                      autocomplete="street-address"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="foto">
                                Foto del empleado
                                <span class="required">*</span>
                            </label>
                            <input type="file" 
                                   name="foto" 
                                   id="foto" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   required>
                            
                            <div class="photo-upload-container">
                                <img id="previewFoto" 
                                     class="photo-preview" 
                                     alt="Vista previa de la foto">
                                
                                <div class="upload-info">
                                    <p><i class="fas fa-info-circle"></i> Formatos permitidos: JPG, PNG, GIF, WEBP</p>
                                    <p><i class="fas fa-image"></i> Tamaño recomendado: 500x500 píxeles</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SECCIÓN: CREDENCIALES DE ACCESO
                         ========================================== -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-key"></i>
                            Credenciales de Acceso
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">
                                    Correo electrónico
                                    <span class="required">*</span>
                                </label>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       required 
                                       placeholder="Ej: empleado@lumispace.com"
                                       autocomplete="email">
                            </div>
                            
                            <div class="form-group">
                                <label for="password">
                                    Contraseña temporal
                                    <span class="required">*</span>
                                </label>
                                <input type="password" 
                                       name="password" 
                                       id="password" 
                                       required 
                                       minlength="6"
                                       placeholder="Mínimo 6 caracteres"
                                       autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="rol">
                                    Rol del usuario
                                    <span class="required">*</span>
                                </label>
                                <select name="rol" id="rol" required>
                                    <option value="">-- Selecciona un rol --</option>
                                    <?php foreach ($roles as $rol): ?>
                                        <option value="<?= htmlspecialchars(strtolower($rol), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars(ucfirst($rol), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="puesto">
                                    Puesto
                                    <span class="required">*</span>
                                </label>
                                <select name="puesto" id="puesto" required>
                                    <option value="">-- Selecciona un puesto --</option>
                                    <option value="cajero">Cajero</option>
                                    <option value="vendedor">Vendedor</option>
                                    <option value="almacenista">Almacenista</option>
                                    <option value="gerente">Gerente</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="asistente">Asistente</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ==========================================
                         SECCIÓN: INFORMACIÓN LABORAL
                         ========================================== -->
                    <div class="form-section">
                        <h3>
                            <i class="fas fa-briefcase"></i>
                            Información Laboral
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="num_empleado">
                                    Número de empleado
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       name="num_empleado" 
                                       id="num_empleado" 
                                       readonly 
                                       required 
                                       value="EMP-<?= date('ymdHis') ?>"
                                       title="Se genera automáticamente">
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_ingreso">
                                    Fecha de ingreso
                                    <span class="required">*</span>
                                </label>
                                <input type="date" 
                                       name="fecha_ingreso" 
                                       id="fecha_ingreso" 
                                       required 
                                       max="<?= date('Y-m-d') ?>" 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="sucursal">
                                Sucursal asignada
                                <span class="required">*</span>
                            </label>
                            <select name="sucursal" id="sucursal" required>
                                <option value="">-- Selecciona una sucursal --</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= htmlspecialchars($sucursal, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($sucursal, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- ==========================================
                         ACCIONES DEL FORMULARIO
                         ========================================== -->
                    <div class="form-actions">
                        <a href="usuarios.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Empleado
                        </button>
                    </div>

                </form>
            </div>
        </section>
    </main>

    <!-- ==========================================
         JAVASCRIPT
         ========================================== -->
    <script>
        // ==========================================
        // VISTA PREVIA DE LA FOTO
        // ==========================================
        document.getElementById('foto').addEventListener('change', function(e) {
            const archivo = e.target.files[0];
            
            if (archivo) {
                // Validar tamaño del archivo (máx 5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB en bytes
                if (archivo.size > maxSize) {
                    alert('⚠️ La imagen es muy grande. El tamaño máximo permitido es 5MB.');
                    e.target.value = '';
                    return;
                }
                
                // Validar tipo de archivo
                const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!tiposPermitidos.includes(archivo.type)) {
                    alert('⚠️ Formato de imagen no válido. Solo se permiten JPG, PNG, GIF y WEBP.');
                    e.target.value = '';
                    return;
                }
                
                // Mostrar vista previa
                const lector = new FileReader();
                lector.onload = function(evento) {
                    const imgPreview = document.getElementById('previewFoto');
                    imgPreview.src = evento.target.result;
                    imgPreview.classList.add('active');
                };
                lector.readAsDataURL(archivo);
            }
        });

        // ==========================================
        // VALIDACIÓN DEL FORMULARIO
        // ==========================================
        document.getElementById('formUsuario').addEventListener('submit', function(e) {
            const form = e.target;
            
            // Validar campos requeridos
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
                
                // Mostrar mensaje de error
                alert('⚠️ Por favor, completa todos los campos obligatorios marcados con *');
                
                // Enfocar el primer campo inválido
                const primerCampoInvalido = form.querySelector(':invalid');
                if (primerCampoInvalido) {
                    primerCampoInvalido.focus();
                }
                
                return false;
            }
            
            // Validar que se haya seleccionado una foto
            const inputFoto = document.getElementById('foto');
            if (!inputFoto.files || inputFoto.files.length === 0) {
                e.preventDefault();
                alert('⚠️ Por favor, selecciona una foto del empleado.');
                inputFoto.focus();
                return false;
            }
        });

        // ==========================================
        // FORMATEO AUTOMÁTICO DEL TELÉFONO
        // ==========================================
        document.getElementById('telefono').addEventListener('input', function(e) {
            // Eliminar caracteres no numéricos
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // ==========================================
        // CONVERTIR EMAIL A MINÚSCULAS
        // ==========================================
        document.getElementById('email').addEventListener('input', function(e) {
            this.value = this.value.toLowerCase();
        });
    </script>
</body>
</html>