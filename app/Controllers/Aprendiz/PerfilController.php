<?php
/**
 * Controlador de Perfil y Ficha del Aprendiz
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Validar autenticación y rol exclusivo de aprendiz.
 * 2. Consultar perfil completo (datos personales, ficha, horario e instructor líder asignado).
 * 3. Procesar de forma segura la actualización de datos de contacto (correo y teléfono) desde el modal #editProfileModal.
 * 4. Blindaje defensivo en 3 niveles (try-catch Throwable, logs privados y fallbacks limpios).
 * 5. Delegar a la vista aprendiz-perfil.php.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once dirname(__DIR__, 2) . '/Models/AprendizModel.php';

class PerfilController
{
    private AprendizModel $aprendizModel;

    public function __construct()
    {
        $this->aprendizModel = new AprendizModel();
    }

    /**
     * Muestra la vista del perfil técnico y formativo del aprendiz.
     */
    public function index(): void
    {
        // 1. Verificación de Seguridad y Sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'aprendiz') === false) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit();
        }

        $idUsuario  = (int)$_SESSION['id_usuario'];
        $idAprendiz = $_SESSION['id_aprendiz'] ?? null;

        $perfil        = [];
        $marcacionHoy  = [];
        $mensajeExito  = $_SESSION['mensaje_exito'] ?? '';
        $mensajeError  = $_SESSION['mensaje_error'] ?? '';
        unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

        try {
            // 2. Recuperación Segura de id_aprendiz si no está en sesión
            if (empty($idAprendiz)) {
                $datosSesion = $this->aprendizModel->obtenerIdAprendizPorUsuario($idUsuario);
                if ($datosSesion) {
                    $idAprendiz = (int)$datosSesion['id_aprendiz'];
                    $_SESSION['id_aprendiz'] = $idAprendiz;
                    $_SESSION['id_ficha']    = $datosSesion['fichas_id_ficha'];
                    $_SESSION['codigo_rfid'] = $datosSesion['codigo_rfid'];
                }
            }

            // 3. Consultar perfil completo del aprendiz e instructor líder
            if (!empty($idAprendiz)) {
                $perfil       = $this->aprendizModel->obtenerPerfilCompleto($idAprendiz) ?: [];
                if (!empty($perfil)) {
                    $_SESSION['numero_ficha'] = $perfil['numero_ficha'] ?? ($_SESSION['numero_ficha'] ?? null);
                    $_SESSION['codigo_rfid']  = $perfil['codigo_rfid'] ?? ($_SESSION['codigo_rfid'] ?? null);
                    $_SESSION['programa']     = $perfil['programa'] ?? ($_SESSION['programa'] ?? null);
                }
                $marcacionHoy = $this->aprendizModel->obtenerMarcacionHoy($idAprendiz) ?: [];
            }
        } catch (Throwable $e) {
            // Blindaje defensivo en servidor para evitar Information Disclosure
            error_log("PerfilController::index: " . $e->getMessage());
            $perfil       = [];
            $marcacionHoy = [];
        }

        // 4. Variables descriptivas para la vista
        $tituloPagina     = 'Mi Perfil & Ficha';
        $numeroFicha      = $perfil['numero_ficha'] ?? 'Sin Ficha';
        $programa         = $perfil['programa'] ?? 'ADSO';
        $codigoRfid       = $perfil['codigo_rfid'] ?? 'Sin Asignar';
        $infoAprendiz     = $perfil;
        $horarioStr       = !empty($idAprendiz) ? $this->aprendizModel->obtenerJornadaActivaAprendiz($idAprendiz) : 'Jornada formativa SENA';

        $instructorNombre = trim(($perfil['instructor_nombres'] ?? 'Sin Asignar') . ' ' . ($perfil['instructor_apellidos'] ?? ''));
        $instructorCorreo = $perfil['instructor_correo'] ?? 'No disponible';

        // 5. Cargar vista de presentación
        require_once dirname(__DIR__, 2) . '/Views/aprendiz/aprendiz-perfil.php';
    }

    /**
     * Procesa la actualización de datos de contacto (teléfono y correo) desde el modal #editProfileModal.
     */
    public function actualizar(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'aprendiz') === false) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit();
        }

        // Prevención de IDOR: se utiliza el id_usuario autenticado en sesión
        $idUsuario = (int)$_SESSION['id_usuario'];
        $correo    = filter_var(trim($_POST['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
        $telefono  = trim($_POST['telefono'] ?? '');

        if (!$correo) {
            $_SESSION['mensaje_error'] = 'Debe suministrar un correo electrónico válido.';
            header('Location: ' . BASE_URL . '/index.php?page=aprendiz_perfil');
            exit();
        }

        try {
            $exito = $this->aprendizModel->actualizarContacto($idUsuario, $correo, !empty($telefono) ? $telefono : null);

            if ($exito) {
                $_SESSION['correo']   = $correo;
                $_SESSION['telefono'] = $telefono;
                $_SESSION['mensaje_exito'] = '¡Información de contacto actualizada exitosamente!';
            } else {
                $_SESSION['mensaje_error'] = 'No fue posible actualizar la información. Intente nuevamente.';
            }
        } catch (Throwable $e) {
            error_log("PerfilController::actualizar: " . $e->getMessage());
            $_SESSION['mensaje_error'] = 'Ocurrió un error inesperado al procesar la actualización.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=aprendiz_perfil');
        exit();
    }
}
