<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

// 🛡️ Verificación de acceso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?error=unauthorized");
    exit();
}

$idPerfil = (int)($_GET['id'] ?? $_SESSION['usuario_id']);
$usuario = getUsuarioPorId($idPerfil);

if (!$usuario) {
    header("Location: dashboard.php?error=" . urlencode("Usuario no encontrado"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>👤 Perfil de <?= htmlspecialchars($usuario['nombre']) ?> - LumiSpace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      font-family: 'Roboto', 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: linear-gradient(135deg, #f4f1ec, #e9e4dd);
      color: #2a1f15;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .4s ease, color .4s ease;
    }

    body.dark {
      background: linear-gradient(135deg, #1b1916, #25221d);
      color: #f5f3f0;
    }

    .perfil-card {
      width: 100%;
      max-width: 900px;
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 8px 32px rgba(0,0,0,.15);
      animation: fadeIn .6s ease;
    }

    body.dark .perfil-card {
      background: rgba(45,43,40,0.7);
    }

    .perfil-header {
      display: flex;
      align-items: center;
      gap: 25px;
      margin-bottom: 30px;
    }

    .perfil-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: #fff;
      font-size: 2.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 18px rgba(0,0,0,0.1);
      text-transform: uppercase;
    }

    .perfil-info h2 {
      font-size: 1.8rem;
      margin: 0;
    }

    .perfil-info p {
      margin: 5px 0;
      font-size: 1rem;
      color: #555;
    }

    body.dark .perfil-info p {
      color: #aaa;
    }

    .perfil-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-top: 25px;
    }

    .detail-box {
      background: rgba(255,255,255,0.85);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.05);
      transition: transform .3s ease;
    }

    body.dark .detail-box {
      background: rgba(45,43,40,0.8);
    }

    .detail-box:hover {
      transform: translateY(-4px);
    }

    .detail-box h4 {
      color: #a1683a;
      margin-bottom: 8px;
      font-size: 0.95rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-box p {
      font-size: 1rem;
      margin: 0;
    }

    .btn-back {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: #fff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 10px;
      margin-top: 30px;
      font-weight: 600;
      transition: all .3s ease;
    }

    .btn-back:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 18px rgba(0,0,0,.2);
    }

    @keyframes fadeIn {
      from {opacity:0;transform:translateY(20px);}
      to {opacity:1;transform:translateY(0);}
    }

    @media (max-width: 768px) {
      .perfil-card {padding: 25px;}
      .perfil-header {flex-direction: column;text-align:center;}
      .perfil-details {grid-template-columns: 1fr;}
    }
  </style>
</head>
<body>
  <div class="perfil-card">
    <div class="perfil-header">
      <div class="perfil-avatar"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
      <div class="perfil-info">
        <h2><?= htmlspecialchars($usuario['nombre']) ?></h2>
        <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($usuario['email']) ?></p>
        <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($usuario['num_empleado'] ?? 'N/A') ?></p>
        <p><i class="fas fa-user-shield"></i> <?= ucfirst($usuario['rol']) ?></p>
      </div>
    </div>

    <div class="perfil-details">
      <div class="detail-box">
        <h4>Puesto</h4>
        <p><?= htmlspecialchars($usuario['puesto'] ?? 'No asignado') ?></p>
      </div>

      <div class="detail-box">
        <h4>Teléfono</h4>
        <p><?= htmlspecialchars($usuario['telefono'] ?? 'Sin registrar') ?></p>
      </div>

      <div class="detail-box">
        <h4>Dirección</h4>
        <p><?= htmlspecialchars($usuario['direccion'] ?? 'Sin especificar') ?></p>
      </div>

      <div class="detail-box">
        <h4>Fecha de ingreso</h4>
        <p><?= !empty($usuario['fecha_ingreso']) ? date('d/m/Y', strtotime($usuario['fecha_ingreso'])) : '-' ?></p>
      </div>

      <div class="detail-box">
        <h4>Salario mensual</h4>
        <p><?= isset($usuario['salario']) ? '$' . number_format($usuario['salario'], 2) : 'No asignado' ?></p>
      </div>

      <div class="detail-box">
        <h4>Sucursal</h4>
        <p><?= htmlspecialchars($usuario['sucursal'] ?? 'Principal') ?></p>
      </div>

      <div class="detail-box">
        <h4>Estado de cuenta</h4>
        <p><?= ucfirst($usuario['estado'] ?? 'Activo') ?></p>
      </div>

      <div class="detail-box">
        <h4>Verificación</h4>
        <p><?= ($usuario['email_verificado'] ?? 0) ? '✅ Correo verificado' : '⛔ No verificado' ?></p>
      </div>
    </div>

    <a href="../admin/usuarios/usuarios.php" class="btn-back">
      <i class="fas fa-arrow-left"></i> Volver a la lista
    </a>
  </div>
</body>
</html>
