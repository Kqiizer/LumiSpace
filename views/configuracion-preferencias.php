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

// ✅ Obtener preferencias actuales
$admin = getUsuarioPorId($usuario_id);

if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: configuracion.php');
    exit();
}

// ✅ Guardar cambios si se envía formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tema               = $_POST['tema'] ?? 'claro';
    $notificaciones     = isset($_POST['notificaciones']) ? 1 : 0;
    $idioma             = $_POST['idioma'] ?? 'es-MX';
    $zona               = $_POST['zona_horaria'] ?? 'America/Mexico_City';

    $conn = getDBConnection();

    $sql = "UPDATE usuarios 
            SET tema=?, notificaciones_email=?, idioma=?, zona_horaria=?
            WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissi", $tema, $notificaciones, $idioma, $zona, $usuario_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Preferencias guardadas correctamente.";
    } else {
        $_SESSION['error'] = "❌ Error al guardar preferencias.";
    }

    header('Location: configuracion-preferencias.php');
    exit();
}

$page_title = "Preferencias del Sistema";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - LumiSpace</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-card {
            background: #fff;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            max-width: 650px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            display: block;
        }
        select, input[type="checkbox"] {
            padding: 10px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #d1d5db;
        }
        .btn-primary {
            background: #4f46e5;
            color: #fff;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .alert {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>

<?php include '../includes/sidebar-admin.php'; ?>

<main class="main">
    <?php include '../includes/header-admin.php'; ?>

    <section class="content">

        <h1 class="page-title"><i class="fas fa-sliders-h"></i> Preferencias del Sistema</h1>
        <p class="page-subtitle">Personaliza tu experiencia en LumiSpace</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="settings-card">
            <form method="POST">

                <div class="form-group">
                    <label>Tema visual</label>
                    <select name="tema">
                        <option value="claro" <?= ($admin['tema'] ?? 'claro') === 'claro' ? 'selected' : '' ?>>Claro</option>
                        <option value="oscuro" <?= ($admin['tema'] ?? '') === 'oscuro' ? 'selected' : '' ?>>Oscuro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notificaciones por correo</label>
                    <input type="checkbox" name="notificaciones" <?= ($admin['notificaciones_email'] ?? 1) == 1 ? 'checked' : '' ?>>
                </div>

                <div class="form-group">
                    <label>Idioma</label>
                    <select name="idioma">
                        <option value="es-MX" <?= ($admin['idioma'] ?? 'es-MX') === 'es-MX' ? 'selected' : '' ?>>Español (México)</option>
                        <option value="es-ES" <?= ($admin['idioma'] ?? '') === 'es-ES' ? 'selected' : '' ?>>Español (España)</option>
                        <option value="en-US" <?= ($admin['idioma'] ?? '') === 'en-US' ? 'selected' : '' ?>>Inglés (Estados Unidos)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Zona Horaria</label>
                    <select name="zona_horaria">
                        <option value="America/Mexico_City" <?= ($admin['zona_horaria'] ?? '') === 'America/Mexico_City' ? 'selected' : '' ?>>GMT-6 México</option>
                        <option value="America/Bogota">GMT-5 Colombia</option>
                        <option value="America/Santiago">GMT-4 Chile</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Preferencias
                </button>

            </form>
        </div>
    </section>

</main>
</body>
</html>
