<?php
/**
 * Configuración del Servidor de Correo (SMTP)
 * Sistema de Control de Asistencias SENA
 * 
 * Los parámetros sensibles y de entorno se obtienen mediante la función env().
 */

// Respaldo defensivo: si no se ha cargado el helper env(), se inicializa aquí
if (!function_exists('env')) {
    $rutaEnvHelper = dirname(__DIR__) . '/helpers/Env.php';
    if (file_exists($rutaEnvHelper)) {
        require_once $rutaEnvHelper;
        Env::cargar(dirname(__DIR__, 2) . '/.env');
    }
}

return [
    // Servidor SMTP
    'host'                => env('MAIL_HOST', 'smtp.hostinger.com'),
    'port'                => (int) env('MAIL_PORT', 465),
    'smtp_secure'         => env('MAIL_ENCRYPTION', 'ssl'),
    'smtp_auth'           => (bool) env('MAIL_AUTH', true),

    // Credenciales de autenticación
    'username'            => env('MAIL_USERNAME', ''),
    'password'            => env('MAIL_PASSWORD', ''),

    // Identidad del remitente
    'from_email'          => env('MAIL_FROM_ADDRESS', env('MAIL_USERNAME', '')),
    'from_name'           => env('MAIL_FROM_NAME', 'SENA - Control de Asistencia'),

    // Tiempo de expiración para códigos de verificación (minutos)
    'code_expiry_minutes' => (int) env('MAIL_CODE_EXPIRY_MINUTES', 15),
];
