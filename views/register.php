<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/../config/functions.php"; 
require_once __DIR__ . "/../config/mail.php";      
require_once __DIR__ . "/../login-google/config.php"; // $google_client

// Bloqueo si ya hay sesión
if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    $rol = strtolower($_SESSION['usuario_rol']);
    $rutas = [
        'admin'  => 'dashboard-admin.php',
        'gestor' => 'dashboard-gestor.php',
        'cajero' => 'dashboard-cajero.php',
    ];
    header("Location: " . ($rutas[$rol] ?? '../index.php'));
    exit();
}

// Helpers
function field(string $name): string {
    return htmlspecialchars($_POST[$name] ?? $_GET[$name] ?? '', ENT_QUOTES, 'UTF-8');
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = "";
$isGoogle = isset($_GET['google']) && $_GET['google'] === '1';
$prefillNombre = field('nombre');
$prefillEmail  = field('email');

// Límite de intentos
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['register_attempts'][$ip])) {
    $_SESSION['register_attempts'][$ip] = 0;
}
if ($_SESSION['register_attempts'][$ip] >= 5) {
    $error = "⚠️ Demasiados intentos de registro desde esta IP. Intenta más tarde.";
}

// Procesar
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
        $nombre    = trim($_POST['nombre'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $rol       = $isGoogle ? 'cajero' : trim($_POST['rol'] ?? ''); // por defecto cajero con Google
        $proveedor = $isGoogle ? 'google' : 'manual';

        // Validaciones
        if ($nombre === '' || $email === '' || (!$isGoogle && $password === '') || $rol === '') {
            $error = "Completa todos los campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo no es válido.";
        } elseif (!$isGoogle && strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif (obtenerUsuarioPorEmail($email)) {
            $error = "⚠️ Este correo ya está registrado. <a href='login.php'>Inicia sesión aquí</a>.";
        } else {
            // Registrar con proveedor
            $res = registrarUsuario($nombre, $email, $isGoogle ? null : $password, $rol);
            if ($res === false) {
                $error = "Error en el registro. Inténtalo de nuevo.";
            } else {
                // Correo de bienvenida
                $subject = "¡Bienvenido/a a LumiSpace!";
                $body = "
                    <div style='font-family:Arial,Helvetica,sans-serif;line-height:1.5'>
                      <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . " 👋</h2>
                      <p>Tu cuenta en <strong>LumiSpace POS</strong> ha sido creada correctamente.</p>
                      <p><strong>Correo:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
                      <p>Ya puedes iniciar sesión con tus credenciales.</p>
                    </div>
                ";
                enviarCorreo($email, $subject, $body);

                header("Location: login.php?ok=1");
                exit();
            }
        }
    }
    $_SESSION['register_attempts'][$ip]++;
}

// Google OAuth URL
$googleAuthUrl = '';
if (isset($google_client) && $google_client instanceof Google_Client) {
    $_SESSION['oauth2_state'] = bin2hex(random_bytes(16));
    $google_client->setState($_SESSION['oauth2_state']);
    $googleAuthUrl = htmlspecialchars($google_client->createAuthUrl(), ENT_QUOTES, 'UTF-8');
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
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .4s; }
    .info  { background:#eef6ff; color:#0b5394; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .4s; }
    .divider { display:flex; align-items:center; text-align:center; margin:1rem 0; }
    .divider::before, .divider::after { content:''; flex:1; border-bottom:1px solid #ccc; }
    .divider span { padding:0 .75rem; color:#666; font-size:.9rem; }
    .social-login { margin-top:1rem; }
    .social-btn { display:flex; align-items:center; justify-content:center; gap:10px; background:#fff; border:1px solid #ccc; padding:.6rem 1rem; border-radius:6px; text-decoration:none; color:#333; font-weight:500; transition:.2s; }
    .social-btn:hover { background:#f1f1f1; }
    .social-btn img { width:18px; height:18px; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-5px);} to{opacity:1; transform:translateY(0);} }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image" style="background: url('../images/pos-register.jpg') no-repeat center center/cover;"></div>

    <div class="auth-form">
      <h2><?= $isGoogle ? "Completar registro con Google" : "Crear cuenta" ?></h2>
      <p class="subtitle">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

      <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

        <div class="input-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" value="<?= $prefillNombre ?>" <?= $isGoogle ? "readonly" : "" ?> required>
          <i class="fa-solid fa-user icon"></i>
        </div>

        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" value="<?= $prefillEmail ?>" <?= $isGoogle ? "readonly" : "" ?> required>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <?php if (!$isGoogle): ?>
        <div class="input-group" style="position:relative">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required minlength="6">
          <i class="fa-solid fa-lock icon"></i>
          <span class="toggle-pass" onclick="togglePassword()"></span>
        </div>
        <?php endif; ?>

        <div class="input-group">
          <label for="rol">Tipo de cuenta</label>
          <select name="rol" <?= $isGoogle ? "disabled" : "" ?> required>
            <option value="">-- Selecciona un rol --</option>
            <option value="admin"   <?= (field('rol')==='admin'   ? 'selected' : '') ?>>Administrador</option>
            <option value="gestor"  <?= (field('rol')==='gestor'  ? 'selected' : '') ?>>Gestor</option>
            <option value="cajero"  <?= (field('rol')==='cajero'  ? 'selected' : '') ?>>Cajero</option>
          </select>
          <i class="fa-solid fa-user-shield icon"></i>
        </div>

        <button type="submit" class="btn-login"><?= $isGoogle ? "Finalizar Registro" : "Crear cuenta" ?></button>
      </form>

      <div class="divider"><span>o</span></div>

      <?php if ($googleAuthUrl && !$isGoogle): ?>
        <div class="social-login">
          <a href="<?= $googleAuthUrl ?>" class="social-btn google">
            <img src="../images/google-icon.png" alt="Google" />
            Registrarse con Google
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <script>
    function togglePassword(){
      const input = document.getElementById("password");
      input.type = input.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>
