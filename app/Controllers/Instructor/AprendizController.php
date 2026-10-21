<?php
/**
 * Controlador de Aprendices para Instructor (InstructorAprendizController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso a la lista de aprendices para rol 'instructor'.
 * 2. Consultar aprendices pertenecientes a las fichas a cargo del docente.
 * 3. Manejar filtrado opcional por ficha formativa.
 * 4. Renderizar la vista desacoplada ('instructor/aprendices') inyectando los datos.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorAprendizController extends BaseController
{
    private InstructorModel $instructorModel;

    public function __construct()
    {
        $this->instructorModel = new InstructorModel();
    }

    /**
     * Muestra el directorio de aprendices asignados al instructor.
     */
    public function index(): void
    {
        // 1. Proteger acceso para rol instructor
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;
        $filtroFicha  = $_GET['ficha'] ?? null;

        $aprendices = [];
        $misFichas  = [];

        try {
            // 2. Recuperar id_instructor si no está en sesión
            if (empty($idInstructor)) {
                $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
                if ($idInstructor) {
                    $_SESSION['id_instructor'] = (int)$idInstructor;
                }
            }

            // 3. Consultar aprendices y lista de fichas para el selector
            if (!empty($idInstructor)) {
                $aprendices = $this->instructorModel->obtenerAprendicesPorInstructor((int)$idInstructor, $filtroFicha);
                $fichasRaw  = $this->instructorModel->obtenerFichasFiltro((int)$idInstructor);
                $misFichas  = array_column($fichasRaw, 'ficha');
            }
        } catch (Throwable $e) {
            error_log("InstructorAprendizController::index: " . $e->getMessage());
        }

        // 4. Renderizar vista de presentación desacoplada
        $this->renderView('instructor/aprendices', [
            'tituloPagina' => 'Mis Aprendices',
            'aprendices'   => $aprendices,
            'misFichas'    => $misFichas,
            'filtroFicha'  => $filtroFicha,
        ]);
    }

    /**
     * Retorna el historial global de inasistencias y marcaciones de un aprendiz vía JSON.
     */
    public function obtenerHistorialGlobal(): void
    {
        $this->requireAuth(['instructor']);
        $idAprendiz = (int)($_GET['id_aprendiz'] ?? $_POST['id_aprendiz'] ?? 0);

        if ($idAprendiz <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Aprendiz no válido.']);
        }

        $historial = $this->instructorModel->obtenerHistorialGlobalAprendiz($idAprendiz);
        $this->jsonResponse([
            'success'   => true,
            'historial' => $historial
        ]);
    }

    /**
     * Guarda o actualiza la observación/nota de una falta o retardo específico.
     */
    public function guardarObservacion(): void
    {
        $this->requireAuth(['instructor']);
        $idRegistro  = (int)($_POST['id_registro'] ?? 0);
        $observacion = trim($_POST['observacion'] ?? '');

        if ($idRegistro <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Registro de asistencia no válido.']);
        }

        $ok = $this->instructorModel->guardarObservacionAsistencia($idRegistro, $observacion);
        $this->jsonResponse([
            'success'     => $ok,
            'message'     => $ok ? 'Nota de inasistencia / retardo guardada correctamente.' : 'Error al guardar la nota.',
            'observacion' => $observacion
        ]);
    }
}
