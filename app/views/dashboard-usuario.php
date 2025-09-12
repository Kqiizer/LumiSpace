<?php
session_start();

// Validar que solo entren usuarios logueados con rol "usuario"
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'usuario') {
    header("Location: login.php?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Usuario</title>
  <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
  <div class="dashboard">
    <header>
      <h1>Bienvenido, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?> 👋</h1>
    </header>

    <main>
      <p>Este es tu panel de usuario.</p>
      <a href="../logout.php" class="btn-logout">Cerrar sesión</a>
    </main>
  </div>
</body>
</html>
