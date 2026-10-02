<?php
/**
 * Controlador del Horario y Calendario del Instructor (InstructorHorarioController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al calendario del instructor verificando el rol 'instructor'.
 * 2. Consultar las fichas formativas a cargo del docente y sus horarios asignados.
 * 3. Recuperar las novedades (retardos, excusas y notas de clase) registradas.
 * 4. Permitir crear, consultar y eliminar notas / observaciones sobre sesiones de clase.
 * 5. Renderizar la vista del calendario FullCalendar inyectando los datos.
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
        $bloques   = [];
        $novedades = [];

        try {
            // 2. Recuperar id_instructor si no está en sesión
            if (empty($idInstructor)) {
                $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
                if ($idInstructor) {
                    $_SESSION['id_instructor'] = (int)$idInstructor;
                }
            }

            // 3. Consultar fichas asignadas, bloques de horario reales y novedades de asistencia
            if (!empty($idInstructor)) {
                $fichas    = $this->instructorModel->obtenerFichasAsignadas((int)$idInstructor);
                $bloques   = $this->instructorModel->obtenerHorariosBloquesInstructor((int)$idInstructor);
                $novedades = $this->instructorModel->obtenerNovedadesCalendario((int)$idInstructor);
            }
        } catch (Throwable $e) {
            error_log("InstructorHorarioController::index: " . $e->getMessage());
        }

        // 4. Renderizar vista desacoplada
        $this->renderView('instructor/horario', [
            'tituloPagina' => 'Mi Horario & Calendario',
            'fichas'       => $fichas,
            'bloques'      => $bloques,
            'novedades'    => $novedades,
            'filtroFicha'  => $filtroFicha,
        ]);
    }

    /**
     * Guarda una nota u observación enviada por el instructor sobre una clase.
     */
    public function guardarNota(): void
    {
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;

        if (empty($idInstructor)) {
            $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
            if ($idInstructor) {
                $_SESSION['id_instructor'] = (int)$idInstructor;
            }
        }

        $fecha       = trim($_POST['fecha'] ?? '');
        $numeroFicha = trim($_POST['numero_ficha'] ?? '');
        $titulo      = trim($_POST['titulo'] ?? 'Observación de clase');
        $observacion = trim($_POST['observacion'] ?? '');

        if (empty($fecha) || empty($observacion)) {
            if ($this->esAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Por favor completa la observación.']);
                exit;
            }
            header('Location: ' . BASE_URL . '/index.php?page=instructor_horario');
            exit;
        }

        // Buscar id_ficha por numero_ficha
        $idFicha = null;
        if (!empty($numeroFicha) && !empty($idInstructor)) {
            $fichas = $this->instructorModel->obtenerFichasAsignadas((int)$idInstructor);
            foreach ($fichas as $f) {
                if ($f['numero_ficha'] === $numeroFicha) {
                    $idFicha = (int)$f['id_ficha'];
                    break;
                }
            }
        }

        $exito = false;
        if (!empty($idInstructor)) {
            $exito = $this->instructorModel->guardarNotaClase((int)$idInstructor, $idFicha, $fecha, $observacion, $titulo);
        }

        if ($this->esAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $exito,
                'message' => $exito ? 'Nota de clase guardada correctamente.' : 'Error al guardar la nota.'
            ]);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?page=instructor_horario');
        exit;
    }

    /**
     * Elimina una nota u observación de clase.
     */
    public function eliminarNota(): void
    {
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;

        if (empty($idInstructor)) {
            $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
            if ($idInstructor) {
                $_SESSION['id_instructor'] = (int)$idInstructor;
            }
        }

        $idNota = (int)($_POST['id_nota'] ?? $_GET['id_nota'] ?? 0);
        $exito  = false;

        if ($idNota > 0 && !empty($idInstructor)) {
            $exito = $this->instructorModel->eliminarNotaClase($idNota, (int)$idInstructor);
        }

        if ($this->esAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $exito,
                'message' => $exito ? 'Nota eliminada correctamente.' : 'Error al eliminar la nota.'
            ]);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?page=instructor_horario');
        exit;
    }

    /**
     * Auxiliar para comprobar petición AJAX
     */
    private function esAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
