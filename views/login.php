<?php
declare(strict_types=1);
define('PUBLIC_ROUTE', true);
session_start();

/* =========================
   🚨 EVITAR CACHE
   ========================= */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   🚨 SI YA HAY SESIÓN → DASHBOARD
   ========================= */
if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    $rol = strtolower($_SESSION['usuario_rol']);
    $rutas = [
        'admin'   => 'dashboard-admin.php',
        'gestor'  => 'dashboard-gestor.php',
        'cajero'  => 'pos.php',
        'usuario' => 'dashboard-usuario.php'
    ];
    header("Location: " . ($rutas[$rol] ?? '../index.php'));
    exit();
}

/* =========================
   CARGAR .ENV
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
require_once __DIR__ . "/../login-google/config.php";

/* =========================
   HELPERS
   ========================= */
function norm_email(string $e): string { return strtolower(trim($e)); }
function normalizarRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    $map = [
        'admin'   => 'admin',
        'gestor'  => 'gestor', 'manager' => 'gestor',
        'cajero'  => 'cajero', 'cashier' => 'cajero',
        'usuario' => 'usuario', 'user' => 'usuario', 'cliente' => 'usuario',
    ];
    return $map[$rol] ?? 'usuario';
}
function redirSegunRol(string $rol): never {
    switch ($rol) {
        case 'admin':   header("Location: dashboard-admin.php"); break;
        case 'gestor':  header("Location: dashboard-gestor.php"); break;
        case 'cajero':  header("Location: pos.php"); break;
        case 'usuario': header("Location: dashboard-usuario.php"); break;
        default:        header("Location: ../index.php");
    }
    exit();
}

/* =========================
   LOGIN CLÁSICO
   ========================= */
$error    = "";
$extraMsg = "";
$emailVal = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailVal = norm_email((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($emailVal !== '' && $password !== '') {
        try {
            $user = obtenerUsuarioPorEmail($emailVal);

            if (!$user) {
                $error = "❌ No estás registrado.";
                $extraMsg = "<a href='register.php'>Regístrate aquí</a>";
                $_SESSION['last_login_email'] = $emailVal;
            } elseif (!empty($user['password']) && password_verify($password, (string)$user['password'])) {
                $rol = normalizarRol($user['rol'] ?? 'usuario');
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = (int)$user['id'];
                $_SESSION['usuario_nombre'] = (string)$user['nombre'];
                $_SESSION['usuario_rol']    = $rol;
                unset($_SESSION['last_login_email']);
                redirSegunRol($rol);
            } else {
                $error = "❌ Contraseña incorrecta.";
                $extraMsg = "<a href='forgot.php'>¿Olvidaste tu contraseña?</a>";
                $_SESSION['last_login_email'] = $emailVal;
            }
        } catch (Throwable $e) {
            $error = "⚠️ Error al autenticar.";
        }
    } else {
        $error = "Completa correo y contraseña.";
    }
}

/* =========================
   BOTÓN GOOGLE
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
  <style>
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation: fadeIn .5s; }
    .info  { background:#eef6ff; color:#0b5394; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation: fadeIn .5s; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-5px);} to{opacity:1; transform:translateY(0);} }
    .toggle-pass { cursor:pointer; position:absolute; right:12px; top:38px; color:#666; font-size:.9rem; }
    .back-arrow {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .95rem; margin-bottom: 1rem;
      text-decoration: none; color: #555; transition: all .3s;
    }
    .back-arrow:hover { color:#0b5394; transform: translateX(-3px); }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image"></div>
    <div class="auth-form">

      <a href="../index.php" class="back-arrow">
        <i class="fa-solid fa-arrow-left"></i> Volver al inicio
      </a>

      <h2>Iniciar Sesión</h2>
      <p class="subtitle">¿No tienes cuenta? <a href="register.php">Crear ahora</a></p>

      <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?> <?= $extraMsg ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['error']) && $_GET['error'] === 'no_registrado'): ?>
        <div class="error">
          ⚠️ Tu cuenta de Google no está registrada. 
          <a href="register.php?email=<?= urlencode($_GET['email'] ?? '') ?>&nombre=<?= urlencode($_GET['nombre'] ?? '') ?>">Regístrate aquí</a>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate>
        <div class="input-group" style="position:relative">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" value="<?= htmlspecialchars($emailVal) ?>" required autocomplete="email" autofocus/>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <div class="input-group" style="position:relative">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" />
          <i class="fa-solid fa-lock icon"></i>
          <span class="toggle-pass" onclick="togglePassword()"><i class=></i></span>
        </div>

        <button type="submit" class="btn-login">Entrar</button>
      </form>

      <div class="divider"><span>o</span></div>

      <?php if ($googleAuthUrl): ?>
        <div class="social-login">
          <a href="<?= $googleAuthUrl ?>" class="social-btn google">
            <img src="../images/google-icon.png" alt="Google" width="18" height="18" />
            Iniciar con Google
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function togglePassword(){
      const input = document.getElementById("password");
      const toggle = document.querySelector(".toggle-pass i");
      if(input.type === "password"){
        input.type = "text";
        toggle.classList.remove("fa-eye");
        toggle.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        toggle.classList.remove("fa-eye-slash");
        toggle.classList.add("fa-eye");
      }
    }
  </script>
</body>
</html>
