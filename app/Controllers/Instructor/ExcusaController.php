<?php
/**
 * Controlador de Excusas Médicas para Instructor (InstructorExcusaController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de excusas para el rol 'instructor'.
 * 2. Consultar las excusas médicas radicadas por aprendices pertenecientes a las fichas del docente.
 * 3. Segmentar entre excusas pendientes por evaluar e historial evaluadas.
 * 4. Procesar la evaluación (Aprobación / Rechazo) y actualizar la asistencia vinculada.
 * 5. Renderizar la vista desacoplada ('instructor/excusas') inyectando los datos.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorExcusaController extends BaseController
{
    private InstructorModel $instructorModel;

    public function __construct()
    {
        $this->instructorModel = new InstructorModel();
    }

    /**
     * Muestra el panel de excusas médicas divididas en pendientes e historial.
     */
    public function index(): void
    {
        $this->requireAuth(['instructor']);

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;
        $filtroEstado = $_GET['estado'] ?? null;

        $excusasPendientes = [];
        $excusasHistorial  = [];

        try {
            if (empty($idInstructor)) {
                $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
                if ($idInstructor) {
                    $_SESSION['id_instructor'] = (int)$idInstructor;
                }
            }

            if (!empty($idInstructor)) {
                $todas = $this->instructorModel->obtenerExcusasPorInstructor((int)$idInstructor, $filtroEstado);
                foreach ($todas as $exc) {
                    if ($exc['estado'] === 'Pendiente') {
                        $excusasPendientes[] = $exc;
                    } else {
                        $excusasHistorial[] = $exc;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("InstructorExcusaController::index: " . $e->getMessage());
        }

        $this->renderView('instructor/excusas', [
            'tituloPagina'      => 'Gestión de Excusas Médicas',
            'excusasPendientes' => $excusasPendientes,
            'excusasHistorial'  => $excusasHistorial,
            'mensaje'           => $_GET['mensaje'] ?? null,
            'error'             => $_GET['error'] ?? null,
        ]);
    }

    /**
     * Procesa la aprobación o rechazo de una excusa médica.
     */
    public function evaluar(): void
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

        $idExcusa      = (int)($_POST['excusa_id'] ?? 0);
        $decision      = trim($_POST['action'] ?? $_POST['decision'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if ($idExcusa <= 0 || empty($decision)) {
            if ($this->esAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos de evaluación incompletos.'], 400);
            }
            $this->redirect('index.php?page=instructor_excusas&error=datos_invalidos');
        }

        $exito = false;
        if (!empty($idInstructor)) {
            $exito = $this->instructorModel->evaluarExcusa($idExcusa, (int)$idInstructor, $decision, $observaciones);
        }

        if ($this->esAjax()) {
            $this->jsonResponse([
                'success' => $exito,
                'message' => $exito ? 'Excusa evaluada con éxito.' : 'Error al procesar la evaluación.'
            ]);
        }

        $paramMensaje = ($decision === 'aprobar' || stripos($decision, 'aprob') !== false) ? 'aprobada' : 'rechazada';
        $this->redirect('index.php?page=instructor_excusas&mensaje=' . ($exito ? $paramMensaje : 'error'));
    }

    /**
     * Comprueba si la petición fue enviada vía AJAX / Fetch
     */
    private function esAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
