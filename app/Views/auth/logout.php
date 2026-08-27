<?php
session_start();

// 1. Vaciar todas las variables de sesión
$_SESSION = array();

// 2. Borrar la cookie de sesión del navegador (caché/cookies del equipo)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Finalmente, destruir la sesión en el servidor
session_destroy();

// 4. Redirigir al inicio de sesión
header("Location: login.php");
exit();
?>
