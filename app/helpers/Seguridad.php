<?php
// Funciones de apoyo relacionadas con seguridad.
// Se agrupan aparte porque no pertenecen a un Modelo (no tocan la base
// de datos) ni a un Controlador especifico: las usan varios a la vez.

class Seguridad
{
    private const MAX_INTENTOS       = 5;
    private const BLOQUEO_SEGUNDOS   = 60;

    // Genera el token CSRF de la sesion actual, o reutiliza el existente.
    // Debe incluirse como campo oculto en todo formulario que modifique datos.
    public static function tokenCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    // Compara el token recibido en un formulario contra el de la sesion.
    // hash_equals evita filtrar informacion por diferencias de tiempo.
    public static function validarCsrf(?string $tokenRecibido): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($tokenRecibido)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $tokenRecibido);
    }

    // Registra un intento fallido de login para el control de fuerza bruta.
    public static function registrarIntentoFallido(): void
    {
        $_SESSION['intentos_login'] = ($_SESSION['intentos_login'] ?? 0) + 1;
        $_SESSION['ultimo_intento'] = time();
    }

    // Indica si se deben bloquear temporalmente los intentos de login.
    public static function loginBloqueado(): bool
    {
        $intentos = $_SESSION['intentos_login'] ?? 0;
        $ultimo   = $_SESSION['ultimo_intento'] ?? 0;

        if ($intentos >= self::MAX_INTENTOS && (time() - $ultimo) < self::BLOQUEO_SEGUNDOS) {
            return true;
        }

        // Paso el tiempo de bloqueo: se reinicia el contador.
        if ($intentos >= self::MAX_INTENTOS) {
            self::limpiarIntentos();
        }

        return false;
    }

    public static function limpiarIntentos(): void
    {
        unset($_SESSION['intentos_login'], $_SESSION['ultimo_intento']);
    }
}
