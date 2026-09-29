<?php
/**
 * Controlador del Horario y Calendario del Instructor (InstructorHorarioController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al calendario del instructor verificando el rol 'instructor'.
 * 2. Consultar las fichas formativas a cargo del docente y sus horarios asignados.
 * 3. Recuperar las novedades (retardos, excusas) registradas en sus fichas.
 * 4. Renderizar la vista del calendario FullCalendar inyectando los datos.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorHorarioController extends BaseController
{
    private InstructorModel $instructorModel;

    public function __construct()
    {
        $this->instructorModel = new InstructorModel();
    }

    /**
     * Muestra la vista del horario y calendario de formación del instructor.
     */
    public function index(): void
    {
        // 1. Proteger acceso para rol instructor
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;
        $filtroFicha  = $_GET['ficha'] ?? null;

        $fichas    = [];
        $novedades = [];

        try {
            // 2. Recuperar id_instructor si no está en sesión
            if (empty($idInstructor)) {
                $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
                if ($idInstructor) {
                    $_SESSION['id_instructor'] = (int)$idInstructor;
                }
            }

            // 3. Consultar fichas asignadas y novedades
            if (!empty($idInstructor)) {
                $fichas    = $this->instructorModel->obtenerFichasAsignadas((int)$idInstructor);
                $novedades = $this->instructorModel->obtenerNovedadesCalendario((int)$idInstructor);
            }
        } catch (Throwable $e) {
            error_log("InstructorHorarioController::index: " . $e->getMessage());
        }

        // 4. Renderizar vista desacoplada
        $this->renderView('instructor/horario', [
            'tituloPagina' => 'Mi Horario & Calendario',
            'fichas'       => $fichas,
            'novedades'    => $novedades,
            'filtroFicha'  => $filtroFicha,
        ]);
    }
}
