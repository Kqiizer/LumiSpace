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
        'gestor'  => '../pos/pos.php',
        'cajero'  => 'pos.php',
        'usuario' => 'index.php'
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
        case 'gestor':  header("Location: ../pos/pos.php"); break;
        case 'cajero':  header("Location: pos.php"); break;
        case 'usuario': header("Location: index.php"); break;
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
    $acepto   = isset($_POST['acepto']); // 🔒 NUEVO: campo de aceptación

    if (!$acepto) {
        $error = "⚠️ Debes aceptar los Términos y Condiciones antes de continuar.";
    } elseif ($emailVal !== '' && $password !== '') {
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
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/auth.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body, input, button, label, a, p, h2 { font-family: "Poppins", sans-serif; }
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
    .terms-check {
      margin-top: 0.8rem;
      font-size: 0.85rem;
      color: #555;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .terms-check .legal-link {
      color: #0b5394;
      text-decoration: underline;
      cursor: pointer;
      background: none;
      border: none;
      padding: 0;
      font: inherit;
    }
    .legal-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 20px;
    }
    .legal-modal.open {
      display: flex;
    }
    .legal-modal__content {
      background: #fff;
      width: min(900px, 100%);
      max-height: 90vh;
      border-radius: 18px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 80px rgba(0,0,0,0.25);
    }
    .legal-modal__header {
      padding: 18px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    .legal-modal__header h3 {
      margin: 0;
      font-size: 1rem;
    }
    .legal-modal__close {
      border: none;
      background: none;
      font-size: 1.4rem;
      cursor: pointer;
      line-height: 1;
      color: #444;
    }
    .legal-modal__frame {
      flex: 1;
      border: none;
      width: 100%;
      background: #f5f5f5;
    }
    .legal-modal__footer {
      padding: 14px 24px;
      border-top: 1px solid rgba(0,0,0,0.05);
      text-align: right;
    }
    .legal-modal__exit {
      background: #8b7355;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 10px 18px;
      cursor: pointer;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image" style="background: url('../images/pos-logi.jpg') no-repeat center center/cover;"></div>

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

      <form method="POST" onsubmit="return validarTerminos();" novalidate>
        <div class="input-group" style="position:relative">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" value="<?= htmlspecialchars($emailVal) ?>" required autocomplete="email" autofocus/>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <div class="input-group" style="position:relative">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" />
          <i class="fa-solid fa-lock icon"></i>
          <span class="toggle-pass" onclick="togglePassword()"><i class="fa fa-eye"></i></span>
        </div>

        <!-- 🔒 NUEVO: Checkbox obligatorio -->
        <div class="terms-check">
          <input type="checkbox" id="acepto" name="acepto" required>
          <label for="acepto">
            Acepto los 
          <a href="../docs/terminos-condiciones.html" class="legal-link" target="_blank">
    Términos y Condiciones
</a>
y la
<a href="../docs/politica-privacidad.html" class="legal-link" target="_blank">
    Política de Privacidad
</a>

          </label>
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

    // 🔒 Validar aceptación antes de enviar
    function validarTerminos(){
      const checkbox = document.getElementById("acepto");
      if(!checkbox.checked){
        alert("Debes aceptar los Términos y Condiciones antes de continuar.");
        return false;
      }
      return true;
    }

    (function(){
      const modal = document.getElementById('legalModal');
      const frame = document.getElementById('legalModalFrame');
      const title = document.getElementById('legalModalTitle');
      if (!modal || !frame || !title) return;

      const DOCS = {
        terms: {
          url: '../docs/terminos-condiciones.html',
          title: 'Términos y Condiciones'
        },
        privacy: {
          url: '../docs/politica-privacidad.html',
          title: 'Política de Privacidad'
        }
      };

      const openModal = (key) => {
        const doc = DOCS[key] || DOCS.terms;
        frame.src = doc.url;
        title.textContent = doc.title;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      };

      const closeModal = () => {
        modal.classList.remove('open');
        frame.src = '';
        document.body.style.overflow = '';
      };

      document.querySelectorAll('[data-legal-doc]').forEach(link => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          openModal(link.dataset.legalDoc);
        });
      });

      modal.querySelectorAll('[data-legal-close]').forEach(btn => {
        btn.addEventListener('click', closeModal);
      });

      modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
          closeModal();
        }
      });
    })();
  </script>

  <div class="legal-modal" id="legalModal" aria-hidden="true">
    <div class="legal-modal__content">
      <div class="legal-modal__header">
        <h3 id="legalModalTitle">Documento legal</h3>
        <button type="button" class="legal-modal__close" data-legal-close>&times;</button>
      </div>
      <iframe class="legal-modal__frame" id="legalModalFrame" title="Documento legal"></iframe>
      <div class="legal-modal__footer">
        <button type="button" class="legal-modal__exit" data-legal-close>Salir</button>
      </div>
    </div>
  </div>
</body>
</html>
