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
 * Devuelve arreglo asociativo o null.
 */
function obtenerUsuarioPorEmail(string $email): ?array {
    $conn  = getDBConnection();
    $email = _normalizeEmail($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $sql  = "SELECT id, nombre, email, password, rol, proveedor, provider_id 
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
 * Retorna el ID insertado o false.
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

    // Hashea contraseña
    $hash = $password ? password_hash($password, PASSWORD_DEFAULT) : null;

    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, proveedor, provider_id) 
             VALUES (?, ?, ?, ?, 'manual', NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);

    if (!$stmt->execute()) {
        error_log("Error en registrarUsuario: " . $stmt->error);
        return false;
    }

    $id = $stmt->insert_id;

    // 📩 Enviar correo de bienvenida
    if (function_exists('enviarCorreo')) {
        $subject = "¡Bienvenido a LumiSpace!";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre) . "</h2>
            <p>Tu cuenta en <b>LumiSpace</b> fue creada con éxito.</p>
            <p>Correo registrado: <b>$email</b></p>
            <p style='text-align:center;'>
                <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost:8080') . "/views/login.php' 
                   style='display:inline-block;background:#4CAF50;color:#fff;padding:10px 18px;text-decoration:none;border-radius:6px;'>
                   Iniciar Sesión
                </a>
            </p>
            <p>Gracias por confiar en nosotros ✨</p>
        ";
        enviarCorreo($email, $subject, $body);
    }

    return $id;
}

/**
 * Compatibilidad con callback.
 */
function insertarUsuario(string $nombre, string $email, ?string $password, string $rol = 'usuario') {
    $res = registrarUsuario($nombre, $email, $password, $rol);
    if ($res === false) return false;
    return (int)$res;
}

/**
 * Registro / inicio social (Google).
 * Si existe → lo devuelve.
 * Si no existe → lo crea sin password con rol usuario.
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

    // Crear sin password, pero guardando proveedor e ID
    $sql  = "INSERT INTO usuarios (nombre, email, password, rol, proveedor, provider_id) 
             VALUES (?, ?, NULL, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nombre, $email, $rol, $proveedor, $providerId);

    if (!$stmt->execute()) {
        error_log("Error en registrarUsuarioSocial: " . $stmt->error);
        return obtenerUsuarioPorEmail($email);
    }

    $id = $stmt->insert_id;

    // 📩 Enviar correo de bienvenida Google
    if (function_exists('enviarCorreo')) {
        $subject = "¡Bienvenido a LumiSpace con Google!";
        $body = "
            <h2>Hola, " . htmlspecialchars($nombre) . "</h2>
            <p>Te has registrado en <b>LumiSpace</b> usando tu cuenta de Google.</p>
            <p>A partir de ahora puedes iniciar sesión directamente con tu correo: <b>$email</b></p>
            <p style='text-align:center;'>
                <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost:8080') . "/views/login.php' 
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
        "proveedor"   => $proveedor,
        "provider_id" => $providerId
    ];
}
