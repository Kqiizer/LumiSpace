<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/../config/functions.php";     // conexión + helpers BD
require_once __DIR__ . "/../login-google/config.php";  // $google_client desde .env

$error = "";
$okMsg = "";

// URL de Google (si está configurado)
$googleAuthUrl = "";
if (isset($google_client) && $google_client instanceof Google_Client) {
    $googleAuthUrl = function_exists('getGoogleAuthUrl')
        ? getGoogleAuthUrl($google_client)
        : $google_client->createAuthUrl();
}

// Sanitizar campo
function field(string $name): string {
    return htmlspecialchars($_POST[$name] ?? '', ENT_QUOTES, 'UTF-8');
}

/* =========================
   PROCESAR FORMULARIO
   ========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isGoogleRegister  = isset($_POST['google_register']);
    $isClassicRegister = isset($_POST['classic_register']);

    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol      = trim($_POST['rol'] ?? '');

    if ($isGoogleRegister) {
        // --- Registro con Google → siempre rol usuario ---
        $rol = "usuario";

        if (!$googleAuthUrl) {
            $error = "Google no está configurado. Revisa login-google/config.php.";
        } else {
            $_SESSION['registro_rol'] = $rol; // guarda rol para callback
            header("Location: " . $googleAuthUrl);
            exit();
        }

    } elseif ($isClassicRegister) {
        // --- Registro clásico ---
        if ($nombre === '' || $email === '' || $password === '' || $rol === '') {
            $error = "Completa todos los campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo no es válido.";
        } else {
            $existe = obtenerUsuarioPorEmail($email);
            if ($existe) {
                $error = "Este correo ya está registrado. Intenta iniciar sesión.";
            } else {
                if (!function_exists('registrarUsuario')) {
                    $error = "No se encontró la función registrarUsuario en config/functions.php";
                } else {
                    $res = registrarUsuario($nombre, $email, $password, $rol);
                    if ($res === false) {
                        $error = "Error en el registro. Inténtalo de nuevo.";
                    } else {
                        header("Location: login.php?success=1");
                        exit();
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - POS</title>
  <link rel="stylesheet" href="../css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; }
    .info  { background:#eef6ff; color:#0b5394; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; }
    .social-btn[disabled] { opacity:.6; cursor:not-allowed; }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <!-- Columna izquierda -->
    <div class="auth-image" style="background: url('../images/pos-register.jpg') no-repeat center center/cover;"></div>

    <!-- Columna derecha -->
    <div class="auth-form">
      <h2>Crear cuenta</h2>
      <p class="subtitle">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

      <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php endif; ?>

      <form method="POST" novalidate>
        <!-- Nombre -->
        <div class="input-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" value="<?= field('nombre'); ?>" required>
          <i class="fa-solid fa-user icon"></i>
        </div>

        <!-- Email -->
        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" value="<?= field('email'); ?>" required>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <!-- Password -->
        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" name="password" required>
          <i class="fa-solid fa-lock icon"></i>
        </div>

        <!-- Rol SOLO para clásico -->
        <div class="input-group">
          <label for="rol">Tipo de cuenta</label>
          <select name="rol" required>
            <option value="">-- Selecciona un rol --</option>
            <option value="admin"   <?= (field('rol')==='admin'   ? 'selected' : '') ?>>Administrador</option>
            <option value="usuario" <?= (field('rol')==='usuario' ? 'selected' : '') ?>>Usuario</option>
            <option value="dueno"   <?= (field('rol')==='dueno'   ? 'selected' : '') ?>>Dueño del POS</option>
            <option value="gestor"  <?= (field('rol')==='gestor'  ? 'selected' : '') ?>>Gestor</option>
          </select>
          <i class="fa-solid fa-user-shield icon"></i>
        </div>

        <!-- Botón registro clásico -->
        <button type="submit" name="classic_register" class="btn-login">Crear cuenta</button>

        <div class="divider"><span>o</span></div>

        <!-- Google (solo si está configurado) -->
        <div class="social-login">
          <button type="submit" name="google_register" class="social-btn google" <?= $googleAuthUrl ? '' : 'disabled' ?>>
            <img src="../images/google-icon.png" alt="Google"> Registrar con Google (Usuario)
          </button>
        </div>
      </form>

      <?php if (!$googleAuthUrl): ?>
        <div class="info" style="margin-top:12px;">
          Google no está configurado. Revisa <code>login-google/config.php</code>.
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
