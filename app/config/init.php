<?php
// Arranque comun del proyecto.
// Cada pagina real (login/index.php, tareas/index.php,
// tareas/crear/index.php...) empieza incluyendo este archivo antes de
// hacer cualquier otra cosa. Aqui se define la sesion, la conexion y
// se cargan los Modelos y Controladores que existen en el proyecto.

declare(strict_types=1);

// Comentario: BASE_PATH es la carpeta raiz del proyecto (un nivel
// arriba de config/, que es donde vive este archivo).
define('BASE_PATH', dirname(__DIR__));

// BASE_URL se calcula comparando la ruta real en disco del archivo que
// se esta ejecutando contra su URL. Asi funciona sin importar cuantas
// carpetas de profundidad tenga esa pagina (tareas/ o tareas/crear/),
// ni en que subcarpeta de htdocs viva el proyecto completo.
$rutaBaseNormalizada   = str_replace('\\', '/', BASE_PATH);
$rutaScriptNormalizada = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);

if (str_starts_with($rutaScriptNormalizada, $rutaBaseNormalizada)) {
    $rutaDentroDelProyecto = substr($rutaScriptNormalizada, strlen($rutaBaseNormalizada));
} else {
    // Respaldo poco frecuente: si el servidor entrega las rutas de forma
    // que no coinciden byte a byte, se usa solo el nombre del script
    // actual para no romper el calculo con una resta negativa.
    $rutaDentroDelProyecto = '/' . basename($rutaScriptNormalizada);
}

define('BASE_URL', rtrim(
    substr(
        $_SERVER['SCRIPT_NAME'],
        0,
        max(0, strlen($_SERVER['SCRIPT_NAME']) - strlen($rutaDentroDelProyecto))
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
/* require_once BASE_PATH . '/Controllers/FichaController.php';
require_once BASE_PATH . '/Controllers/InstructorController.php'; */

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
