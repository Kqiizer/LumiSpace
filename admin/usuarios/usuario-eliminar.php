<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// Solo GET con ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: usuarios.php?error=" . urlencode("ID de usuario no proporcionado"));
    exit();
}

$idEliminar = intval($_GET['id']);

// Validar que el ID sea válido
if ($idEliminar <= 0) {
    header("Location: usuarios.php?error=" . urlencode("ID de usuario inválido"));
    exit();
}

// 🚫 Evitar que el admin se elimine a sí mismo
if ($idEliminar === intval($_SESSION['usuario_id'])) {
    header("Location: usuarios.php?error=" . urlencode("No puedes eliminarte a ti mismo"));
    exit();
}

// Conexión a la base de datos
$conn = getDBConnection();

// Verificar que el usuario existe y obtener su información
$stmt = $conn->prepare("SELECT nombre, email, rol FROM usuarios WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $idEliminar);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario) {
    header("Location: usuarios.php?error=" . urlencode("El usuario no existe"));
    exit();
}

// 🛡️ Protección adicional: evitar eliminar otros admins (opcional)
if ($usuario['rol'] === 'admin') {
    header("Location: usuarios.php?error=" . urlencode("No se pueden eliminar cuentas de administrador por seguridad"));
    exit();
}

// Eliminar el usuario
$stmtDelete = $conn->prepare("DELETE FROM usuarios WHERE id = ? LIMIT 1");
$stmtDelete->bind_param("i", $idEliminar);
$exito = $stmtDelete->execute();
$stmtDelete->close();

if (!$exito) {
    header("Location: usuarios.php?error=" . urlencode("Error al eliminar el usuario"));
    exit();
}

// 📧 Opcional: Enviar correo de notificación al usuario eliminado
if (function_exists('enviarCorreo')) {
    $subject = "Cuenta desactivada - LumiSpace";
    $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #dc3545, #c82333); padding: 30px; border-radius: 12px 12px 0 0; text-align: center;'>
                <h1 style='color: #fff; margin: 0;'>Cuenta Desactivada</h1>
            </div>
            
            <div style='background: #fff; padding: 30px; border-radius: 0 0 12px 12px;'>
                <h2 style='color: #dc3545; margin-top: 0;'>Hola, " . htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') . "</h2>
                
                <p style='font-size: 16px; line-height: 1.6; color: #333;'>
                    Te informamos que tu cuenta en <strong>LumiSpace</strong> ha sido desactivada por el administrador.
                </p>
                
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                    <p style='margin: 0; color: #856404;'>
                        Si crees que esto es un error, por favor contacta con el administrador del sistema.
                    </p>
                </div>
                
                <hr style='border: none; border-top: 1px solid #e0e0e0; margin: 30px 0;'>
                
                <p style='color: #999; font-size: 12px; margin-top: 20px;'>
                    Este es un correo automático, por favor no respondas a este mensaje.
                </p>
            </div>
            
            <div style='text-align: center; padding: 20px; color: #999; font-size: 12px;'>
                <p>© " . date('Y') . " LumiSpace. Todos los derechos reservados.</p>
            </div>
        </div>
    ";
    
    @enviarCorreo($usuario['email'], $subject, $body);
}

// ✅ Redirigir con mensaje de éxito
$nombreUsuario = htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8');
header("Location: usuarios.php?msg=" . urlencode("✅ Usuario '{$nombreUsuario}' eliminado correctamente"));
exit();