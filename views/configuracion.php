<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/functions.php';

// ✅ Validar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php?error=unauth');
    exit();
}

$usuario_id = (int) $_SESSION['usuario_id'];
$admin = getUsuarioPorId($usuario_id);

if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: dashboard.php');
    exit();
}

$page_title = "Preferencias y Actividad";
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
        }
        .settings-card h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
            display:flex;
            gap:10px;
            align-items:center;
        }
        .info-group {
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-label {
            font-size: .85rem;
            font-weight: 600;
            color: #6b7280;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 500;
            margin-top: 4px;
        }
        .btn-secondary {
            background: #f3f4f6;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display:inline-flex;
            gap:8px;
            align-items:center;
            color:#374151;
        }
    </style>
</head>
<body>

    <?php include '../includes/sidebar-admin.php'; ?>

    <main class="main">
        <?php include '../includes/header-admin.php'; ?>

        <section class="content">

            <div style="margin-bottom: 32px;">
                <h1 class="page-title">
                    <i class="fas fa-sliders-h"></i> Preferencias & Actividad
                </h1>
                <p class="page-subtitle">Controla tu experiencia en el sistema</p>
            </div>

            <div class="settings-container">

                <!-- ✅ PREFERENCIAS -->
                <div class="settings-card">
                    <h2><i class="fas fa-cog"></i> Preferencias del Sistema</h2>

                    <div class="info-group">
                        <div class="info-label">Idioma</div>
                        <div class="info-value">Español (México)</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Zona Horaria</div>
                        <div class="info-value">GMT-6 (América/México)</div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Notificaciones</div>
                        <div class="info-value"><span style="color:#059669;font-weight:600;">Activadas</span></div>
                    </div>

                    <div class="info-group">
                        <div class="info-label">Tema</div>
                        <div class="info-value">Claro</div>
                    </div>

                    <a href="configuracion-preferencias.php" class="btn-secondary">
                        <i class="fas fa-edit"></i> Modificar Preferencias
                    </a>
                </div>

                <!-- ✅ ACTIVIDAD -->
                <div class="settings-card">
                    <h2><i class="fas fa-history"></i> Actividad Reciente</h2>

                    <div class="info-group">
                        <div class="info-label">Accesos Recientes</div>
                        <div class="info-value" style="font-size:.9rem; color:#6b7280;">
                            <p>Hoy a las <?= date('H:i') ?> - Web</p>
                            <p>Ayer a las 14:32 - Web</p>
                            <p><?= date('d/m/Y', strtotime('-2 days')) ?> - Mobile</p>
                        </div>
                    </div>

                    <a href="configuracion-actividad.php" class="btn-secondary">
                        <i class="fas fa-list"></i> Ver Historial Completo
                    </a>
                </div>

            </div>
        </section>
    </main>
</body>
</html>
