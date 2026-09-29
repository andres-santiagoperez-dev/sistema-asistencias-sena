<?php
/**
 * Controlador de Horario y Calendario del Aprendiz
 * Sistema de Control de Asistencia SENA
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once dirname(__DIR__, 2) . '/Models/AprendizModel.php';

class HorarioController
{
    private AprendizModel $aprendizModel;

    public function __construct()
    {
        $this->aprendizModel = new AprendizModel();
    }

    /**
     * Muestra la vista del horario semanal y calendario interactivo.
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

        $infoAprendiz = [];
        $marcacionHoy = [];
        $asistencias  = [];
        $horarioStr   = '06:00 - 12:00';

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

            // 3. Consultar datos de formación, marcación de hoy y asistencias individuales
            if (!empty($idAprendiz)) {
                $infoAprendiz = $this->aprendizModel->obtenerHorarioPorId($idAprendiz) ?: [];
                $marcacionHoy = $this->aprendizModel->obtenerMarcacionHoy($idAprendiz) ?: [];
                $asistencias  = $this->aprendizModel->obtenerHistorialAsistencia($idAprendiz) ?: [];

                $horaEntrada = $infoAprendiz['hora_entrada'] ?? '06:00';
                $horaSalida  = $infoAprendiz['hora_salida'] ?? '12:00';
                $horarioStr  = $horaEntrada . ' - ' . $horaSalida;
            }
        } catch (Throwable $e) {
            // Registro defensivo en servidor para evitar Information Disclosure
            error_log("HorarioController::index: " . $e->getMessage());
            $infoAprendiz = [];
            $marcacionHoy = [];
            $asistencias  = [];
        }

        // 4. Variables descriptivas para la vista
        $tituloPagina = 'Mi Horario & Calendario';
        $numeroFicha  = $infoAprendiz['numero_ficha'] ?? 'Sin Ficha';
        $programa     = $infoAprendiz['programa'] ?? 'ADSO';

        // 5. Renderizar vista de presentación
        require_once dirname(__DIR__, 2) . '/Views/aprendiz/aprendiz-horario.php';
    }
}
