<?php
/**
 * Configuración General y Parámetros de Base de Datos
 * Sistema de Control de Asistencia SENA
 * 
 * Consume las variables definidas en el entorno (.env) a través de la función env().
 */

// Respaldo defensivo: si no se ha cargado el helper env(), se inicializa aquí
if (!function_exists('env')) {
    $rutaEnvHelper = dirname(__DIR__) . '/helpers/Env.php';
    if (file_exists($rutaEnvHelper)) {
        require_once $rutaEnvHelper;
        Env::cargar(dirname(__DIR__, 2) . '/.env');
    }
}

// Zona horaria institucional
date_default_timezone_set(env('APP_TIMEZONE', 'America/Bogota'));

// Parámetros de conexión a la base de datos
define('DB_HOST', env('DB_HOST', '195.35.61.56'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'u137387537_araujo'));
define('DB_USER', env('DB_USER', 'u137387537_araujo'));
define('DB_PASS', env('DB_PASS', 'gftv8AWs4@U'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Modo de depuración (true en desarrollo local, false en producción)
define('MODO_DEBUG', (bool) env('APP_DEBUG', false));
