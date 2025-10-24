<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// 🔹 Obtener roles desde la base de datos
$roles = [];
try {
    // Conexión directa a la base de datos
    require_once __DIR__ . "/../../config/db.php";
    
    // Si tienes una clase Database o conexión PDO
    if (class_exists('Database')) {
        $db = new Database();
        $pdo = $db->getConnection();
    } else {
        // Conexión manual si no existe la clase
        $host = 'localhost';
        $dbname = 'lumispace'; // Cambia por el nombre de tu BD
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    $stmt = $pdo->query("SELECT id, nombre, descripcion FROM roles WHERE activo = 1 ORDER BY nombre ASC");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar roles: " . $e->getMessage());
    // Los roles por defecto se mostrarán si hay error
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo Empleado - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f8f6f3 0%, #ebe8e3 100%);
      color: #2a1f15;
      transition: all .3s ease;
    }

    body.dark {
      background: linear-gradient(135deg, #1a1612 0%, #0f0d0b 100%);
      color: #f5f3f0;
    }

    .main {
      margin-left: 280px;
      padding: 20px;
      min-height: 100vh;
    }

    .content {
      max-width: 1000px;
      margin: 0 auto;
    }

    /* Header de página */
    .page-header {
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      padding: 24px 30px;
      border-radius: 16px;
      margin-bottom: 24px;
      box-shadow: 0 8px 24px rgba(161, 104, 58, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .page-header h1 {
      color: #fff;
      font-size: 1.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .btn-back-header {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 10px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all .3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-back-header:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }

    /* Alertas */
    .alert {
      padding: 14px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      animation: slideDown 0.4s ease;
    }

    .alert i {
      font-size: 1.2rem;
    }

    .alert.error {
      background: #fee;
      color: #c33;
      border-left: 4px solid #c33;
    }

    .alert.success {
      background: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }

    body.dark .alert.error {
      background: rgba(220, 53, 69, 0.2);
      color: #ff6b6b;
    }

    body.dark .alert.success {
      background: rgba(40, 167, 69, 0.2);
      color: #51cf66;
    }

    /* Tarjeta del formulario */
    .form-card {
      background: #fff;
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
      animation: fadeIn 0.5s ease;
    }

    body.dark .form-card {
      background: #2d2520;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    }

    /* Secciones del formulario */
    .form-section {
      margin-bottom: 32px;
      padding-bottom: 28px;
      border-bottom: 2px solid rgba(161, 104, 58, 0.1);
    }

    .form-section:last-of-type {
      border-bottom: none;
      margin-bottom: 0;
    }

    .section-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #a1683a;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding-bottom: 12px;
      border-bottom: 2px solid rgba(161, 104, 58, 0.2);
    }

    body.dark .section-title {
      color: #d4a574;
    }

    .section-title i {
      font-size: 1.3rem;
      width: 32px;
      height: 32px;
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: #fff;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Grid del formulario */
    .form-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      margin-bottom: 20px;
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
      margin-bottom: 8px;
      font-size: 0.95rem;
      color: #2a1f15;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    body.dark label {
      color: #f5f3f0;
    }

    label .required {
      color: #dc3545;
      font-size: 1.1rem;
    }

    label .badge {
      background: rgba(161, 104, 58, 0.15);
      color: #a1683a;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      margin-left: auto;
    }

    /* Inputs */
    input, select, textarea {
      padding: 12px 16px;
      border: 2px solid #e0d9cf;
      border-radius: 10px;
      font-size: 0.95rem;
      font-family: inherit;
      transition: all .3s ease;
      background: #fafaf8;
      color: #2a1f15;
    }

    body.dark input,
    body.dark select,
    body.dark textarea {
      background: #1a1612;
      border-color: #3d3530;
      color: #f5f3f0;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: #a1683a;
      box-shadow: 0 0 0 4px rgba(161, 104, 58, 0.1);
      background: #fff;
    }

    body.dark input:focus,
    body.dark select:focus,
    body.dark textarea:focus {
      background: #2d2520;
    }

    textarea {
      resize: vertical;
      min-height: 100px;
      font-family: inherit;
    }

    select {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23a1683a' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 40px;
    }

    small {
      color: #6c757d;
      font-size: 0.85rem;
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    body.dark small {
      color: #aaa;
    }

    small i {
      font-size: 0.9rem;
    }

    /* Botones */
    .form-actions {
      display: flex;
      gap: 12px;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 2px solid rgba(161, 104, 58, 0.1);
    }

    .btn-primary {
      flex: 1;
      background: linear-gradient(135deg, #a1683a, #8f5e4b);
      color: #fff;
      padding: 14px 24px;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(161, 104, 58, 0.3);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .btn-secondary {
      padding: 14px 24px;
      background: #e9ecef;
      color: #495057;
      border: none;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    body.dark .btn-secondary {
      background: #3d3530;
      color: #f5f3f0;
    }

    .btn-secondary:hover {
      background: #dee2e6;
      transform: translateY(-2px);
    }

    body.dark .btn-secondary:hover {
      background: #4d4540;
    }

    /* Input con icono */
    .input-icon {
      position: relative;
    }

    .input-icon i {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #a1683a;
      font-size: 1.1rem;
    }

    .input-icon input {
      padding-left: 44px;
    }

    /* Animaciones */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .main {
        margin-left: 0;
        padding: 80px 16px 16px;
      }
    }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }

      .page-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
      }

      .page-header h1 {
        font-size: 1.4rem;
      }

      .form-card {
        padding: 24px;
      }

      .form-actions {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <!-- Header -->
      <div class="page-header">
        <h1>
          <i class="fas fa-user-plus"></i>
          Registrar Nuevo Empleado
        </h1>
        <a href="usuarios.php" class="btn-back-header">
          <i class="fas fa-arrow-left"></i>
          Volver
        </a>
      </div>

      <!-- Mensajes de alerta -->
      <?php if (isset($_GET['error'])): ?>
        <div class="alert error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
      <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert success">
          <i class="fas fa-check-circle"></i>
          <span><?= htmlspecialchars($_GET['msg']) ?></span>
        </div>
      <?php endif; ?>

      <!-- Formulario -->
      <div class="form-card">
        <form action="usuario-guardar.php" method="POST" autocomplete="off" id="formUsuario">
          
          <!-- SECCIÓN: Información Personal -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-user"></i>
              <span>Información Personal</span>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="nombre">
                  Nombre completo 
                  <span class="required">*</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-user"></i>
                  <input 
                    type="text" 
                    name="nombre" 
                    id="nombre" 
                    required 
                    pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$" 
                    title="Solo se permiten letras y espacios"
                    placeholder="Juan Pérez García">
                </div>
              </div>

              <div class="form-group">
                <label for="telefono">
                  Teléfono 
                  <span class="required">*</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-phone"></i>
                  <input 
                    type="tel" 
                    name="telefono" 
                    id="telefono" 
                    required 
                    pattern="[0-9]{10}" 
                    title="Ingresa un teléfono de 10 dígitos"
                    placeholder="5551234567">
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group full-width">
                <label for="direccion">
                  Dirección completa 
                  <span class="required">*</span>
                </label>
                <textarea 
                  name="direccion" 
                  id="direccion" 
                  required 
                  placeholder="Calle, número, colonia, código postal, ciudad"></textarea>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Credenciales de Acceso -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-key"></i>
              <span>Credenciales de Acceso</span>
            </div>
            
            <div class="form-row">
              <div class="form-group full-width">
                <label for="email">
                  Correo electrónico 
                  <span class="required">*</span>
                  <span class="badge">Usuario de acceso</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-envelope"></i>
                  <input 
                    type="email" 
                    name="email" 
                    id="email" 
                    required
                    placeholder="empleado@lumispace.com">
                </div>
                <small>
                  <i class="fas fa-info-circle"></i>
                  La contraseña inicial será igual al correo registrado. El usuario deberá cambiarla en su primer acceso.
                </small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="rol">
                  Rol en el sistema 
                  <span class="required">*</span>
                </label>
                <select name="rol" id="rol" required>
                  <option value="">-- Selecciona un rol --</option>
                  <?php if (count($roles) > 0): ?>
                    <?php foreach ($roles as $rol): ?>
                      <option value="<?= htmlspecialchars($rol['nombre']) ?>">
                        <?= htmlspecialchars($rol['nombre']) ?>
                        <?php if (!empty($rol['descripcion'])): ?>
                          - <?= htmlspecialchars($rol['descripcion']) ?>
                        <?php endif; ?>
                      </option>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <option value="cajero">🛒 Cajero (Solo Punto de Venta)</option>
                    <option value="gestor">📦 Gestor (Inventario y Ventas)</option>
                    <option value="admin">👑 Administrador (Acceso Total)</option>
                  <?php endif; ?>
                </select>
                <small>
                  <i class="fas fa-shield-alt"></i>
                  Define los permisos de acceso al sistema
                </small>
              </div>

              <div class="form-group">
                <label for="puesto">
                  Puesto de trabajo 
                  <span class="required">*</span>
                </label>
                <select name="puesto" id="puesto" required>
                  <option value="">-- Selecciona puesto --</option>
                  <option value="cajero">Cajero</option>
                  <option value="vendedor">Vendedor</option>
                  <option value="almacenista">Almacenista</option>
                  <option value="encargado">Encargado de Tienda</option>
                  <option value="gerente">Gerente</option>
                  <option value="soporte">Soporte Técnico</option>
                  <option value="otro">Otro</option>
                </select>
              </div>
            </div>
          </div>

          <!-- SECCIÓN: Información Laboral -->
          <div class="form-section">
            <div class="section-title">
              <i class="fas fa-briefcase"></i>
              <span>Información Laboral</span>
            </div>
            
            <div class="form-row">
              <div class="form-group">
                <label for="num_empleado">
                  Número de empleado 
                  <span class="required">*</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-id-card"></i>
                  <input 
                    type="text" 
                    name="num_empleado" 
                    id="num_empleado" 
                    required 
                    pattern="[A-Z0-9\-]+" 
                    title="Solo letras mayúsculas, números y guiones"
                    placeholder="EMP-001">
                </div>
                <small>
                  <i class="fas fa-fingerprint"></i>
                  Identificador único del empleado
                </small>
              </div>

              <div class="form-group">
                <label for="fecha_ingreso">
                  Fecha de ingreso 
                  <span class="required">*</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-calendar"></i>
                  <input 
                    type="date" 
                    name="fecha_ingreso" 
                    id="fecha_ingreso" 
                    required>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="salario">
                  Salario mensual
                  <span class="badge">Opcional</span>
                </label>
                <div class="input-icon">
                  <i class="fas fa-dollar-sign"></i>
                  <input 
                    type="number" 
                    name="salario" 
                    id="salario" 
                    min="0" 
                    step="0.01" 
                    placeholder="0.00">
                </div>
                <small>
                  <i class="fas fa-money-bill-wave"></i>
                  Para registro de nómina interna
                </small>
              </div>

              <div class="form-group">
                <label for="sucursal">
                  Sucursal asignada 
                  <span class="required">*</span>
                </label>
                <select name="sucursal" id="sucursal" required>
                  <option value="principal">🏢 Sucursal Principal</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="form-actions">
            <a href="usuarios.php" class="btn-secondary">
              <i class="fas fa-times"></i>
              Cancelar
            </a>
            <button type="submit" class="btn-primary">
              <i class="fas fa-save"></i>
              Guardar Empleado
            </button>
          </div>

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
      
      // Validar nombre
      if (!/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/.test(nombre)) {
        e.preventDefault();
        alert("❌ El nombre solo puede contener letras y espacios.");
        return;
      }
      
      // Validar teléfono
      if (!/^[0-9]{10}$/.test(telefono)) {
        e.preventDefault();
        alert("❌ El teléfono debe tener exactamente 10 dígitos.");
        return;
      }
      
      // Validar número de empleado
      if (!/^[A-Z0-9\-]+$/.test(numEmpleado)) {
        e.preventDefault();
        alert("❌ El número de empleado solo puede contener letras mayúsculas, números y guiones.");
        return;
      }
    });

    // Establecer fecha actual como máxima para fecha de ingreso
    document.getElementById("fecha_ingreso").max = new Date().toISOString().split('T')[0];
    
    // Convertir número de empleado a mayúsculas automáticamente
    document.getElementById("num_empleado").addEventListener("input", function() {
      this.value = this.value.toUpperCase();
    });

    // Formatear teléfono mientras se escribe
    document.getElementById("telefono").addEventListener("input", function() {
      this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
  </script>
</body>
</html>