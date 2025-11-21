<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   Destruir Sesión
   ========================= */
$_SESSION = [];  // Limpia variables de sesión

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();  // Destruye la sesión

/* =========================
   Eliminar cookies de Google
   ========================= */
if (isset($_COOKIE['g_state'])) {
    setcookie("g_state", "", time() - 3600, "/");
}

/* =========================
   Redirigir al login
   ========================= */
header("Location: views/login.php?logout=1");
exit();
