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

// ✅ CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuario_id = (int) $_SESSION['usuario_id'];

// ✅ Obtener datos actuales del usuario
$admin = getUsuarioPorId($usuario_id);
if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: configuracion.php');
    exit();
}

// ✅ Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['error'] = "Token de seguridad inválido. Intenta de nuevo.";
        header('Location: configuracion-editar.php');
        exit();
    }

    $nombre    = trim($_POST['nombre'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $telefono  = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $sucursal  = trim($_POST['sucursal'] ?? '');
    $puesto    = trim($_POST['puesto'] ?? '');

    // Validaciones básicas
    if ($nombre === '' || $email === '') {
        $_SESSION['error'] = "Nombre y correo son obligatorios.";
        header('Location: configuracion-editar.php');
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "El correo electrónico no es válido.";
        header('Location: configuracion-editar.php');
        exit();
    }

    $conn = getDBConnection();
    $sql = "UPDATE usuarios 
            SET nombre = ?, email = ?, telefono = ?, direccion = ?, sucursal = ?, puesto = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("❌ Error prepare configuracion-editar: " . $conn->error);
        $_SESSION['error'] = "Ocurrió un error al actualizar tu perfil.";
        header('Location: configuracion-editar.php');
        exit();
    }

    $stmt->bind_param(
        "ssssssi",
        $nombre,
        $email,
        $telefono,
        $direccion,
        $sucursal,
        $puesto,
        $usuario_id
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Perfil actualizado correctamente.";
        header('Location: configuracion.php');
        exit();
    } else {
        error_log("❌ Error execute configuracion-editar: " . $stmt->error);
        $_SESSION['error'] = "No se pudo guardar la información. Intenta más tarde.";
        header('Location: configuracion-editar.php');
        exit();
    }
}

$page_title = "Editar perfil";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - LumiSpace Admin</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-card {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            max-width: 800px;
            margin-top: 24px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
            gap: 16px 24px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: .875rem;
            font-weight: 600;
            color: #4b5563;
        }
        .form-input {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: .95rem;
            outline: none;
            transition: all .2s ease;
        }
        .form-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 1px rgba(79,70,229,.2);
        }
        .btn-row {
            margin-top: 24px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .btn-primary {
            background: #4f46e5;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background: #4338ca;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .9rem;
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
    </style>
</head>
<body>
<?php include '../includes/sidebar-admin.php'; ?>

<main class="main">
    <?php include '../includes/header-admin.php'; ?>

    <section class="content">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 24px; color:#6b7280; font-size:.875rem;">
            <a href="dashboard.php" style="color:#6b7280; text-decoration:none;">Dashboard</a>
            <span style="margin:0 8px;">/</span>
            <a href="configuracion.php" style="color:#6b7280; text-decoration:none;">Configuración</a>
            <span style="margin:0 8px;">/</span>
            <span style="color:#1f2937; font-weight:600;">Editar perfil</span>
        </nav>

        <div style="margin-bottom: 24px;">
            <h1 class="page-title" style="margin-bottom: 8px;">
                <i class="fas fa-user-edit"></i> Editar información personal
            </h1>
            <p class="page-subtitle">
                Actualiza tu nombre, correo y datos de contacto asociados a tu cuenta.
            </p>
        </div>

        <!-- Alertas -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="form-card">
            <form method="post" action="configuracion-editar.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="nombre">Nombre completo *</label>
                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            class="form-input"
                            required
                            value="<?= htmlspecialchars($admin['nombre'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Correo electrónico *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            required
                            value="<?= htmlspecialchars($admin['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input
                            type="text"
                            id="telefono"
                            name="telefono"
                            class="form-input"
                            value="<?= htmlspecialchars($admin['telefono'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sucursal">Sucursal</label>
                        <input
                            type="text"
                            id="sucursal"
                            name="sucursal"
                            class="form-input"
                            value="<?= htmlspecialchars($admin['sucursal'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="puesto">Puesto</label>
                        <input
                            type="text"
                            id="puesto"
                            name="puesto"
                            class="form-input"
                            value="<?= htmlspecialchars($admin['puesto'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label" for="direccion">Dirección</label>
                        <input
                            type="text"
                            id="direccion"
                            name="direccion"
                            class="form-input"
                            value="<?= htmlspecialchars($admin['direccion'] ?? '') ?>">
                    </div>
                </div>

                <div class="btn-row">
                    <a href="configuracion.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </section>
</main>
</body>
</html>
