<?php
/**
 * Controlador del Dashboard del Instructor (InstructorDashboardController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al panel docente verificando el rol 'instructor'.
 * 2. Orquestar la obtención de KPIs y fichas asignadas desde InstructorModel.
 * 3. Renderizar la vista desacoplada ('instructor/dashboard') inyectando los datos.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorDashboardController extends BaseController
{
    private InstructorModel $instructorModel;

    public function __construct()
    {
        $this->instructorModel = new InstructorModel();
    }

    /**
     * Muestra el panel principal del instructor con estadísticas y fichas formativas a cargo.
     */
    public function index(): void
    {
        // 1. Proteger acceso para rol instructor
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;

        $stats = [
            'fichasAsignadas'   => 0,
            'aprendicesACargo'  => 0,
            'retardosHoy'       => 0,
            'excusasPendientes' => 0,
        ];
        $misFichas = [];

        try {
            // 2. Recuperación Segura de id_instructor si no está en sesión
            if (empty($idInstructor)) {
                $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
                if ($idInstructor) {
                    $_SESSION['id_instructor'] = (int)$idInstructor;
                }
            }

            // 3. Consulta de Métricas y Fichas Formativas a Cargo
            if (!empty($idInstructor)) {
                $stats     = $this->instructorModel->obtenerEstadisticasDashboard((int)$idInstructor);
                $misFichas = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);
            }
        } catch (Throwable $e) {
            error_log("InstructorDashboardController::index: " . $e->getMessage());
        }

        // 4. Renderizar vista de presentación desacoplada
        $this->renderView('instructor/dashboard', [
            'tituloPagina' => 'Mis Fichas',
            'stats'        => $stats,
            'misFichas'    => $misFichas,
        ]);
    }
}
