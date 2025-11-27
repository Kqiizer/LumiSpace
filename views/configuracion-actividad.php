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

// ✅ Obtener datos del usuario
$admin = getUsuarioPorId($usuario_id);

if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: configuracion.php');
    exit();
}

$page_title = "Actividad Reciente";

// ✅ Datos temporales (hasta conectar logs reales)
$actividades = [
    [
        'accion' => 'Inicio de sesión exitoso',
        'fecha'  => date('d/m/Y H:i'),
        'origen' => 'Web'
    ],
    [
        'accion' => 'Visualizó el panel administrativo',
        'fecha'  => date('d/m/Y H:i', strtotime('-2 hours')),
        'origen' => 'Web'
    ],
    [
        'accion' => 'Intento de acceso denegado (sección Gestores)',
        'fecha'  => date('d/m/Y H:i', strtotime('-1 day')),
        'origen' => 'Web'
    ],
    [
        'accion' => 'Cierre de sesión',
        'fecha'  => date('d/m/Y H:i', strtotime('-2 days')),
        'origen' => 'Mobile'
    ]
];
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
        .activity-card {
            background: #fff;
            padding: 26px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .activity-item {
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-info {
            font-size: 0.95rem;
            color: #374151;
        }

        .activity-date {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .badge-origin {
            padding: 6px 12px;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0e7ff;
            color: #4f46e5;
        }

        .btn-back {
            background: #4f46e5;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: .2s ease;
        }

        .btn-back:hover {
            background: #4338ca;
        }
    </style>
</head>
<body>

    <?php include '../includes/sidebar-admin.php'; ?>

    <main class="main">
        <?php include '../includes/header-admin.php'; ?>

        <section class="content">

            <nav style="margin-bottom: 24px; color: #6b7280; font-size: 0.875rem;">
                <a href="dashboard.php" style="color: #6b7280; text-decoration: none;">Dashboard</a>
                <span style="margin: 0 8px;">/</span>
                <a href="configuracion.php" style="color: #6b7280; text-decoration: none;">Configuración</a>
                <span style="margin: 0 8px;">/</span>
                <span style="color: #1a202c; font-weight: 600;">Actividad</span>
            </nav>

            <h1 class="page-title" style="margin-bottom: 20px;">
                <i class="fas fa-history"></i> Actividad Reciente
            </h1>
            <p class="page-subtitle">Registros de actividad vinculados a tu cuenta.</p>

            <div class="activity-card">
                <?php if (!empty($actividades)): ?>
                    <?php foreach ($actividades as $log): ?>
                        <div class="activity-item">
                            <div>
                                <div class="activity-info"><?= htmlspecialchars($log['accion']) ?></div>
                                <div class="activity-date"><?= $log['fecha'] ?></div>
                            </div>
                            <div class="badge-origin"><?= htmlspecialchars($log['origen']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#6b7280; text-align:center;">Sin registros disponibles.</p>
                <?php endif; ?>
            </div>

            <a href="configuracion.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Configuración
            </a>

        </section>
    </main>

</body>
</html>
