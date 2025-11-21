<?php
/**
 * ============================================
 * PROCESAMIENTO DE REGISTRO DE EMPLEADOS
 * ============================================
 * Archivo: usuario-agregar-procesar.php
 * Descripción: Procesa el alta de nuevos empleados en el sistema
 * Permisos: Solo administradores
 * ============================================
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🔒 Solo administradores
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: usuarios.php?error=metodo_invalido");
    exit();
}

// ==========================================
// CAPTURA DE DATOS
// ==========================================
$datosEmpleado = [
    'nombre'        => trim($_POST['nombre'] ?? ''),
    'email'         => strtolower(trim($_POST['email'] ?? '')),
    'telefono'      => trim($_POST['telefono'] ?? ''),
    'direccion'     => trim($_POST['direccion'] ?? ''),
    'rol'           => strtolower(trim($_POST['rol'] ?? '')),
    'puesto'        => trim($_POST['puesto'] ?? ''),
    'num_empleado'  => strtoupper(trim($_POST['num_empleado'] ?? '')),
    'fecha_ingreso' => trim($_POST['fecha_ingreso'] ?? ''),
    'sucursal'      => trim($_POST['sucursal'] ?? 'principal')
];

// La contraseña inicial puede ser el correo o una cadena generada
$password = $datosEmpleado['email'];

// ==========================================
// VALIDACIÓN DE DATOS
// ==========================================
$errores = [];

// Validar campos
if ($datosEmpleado['nombre'] === '' || !preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/u', $datosEmpleado['nombre']))
    $errores[] = "El nombre es obligatorio y solo puede contener letras.";

if ($datosEmpleado['email'] === '' || !filter_var($datosEmpleado['email'], FILTER_VALIDATE_EMAIL))
    $errores[] = "Correo electrónico inválido.";

if (!preg_match('/^[0-9]{10}$/', $datosEmpleado['telefono']))
    $errores[] = "El teléfono debe tener 10 dígitos.";

if ($datosEmpleado['direccion'] === '')
    $errores[] = "La dirección es obligatoria.";

$rolesPermitidos = ['usuario', 'gestor', 'cajero', 'admin'];
if (!in_array($datosEmpleado['rol'], $rolesPermitidos, true))
    $errores[] = "Rol inválido.";

if ($datosEmpleado['puesto'] === '')
    $errores[] = "El puesto es obligatorio.";

if (empty($datosEmpleado['fecha_ingreso']) || strtotime($datosEmpleado['fecha_ingreso']) > time())
    $errores[] = "Fecha de ingreso inválida.";

// ==========================================
// VALIDAR FOTO
// ==========================================
$fotoRutaRelativa = null;
if (!empty($_FILES['foto']['name'])) {
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $permitidas)) {
        $errores[] = "La foto debe ser JPG, PNG, GIF o WEBP.";
    }
}

// 🚨 Si hay errores
if (!empty($errores)) {
    $q = urlencode(implode(' | ', $errores));
    header("Location: usuario-agregar.php?error=$q");
    exit();
}

// ==========================================
// CONEXIÓN BD
// ==========================================
$conn = getDBConnection();

// Generar número de empleado si no existe
if (empty($datosEmpleado['num_empleado'])) {
    $r = $conn->query("SELECT COUNT(*) AS total FROM usuarios");
    $total = $r->fetch_assoc()['total'] ?? 0;
    $datosEmpleado['num_empleado'] = sprintf("EMP-%03d", $total + 1);
}

// Evitar duplicados
$stmtEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$stmtEmail->bind_param("s", $datosEmpleado['email']);
$stmtEmail->execute();
if ($stmtEmail->get_result()->fetch_assoc()) {
    header("Location: usuario-agregar.php?error=" . urlencode("El correo ya está registrado."));
    exit();
}
$stmtEmail->close();

$stmtNE = $conn->prepare("SELECT id FROM usuarios WHERE num_empleado = ? LIMIT 1");
$stmtNE->bind_param("s", $datosEmpleado['num_empleado']);
$stmtNE->execute();
if ($stmtNE->get_result()->fetch_assoc()) {
    header("Location: usuario-agregar.php?error=" . urlencode("El número de empleado ya existe."));
    exit();
}
$stmtNE->close();

// ==========================================
// SUBIR FOTO
// ==========================================
if (!empty($_FILES['foto']['name'])) {
    $dir = __DIR__ . "/../../uploads/empleados/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $nombreFoto = uniqid('emp_') . '.' . $ext;
    $destino = $dir . $nombreFoto;
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
        $fotoRutaRelativa = "uploads/empleados/" . $nombreFoto;
    }
}

// ==========================================
// INSERTAR REGISTRO
// ==========================================
$hash = password_hash($password, PASSWORD_DEFAULT);
$sql = "INSERT INTO usuarios 
(nombre, email, password, telefono, direccion, rol, puesto, num_empleado, fecha_ingreso, sucursal, estado, proveedor, provider_id, email_verificado, token_verificacion, foto, fecha_registro)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', 'manual', NULL, 1, NULL, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssssss",
    $datosEmpleado['nombre'],
    $datosEmpleado['email'],
    $hash,
    $datosEmpleado['telefono'],
    $datosEmpleado['direccion'],
    $datosEmpleado['rol'],
    $datosEmpleado['puesto'],
    $datosEmpleado['num_empleado'],
    $datosEmpleado['fecha_ingreso'],
    $datosEmpleado['sucursal'],
    $fotoRutaRelativa
);

if (!$stmt->execute()) {
    header("Location: usuario-agregar.php?error=" . urlencode("Error BD: " . $stmt->error));
    exit();
}

$nuevoId = $conn->insert_id;
$stmt->close();

// ==========================================
// CORREO OPCIONAL
// ==========================================
if (function_exists('enviarCorreo')) {
    $baseUrl = rtrim($_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace', '/');
    $urlLogin = $baseUrl . "/views/login.php";
    $asunto = "🎉 Bienvenido a LumiSpace, {$datosEmpleado['nombre']}!";
    $cuerpo = "
        <h2>¡Hola {$datosEmpleado['nombre']}!</h2>
        <p>Tu cuenta ha sido creada en <strong>LumiSpace</strong>.</p>
        <p><strong>Correo:</strong> {$datosEmpleado['email']}</p>
        <p><strong>Número de empleado:</strong> {$datosEmpleado['num_empleado']}</p>
        <p><a href='{$urlLogin}' style='color:#a1683a;font-weight:bold;'>Iniciar sesión</a></p>
    ";
    @enviarCorreo($datosEmpleado['email'], $asunto, $cuerpo);
}

// ==========================================
// ✅ REDIRECCIÓN FINAL AL PERFIL
// ==========================================
header("Location: ../../views/perfil.php?id={$nuevoId}");
exit();
