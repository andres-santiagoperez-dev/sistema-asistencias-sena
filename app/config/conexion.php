<?php
// Conexion unica a la base de datos usando PDO.
// Todos los modelos obtienen la conexion a traves de Conexion::obtener(),
// nunca creando su propio new PDO(...).

require_once __DIR__ . '/config.php';

class Conexion
{
    private static ?PDO $instancia = null;

    // Evita que la clase se instancie desde fuera.
    private function __construct()
    {
    }

    public static function obtener(): PDO
    {
        if (self::$instancia === null) {
            $puerto = defined('DB_PORT') && !empty(DB_PORT) ? ';port=' . DB_PORT : '';
            $dsn    = 'mysql:host=' . DB_HOST . $puerto . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

            self::$instancia = new PDO($dsn, DB_USER, DB_PASS, [
                // Los errores de PDO se convierten en excepciones capturables.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

                // Los resultados llegan como arreglos asociativos por defecto.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Las consultas preparadas se envian de forma nativa al motor,
                // en vez de ser emuladas por PHP.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            self::$instancia->exec("SET time_zone = '-05:00'");
        }

        return self::$instancia;
    }
}
