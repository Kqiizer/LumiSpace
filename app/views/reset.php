<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$msg = "";
$alertClass = "";

$conn = getDBConnection();
$token = $_GET["token"] ?? "";

// Validar token
$sql = "SELECT id FROM usuarios WHERE reset_token=? AND reset_expira > NOW() LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("<p class='error'>❌ Token inválido o expirado.</p>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    if ($password === $password2 && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Actualizar contraseña y limpiar token
        $upd = $conn->prepare("UPDATE usuarios SET password=?, reset_token=NULL, reset_expira=NULL WHERE id=?");
        $upd->bind_param("si", $hash, $user["id"]);
        $upd->execute();

        $msg = "✅ Contraseña actualizada. Ahora puedes iniciar sesión.";
        $alertClass = "success";
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

      <form method="POST">
        <div class="input-group">
          <input type="password" name="password" placeholder="Nueva contraseña" required>
          <span class="icon">🔑</span>
        </div>
        <div class="input-group">
          <input type="password" name="password2" placeholder="Confirmar contraseña" required>
          <span class="icon">🔑</span>
        </div>
        <button type="submit" class="btn-login">Actualizar</button>
      </form>

      <div class="form-options">
        <a href="login.php">← Volver al login</a>
      </div>
    </div>
  </div>
</body>
</html>
