<?php
/**
 * Eliminación de Roles - Sistema LumiSpace
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
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// ====================================
// 🔐 AUTENTICACIÓN
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    $_SESSION['error_message'] = 'No tienes permisos para realizar esta acción';
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// ====================================
// 🛡️ VALIDACIÓN DEL ID
// ====================================
$rolId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$rolId || $rolId <= 0) {
    $_SESSION['error_message'] = 'ID de rol inválido';
    header("Location: roles.php?error=invalid_id");
    exit();
}

// ====================================
// 📊 OBTENER DATOS DEL ROL
// ====================================
try {
    $conn = getDBConnection();
    
    // Obtener información del rol
    $stmt = $conn->prepare("SELECT r.id, r.nombre, r.descripcion, COUNT(u.id) as usuarios_count
                           FROM roles r
                           LEFT JOIN usuarios u ON u.rol = r.nombre
                           WHERE r.id = ?
                           GROUP BY r.id, r.nombre, r.descripcion");
    $stmt->bind_param('i', $rolId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rol = $result->fetch_assoc();
    $stmt->close();
    
    if (!$rol) {
        $_SESSION['error_message'] = 'El rol no existe o ya fue eliminado';
        header("Location: roles.php?error=not_found");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error al obtener rol: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error al obtener información del rol';
    header("Location: roles.php?error=db_error");
    exit();
}

// ====================================
// 🛡️ VALIDACIONES DE SEGURIDAD
// ====================================

// Verificar que no sea un rol protegido
$protectedRoles = ['admin', 'administrador'];
if (in_array(strtolower($rol['nombre']), $protectedRoles)) {
    $_SESSION['error_message'] = 'No se puede eliminar el rol de Administrador por seguridad';
    header("Location: roles.php?error=protected_role");
    exit();
}

// Verificar que no tenga usuarios asignados
if ($rol['usuarios_count'] > 0) {
    $_SESSION['error_message'] = sprintf(
        'No se puede eliminar el rol "%s" porque tiene %d usuario(s) asignado(s). Reasigna primero los usuarios a otro rol.',
        $rol['nombre'],
        $rol['usuarios_count']
    );
    header("Location: roles.php?error=has_users");
    exit();
}

// ====================================
// 🗑️ PROCESAMIENTO DE ELIMINACIÓN
// ====================================
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($confirmed) {
    try {
        // Eliminar el rol
        $deleteStmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
        $deleteStmt->bind_param('i', $rolId);
        $deleted = $deleteStmt->execute();
        $deleteStmt->close();
        
        if ($deleted) {
            // Log de auditoría
            $logMessage = sprintf(
                "[%s] Usuario #%d eliminó el rol #%d (%s)",
                date('Y-m-d H:i:s'),
                $_SESSION['usuario_id'],
                $rolId,
                $rol['nombre']
            );
            error_log($logMessage);
            
            $_SESSION['success_message'] = sprintf('El rol "%s" fue eliminado correctamente', $rol['nombre']);
            header("Location: roles.php?success=1&msg=" . urlencode("Rol eliminado exitosamente"));
            exit();
        } else {
            $_SESSION['error_message'] = 'Error al eliminar el rol. Por favor, intenta nuevamente.';
            header("Location: roles.php?error=delete_failed");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Error al eliminar rol {$rolId}: " . $e->getMessage());
        $_SESSION['error_message'] = 'Ocurrió un error inesperado. Por favor, contacta al administrador.';
        header("Location: roles.php?error=exception");
        exit();
    }
}

// ====================================
// 🎨 PÁGINA DE CONFIRMACIÓN
// ====================================
$rolNombre = htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8');
$rolDescripcion = htmlspecialchars($rol['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Eliminación - LumiSpace</title>
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --danger-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --shadow-lg: 0 8px 24px rgba(0,0,0,.15);
            --radius-lg: 16px;
        }

        body {
            background: linear-gradient(135deg, var(--act1) 0%, var(--act2) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-card {
            background: var(--card-bg-1);
            max-width: 500px;
            width: 100%;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: var(--danger-gradient);
            color: #fff;
            padding: 30px;
            text-align: center;
        }

        .card-header__icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .card-body {
            padding: 30px;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .warning-box strong {
            display: block;
            color: #856404;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .warning-box p {
            color: #856404;
            margin: 8px 0;
            font-size: 0.95rem;
        }

        .role-info {
            text-align: center;
            padding: 20px;
            background: var(--card-bg-2);
            border-radius: 12px;
            margin-bottom: 24px;
            border: 2px solid var(--card-bd);
        }

        .role-info__label {
            font-size: 0.85rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .role-info__name {
            font-size: 1.5rem;
            color: #f5576c;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .role-info__description {
            font-size: 0.9rem;
            color: var(--muted);
            font-style: italic;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f5576c, #f093fb);
            color: #fff;
            box-shadow: 0 4px 14px rgba(245, 87, 108, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
        }

        .btn-secondary {
            background: var(--muted);
            color: #fff;
            box-shadow: 0 4px 14px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            background: var(--text);
            transform: translateY(-2px);
        }

        .info-text {
            text-align: center;
            color: var(--muted);
            margin-bottom: 24px;
            font-size: 0.95rem;
        }

        @media (max-width: 600px) {
            .actions {
                flex-direction: column-reverse;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-card">
        <div class="card-header">
            <div class="card-header__icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>⚠️ Confirmar Eliminación</h2>
        </div>

        <div class="card-body">
            <div class="warning-box">
                <strong><i class="fas fa-info-circle"></i> Advertencia</strong>
                <p>Esta acción es <strong>permanente e irreversible</strong>.</p>
                <p>Se eliminarán todos los datos asociados a este rol.</p>
            </div>

            <div class="role-info">
                <div class="role-info__label">Rol a eliminar:</div>
                <div class="role-info__name"><?= $rolNombre ?></div>
                <?php if ($rolDescripcion): ?>
                    <div class="role-info__description"><?= $rolDescripcion ?></div>
                <?php endif; ?>
            </div>

            <p class="info-text">
                ¿Estás seguro de que deseas continuar?
            </p>

            <div class="actions">
                <a href="roles.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancelar
                </a>
                <a href="?id=<?= $rolId ?>&confirm=yes" class="btn btn-danger" id="deleteBtn">
                    <i class="fas fa-trash-alt"></i>
                    Sí, Eliminar Rol
                </a>
            </div>
        </div>
    </div>

    <script>
        'use strict';

        document.addEventListener('DOMContentLoaded', () => {
            // Auto-focus en el botón de cancelar para evitar eliminaciones accidentales
            const cancelBtn = document.querySelector('.btn-secondary');
            if (cancelBtn) {
                cancelBtn.focus();
            }

            // Atajos de teclado
            document.addEventListener('keydown', (e) => {
                // Escape = Cancelar
                if (e.key === 'Escape') {
                    window.location.href = 'roles.php';
                }
            });

            // Confirmación adicional en el botón de eliminar
            const deleteBtn = document.getElementById('deleteBtn');
            deleteBtn.addEventListener('click', (e) => {
                const confirmed = confirm(
                    '⚠️ ÚLTIMA CONFIRMACIÓN\n\n' +
                    'Esta acción eliminará permanentemente el rol "<?= $rolNombre ?>".\n\n' +
                    '¿Estás COMPLETAMENTE SEGURO de continuar?'
                );
                
                if (!confirmed) {
                    e.preventDefault();
                } else {
                    // Deshabilitar botón para evitar doble clic
                    deleteBtn.style.pointerEvents = 'none';
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';
                }
            });

            // Animación de entrada
            const card = document.querySelector('.confirmation-card');
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'scale(1)';
            }, 100);
        });

        console.log('⚠️ Página de confirmación de eliminación cargada');
    </script>
</body>
</html>