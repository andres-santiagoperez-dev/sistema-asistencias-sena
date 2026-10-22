<?php
declare(strict_types=1);

/**
 * Cargador Nativo de Variables de Entorno (.env)
 * Sistema de Control de Asistencia SENA
 * 
 * Permite cargar archivos de entorno .env sin dependencias externas
 * y proporciona la función helper global env().
 */
class Env
{
    /**
     * Carga las variables de un archivo .env en $_ENV, $_SERVER y putenv.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo .env
     * @return bool True si se cargó correctamente, false en caso contrario
     */
    public static function cargar(string $rutaArchivo): bool
    {
        if (!is_file($rutaArchivo) || !is_readable($rutaArchivo)) {
            return false;
        }

        $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lineas === false) {
            return false;
        }

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            // Ignorar líneas vacías o comentarios puros (# o ;)
            if ($linea === '' || str_starts_with($linea, '#') || str_starts_with($linea, ';')) {
                continue;
            }

            // Omitir prefijo "export " si existe
            if (str_starts_with($linea, 'export ')) {
                $linea = trim(substr($linea, 7));
            }

            // Buscar la primera aparición del signo igual '='
            $posIgual = strpos($linea, '=');
            if ($posIgual === false) {
                continue;
            }

            $clave = trim(substr($linea, 0, $posIgual));
            $valor = trim(substr($linea, $posIgual + 1));

            // Validar que la clave sea válida (letras, números, guiones bajos)
            if ($clave === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $clave)) {
                continue;
            }

            // Procesar el valor: comillas envolventes y comentarios al final
            if (preg_match('/^([\'"])(.*)\1(?:\s*#.*)?$/s', $valor, $coincidencias)) {
                $valor = $coincidencias[2];
            } else {
                // Si no tiene comillas envolventes, descartar comentario en línea
                $posComentario = strpos($valor, '#');
                if ($posComentario !== false) {
                    $valor = trim(substr($valor, 0, $posComentario));
                }
            }

            // Asignar en las superglobales y en el entorno de PHP
            $_ENV[$clave]    = $valor;
            $_SERVER[$clave] = $valor;
            putenv("{$clave}={$valor}");
        }

        return true;
    }

    /**
     * Obtiene el valor de una variable de entorno con tipado inteligente.
     *
     * @param string $clave Nombre de la variable
     * @param mixed $defecto Valor retornado si la clave no existe o está vacía
     * @return mixed
     */
    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        // Buscar en $_ENV, luego en $_SERVER y finalmente mediante getenv()
        $valor = $_ENV[$clave] ?? $_SERVER[$clave] ?? getenv($clave);

        if ($valor === false || $valor === null) {
            return $defecto;
        }

        if (is_string($valor)) {
            $valorTrim = trim($valor);
            $valorLower = strtolower($valorTrim);

            return match ($valorLower) {
                'true', '(true)'   => true,
                'false', '(false)' => false,
                'empty', '(empty)' => '',
                'null', '(null)'   => null,
                default            => $valor,
            };
        }

        return $valor;
    }
}

/**
 * Función global auxiliar env() para acceso rápido y limpio a las variables.
 */
if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::obtener($key, $default);
    }
}
