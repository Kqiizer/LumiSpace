<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
  header("Location: ../login.php?error=unauthorized");
  exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Nueva Categoría - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .form-card {
      max-width: 600px;
      margin: 0 auto;
      padding: 24px;
      border-radius: var(--radius);
      background: var(--card-bg-1);
      box-shadow: var(--shadow);
      border: 1px solid var(--card-bd);
    }
    .form-card h2 { margin-bottom: 20px; color: var(--act1); }
    form { display: flex; flex-direction: column; gap: 14px; }
    label { font-weight: 600; color: var(--text); }
    input, textarea {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--card-bd);
      border-radius: 8px;
      background: var(--card-bg-2);
      font-size: .95rem;
    }
    textarea { resize: vertical; min-height: 100px; }
    .form-actions {
      display: flex; gap: 12px; justify-content: flex-end; margin-top: 10px;
    }
    .btn {
      padding: 10px 18px; border-radius: 8px; border: none;
      cursor: pointer; font-weight: 600; text-decoration: none;
    }
    .btn-primary { background: linear-gradient(90deg, var(--act1), var(--act2)); color: #fff; }
    .btn-primary:hover { filter: brightness(1.1); }
    .btn-secondary { background: var(--card-bg-2); color: var(--text); }
    .btn-secondary:hover { background: var(--card-bg-1); }
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>
    
    <section class="content wide">
      <div class="form-card">
        <h2>➕ Nueva Categoría</h2>
        <form method="POST" action="categoria-guardar.php">
          
          <div>
            <label for="nombre">Nombre <span style="color:red;">*</span></label>
            <input type="text" id="nombre" name="nombre" placeholder="Ej. Iluminación LED" required>
          </div>
          
          <div>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" placeholder="Ej. Categoría para focos LED, tiras y lámparas."></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">💾 Guardar</button>
            <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>
</body>
</html>
