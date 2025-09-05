<?php
include("../config/functions.php"); // conexión + funciones

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim($_POST['nombre']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $rol      = $_POST['rol'];

    if (registrarUsuario($nombre, $email, $password, $rol)) {
        header("Location: login.php?success=1");
        exit();
    } else {
        $error = "Error en el registro. Inténtalo de nuevo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - POS</title>
  <link rel="stylesheet" href="../css/auth.css">
  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="auth-wrapper">
    <!-- Columna izquierda -->
    <div class="auth-image" style="background: url('../images/pos-register.jpg') no-repeat center center/cover;"></div>

    <!-- Columna derecha -->
    <div class="auth-form">
      <h2>Crear cuenta</h2>
      <p class="subtitle">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>

      <?php if (!empty($error)): ?>
        <p class="error"><?= $error; ?></p>
      <?php endif; ?>

      <form method="POST">
        <!-- Nombre -->
        <div class="input-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" required>
          <i class="fa-solid fa-user icon"></i>
        </div>

        <!-- Email -->
        <div class="input-group">
          <label for="email">Correo electrónico</label>
          <input type="email" name="email" required>
          <i class="fa-solid fa-envelope icon"></i>
        </div>

        <!-- Password -->
        <div class="input-group">
          <label for="password">Contraseña</label>
          <input type="password" name="password" required>
          <i class="fa-solid fa-lock icon"></i>
        </div>

        <!-- Rol -->
        <div class="input-group">
          <label for="rol">Tipo de cuenta</label>
          <select name="rol" required>
            <option value="">-- Selecciona un rol --</option>
            <option value="admin">Administrador</option>
            <option value="usuario">Usuario</option>
            <option value="dueno">Dueño del POS</option>
          </select>
          <i class="fa-solid fa-user-shield icon"></i>
        </div>

        <!-- Botón -->
        

      <!-- Divisor -->
      <div class="divider"><span>o</span></div>

      <!-- Social login -->
      <div class="social-login">
        <button class="social-btn google">
          <img src="../images/google-icon.png" alt="Google"> Registrar con Google
        </button>
        <button class="social-btn facebook">
          <img src="../images/facebook-icon.png" alt="Facebook"> Registrar con Facebook
        </button>
      </div>
    </div>
  </div>
</body>
</html>
