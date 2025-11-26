<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

// ✅ Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ✅ Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?error=unauth');
    exit();
}

$usuario_id = (int) $_SESSION['usuario_id'];

// ✅ Obtener datos del usuario
$admin = getUsuarioPorId($usuario_id);

if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: dashboard.php');
    exit();
}

// ✅ Obtener estadísticas del usuario
$stats = [
    'ultima_sesion' => $admin['ultima_sesion'] ?? date('Y-m-d H:i:s'),
    'sesiones_activas' => 1,
    'intentos_fallidos' => 0
];

$page_title = "Configuración de Cuenta";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - LumiSpace Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .settings-card {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }

        .settings-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card h2 i {
            color: #4f46e5;
            font-size: 1.4rem;
        }

        .info-group {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-group:last-of-type {
            border-bottom: none;
            margin-bottom: 24px;
        }

        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: #1a202c;
            font-weight: 500;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .badge-primary {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .badge-success {
            background: #d1fae5;
            color: #059669;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: #4f46e5;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #059669;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert i {
            font-size: 1.2rem;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: #f9fafb;
            padding: 16px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .section-divider {
            margin: 32px 0;
            border: none;
            border-top: 2px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar-admin.php'; ?>
    
    <main class="main">
        <?php include '../includes/header-admin.php'; ?>
        
        <section class="content">
            <!-- Breadcrumb -->
            <nav style="margin-bottom: 24px; color: #6b7280; font-size: 0.875rem;">
                <a href="dashboard.php" style="color: #6b7280; text-decoration: none;">Dashboard</a>
                <span style="margin: 0 8px;">/</span>
                <span style="color: #1a202c; font-weight: 600;">Configuración</span>
            </nav>

            <!-- Título de página -->
            <div style="margin-bottom: 32px;">
                <h1 class="page-title" style="margin-bottom: 8px;">
                    <i class="fas fa-cog"></i> Configuración de Cuenta
                </h1>
                <p class="page-subtitle">Administra tu perfil, seguridad y preferencias del sistema</p>
            </div>

            <!-- Alertas -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Grid de configuraciones -->
            <div class="settings-container">
                <!-- Información Personal -->
                <div class="settings-card">
                    <h2>
                        <i class="fas fa-user-circle"></i>
                        Información Personal
                    </h2>

                    <div class="info-group">
                        <div class="info-label">Nombre Completo</div>
                        <div class="info-value"><?= htmlspecialchars($admin['nombre']) ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Correo Electrónico</div>
                        <div class="info-value"><?= htmlspecialchars($admin['email']) ?></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Teléfono</div>
                        <div class="info-value">
                            <?= htmlspecialchars($admin['telefono'] ?: 'No registrado') ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Rol de Usuario</div>
                        <div class="info-value">
                            <span class="badge badge-primary">
                                <?= htmlspecialchars(ucfirst($admin['rol'] ?? 'Administrador')) ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Sucursal Asignada</div>
                        <div class="info-value">
                            <?= htmlspecialchars($admin['sucursal'] ?: 'Sin asignar') ?>
                        </div>
                    </div>

                    <div class="btn-group">
                        <a href="configuracion-editar.php" class="btn-primary">
                            <i class="fas fa-edit"></i>
                            Editar Perfil
                        </a>
                    </div>
                </div>

                <!-- Seguridad -->
                <div class="settings-card">
                    <h2>
                        <i class="fas fa-shield-alt"></i>
                        Seguridad y Acceso
                    </h2>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?= $stats['sesiones_activas'] ?></div>
                            <div class="stat-label">Sesiones activas</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?= $stats['intentos_fallidos'] ?></div>
                            <div class="stat-label">Intentos fallidos</div>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Última Sesión</div>
                        <div class="info-value">
                            <?php
                            $ultima = strtotime($stats['ultima_sesion']);
                            echo date('d/m/Y', $ultima) . ' a las ' . date('H:i', $ultima);
                            ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Estado de la Cuenta</div>
                        <div class="info-value">
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> Activa
                            </span>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div class="btn-group">
                        <a href="configuracion-password.php" class="btn-primary">
                            <i class="fas fa-key"></i>
                            Cambiar Contraseña
                        </a>
                        <a href="configuracion-2fa.php" class="btn-secondary">
                            <i class="fas fa-mobile-alt"></i>
                            Autenticación 2FA
                        </a>
                    </div>
                </div>

                <!-- Preferencias del Sistema -->
                <div class="settings-card">
                    <h2>
                        <i class="fas fa-sliders-h"></i>
                        Preferencias del Sistema
                    </h2>

                    <div class="info-group">
                        <div class="info-label">Idioma de la Interfaz</div>
                        <div class="info-value">Español (México)</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Zona Horaria</div>
                        <div class="info-value">GMT-6 (América/México)</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Notificaciones por Email</div>
                        <div class="info-value">
                            <span class="badge badge-success">Activadas</span>
                        </div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Tema de Interfaz</div>
                        <div class="info-value">Claro</div>
                    </div>

                    <div class="btn-group">
                        <a href="configuracion-preferencias.php" class="btn-secondary">
                            <i class="fas fa-cog"></i>
                            Modificar Preferencias
                        </a>
                    </div>
                </div>

                <!-- Actividad Reciente -->
                <div class="settings-card">
                    <h2>
                        <i class="fas fa-history"></i>
                        Actividad y Logs
                    </h2>

                    <div class="info-group">
                        <div class="info-label">Accesos Recientes</div>
                        <div class="info-value" style="font-size: 0.875rem; color: #6b7280;">
                            <div style="margin-bottom: 8px;">
                                <i class="fas fa-circle" style="color: #10b981; font-size: 0.5rem;"></i>
                                Hoy a las <?= date('H:i') ?> - Web
                            </div>
                            <div style="margin-bottom: 8px;">
                                <i class="fas fa-circle" style="color: #6b7280; font-size: 0.5rem;"></i>
                                Ayer a las 14:32 - Web
                            </div>
                            <div>
                                <i class="fas fa-circle" style="color: #6b7280; font-size: 0.5rem;"></i>
                                <?= date('d/m/Y', strtotime('-2 days')) ?> - Mobile
                            </div>
                        </div>
                    </div>

                    <hr class="section-divider">

                    <div class="btn-group">
                        <a href="configuracion-actividad.php" class="btn-secondary">
                            <i class="fas fa-list"></i>
                            Ver Historial Completo
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sección de acciones peligrosas -->
            <div class="settings-card" style="margin-top: 24px; border: 2px solid #fee2e2;">
                <h2 style="color: #dc2626;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Zona de Peligro
                </h2>
                
                <p style="color: #6b7280; margin-bottom: 20px;">
                    Las siguientes acciones son irreversibles. Procede con precaución.
                </p>

                <div class="btn-group">
                    <button class="btn-secondary" onclick="cerrarSesiones()" style="border: 1px solid #dc2626; color: #dc2626;">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar Todas las Sesiones
                    </button>
                    <button class="btn-secondary" onclick="descargarDatos()" style="border: 1px solid #374151;">
                        <i class="fas fa-download"></i>
                        Descargar Mis Datos
                    </button>
                </div>
            </div>

        </section>
    </main>

    <script>
        function cerrarSesiones() {
            if (confirm('¿Estás seguro de que deseas cerrar todas las sesiones activas? Tendrás que iniciar sesión nuevamente.')) {
                window.location.href = 'cerrar-sesiones.php?csrf_token=<?= $_SESSION['csrf_token'] ?>';
            }
        }

        function descargarDatos() {
            if (confirm('¿Deseas descargar una copia de todos tus datos personales?')) {
                window.location.href = 'descargar-datos.php?csrf_token=<?= $_SESSION['csrf_token'] ?>';
            }
        }

        // Auto-ocultar alertas después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>