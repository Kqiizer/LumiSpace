<?php
session_start();
include("../config/functions.php"); // conexión + funciones

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    $user = obtenerUsuarioPorEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['usuario_id']     = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];
        $_SESSION['usuario_rol']    = $user['rol'];

        // Redirigir según rol
        $rutas = [
            'admin'   => "dashboard-admin.php",
            'usuario' => "dashboard-usuario.php",
            'dueno'   => "dashboard-dueno.php"
        ];

        header("Location: " . ($rutas[$user['rol']] ?? "login.php?error=rol"));
        exit();
    } else {
        $error = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - POS</title>
  <link rel="stylesheet" href="../css/auth.css">
  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="auth-wrapper">
    <!-- Columna izquierda -->
    <div class="auth-image"></div>

    <!-- Columna derecha -->
    <div class="auth-form">
      <h2>Iniciar Sesión</h2>
      <p class="subtitle">¿No tienes cuenta? <a href="register.php">Crear ahora</a></p>

      <?php if (!empty($error)): ?>
        <p class="error"><?= $error; ?></p>
      <?php endif; ?>

      <form method="POST">
        <!-- Campo email -->
        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" required>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <!-- Campo contraseña -->
        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" name="password" required>
          <i class="fa-solid fa-lock icon"></i>
        </div>

        <!-- Botón login -->
      

      <!-- Divisor -->
      <div class="divider"><span>o</span></div>

      <!-- Social login -->
      <div class="social-login">
        <!-- AQUÍ VAN LOS LINKS DE GOOGLE Y FACEBOOK -->
        <a href="../oauth/google_login.php" class="social-btn google">
          <img src="../images/google-icon.png" alt="Google"> Iniciar con Google
        </a>
        <a href="../oauth/facebook_login.php" class="social-btn facebook">
          <img src="../images/facebook-icon.png" alt="Facebook"> Iniciar con Facebook
        </a>
      </div>
    </div>
  </div>
</body>
</html>
