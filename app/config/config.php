<?php
// Datos de conexion a la base de datos y ajustes generales del proyecto.
// En un entorno real estos valores no deben subirse a un repositorio publico;
// aqui se dejan como constantes simples para fines didacticos.

define('DB_HOST', 'localhost');
define('DB_NAME', 'sena_asistencia');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// En produccion este valor debe quedar en false.
// Controla si se muestran detalles tecnicos de los errores.
define('MODO_DEBUG', true);
