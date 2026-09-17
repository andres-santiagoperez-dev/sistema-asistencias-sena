<?php
/**
 * Inicializador global del sistema (Bootstrap/Init)
 * 
 * Responsabilidades:
 * 1. Garantizar que no se acceda directamente a archivos del sistema.
 * 2. Definir rutas físicas absolutas (BASE_PATH, APP_PATH) y URL base (BASE_URL).
 * 3. Iniciar sesión de forma segura y consistente.
 * 4. Cargar la conexión central a la base de datos.
 */

// 1. Guardia de seguridad: evita ejecución directa vía navegador
defined('ACCESO_PERMITIDO') or exit('Acceso denegado.');

// 2. Definición de rutas absolutas en disco
define('BASE_PATH', dirname(__DIR__, 2)); // Raíz del proyecto
define('APP_PATH', BASE_PATH . '/app');   // Carpeta de la aplicación

// 3. Cálculo dinámico de la URL base
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl   = rtrim(str_replace('\\', '/', $scriptDir), '/');
define('BASE_URL', $baseUrl);

// 4. Configuración y arranque de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 5. Carga de la conexión a la base de datos
require_once APP_PATH . '/config/database.php';
