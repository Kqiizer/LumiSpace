<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . "/../config/functions.php"; 
require_once __DIR__ . "/../config/mail.php";      
require_once __DIR__ . "/../login-google/config.php"; // $google_client

// 🚨 Bloqueo si ya hay sesión
if (isset($_SESSION['usuario_id'], $_SESSION['usuario_rol'])) {
    header("Location: ../index.php");
    exit();
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$error = "";
$isGoogle = isset($_GET['google']) && $_GET['google'] === '1';

// ===============================
// 🚀 Registro automático con Google
// ===============================
if ($isGoogle && isset($_SESSION['google_user'])) {
    $googleUser = $_SESSION['google_user']; 
    $nombre = trim($googleUser['name'] ?? '');
    $email  = strtolower(trim($googleUser['email'] ?? ''));
    $rol    = "usuario";

    if ($nombre !== '' && $email !== '') {
        $user = obtenerUsuarioPorEmail($email);

        if ($user) {
            $_SESSION['usuario_id']     = $user['id'];
            $_SESSION['usuario_rol']    = $user['rol'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_email']  = $user['email'];
        } else {
            $res = registrarUsuario($nombre, $email, null, $rol);
            if ($res !== false) {
                $_SESSION['usuario_id']     = $res;
                $_SESSION['usuario_rol']    = $rol;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_email']  = $email;

                $subject = "¡Bienvenido/a a LumiSpace!";
                $body = "
                    <div style='font-family:Arial,Helvetica,sans-serif;line-height:1.5'>
                      <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . " 👋</h2>
                      <p>Tu cuenta en <strong>LumiSpace POS</strong> ha sido creada con Google.</p>
                      <p><strong>Correo:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>
                      <p>Ya puedes empezar a usar el sistema.</p>
                    </div>
                ";
                enviarCorreo($email, $subject, $body);
            } else {
                $error = "❌ No se pudo registrar con Google.";
            }
        }

        if (empty($error)) {
            header("Location: ../index.php");
            exit();
        }
    } else {
        $error = "❌ Datos inválidos recibidos de Google.";
    }
}

// Helpers
function field(string $name): string {
    return htmlspecialchars($_POST[$name] ?? $_GET[$name] ?? '', ENT_QUOTES, 'UTF-8');
}

$prefillNombre = field('nombre');
$prefillEmail  = field('email');

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!isset($_SESSION['register_attempts'][$ip])) {
    $_SESSION['register_attempts'][$ip] = 0;
}
if ($_SESSION['register_attempts'][$ip] >= 5) {
    $error = "⚠️ Demasiados intentos de registro desde esta IP. Intenta más tarde.";
}

// Procesar registro manual
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error && !$isGoogle) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Solicitud inválida.";
    } else {
        $nombre    = trim($_POST['nombre'] ?? '');
        $email     = strtolower(trim($_POST['email'] ?? ''));
        $password  = (string)($_POST['password'] ?? '');
        $rol       = "usuario"; 
        $acepto    = isset($_POST['acepto']); // 🔒 NUEVO

        if (!$acepto) {
            $error = "⚠️ Debes aceptar los Términos y Condiciones antes de registrarte.";
        } elseif ($nombre === '' || $email === '' || $password === '') {
            $error = "Completa todos los campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo no es válido.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif (obtenerUsuarioPorEmail($email)) {
            $error = "⚠️ Este correo ya está registrado. <a href='login.php'>Inicia sesión aquí</a>.";
        } else {
            $res = registrarUsuario($nombre, $email, $password, $rol);
            if ($res !== false) {
                $_SESSION['usuario_id']     = $res;
                $_SESSION['usuario_rol']    = $rol;
                $_SESSION['usuario_nombre'] = $nombre;
                $_SESSION['usuario_email']  = $email;
                header("Location: ../index.php");
                exit();
            } else {
                $error = "❌ Error en el registro.";
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/auth.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body, input, button, label, a, p, h2 { font-family: "Poppins", sans-serif; }
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .4s; }
    .divider { display:flex; align-items:center; text-align:center; margin:1rem 0; }
    .divider::before, .divider::after { content:''; flex:1; border-bottom:1px solid #ccc; }
    .divider span { padding:0 .75rem; color:#666; font-size:.9rem; }
    .social-login { margin-top:1rem; }
    .social-btn { display:flex; align-items:center; justify-content:center; gap:10px; background:#fff; border:1px solid #ccc; padding:.6rem 1rem; border-radius:6px; text-decoration:none; color:#333; font-weight:500; transition:.2s; }
    .social-btn:hover { background:#f1f1f1; }
    .social-btn img { width:18px; height:18px; }
    .terms-check {
      margin-top: .8rem;
      font-size: .85rem;
      color: #555;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .terms-check .legal-link {
      color: #0b5394;
      text-decoration: underline;
      cursor: pointer;
      border: none;
      background: none;
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
      <h2>Crear cuenta</h2>
      <p class="subtitle">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

      <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
      <?php endif; ?>

      <?php if (!$isGoogle): ?>
      <form method="POST" onsubmit="return validarTerminos();" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="rol" value="usuario">

        <div class="input-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" value="<?= $prefillNombre ?>" required>
          <i class="fa-solid fa-user icon"></i>
        </div>

        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" value="<?= $prefillEmail ?>" required>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <div class="input-group" style="position:relative">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required minlength="6">
          <i class="fa-solid fa-lock icon"></i>
        </div>

        <!-- 🔒 NUEVO: Aceptar términos -->
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

        <button type="submit" class="btn-login">Crear cuenta</button>
      </form>
      <?php endif; ?>

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
    // 🔒 Evita envío si no acepta
    function validarTerminos(){
      const check = document.getElementById("acepto");
      if(!check.checked){
        alert("Debes aceptar los Términos y Condiciones antes de registrarte.");
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
