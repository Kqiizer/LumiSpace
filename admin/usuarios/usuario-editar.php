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

// Determinar ruta de la foto (si existe)
$fotoActual = !empty($usuario['foto']) && file_exists(__DIR__ . "/../../" . $usuario['foto'])
    ? "../../" . htmlspecialchars($usuario['foto'])
    : "https://via.placeholder.com/150x150?text=Sin+Foto";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>✏️ Editar Usuario - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #f4f1ec, #e9e4dd); color: #2a1f15; }
    body.dark { background: linear-gradient(135deg, #1b1916, #25221d); color: #f5f3f0; }
    section.content { max-width: 900px; margin: 0 auto; padding: 20px; }
    .page-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 22px; border-radius: 16px; background: linear-gradient(135deg, #a1683a, #8f5e4b); color: #fff; margin-bottom: 24px; }
    .alert { padding: 12px 16px; border-radius: 10px; font-weight: 600; margin-bottom: 18px; text-align: center; }
    .alert.error { background: #f8d7da; color: #721c24; }
    .alert.success { background: #d4edda; color: #155724; }
    .form-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); padding: 28px; border-radius: 18px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
    .form-section { margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid rgba(161,104,58,0.2); }
    .section-title { font-size: 1.15rem; font-weight: 700; color: #a1683a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-group { display: flex; flex-direction: column; }
    .form-group.full-width { grid-column: 1 / -1; }
    label { font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
    input, select, textarea { padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: .95rem; transition: all .2s ease; background-color: #fff; font-family: 'Poppins', sans-serif; }
    input:focus, select:focus, textarea:focus { outline: none; border-color: #a1683a; box-shadow: 0 0 4px rgba(161,104,58,.5); }
    textarea { resize: vertical; min-height: 80px; }
    .btn-primary { background: linear-gradient(135deg, #a1683a, #8f5e4b); border: none; color: #fff; font-weight: 600; padding: 12px 24px; border-radius: 8px; cursor: pointer; transition: all .3s ease; font-size: 1rem; width: 100%; margin-top: 20px; }
    .btn-back { display: block; text-align: center; margin-top: 12px; color: #8f5e4b; font-weight: 600; text-decoration: none; }
    .foto-preview { text-align: center; margin-bottom: 20px; }
    .foto-preview img { border-radius: 50%; width: 130px; height: 130px; object-fit: cover; border: 4px solid #a1683a; box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
    .foto-preview label { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #8f5e4b; color: #fff; border-radius: 8px; cursor: pointer; font-weight: 600; transition: .3s; }
    .foto-preview label:hover { background: #a1683a; }
    .foto-preview input { display: none; }
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
  </style>
</head>

<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <div class="page-header">
        <h2>✏️ Editar Empleado</h2>
        <span>ID: <?= $usuario['id'] ?></span>
      </div>

      <?php if (isset($_GET['error'])): ?>
        <div class="alert error"><?= htmlspecialchars($_GET['error']) ?></div>
      <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php endif; ?>

      <div class="form-card">
        <form action="usuario-actualizar.php" method="POST" enctype="multipart/form-data" autocomplete="off" id="formUsuario">
          <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

          <!-- 🖼️ Foto del empleado -->
          <div class="foto-preview">
            <img src="<?= $fotoActual ?>" id="previewFoto" alt="Foto del empleado">
            <br>
            <label for="foto"><i class="fas fa-camera"></i> Cambiar foto</label>
            <input type="file" name="foto" id="foto" accept="image/*">
          </div>

          <!-- === Secciones del formulario (idénticas a tu diseño actual) === -->
          <div class="form-section">
            <div class="section-title"><i class="fas fa-user"></i> Información Personal</div>
            <div class="form-row">
              <div class="form-group">
                <label>Nombre completo <span class="required">*</span></label>
                <input type="text" name="nombre" required pattern="^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$"
                       value="<?= htmlspecialchars($usuario['nombre']) ?>">
              </div>
              <div class="form-group">
                <label>Teléfono <span class="required">*</span></label>
                <input type="tel" name="telefono" required pattern="[0-9]{10}"
                       value="<?= htmlspecialchars($usuario['telefono']) ?>">
              </div>
            </div>
            <div class="form-group full-width">
              <label>Dirección <span class="required">*</span></label>
              <textarea name="direccion" required><?= htmlspecialchars($usuario['direccion']) ?></textarea>
            </div>
          </div>

          <!-- SECCIÓN DE ACCESO -->
          <div class="form-section">
            <div class="section-title"><i class="fas fa-key"></i> Acceso</div>
            <div class="form-group full-width">
              <label>Correo electrónico <span class="required">*</span></label>
              <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email']) ?>">
            </div>
            <div class="form-group full-width">
              <label>Nueva contraseña (opcional)</label>
              <input type="password" name="password" placeholder="Dejar vacío para no cambiar">
            </div>
          </div>

          <!-- SECCIÓN LABORAL -->
          <div class="form-section">
            <div class="section-title"><i class="fas fa-briefcase"></i> Información Laboral</div>
            <div class="form-row">
              <div class="form-group">
                <label>Número de empleado <span class="required">*</span></label>
                <input type="text" name="num_empleado" required pattern="[A-Z0-9\-]+"
                       value="<?= htmlspecialchars($usuario['num_empleado']) ?>">
              </div>
              <div class="form-group">
                <label>Fecha de ingreso <span class="required">*</span></label>
                <input type="date" name="fecha_ingreso" required value="<?= htmlspecialchars($usuario['fecha_ingreso']) ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Puesto</label>
                <select name="puesto" required>
                  <option value="">-- Selecciona --</option>
                  <?php
                  $puestos = ['cajero','vendedor','almacenista','gerente','soporte','otro'];
                  foreach ($puestos as $p) {
                      $sel = ($usuario['puesto'] === $p) ? 'selected' : '';
                      echo "<option value='{$p}' {$sel}>".ucfirst($p)."</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="form-group">
                <label>Estado</label>
                <select name="estado" required>
                  <option value="activo" <?= $usuario['estado']==='activo'?'selected':'' ?>>Activo</option>
                  <option value="inactivo" <?= $usuario['estado']==='inactivo'?'selected':'' ?>>Inactivo</option>
                  <option value="suspendido" <?= $usuario['estado']==='suspendido'?'selected':'' ?>>Suspendido</option>
                </select>
              </div>
            </div>
          </div>

          <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
          <a href="usuarios.php" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
        </form>
      </div>
    </section>
  </main>

<script>
document.getElementById("foto").addEventListener("change", e => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = ev => document.getElementById("previewFoto").src = ev.target.result;
    reader.readAsDataURL(file);
  }
});
</script>
</body>
</html>
