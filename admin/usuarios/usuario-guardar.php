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

// La contraseña será igual al email (como lo tenías)
$password       = $email;

// ====== Validaciones obligatorias ======
$errores = [];

// Nombre: solo letras (incluye acentos) y espacios
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
    $errores[] = "Rol inválido. Debe ser: usuario, gestor, cajero o admin.";
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

// Salario (opcional, pero si viene debe ser válido)
if ($salario !== '' && (!is_numeric($salario) || $salario < 0)) {
    $errores[] = "El salario debe ser un número válido mayor o igual a 0.";
}

// Si hay errores, regresamos
if (!empty($errores)) {
    $q = urlencode(implode(' | ', $errores));
    header("Location: usuario-agregar.php?error={$q}");
    exit();
}

// ====== Validación de duplicados ======
$conn = getDBConnection();

// Verificar email duplicado
$chkEmail = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
$chkEmail->bind_param("s", $email);
$chkEmail->execute();
$dupEmail = $chkEmail->get_result()->fetch_assoc();
$chkEmail->close();

if ($dupEmail) {
    header("Location: usuario-agregar.php?error=" . urlencode("El correo ya está registrado."));
    exit();
}

// Verificar número de empleado duplicado
$chkEmp = $conn->prepare("SELECT id FROM usuarios WHERE num_empleado = ? LIMIT 1");
$chkEmp->bind_param("s", $num_empleado);
$chkEmp->execute();
$dupEmp = $chkEmp->get_result()->fetch_assoc();
$chkEmp->close();

if ($dupEmp) {
    header("Location: usuario-agregar.php?error=" . urlencode("El número de empleado ya está registrado."));
    exit();
}

// ====== Preparar datos para inserción ======
$hash           = password_hash($password, PASSWORD_DEFAULT);
$estado         = 'activo';
$proveedor      = 'manual';
$provider_id    = null;
$email_verificado = 1;  // Verificado automáticamente
$token          = null;

// Convertir salario vacío a NULL
$salarioFinal = ($salario === '') ? null : floatval($salario);

// ====== Verificar columnas disponibles ======
$hasFecha = false;
$colsRes = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'fecha_registro'");
if ($colsRes && $colsRes->num_rows > 0) $hasFecha = true;

// ====== Inserción en base de datos ======
// Nota: Asegúrate de que tu tabla tenga estas columnas. Si no, créalas con:
// ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20);
// ALTER TABLE usuarios ADD COLUMN direccion TEXT;
// ALTER TABLE usuarios ADD COLUMN puesto VARCHAR(100);
// ALTER TABLE usuarios ADD COLUMN num_empleado VARCHAR(50) UNIQUE;
// ALTER TABLE usuarios ADD COLUMN fecha_ingreso DATE;
// ALTER TABLE usuarios ADD COLUMN salario DECIMAL(10,2);
// ALTER TABLE usuarios ADD COLUMN sucursal VARCHAR(100);

if ($hasFecha) {
    $sql = "INSERT INTO usuarios 
        (nombre, email, password, telefono, direccion, rol, puesto, num_empleado, 
         fecha_ingreso, salario, sucursal, estado, proveedor, provider_id, 
         email_verificado, token_verificacion, fecha_registro)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
} else {
    $sql = "INSERT INTO usuarios 
        (nombre, email, password, telefono, direccion, rol, puesto, num_empleado, 
         fecha_ingreso, salario, sucursal, estado, proveedor, provider_id, 
         email_verificado, token_verificacion)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header("Location: usuario-agregar.php?error=" . urlencode("Error al preparar consulta: " . $conn->error));
    exit();
}

if ($hasFecha) {
    $stmt->bind_param(
        "sssssssssdssssss",
        $nombre, $email, $hash, $telefono, $direccion, 
        $rol, $puesto, $num_empleado, $fecha_ingreso, $salarioFinal, 
        $sucursal, $estado, $proveedor, $provider_id, 
        $email_verificado, $token
    );
} else {
    $stmt->bind_param(
        "sssssssssdssssss",
        $nombre, $email, $hash, $telefono, $direccion, 
        $rol, $puesto, $num_empleado, $fecha_ingreso, $salarioFinal, 
        $sucursal, $estado, $proveedor, $provider_id, 
        $email_verificado, $token
    );
}

$ok = $stmt->execute();
if (!$ok) {
    $err = $stmt->error;
    $stmt->close();
    header("Location: usuario-agregar.php?error=" . urlencode("No se pudo guardar: $err"));
    exit();
}

$nuevoId = $stmt->insert_id;
$stmt->close();

// ====== Enviar correo de bienvenida mejorado ======
if (function_exists('enviarCorreo')) {
    $base = $_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace';
    $loginUrl = rtrim($base, '/') . "/views/login.php";
    
    // Traducir rol a texto legible
    $rolTexto = [
        'usuario' => 'Usuario (Cliente)',
        'cajero' => 'Cajero',
        'gestor' => 'Gestor',
        'admin' => 'Administrador'
    ][$rol] ?? ucfirst($rol);
    
    // Formatear salario si existe
    $salarioTexto = $salarioFinal ? '$' . number_format($salarioFinal, 2) : 'No especificado';
    
    $subject = "🎉 Bienvenido al equipo de LumiSpace";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #a1683a, #8f5e4b); padding: 30px; border-radius: 12px 12px 0 0; text-align: center;'>
                <h1 style='color: #fff; margin: 0;'>¡Bienvenido a LumiSpace! 🎊</h1>
            </div>
            
            <div style='background: #fff; padding: 30px; border-radius: 0 0 12px 12px;'>
                <h2 style='color: #a1683a; margin-top: 0;'>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
                
                <p style='font-size: 16px; line-height: 1.6; color: #333;'>
                    Tu cuenta ha sido creada exitosamente por el administrador. A continuación encontrarás los detalles de tu registro:
                </p>
                
                <div style='background: #f5f3f0; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #a1683a; margin-top: 0;'>📋 Información de tu cuenta</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>📧 Correo:</td>
                            <td style='padding: 8px 0; color: #333;'>{$email}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>🔑 Contraseña inicial:</td>
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
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>🆔 Núm. Empleado:</td>
                            <td style='padding: 8px 0; color: #333;'>{$num_empleado}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>📅 Fecha de ingreso:</td>
                            <td style='padding: 8px 0; color: #333;'>" . date('d/m/Y', strtotime($fecha_ingreso)) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #666; font-weight: bold;'>🏢 Sucursal:</td>
                            <td style='padding: 8px 0; color: #333;'>" . htmlspecialchars(ucfirst($sucursal), ENT_QUOTES, 'UTF-8') . "</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0; color: #856404; font-weight: bold;'>⚠️ Importante:</p>
                    <p style='margin: 8px 0 0 0; color: #856404;'>
                        Por seguridad, te recomendamos cambiar tu contraseña después del primer inicio de sesión.
                    </p>
                </div>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$loginUrl}' style='display: inline-block; background: linear-gradient(135deg, #a1683a, #8f5e4b); color: #fff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 16px;'>
                        🚀 Iniciar Sesión
                    </a>
                </p>
                
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                
                <p style='color: #777; font-size: 14px; line-height: 1.6;'>
                    Si tienes alguna duda o problema para acceder, no dudes en contactar al administrador del sistema.
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
header("Location: usuarios.php?msg=" . urlencode("✅ Empleado registrado exitosamente (ID #{$nuevoId}). Se envió correo de bienvenida a {$email}"));
exit();