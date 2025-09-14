<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$msg = "";
$alertClass = "";

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

if (!$user) {
    $msg = "❌ Token inválido o expirado. Solicita uno nuevo.";
    $alertClass = "error";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    if ($password === $password2 && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Actualizar contraseña y limpiar token
        $upd = $conn->prepare("UPDATE usuarios 
                               SET password=?, reset_token=NULL, reset_expira=NULL 
                               WHERE id=?");
        $upd->bind_param("si", $hash, $user["id"]);
        $upd->execute();

        $msg = "✅ Contraseña actualizada. Serás redirigido al login...";
        $alertClass = "success";

        // Redirigir al login después de 3 segundos
        header("Refresh: 3; URL=login.php");
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
    .toggle-pass { cursor:pointer; position:absolute; right:10px; top:36px; font-size:.9rem; color:#666; }
    .input-group { position:relative; }
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

      <?php if ($user): // ✅ solo mostrar formulario si el token es válido ?>
      <form method="POST">
        <div class="input-group">
          <input type="password" id="password" name="password" placeholder="Nueva contraseña" required minlength="6">
          <span class="icon">🔑</span>
          <span class="toggle-pass" onclick="togglePass('password')"></span>
        </div>
        <div class="input-group">
          <input type="password" id="password2" name="password2" placeholder="Confirmar contraseña" required minlength="6">
          <span class="icon">🔑</span>
          <span class="toggle-pass" onclick="togglePass('password2')"></span>
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
      input.type = input.type === "password" ? "text" : "password";
    }
  </script>
</body>
</html>
