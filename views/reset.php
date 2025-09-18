<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$msg = "";
$alertClass = "";
$showForm = false;

$conn  = getDBConnection();
$token = $_GET["token"] ?? "";

// ✅ Validar que el token exista en la URL
if ($token === "") {
    header("Location: forgot.php?error=missing_token");
    exit();
}

// Buscar usuario con ese token válido
$sql  = "SELECT id FROM usuarios WHERE reset_token=? AND reset_expira > NOW() LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($user) {
    $showForm = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $user) {
    $password  = trim($_POST["password"] ?? "");
    $password2 = trim($_POST["password2"] ?? "");

    if ($password === $password2 && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Actualizar contraseña y limpiar token
        $upd = $conn->prepare("UPDATE usuarios 
                               SET password=?, reset_token=NULL, reset_expira=NULL 
                               WHERE id=?");
        $upd->bind_param("si", $hash, $user["id"]);
        if ($upd->execute()) {
            // ✅ Redirigir inmediatamente al login
            header("Location: login.php?success=password_updated");
            exit();
        } else {
            $msg = "❌ Error al actualizar. Intenta de nuevo.";
            $alertClass = "error";
        }
    } else {
        $msg = "❌ Las contraseñas no coinciden o son muy cortas (mínimo 6 caracteres).";
        $alertClass = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Restablecer contraseña</title>
  <link rel="stylesheet" href="../css/auth.css">
  <style>
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .3s; }
    .success { background:#e6ffe6; color:#006400; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .3s; }
    .input-group { margin-bottom: 1rem; }
    .input-wrapper { position: relative; display: flex; align-items: center; }
    .input-wrapper input { flex: 1; padding: .6rem; border: 1px solid #444; border-radius: 6px; background: #1e1e1e; color: #fff; }
    .toggle-pass { margin-left: .5rem; font-size: .85rem; background: none; border: none; color: #ccc; cursor: pointer; }
    .toggle-pass:hover { color: #fff; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-5px);} to{opacity:1; transform:translateY(0);} }
  </style>
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image"></div>
    <div class="auth-form">
      <h2>🔒 Restablecer contraseña</h2>
      <p class="subtitle">Ingresa tu nueva contraseña</p>

      <?php if ($msg): ?>
        <div class="<?= $alertClass; ?>"><?= $msg; ?></div>
      <?php endif; ?>

      <?php if ($showForm): ?>
      <form method="POST">
        <div class="input-group">
          <label for="password">Nueva contraseña</label>
          <div class="input-wrapper">
            <input type="password" id="password" name="password" required minlength="6">
            <button type="button" class="toggle-pass" onclick="togglePass('password')">Mostrar</button>
          </div>
        </div>
        <div class="input-group">
          <label for="password2">Confirmar contraseña</label>
          <div class="input-wrapper">
            <input type="password" id="password2" name="password2" required minlength="6">
            <button type="button" class="toggle-pass" onclick="togglePass('password2')">Mostrar</button>
          </div>
        </div>
        <button type="submit" class="btn-login">Actualizar</button>
      </form>
      <?php else: ?>
        <p class="error">⚠️ El enlace no es válido o ha expirado. <a href="forgot.php">Solicita uno nuevo</a>.</p>
      <?php endif; ?>

      <div class="form-options">
        <a href="login.php">← Volver al login</a>
      </div>
    </div>
  </div>

  <script>
    function togglePass(id){
      const input = document.getElementById(id);
      const btn = input.parentElement.querySelector(".toggle-pass");
      if (input.type === "password") {
        input.type = "text";
        btn.textContent = "Ocultar";
      } else {
        input.type = "password";
        btn.textContent = "Mostrar";
      }
    }
  </script>
</body>
</html>
