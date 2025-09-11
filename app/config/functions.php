<?php
include_once(__DIR__ . "/../config/db.php");

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
    $permitidos = ['usuario', 'gestor', 'admin'];
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

    $sql  = "SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? LIMIT 1";
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

    // Hashea contraseña con algoritmo por defecto (bcrypt/argon2i según PHP)
    $hash = $password ? password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]) : null;

    $sql  = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $email, $hash, $rol);

    if (!$stmt->execute()) {
        error_log("Error en registrarUsuario: " . $stmt->error);
        return false;
    }

    return $stmt->insert_id ?: true;
}

/**
 * Compatibilidad con callback.
 */
function insertarUsuario(string $nombre, string $email, ?string $password, string $rol = 'usuario') {
    $res = registrarUsuario($nombre, $email, $password, $rol);
    if ($res === false) return false;

    if ($res === true) {
        $u = obtenerUsuarioPorEmail($email);
        return $u['id'] ?? false;
    }
    return (int)$res;
}

/**
 * Registro / inicio social (Google).
 * Si existe → lo devuelve.
 * Si no existe → lo crea sin password con rol usuario.
 */
function registrarUsuarioSocial(string $nombre, string $email, string $proveedor = "google", string $rol = "usuario"): ?array {
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

    // Crear sin password
    $sql  = "INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, NULL, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $nombre, $email, $rol);

    if (!$stmt->execute()) {
        error_log("Error en registrarUsuarioSocial: " . $stmt->error);
        $u = obtenerUsuarioPorEmail($email);
        if ($u) return $u;
        return null;
    }

    $id = $stmt->insert_id;
    return [
        "id"     => $id,
        "nombre" => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'),
        "email"  => $email,
        "rol"    => $rol
    ];
}
