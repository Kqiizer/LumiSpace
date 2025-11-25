<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$msg = "";
$alertClass = "";

// ⚠️ Validar que venga del login
if (!isset($_SESSION["last_login_email"])) {
    $msg = "⚠️ Primero intenta iniciar sesión antes de recuperar tu contraseña.";
    $alertClass = "error";
} else {
    $email = $_SESSION["last_login_email"];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $conn = getDBConnection();
        $sql  = "SELECT id, email FROM usuarios WHERE email=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
$token  = bin2hex(random_bytes(32));
$expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

$upd = $conn->prepare("UPDATE usuarios SET reset_token=?, reset_expira=? WHERE id=?");
$upd->bind_param("ssi", $token, $expira, $user["id"]);
$upd->execute();

// 🔥 FORZAR URL DEL HOSTING — YA NO USAMOS LOCALHOST NI BASE_URL
$resetLink = "https://lumispace.shop/views/reset.php?token=" . urlencode($token);

$subject = "Restablecer contraseña";
$body = "
    Hola,<br>
    Haz clic en el siguiente enlace para restablecer tu contraseña:<br><br>
    <a href='{$resetLink}'>{$resetLink}</a><br><br>
    Este enlace caduca en 1 hora.
";


            if (enviarCorreo($user["email"], $subject, $body)) {
                $msg = "✅ Hemos enviado un enlace de recuperación a tu correo registrado.";
                $alertClass = "success";
            } else {
                $msg = "❌ Error al enviar el correo.";
                $alertClass = "error";
            }

        } else {
            $msg = "⚠️ Ese correo no está registrado.";
            $alertClass = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Olvidé mi contraseña</title>
  <link rel="stylesheet" href="../css/auth.css">
  <style>
    .error { background:#ffe6e6; color:#b10000; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .3s; }
    .success { background:#e6ffe6; color:#006400; padding:.75rem 1rem; border-radius:.5rem; margin:.5rem 0; animation:fadeIn .3s; }
    @keyframes fadeIn { from{opacity:0; transform:translateY(-5px);} to{opacity:1; transform:translateY(0);} }
  </style>
</head>
<body>
  <div class="auth-wrapper">
        <div class="auth-image" style="background: url('../images/pos-logi.jpg') no-repeat center center/cover;"></div>

    <div class="auth-form">
      <h2>🔑 Recuperar contraseña</h2>
      <p class="subtitle">Haz clic en el botón para enviarte un enlace de recuperación</p>

      <?php if ($msg): ?>
        <div class="<?= $alertClass; ?>"><?= $msg; ?></div>
      <?php endif; ?>

      <?php if (isset($_SESSION["last_login_email"])): ?>
      <form method="POST">
        <button type="submit" class="btn-login">Enviar enlace</button>
      </form>
      <?php endif; ?>

      <div class="form-options">
        <a href="login.php">← Volver al login</a>
      </div>
    </div>
  </div>
</body>
</html>
