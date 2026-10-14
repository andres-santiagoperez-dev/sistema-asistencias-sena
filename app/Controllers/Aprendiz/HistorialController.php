<?php
/**
 * Controlador de Historial de Asistencia del Aprendiz
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Validar autenticación y rol de aprendiz.
 * 2. Recuperar de forma segura el identificador del aprendiz en sesión.
 * 3. Capturar y sanitizar filtros temporales por rango de fechas (fecha_inicio, fecha_fin).
 * 4. Consultar historial de asistencias, métricas KPI y estado de marcación de hoy vía AprendizModel.
 * 5. Blindaje defensivo en 3 niveles (try-catch Throwable, logs privados y fallbacks seguros).
 * 6. Delegar a la vista aprendiz-historial.php.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once dirname(__DIR__, 2) . '/Models/AprendizModel.php';

class HistorialController
{
    private AprendizModel $aprendizModel;

    public function __construct()
    {
        $this->aprendizModel = new AprendizModel();
    }

    /**
     * Muestra el historial de asistencias del aprendiz con filtros de fecha y KPIs.
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

        // 2. Filtros de Fecha Sanitizados
        $fechaInicio = !empty($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : null;
        $fechaFin    = !empty($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : null;

        $infoAprendiz = [];
        $marcacionHoy = [];
        $historial    = [];
        $stats        = [
            'a_tiempo'                  => 0,
            'retardos'                  => 0,
            'inasistencias'             => 0,
            'inasistencias_justificadas' => 0
        ];
        $horarioStr   = '06:00 - 12:00';

        try {
            // 3. Recuperación Segura de id_aprendiz si no está en sesión
            if (empty($idAprendiz)) {
                $datosSesion = $this->aprendizModel->obtenerIdAprendizPorUsuario($idUsuario);
                if ($datosSesion) {
                    $idAprendiz = (int)$datosSesion['id_aprendiz'];
                    $_SESSION['id_aprendiz'] = $idAprendiz;
                    $_SESSION['id_ficha']    = $datosSesion['fichas_id_ficha'];
                    $_SESSION['codigo_rfid'] = $datosSesion['codigo_rfid'];
                }
            }

            // 4. Consultar datos de formación, marcación de hoy, historial y KPIs
            if (!empty($idAprendiz)) {
                $infoAprendiz = $this->aprendizModel->obtenerHorarioPorId($idAprendiz) ?: [];
                $marcacionHoy = $this->aprendizModel->obtenerMarcacionHoy($idAprendiz) ?: [];
                $historial    = $this->aprendizModel->obtenerHistorialAsistencia($idAprendiz, $fechaInicio, $fechaFin);
                $stats        = $this->aprendizModel->obtenerEstadisticasHistorial($idAprendiz, $fechaInicio, $fechaFin);

                $horarioStr  = $this->aprendizModel->obtenerJornadaActivaAprendiz($idAprendiz);
            }
        } catch (Throwable $e) {
            // Registro silencioso en log del servidor para evitar Information Disclosure
            error_log("HistorialController::index: " . $e->getMessage());
            $infoAprendiz = [];
            $marcacionHoy = [];
            $historial    = [];
            $stats        = [
                'a_tiempo'                  => 0,
                'retardos'                  => 0,
                'inasistencias'             => 0,
                'inasistencias_justificadas' => 0
            ];
        }

        // 5. Variables para la vista
        $tituloPagina = 'Historial de Asistencia';
        $numeroFicha  = $infoAprendiz['numero_ficha'] ?? 'Sin Ficha';
        $programa     = $infoAprendiz['programa'] ?? 'ADSO';

        // 6. Cargar la vista de presentación
        require_once dirname(__DIR__, 2) . '/Views/aprendiz/aprendiz-historial.php';
    }

    /**
     * Muestra el Certificado Oficial de Asistencia del Aprendiz en formato imprimible/PDF.
     */
    public function exportarPdf(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'aprendiz') === false) {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit();
        }

        $idUsuario  = (int)$_SESSION['id_usuario'];
        $idAprendiz = $_SESSION['id_aprendiz'] ?? null;

        $fechaInicio = !empty($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : null;
        $fechaFin    = !empty($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : null;

        $infoAprendiz = [];
        $historial    = [];
        $stats        = [
            'a_tiempo'                  => 0,
            'retardos'                  => 0,
            'inasistencias'             => 0,
            'inasistencias_justificadas' => 0
        ];

        try {
            if (empty($idAprendiz)) {
                $datosSesion = $this->aprendizModel->obtenerIdAprendizPorUsuario($idUsuario);
                if ($datosSesion) {
                    $idAprendiz = (int)$datosSesion['id_aprendiz'];
                    $_SESSION['id_aprendiz'] = $idAprendiz;
                    $_SESSION['id_ficha']    = $datosSesion['fichas_id_ficha'];
                }
            }

            if (!empty($idAprendiz)) {
                $infoAprendiz = $this->aprendizModel->obtenerHorarioPorId($idAprendiz) ?: [];
                $historial    = $this->aprendizModel->obtenerHistorialAsistencia($idAprendiz, $fechaInicio, $fechaFin);
                $stats        = $this->aprendizModel->obtenerEstadisticasHistorial($idAprendiz, $fechaInicio, $fechaFin);
            }
        } catch (Throwable $e) {
            error_log("HistorialController::exportarPdf: " . $e->getMessage());
        }

        require_once dirname(__DIR__, 2) . '/Views/aprendiz/historial_impresion.php';
        exit;
    }
}
