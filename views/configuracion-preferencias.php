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

// ✅ Obtener usuario
$admin = getUsuarioPorId($usuario_id);
if (!$admin) {
    $_SESSION['error'] = "No se pudo cargar la información del usuario.";
    header('Location: configuracion.php');
    exit();
}

// ✅ Si envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idioma = $_POST['idioma'] ?? 'es-MX';

    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE usuarios SET idioma=? WHERE id=?");
    $stmt->bind_param("si", $idioma, $usuario_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Idioma actualizado correctamente.";
    } else {
        $_SESSION['error'] = "Error al actualizar el idioma.";
    }

    header("Location: configuracion-preferencias.php");
    exit();
}

$page_title = "Preferencias";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preferencias - LumiSpace</title>
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>

<?php include '../includes/sidebar-admin.php'; ?>
<main class="main">
<?php include '../includes/header-admin.php'; ?>

<section class="content">

    <h1 class="page-title"><i class="fas fa-sliders-h"></i> Preferencias del Sistema</h1>

    <?php if (isset($_SESSION['success'])): ?>
        <p style="background:#d1fae5;padding:10px;border-radius:6px;color:#065f46;">
            ✅ <?= $_SESSION['success'] ?>
        </p>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <p style="background:#fee2e2;padding:10px;border-radius:6px;color:#991b1b;">
            ❌ <?= $_SESSION['error'] ?>
        </p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST" style="margin-top:20px;">
        <label style="font-weight:600;">Idioma de la interfaz:</label>
        <select name="idioma" style="padding:10px;border-radius:6px;margin-top:6px;">
            <option value="es-MX" <?= $admin['idioma'] === 'es-MX' ? 'selected' : '' ?>>Español (México)</option>
            <option value="en-US" <?= $admin['idioma'] === 'en-US' ? 'selected' : '' ?>>Inglés (Estados Unidos)</option>
        </select>

        <button type="submit" style="margin-top:15px;padding:10px 20px;background:#4f46e5;color:#fff;border:none;border-radius:6px;cursor:pointer;">
            Guardar cambios
        </button>
    </form>

</section>
</main>

</body>
</html>
