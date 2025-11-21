<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nuevo Proveedor - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 700px;
      margin: 0 auto;
      padding: 24px;
      border-radius: var(--radius);
      background: var(--card-bg-1);
      box-shadow: var(--shadow);
      border: 1px solid var(--card-bd);
    }
    .form-card h2 {
      margin-bottom: 20px;
      color: var(--act1);
    }
    form {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }
    form label {
      font-weight: 600;
      margin-bottom: 6px;
      display: block;
      color: var(--text);
    }
    form input, form textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--card-bd);
      border-radius: 8px;
      background: var(--card-bg-2);
      font-size: .95rem;
    }
    textarea {
      resize: vertical;
      min-height: 90px;
      grid-column: span 2;
    }
    .form-actions {
      grid-column: span 2;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 10px;
    }
    .btn {
      padding: 10px 18px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      transition: all .25s ease;
      text-decoration: none;
      display: inline-block;
      text-align: center;
    }
    .btn-primary {
      background: linear-gradient(90deg, var(--act1), var(--act2));
      color: #fff;
    }
    .btn-primary:hover {
      filter: brightness(1.1);
      transform: translateY(-2px);
    }
    .btn-secondary {
      background: var(--card-bg-2);
      color: var(--text);
    }
    .btn-secondary:hover {
      background: var(--card-bg-1);
    }
    .error-msg {
      color: #c00;
      font-weight: bold;
      font-size: .9rem;
      margin-top: 5px;
      display: none;
    }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
    <section class="content wide">
      <div class="form-card">
        <h2>➕ Nuevo Proveedor</h2>
        <form method="POST" action="proveedor-guardar.php" onsubmit="return validarNombre()">
          
          <div>
            <label for="nombre">Nombre</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Iluminaciones Pérez" required>
            <div id="errorNombre" class="error-msg">❌ El nombre no puede contener números</div>
          </div>

          <div>
            <label for="contacto">Contacto</label>
            <input type="text" id="contacto" name="contacto" placeholder="Persona de contacto">
          </div>

          <div>
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" placeholder="+52 55 1234 5678">
          </div>

          <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="correo@ejemplo.com">
          </div>

          <div style="grid-column: span 2;">
            <label for="direccion">Dirección</label>
            <textarea id="direccion" name="direccion" placeholder="Calle, número, colonia, ciudad..."></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <a href="proveedores.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script>
    function validarNombre() {
      const nombre = document.getElementById("nombre").value.trim();
      const errorMsg = document.getElementById("errorNombre");
      const regex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/; // Solo letras y espacios
      
      if (!regex.test(nombre)) {
        errorMsg.style.display = "block";
        return false; // ❌ Cancela envío
      } else {
        errorMsg.style.display = "none";
        return true;
      }
    }
  </script>
</body>
</html>
