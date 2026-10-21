<?php
// Arranque comun del proyecto.
// Cada pagina real (login/index.php, tareas/index.php,
// tareas/crear/index.php...) empieza incluyendo este archivo antes de
// hacer cualquier otra cosa. Aqui se define la sesion, la conexion y
// se cargan los Modelos y Controladores que existen en el proyecto.

declare(strict_types=1);

date_default_timezone_set('America/Bogota');

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

// Autoloader de Composer para librerías de terceros (PhpSpreadsheet, etc.)
if (file_exists(dirname(BASE_PATH) . '/vendor/autoload.php')) {
    require_once dirname(BASE_PATH) . '/vendor/autoload.php';
}

// La cookie de sesion se configura ANTES de session_start(), o estas
// opciones no tendrian efecto. Solo si la sesión aún no está activa.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Carga de base de datos, helpers y controlador base
require_once BASE_PATH . '/config/conexion.php';
require_once BASE_PATH . '/helpers/Seguridad.php';
require_once BASE_PATH . '/Services/MailService.php';
require_once BASE_PATH . '/Controllers/BaseController.php';

// Controladores de autenticación y base
require_once BASE_PATH . '/Controllers/LoginController.php';

// Controladores del Módulo de Administración (Admin)
require_once BASE_PATH . '/Controllers/Admin/DashboardController.php';
require_once BASE_PATH . '/Controllers/Admin/FichaController.php';
require_once BASE_PATH . '/Controllers/Admin/InstructorController.php';
require_once BASE_PATH . '/Controllers/Admin/AprendizController.php';
require_once BASE_PATH . '/Controllers/Admin/AdminController.php';
require_once BASE_PATH . '/Controllers/Admin/ReporteController.php';

// Controladores del Portal del Aprendiz
require_once BASE_PATH . '/Controllers/Aprendiz/HorarioController.php';
require_once BASE_PATH . '/Controllers/Aprendiz/HistorialController.php';
require_once BASE_PATH . '/Controllers/Aprendiz/PerfilController.php';
require_once BASE_PATH . '/Controllers/Aprendiz/ExcusaController.php';

// Controladores del Portal del Instructor
require_once BASE_PATH . '/Controllers/Instructor/DashboardController.php';
require_once BASE_PATH . '/Controllers/Instructor/AprendizController.php';
require_once BASE_PATH . '/Controllers/Instructor/HorarioController.php';
require_once BASE_PATH . '/Controllers/Instructor/ExcusaController.php';
require_once BASE_PATH . '/Controllers/Instructor/IngresosController.php';




// Manejador global de excepciones: registra el fallo en el log del servidor
// y previene cualquier volcado de errores técnicos (como SQLSTATE) en el frontend.
set_exception_handler(function (Throwable $e): void {
    error_log('[EXCEPCIÓN NO CAPTURADA] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());

    // Si la sesión está activa y aún no se han enviado cabeceras, redirige con aviso amigable
    if (!headers_sent() && isset($_SESSION['id_usuario'])) {
        $destino = defined('BASE_URL') ? BASE_URL . '/index.php?page=dashboard' : 'index.php';
        header("Location: {$destino}");
        exit;
    }

    http_response_code(500);
    echo '<!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>SENA - Sistema de Asistencias</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    </head>
    <body style="background:#131e3a; color:#fff; display:flex; align-items:center; justify-content:center; min-height:100vh; font-family:sans-serif;">
        <div class="card p-4 text-center border-0 shadow-lg" style="background:#1b2a47; max-width:480px; border-radius:12px;">
            <div class="mb-3 text-warning fs-1"><i class="bi bi-exclamation-triangle"></i> ⚠</div>
            <h4 class="fw-bold mb-2 text-white">Novedad de Carga</h4>
            <p class="text-secondary small mb-3">El sistema ha protegido tu sesión ante una novedad temporal en la base de datos. Por favor recarga o regresa al panel principal.</p>
            <a href="' . (defined('BASE_URL') ? BASE_URL : '.') . '/index.php" class="btn btn-success fw-bold" style="background:#39a900; border:none;">Volver al Inicio</a>
        </div>
    </body>
    </html>';
    exit;
});
