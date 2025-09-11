<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";
require_once __DIR__ . "/../config/mail.php";

$msg = "";
$alertClass = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $msg = "⚠️ Ingresa un correo válido.";
        $alertClass = "error";
    } else {
        $conn = getDBConnection();

        // Buscar usuario por correo
        $sql = "SELECT id FROM usuarios WHERE email=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Generar token
            $token  = bin2hex(random_bytes(32));
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Guardar en BD
            $upd = $conn->prepare("UPDATE usuarios SET reset_token=?, reset_expira=? WHERE id=?");
            $upd->bind_param("ssi", $token, $expira, $user["id"]);
            $upd->execute();

            // Construir enlace
            $baseUrl = getenv("BASE_URL") ?: "http://localhost/LumiSpace";
            $link = $baseUrl . "/views/reset.php?token=" . urlencode($token);

            // Enviar correo con helper
            $body = "
                <h3>Recuperar contraseña</h3>
                <p>Haz clic en el siguiente enlace para restablecer tu contraseña:</p>
                <p><a href='$link'>$link</a></p>
                <p>Este enlace expira en 1 hora.</p>
            ";

            $sent = enviarCorreo($email, "Recuperación de contraseña", $body);

            if ($sent !== true) {
                error_log($sent);
            }
        }

        // Siempre mostrar mensaje genérico
        $msg = "📧 Si el correo existe, recibirás un enlace para restablecer tu contraseña.";
        $alertClass = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Olvidé mi contraseña</title>
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
  <div class="auth-wrapper">
    <div class="auth-image"></div>
    <div class="auth-form">
      <h2>🔑 Recuperar contraseña</h2>
      <p class="subtitle">Ingresa tu correo y te enviaremos un enlace de recuperación</p>

      <?php if ($msg): ?>
        <div class="<?= $alertClass; ?>"><?= $msg; ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="input-group">
          <input type="email" name="email" placeholder="Correo electrónico" required>
          <span class="icon">📧</span>
        </div>
        <button type="submit" class="btn-login">Enviar enlace</button>
      </form>

      <div class="form-options">
        <a href="login.php">← Volver al login</a>
      </div>
    </div>
  </div>
</body>
</html>
