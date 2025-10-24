<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// Validar que venga el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=" . urlencode("ID de usuario no proporcionado"));
    exit();
}

$idEditar = intval($_GET['id']);

if ($idEditar <= 0) {
    header("Location: usuarios.php?error=" . urlencode("ID de usuario inválido"));
    exit();
}

// Obtener datos del usuario
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $idEditar);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: usuarios.php?error=" . urlencode("El usuario no existe"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>✏️ Editar Usuario - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f4f1ec, #e9e4dd);
      color: #2a1f15;
      transition: background .4s ease, color .4s ease;
    }
    body.dark {
      background: linear-gradient(135deg, #1b1916, #25221d);
      color: #f5f3f0;
    }

    section.content {
      max-width: 900px;
      margin: 0 auto;
      padding: 20px;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 16px 22px;
      border-radius: 16px;
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: #fff;
      box-shadow: 0 8px 24px rgba(0,0,0,.1);
      margin-bottom: 24px;
    }

    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      font-weight: 600;
      margin-bottom: 18px;
      text-align: center;
      animation: fadeIn .4s ease;
    }
    .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }

    .form-card {
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(10px);
      padding: 28px;
      border-radius: 18px;
      box-shadow: 0 8px 24px rgba(0,0,0,.12);
      transition: all .3s ease;
      animation: fadeIn .5s ease;
    }
    body.dark .form-card {
      background: rgba(45,43,40,0.7);
    }

    .form-section {
      margin-bottom: 28px;
      padding-bottom: 20px;
      border-bottom: 2px solid rgba(161,104,58,0.2);
    }
    .form-section:last-of-type {
      border-bottom: none;
    }

    .section-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #a1683a;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    body.dark .section-title {
      color: #d4a574;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
    }

    .form-group.full-width {
      grid-column: 1 / -1;
    }

    label {
      font-weight: 600;
      margin-bottom: 6px;
      font-size: 0.9rem;
    }

    label .required {
      color: #d9534f;
      margin-left: 2px;
    }

    input, select, textarea {
      padding: 10px 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: .95rem;
      transition: all .2s ease;
      background-color: #fff;
      font-family: 'Poppins', sans-serif;
    }
    body.dark input, body.dark select, body.dark textarea {
      background-color: #2d2b28;
      color: #f5f3f0;
      border-color: #423a32;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #a1683a;
      box-shadow: 0 0 4px rgba(161,104,58,.5);
    }

    textarea {
      resize: vertical;
      min-height: 80px;
    }

    small {
      color: #666;
      font-size: 0.82rem;
      margin-top: 4px;
    }
    body.dark small {
      color: #999;
    }

    .info-badge {
      display: inline-block;
      padding: 4px 10px;
      background: #e3f2fd;
      color: #1976d2;
      border-radius: 6px;
      font-size: 0.85rem;
      margin-left: 8px;
    }
    body.dark .info-badge {
      background: #1e3a5f;
      color: #90caf9;
    }

    .btn-primary {
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      border: none;
      color: #fff;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      transition: all .3s ease;
      font-size: 1rem;
      width: 100%;
      margin-top: 20px;
    }
    .btn-primary:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    }

    .btn-back {
      display: inline-block;
      margin-top: 12px;
      text-decoration: none;
      color: #8f5e4b;
      font-weight: 600;
      transition: color .3s ease;
      text-align: center;
      display: block;
    }
    .btn-back:hover { color: #a1683a; }

    .password-section {
      background: #fff3cd;
      border: 1px solid #ffc107;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
    body.dark .password-section {
      background: #3d3420;
      border-color: #856404;
    }

    @keyframes fadeIn { from {opacity:0;transform:translateY(10px);} to {opacity:1;transform:translateY(0);} }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <div class="page-header">
        <h2>✏️ Editar Empleado</h2>
        <span class="info-badge">ID: <?= $usuario['id'] ?></span>
      </div>

      <!-- Mensajes -->
      <?php if (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php endif; ?>

      <div class="form-card">
        <form action="usuario-actualizar.php" method="POST" autocomplete="off" id="formUsuario">
          <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
          
          <!-- SECCIÓN: Información Personal -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-user"></i> Información Personal
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="nombre">Nombre completo <span class="required">*</span></label>
                <input type="text" name="nombre" id="nombre" required 
                       pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$" 
                       title="Solo se permiten letras y espacios"
                       value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>">
              </div>

              <div class="form-group">
                <label for="telefono">Teléfono <span class="required">*</span></label>
                <input type="tel" name="telefono" id="telefono" required 
                       pattern="[0-9]{10}" 
                       title="Ingresa un teléfono de 10 dígitos"
                       placeholder="5551234567"
                       value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label for="direccion">Dirección completa <span class="required">*</span></label>
                <textarea name="direccion" id="direccion" required 
                          placeholder="Calle, número, colonia, código postal, ciudad"><?= htmlspecialchars($usuario['direccion'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Información de Acceso -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-key"></i> Credenciales de Acceso
            </div>
            
            <div class="form-row">
              <div class="form-group full-width">
                <label for="email">Correo electrónico <span class="required">*</span></label>
                <input type="email" name="email" id="email" required
                       value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
              </div>
            </div>

            <div class="password-section">
              <p style="margin: 0 0 10px 0; font-weight: 600; color: #856404;">
                <i class="fas fa-info-circle"></i> Cambiar Contraseña
              </p>
              <small style="color: #856404;">
                Deja este campo vacío si NO deseas cambiar la contraseña. Solo completa si quieres asignar una nueva.
              </small>
              <input type="password" name="password" id="password" 
                     placeholder="Nueva contraseña (dejar vacío para no cambiar)"
                     style="margin-top: 10px; width: 100%;">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="rol">Rol en el sistema <span class="required">*</span></label>
                <select name="rol" id="rol" required>
                  <option value="">-- Selecciona un rol --</option>
                  <option value="usuario" <?= ($usuario['rol'] ?? '') === 'usuario' ? 'selected' : '' ?>>Usuario (Cliente)</option>
                  <option value="cajero" <?= ($usuario['rol'] ?? '') === 'cajero' ? 'selected' : '' ?>>Cajero (Punto de Venta)</option>
                  <option value="gestor" <?= ($usuario['rol'] ?? '') === 'gestor' ? 'selected' : '' ?>>Gestor (Inventario/Ventas)</option>
                  <option value="admin" <?= ($usuario['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador (Acceso Total)</option>
                </select>
              </div>

              <div class="form-group">
                <label for="puesto">Puesto de trabajo <span class="required">*</span></label>
                <select name="puesto" id="puesto" required>
                  <option value="">-- Selecciona puesto --</option>
                  <option value="cajero" <?= ($usuario['puesto'] ?? '') === 'cajero' ? 'selected' : '' ?>>Cajero</option>
                  <option value="vendedor" <?= ($usuario['puesto'] ?? '') === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                  <option value="almacenista" <?= ($usuario['puesto'] ?? '') === 'almacenista' ? 'selected' : '' ?>>Almacenista</option>
                  <option value="encargado" <?= ($usuario['puesto'] ?? '') === 'encargado' ? 'selected' : '' ?>>Encargado de Tienda</option>
                  <option value="gerente" <?= ($usuario['puesto'] ?? '') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                  <option value="soporte" <?= ($usuario['puesto'] ?? '') === 'soporte' ? 'selected' : '' ?>>Soporte Técnico</option>
                  <option value="otro" <?= ($usuario['puesto'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                </select>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Información Laboral -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-briefcase"></i> Información Laboral
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="num_empleado">Número de empleado <span class="required">*</span></label>
                <input type="text" name="num_empleado" id="num_empleado" required 
                       pattern="[A-Z0-9\-]+" 
                       title="Solo letras mayúsculas, números y guiones"
                       placeholder="EMP-001"
                       value="<?= htmlspecialchars($usuario['num_empleado'] ?? '') ?>">
                <small>Identificador único del empleado</small>
              </div>

              <div class="form-group">
                <label for="fecha_ingreso">Fecha de ingreso <span class="required">*</span></label>
                <input type="date" name="fecha_ingreso" id="fecha_ingreso" required
                       value="<?= htmlspecialchars($usuario['fecha_ingreso'] ?? '') ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="salario">Salario mensual</label>
                <input type="number" name="salario" id="salario" 
                       min="0" step="0.01" 
                       placeholder="0.00"
                       value="<?= htmlspecialchars($usuario['salario'] ?? '') ?>">
                <small>Opcional - Para registro de nómina</small>
              </div>

              <div class="form-group">
                <label for="sucursal">Sucursal asignada <span class="required">*</span></label>
                <select name="sucursal" id="sucursal" required>
                  <option value="principal" <?= ($usuario['sucursal'] ?? 'principal') === 'principal' ? 'selected' : '' ?>>Sucursal Principal</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="estado">Estado de la cuenta <span class="required">*</span></label>
                <select name="estado" id="estado" required>
                  <option value="activo" <?= ($usuario['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>✅ Activo</option>
                  <option value="inactivo" <?= ($usuario['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>⛔ Inactivo</option>
                  <option value="suspendido" <?= ($usuario['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>⚠️ Suspendido</option>
                </select>
              </div>

              <div class="form-group">
                <label>Fecha de registro</label>
                <input type="text" disabled 
                       value="<?= isset($usuario['fecha_registro']) ? date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) : 'No disponible' ?>"
                       style="background: #e9ecef; cursor: not-allowed;">
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary">
            <i class="fas fa-save"></i> Guardar Cambios
          </button>
          <a href="usuarios.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Volver a la lista
          </a>
        </form>
      </div>
    </section>
  </main>

<script>
// Validación del formulario
document.getElementById("formUsuario").addEventListener("submit", (e) => {
  const nombre = document.getElementById("nombre").value.trim();
  const telefono = document.getElementById("telefono").value.trim();
  const numEmpleado = document.getElementById("num_empleado").value.trim();
  const password = document.getElementById("password").value;
  
  // Validar nombre
  if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/.test(nombre)) {
    e.preventDefault();
    alert("El nombre solo puede contener letras y espacios.");
    return;
  }
  
  // Validar teléfono
  if (!/^[0-9]{10}$/.test(telefono)) {
    e.preventDefault();
    alert("El teléfono debe tener exactamente 10 dígitos.");
    return;
  }
  
  // Validar número de empleado
  if (!/^[A-Z0-9\-]+$/.test(numEmpleado)) {
    e.preventDefault();
    alert("El número de empleado solo puede contener letras mayúsculas, números y guiones.");
    return;
  }

  // Validar contraseña si se ingresó
  if (password !== '' && password.length < 6) {
    e.preventDefault();
    alert("La contraseña debe tener al menos 6 caracteres.");
    return;
  }
});

// Establecer fecha actual como máxima para fecha de ingreso
document.getElementById("fecha_ingreso").max = new Date().toISOString().split('T')[0];
</script>
</body>
</html>