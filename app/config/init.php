<?php
// Arranque comun del proyecto.
// Cada pagina real (login/index.php, tareas/index.php,
// tareas/crear/index.php...) empieza incluyendo este archivo antes de
// hacer cualquier otra cosa. Aqui se define la sesion, la conexion y
// se cargan los Modelos y Controladores que existen en el proyecto.

declare(strict_types=1);

// Comentario: BASE_PATH apunta a la carpeta app del proyecto (un nivel
// arriba de config/, donde viven Controladores, Modelos y Vistas).
define('BASE_PATH', dirname(__DIR__));

// BASE_URL se calcula comparando la ruta real en disco de la raíz del proyecto
// (un nivel arriba de app/) contra la URL del script actual. Así funciona sin importar
// cuántas carpetas de profundidad tenga el archivo o en qué subcarpeta viva el proyecto.
$rutaRaizNormalizada   = str_replace('\\', '/', dirname(BASE_PATH));
$rutaScriptNormalizada = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');

if (str_starts_with($rutaScriptNormalizada, $rutaRaizNormalizada)) {
    $rutaDentroDelProyecto = substr($rutaScriptNormalizada, strlen($rutaRaizNormalizada));
} else {
    $rutaDentroDelProyecto = '/' . basename($rutaScriptNormalizada);
}

define('BASE_URL', rtrim(
    substr(
        $_SERVER['SCRIPT_NAME'] ?? '',
        0,
        max(0, strlen($_SERVER['SCRIPT_NAME'] ?? '') - strlen($rutaDentroDelProyecto))
    ),
    '/'
));

require_once BASE_PATH . '/config/config.php';

// La cookie de sesion se configura ANTES de session_start(), o estas
// opciones no tendrian efecto.
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// Carga de base de datos, helpers y controlador base
require_once BASE_PATH . '/config/conexion.php';
require_once BASE_PATH . '/helpers/Seguridad.php';
require_once BASE_PATH . '/Controllers/BaseController.php';

// Controladores específicos
require_once BASE_PATH . '/Controllers/LoginController.php';
require_once BASE_PATH . '/Controllers/FichaController.php';
require_once BASE_PATH . '/Controllers/InstructorController.php';

// Cualquier excepcion que ningun Controlador haya capturado llega hasta
// aqui. El detalle tecnico se registra en el servidor y el usuario final
// solo ve un mensaje generico, salvo en modo de depuracion.
set_exception_handler(function (Throwable $e): void {
    error_log('Excepcion no controlada: ' . $e->getMessage());

    http_response_code(500);

    if (MODO_DEBUG) {
        echo 'Error no controlado: ' . htmlspecialchars($e->getMessage());
        return;
    }

    echo 'Ocurrio un error inesperado. Intenta de nuevo mas tarde.';
});
