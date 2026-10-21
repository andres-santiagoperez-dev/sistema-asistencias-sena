<?php
/**
 * Configuración del Servidor de Correo (SMTP)
 * Sistema de Control de Asistencias SENA
 * 
 * Proveedor actual: Hostinger (adsocta.com)
 */

return [
    // Servidor SMTP de Hostinger
    'host'        => 'smtp.hostinger.com',
    'port'        => 465,                 // 465 para SSL (Recomendado Hostinger) o 587 para TLS
    'smtp_secure' => 'ssl',                 // 'ssl' (puerto 465) o 'tls' (puerto 587)
    'smtp_auth'   => true,

    // Credenciales del emisor
    'username'    => 'ingresoscta@adsocta.com',
    
    // Contraseña de la cuenta de correo creada en Hostinger
    'password'    => '*Adso3234081*',

    // Datos del remitente
    'from_email'  => 'ingresoscta@adsocta.com',
    'from_name'   => 'SENA - Control de Asistencia',
    
    // Tiempo de validez del código en minutos
    'code_expiry_minutes' => 15,
];
