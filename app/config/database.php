<?php
/**
 * Adaptador / Puente de Conexión a Base de Datos (Database)
 * 
 * Este archivo provee compatibilidad con las vistas y controladores existentes
 * que requieren 'config/database.php' y llaman a Database::conectar(),
 * delegando la conexión al Singleton oficial del sistema: Conexion::obtener().
 */

require_once __DIR__ . '/conexion.php';

class Database
{
    public static function conectar(): PDO
    {
        return Conexion::obtener();
    }
}
