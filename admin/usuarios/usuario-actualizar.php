<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header("Location: usuarios.php?error=metodo_invalido");
    exit();
}

// ====== Captura y saneo de TODOS los campos ======
$id             = intval($_POST['id'] ?? 0);
$nombre         = trim($_POST['nombre'] ?? '');
$email          = strtolower(trim($_POST['email'] ?? ''));
$telefono       = trim($_POST['telefono'] ?? '');
$direccion      = trim($_POST['direccion'] ?? '');
$rol            = strtolower(trim($_POST['rol'] ?? ''));
$puesto         = trim($_POST['puesto'] ?? '');
$num_empleado   = strtoupper(trim($_POST['num_empleado'] ?? ''));
$fecha_ingreso  = trim($_POST['fecha_ingreso'] ?? '');
$salario        = trim($_POST['salario'] ?? '');
$sucursal       = trim($_POST['sucursal'] ?? 'principal');
$estado         = trim($_POST['estado'] ?? 'activo');
$password       = trim($_POST['password'] ?? ''); // Opcional

// ====== Validaciones obligatorias ======
$errores = [];

// ID válido
if ($id <= 0) {
    $errores[] = "ID de usuario inválido.";
}

// 🚫 Evitar que el admin se edite a sí mismo el rol (opcional)
$conn = getDBConnection();
$stmtCheck = $conn->prepare("SELECT rol FROM usuarios WHERE id = ? LIMIT 1");
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$usuarioActual = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if (!$usuarioActual) {
    header("Location: usuarios.php?error=" . urlencode("El usuario no existe"));
    exit();
}

// Si el admin se está editando a sí mismo, no permitir cambio de rol
if ($id === intval($_SESSION['usuario_id']) && $usuarioActual['rol'] === 'admin' && $rol !== 'admin') {
    $errores[] = "No puedes cambiar tu propio rol de administrador.";
}

// Nombre: solo letras y espacios
if ($nombre === '') {
    $errores[] = "El nombre es obligatorio.";
} elseif (!preg_match('/^[\p{L}\s]+$/u', $nombre)) {
    $errores[] = "El nombre solo puede contener letras y espacios.";
}

// Email
if ($email === '') {
    $errores[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "El correo electrónico no es válido.";
}

// Teléfono
if ($telefono === '') {
    $errores[] = "El teléfono es obligatorio.";
} elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
    $errores[] = "El teléfono debe tener 10 dígitos.";
}

// Dirección
if ($direccion === '') {
    $errores[] = "La dirección es obligatoria.";
}

// Rol
$rolesPermitidos = ['usuario','gestor','cajero','admin'];
if ($rol === '' || !in_array($rol, $rolesPermitidos, true)) {
    $errores[] = "Rol inválido.";
}

// Puesto
if ($puesto === '') {
    $errores[] = "El puesto de trabajo es obligatorio.";
}

// Número de empleado
if ($num_empleado === '') {
    $errores[] = "El número de empleado es obligatorio.";
} elseif (!preg_match('/^[A-Z0-9\-]+$/', $num_empleado)) {
    $errores[] = "El número de empleado solo puede contener letras mayúsculas, números y guiones.";
}

// Fecha de ingreso
if ($fecha_ingreso === '') {
    $errores[] = "La fecha de ingreso es obligatoria.";
} elseif (strtotime($fecha_ingreso) > time()) {
    $errores[] = "La fecha de ingreso no puede ser futura.";
}

// Salario (opcional)
if ($salario !== '' && (!is_numeric($salario) || $salario < 0)) {
    $errores[] = "El salario debe ser un número válido.";
}

// Contraseña (opcional, pero si viene debe ser válida)
if ($password !== '' && mb_strlen($password) < 6) {
    $errores[] = "La contraseña debe tener al menos 6 caracteres.";
}

// Estado
$estadosPermitidos = ['activo', 'inactivo', 'suspendido'];
if (!in_array($estado, $estadosPermitidos, true)) {
    $errores[] = "Estado inválido.";
}

// Si hay errores, regresamos
if (!empty($errores)) {
    $q = urlencode(implode(' | ', $errores));
    header("Location: usuario-editar.php?id={$id}&error={$q}");
    exit();
}

// ====== Validación de duplicados (excepto el mismo usuario) ======

// Verificar email duplicado
$chkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
$chkEmail->bind_param("si", $email, $id);
$chkEmail->execute();
$dupEmail = $chkEmail->get_result()->fetch_assoc();
$chkEmail->close();

if ($dupEmail) {
    header("Location: usuario-editar.php?id={$id}&error=" . urlencode("El correo ya está registrado por otro usuario"));
    exit();
}

// Verificar número de empleado duplicado
$chkEmp = $conn->prepare("SELECT id FROM usuarios WHERE num_empleado = ? AND id != ? LIMIT 1");
$chkEmp->bind_param("si", $num_empleado, $id);
$chkEmp->execute();
$dupEmp = $chkEmp->get_result()->fetch_assoc();
$chkEmp->close();

if ($dupEmp) {
    header("Location: usuario-editar.php?id={$id}&error=" . urlencode("El número de empleado ya está registrado"));
    exit();
}

// ====== Preparar actualización ======
$salarioFinal = ($salario === '') ? null : floatval($salario);

// Construir query dependiendo si se cambia la contraseña
if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "UPDATE usuarios SET 
            nombre = ?, email = ?, password = ?, telefono = ?, direccion = ?,
            rol = ?, puesto = ?, num_empleado = ?, fecha_ingreso = ?, salario = ?,
            sucursal = ?, estado = ?
            WHERE id = ? LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssdssi",
        $nombre, $email, $hash, $telefono, $direccion,
        $rol, $puesto, $num_empleado, $fecha_ingreso, $salarioFinal,
        $sucursal, $estado, $id
    );
} else {
    // Sin cambiar contraseña
    $sql = "UPDATE usuarios SET 
            nombre = ?, email = ?, telefono = ?, direccion = ?,
            rol = ?, puesto = ?, num_empleado = ?, fecha_ingreso = ?, salario = ?,
            sucursal = ?, estado = ?
            WHERE id = ? LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssdssi",
        $nombre, $email, $telefono, $direccion,
        $rol, $puesto, $num_empleado, $fecha_ingreso, $salarioFinal,
        $sucursal, $estado, $id
    );
}

$ok = $stmt->execute();
if (!$ok) {
    $err = $stmt->error;
    $stmt->close();
    header("Location: usuario-editar.php?id={$id}&error=" . urlencode("Error al actualizar: $err"));
    exit();
}

$stmt->close();

// ====== Enviar correo de notificación (opcional) ======
if (function_exists('enviarCorreo')) {
    $subject = "Información de cuenta actualizada - LumiSpace";
    $base = $_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace';
    $loginUrl = rtrim($base, '/') . "/views/login.php";
    
    $rolTexto = [
        'usuario' => 'Usuario (Cliente)',
        'cajero' => 'Cajero',
        'gestor' => 'Gestor',
        'admin' => 'Administrador'
    ][$rol] ?? ucfirst($rol);
    
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #17a2b8, #138496); padding: 30px; border-radius: 12px 12px 0 0; text-align: center;'>
                <h1 style='color: #fff; margin: 0;'>Información Actualizada</h1>
            </div>
            
            <div style='background: #fff; padding: 30px; border-radius: 0 0 12px 12px;'>
                <h2 style='color: #17a2b8; margin-top: 0;'>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
                
                <p style='font-size: 16px; line-height: 1.6; color: #333;'>
                    Tu información en <strong>LumiSpace</strong> ha sido actualizada por el administrador.
                </p>
                
                <div style='background: #f5f3f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #17a2b8; margin-top: 0;'>📋 Información actualizada</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>📧 Correo:</td>
                            <td style='padding: 8px 0; color: #333;'>{$email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>👤 Rol:</td>
                            <td style='padding: 8px 0; color: #333;'>{$rolTexto}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>💼 Puesto:</td>
                            <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars(ucfirst($puesto), ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>📊 Estado:</td>
                            <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars(ucfirst($estado), ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                    </table>
                </div>
                " . ($password !== '' ? "
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0; color: #856404; font-weight: bold;'>🔑 Tu contraseña ha sido actualizada</p>
                    <p style='margin: 8px 0 0 0; color: #856404;'>
                        Si no reconoces este cambio, contacta al administrador inmediatamente.
                    </p>
                </div>
                " : "") . "
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: linear-gradient(135deg, #17a2b8, #138496); color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px;'>
                        🚀 Ir al Sistema
                    </a>
                </p>
                
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                
                <p style='color: #777; font-size: 14px; line-height: 1.6;'>
                    Si tienes alguna duda sobre estos cambios, contacta al administrador del sistema.
                </p>
                
                <p style='color: #999; font-size: 12px; margin-top: 20px;'>
                    Este es un correo automático, por favor no respondas a este mensaje.
                </p>
            </div>
            
            <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
                <p>© " . date('Y') . " LumiSpace. Todos los derechos reservados.</p>
            </div>
        </div>
    ";
    
    @enviarCorreo($email, $subject, $body);
}

// ✅ Redirigir con mensaje de éxito
$nombreUsuario = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
header("Location: usuarios.php?msg=" . urlencode("✅ Usuario '{$nombreUsuario}' actualizado correctamente"));
exit();