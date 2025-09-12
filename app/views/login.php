<?php
declare(strict_types=1);
define('PUBLIC_ROUTE', true);
session_start();

/* =========================
   EVITAR CACHE DEL LOGIN
   ========================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   SI YA HAY SESIÓN → REDIRIGE SEGÚN ROL
   ========================= */
if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    $rol = strtolower($_SESSION['usuario_rol']);
    switch ($rol) {
        case 'admin':
            header("Location: dashboard-admin.php");
            break;
        case 'gestor':
            header("Location: dashboard-gestor.php");
            break;
        case 'dueno':
        case 'dueño':
            header("Location: dashboard-dueno.php");
            break;
        default:
            header("Location: ../index.php");
    }
    exit();
}

/* =========================
   CARGAR VARIABLES .ENV
   ========================= */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

/* =========================
   INCLUDES
   ========================= */
require_once __DIR__ . "/../config/functions.php";
require_once __DIR__ . "/../login-google/config.php"; // define $google_client usando credenciales de .env

/* =========================
   HELPERS
   ========================= */
function norm_email(string $e): string { return strtolower(trim($e)); }
function normalizarRol(?string $rol): string {
  $rol = strtolower(trim((string)$rol));
  $map = [
    'admin'   => 'admin',
    'gestor'  => 'gestor', 'manager' => 'gestor',
    'dueno'   => 'dueno', 'dueño' => 'dueno', 'owner' => 'dueno',
    'usuario' => 'usuario', 'user' => 'usuario', 'cliente' => 'usuario',
  ];
  return $map[$rol] ?? 'usuario';
}

/* =========================
   LOGIN CLÁSICO
   ========================= */
$error    = "";
$intentos = $_SESSION['login_intentos'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email    = norm_email((string)($_POST['email'] ?? ''));
  $password = (string)($_POST['password'] ?? '');

  if ($email !== '' && $password !== '') {
    try {
      $user = obtenerUsuarioPorEmail($email);
      if ($user && !empty($user['password']) && password_verify($password, (string)$user['password'])) {
        
        $rol = normalizarRol($user['rol'] ?? 'usuario');

        session_regenerate_id(true);
        $_SESSION['usuario_id']     = (int)$user['id'];
        $_SESSION['usuario_nombre'] = (string)$user['nombre'];
        $_SESSION['usuario_rol']    = $rol;
        $_SESSION['login_intentos'] = 0;

        // ✅ Redirigir según rol
        switch ($rol) {
            case 'admin':
                header("Location: dashboard-admin.php");
                break;
            case 'gestor':
                header("Location: dashboard-gestor.php");
                break;
            case 'dueno':
            case 'dueño':
                header("Location: dashboard-dueno.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
      } else {
        $intentos++;
        $_SESSION['login_intentos'] = $intentos;
        $error = "Correo o contraseña incorrectos.";
      }
    } catch (Throwable $e) {
      $error = "Error al autenticar.";
    }
  } else {
    $error = "Completa correo y contraseña.";
  }
}

/* =========================
   BOTÓN DE GOOGLE
   ========================= */
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - POS</title>
  <link rel="stylesheet" href="../css/auth.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image"></div>

    <div class="auth-form">
      <h2>Iniciar Sesión</h2>
      <p class="subtitle">¿No tienes cuenta? <a href="register.php">Crear ahora</a></p>

      <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($intentos >= 1): ?>
          <p class="info"><a href="forgot.php">¿Olvidaste tu contraseña?</a></p>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'no_registrado'): ?>
        <p class="error">Tu cuenta de Google no está registrada. 
          <a href="register.php?email=<?= urlencode($_GET['email'] ?? '') ?>&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>">Regístrate aquí</a>
        </p>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" required autocomplete="email" />
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" name="password" required autocomplete="current-password" />
          <i class="fa-solid fa-lock icon"></i>
        </div>

        <button type="submit" class="btn-login">Entrar</button>
      </form>

      <div class="divider"><span>o</span></div>

      <?php if ($googleAuthUrl && !isset($_SESSION['usuario_id'])): ?>
        <div class="social-login">
          <a href="<?= $googleAuthUrl ?>" class="social-btn google">
            <img src="../images/google-icon.png" alt="Google" width="18" height="18" />
            Iniciar con Google
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
