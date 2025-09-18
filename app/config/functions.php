<?php
include_once(__DIR__ . "/db.php");
require_once __DIR__ . "/mail.php"; // 📩 para enviar correos

/**
 * Normaliza y valida email.
 */
function _normalizeEmail(string $email): string {
    return strtolower(trim($email));
}

/**
 * Valida un rol y lo restringe a valores permitidos.
 */
function _sanitizeRol(?string $rol): string {
    $rol = strtolower(trim((string)$rol));
    $permitidos = ['usuario', 'gestor', 'admin', 'dueno'];
    return in_array($rol, $permitidos, true) ? $rol : 'usuario';
}

/**
 * Busca usuario por email (login normal y social).
 */
function obtenerUsuarioPorEmail(string $email): ?array {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $sql  = "SELECT id, nombre, email, password, rol, estado, proveedor, provider_id, email_verificado 
             FROM usuarios 
             WHERE email = ? 
             LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    return $res->fetch_assoc() ?: null;
}

/**
 * Inserta usuario clásico (correo + contraseña).
 */
function registrarUsuario(string $nombre, string $email, ?string $password, string $rol = "usuario") {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);
    $rol   = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Evita duplicados
    if (obtenerUsuarioPorEmail($email)) {
        return false;
    }

    // Hashea contraseña (opcional en social login)
    $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

    // Token de verificación de email
    $token = bin2hex(random_bytes(32));

    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado, token_verificacion) 
             VALUES (?, ?, ?, ?, 'activo', 'manual', NULL, 0, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $email, $hash, $rol, $token);

    if (!$stmt->execute()) {
        error_log("❌ Error en registrarUsuario: " . $stmt->error);
        return false;
    }

    $id = $stmt->insert_id;

    // 📩 Enviar correo de bienvenida con verificación
    if (function_exists('enviarCorreo')) {
        $verifyLink = ($_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace') . "/views/verify.php?token=$token";

        $subject = "Confirma tu cuenta en LumiSpace";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
            <p>Tu cuenta en <b>LumiSpace</b> fue creada con éxito.</p>
            <p>Por favor, confirma tu correo electrónico haciendo clic en el siguiente botón:</p>
            <p style='text-align:center;'>
                <a href='$verifyLink' 
                   style='display:inline-block;background:#4CAF50;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;'>
                   Confirmar Correo
                </a>
            </p>
            <p>Si no fuiste tú, ignora este mensaje.</p>
        ";
        enviarCorreo($email, $subject, $body);
    }

    return $id;
}

/**
 * Alias para compatibilidad.
 */
function insertarUsuario(string $nombre, string $email, ?string $password, string $rol = 'usuario') {
    $res = registrarUsuario($nombre, $email, $password, $rol);
    return $res === false ? false : (int)$res;
}

/**
 * Registro / inicio social (Google).
 */
function registrarUsuarioSocial(string $nombre, string $email, ?string $providerId = null, string $rol = "usuario", string $proveedor = "google"): ?array {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);
    $rol   = _sanitizeRol($rol);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    // ¿Ya existe?
    $user = obtenerUsuarioPorEmail($email);
    if ($user) {
        return $user;
    }

    // Crear usuario social
    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, estado, proveedor, provider_id, email_verificado) 
             VALUES (?, ?, NULL, ?, 'activo', ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $rol, $proveedor, $providerId);

    if (!$stmt->execute()) {
        error_log("❌ Error en registrarUsuarioSocial: " . $stmt->error);
        return obtenerUsuarioPorEmail($email);
    }

    $id = $stmt->insert_id;

    // 📩 Enviar correo de bienvenida Google
    if (function_exists('enviarCorreo')) {
        $subject = "¡Bienvenido a LumiSpace con Google!";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</h2>
            <p>Te has registrado en <b>LumiSpace</b> usando tu cuenta de Google.</p>
            <p>A partir de ahora puedes iniciar sesión directamente con tu correo: <b>$email</b></p>
            <p style='text-align:center;'>
                <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost/LumiSpace') . "/views/login.php' 
                   style='display:inline-block;background:#4285F4;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;'>
                   Iniciar con Google
                </a>
            </p>
            <p>Gracias por confiar en nosotros ✨</p>
        ";
        enviarCorreo($email, $subject, $body);
    }

    return [
        "id"          => $id,
        "nombre"      => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        "email"       => $email,
        "rol"         => $rol,
        "estado"      => "activo",
        "proveedor"   => $proveedor,
        "provider_id" => $providerId,
        "email_verificado" => 1
    ];
}
