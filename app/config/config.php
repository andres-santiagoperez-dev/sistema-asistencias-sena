<?php
// Datos de conexion a la base de datos y ajustes generales del proyecto.
date_default_timezone_set('America/Bogota');

define('DB_HOST', 'localhost');
define('DB_NAME', 'sena_asistencia');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// En produccion este valor debe quedar en false.
// Controla si se muestran detalles tecnicos de los errores.
define('MODO_DEBUG', true);
