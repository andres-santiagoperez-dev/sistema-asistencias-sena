<?php

class Database
{
    private static ?PDO $conexion = null;

    // Cambiamos el tipo de retorno a PDO 
    public static function conectar(): ?PDO
    {
        if (self::$conexion === null) {
            $host    = 'localhost';
            $puerto  = '3306';
            $bd      = 'sena_asistencia';
            $usuario = 'root';
            $clave   = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$puerto};dbname={$bd};charset={$charset}";

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                // Instanciamos PDO
                self::$conexion = new PDO($dsn, $usuario, $clave, $opciones);
            } catch (PDOException $e) {
                // Si hay un error, lo atrapamos aquí y detenemos la ejecución
                die("Estamos Tratando de solucionar el problema");
            }
        }

        return self::$conexion;
    }
}