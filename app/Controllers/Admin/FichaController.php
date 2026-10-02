<?php
/**
 * Controlador de Fichas (FichaController)
 * 
 * Responsabilidad:
 * 1. Proteger el acceso solo a administradores.
 * 2. Filtrar los listados según el área técnica del administrador en sesión ('comercio' o 'Tics').
 * 3. Enviar a la vista 'admin/fichas' los datos limpios de fichas, instructores y horarios.
 * 4. Procesar el registro de nuevas fichas y asignaciones múltiples vía POST.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/FichaModel.php';

class FichaController extends BaseController
{
    /**
     * Muestra el listado de fichas y datos para los formularios modales.
     */
    public function index(): void
    {
        $this->requireAuth(['admin']);

        // 1. Detección del área asignada al administrador
        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        $fichas       = [];
        $instructores = [];
        $horarios     = [];

        try {
            // 2. Consulta de datos limpios mediante el Modelo
            $fichaModel   = new FichaModel();
            $fichas       = $fichaModel->listarFichas($filtroArea);
            $instructores = $fichaModel->listarInstructoresDisponibles($filtroArea);
            $horarios     = $fichaModel->listarHorarios();
        } catch (Throwable $e) {
            error_log("FichaController::index: " . $e->getMessage());
        }

        // 3. Renderizado de la vista desacoplada
        $this->renderView('admin/fichas', [
            'fichas'       => $fichas,
            'instructores' => $instructores,
            'horarios'     => $horarios,
            'adminArea'    => $adminArea,
            'mensaje'      => $_GET['mensaje'] ?? null,
            'error'        => $_GET['error'] ?? null
        ]);
    }

    /**
     * Procesa la creación de una ficha y la asignación de sus instructores (N:M).
     */
    public function guardar(): void
    {
        $this->requireAuth(['admin']);

        $codigo    = trim($_POST['codigo'] ?? '');
        $programa  = trim($_POST['programa'] ?? '');
        $adminArea = $_SESSION['admin_area'] ?? 'todas';

        // Si el administrador tiene área fija, se respeta; si no, toma la seleccionada
        $area = ($adminArea !== 'todas') ? $adminArea : trim($_POST['area'] ?? 'comercio');

        // Soporte para selección de instructor individual o múltiple
        $instructoresIds = [];
        if (!empty($_POST['instructor_ids']) && is_array($_POST['instructor_ids'])) {
            $instructoresIds = $_POST['instructor_ids'];
        } elseif (!empty($_POST['instructor_id'])) {
            $instructoresIds[] = (int)$_POST['instructor_id'];
        }

        // Validación básica de campos
        if ($codigo === '' || $programa === '') {
            $this->redirect('index.php?page=fichas&error=' . urlencode('El número de ficha y el programa son obligatorios.'));
        }

        try {
            $fichaModel = new FichaModel();
            $fichaModel->crearFicha($codigo, $programa, $area, $instructoresIds);
            $this->redirect('index.php?page=fichas&mensaje=' . urlencode('Ficha registrada correctamente con sus instructores asociados.'));
        } catch (Throwable $e) {
            error_log('Error al registrar ficha: ' . $e->getMessage());
            $this->redirect('index.php?page=fichas&error=' . urlencode('No se pudo registrar la ficha. Verifique que el número no esté duplicado.'));
        }
    }

    /**
     * Devuelve los bloques de horario asignados a una ficha vía AJAX.
     */
    public function obtenerBloques(): void
    {
        $this->requireAuth(['admin']);
        $idFicha = (int)($_GET['id_ficha'] ?? 0);

        if ($idFicha <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Ficha inválida.'], 400);
        }

        try {
            $fichaModel = new FichaModel();
            $ficha = $fichaModel->obtenerFichaPorId($idFicha);
            if (!$ficha) {
                $this->jsonResponse(['success' => false, 'error' => 'La ficha no existe.'], 404);
            }

            $bloques = $fichaModel->obtenerBloquesPorFicha($idFicha);
            $this->jsonResponse([
                'success' => true,
                'ficha'   => $ficha,
                'bloques' => $bloques
            ]);
        } catch (Throwable $e) {
            error_log("FichaController::obtenerBloques: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Error al consultar bloques.'], 500);
        }
    }

    /**
     * Guarda o actualiza un bloque de horario para una ficha vía AJAX.
     */
    public function guardarBloque(): void
    {
        $this->requireAuth(['admin']);

        $idFicha       = (int)($_POST['id_ficha'] ?? 0);
        $idHorario     = !empty($_POST['id_horario']) ? (int)$_POST['id_horario'] : null;
        $idInstructor  = !empty($_POST['id_instructor']) ? (int)$_POST['id_instructor'] : null;
        $diasSemana    = trim($_POST['dias_semana'] ?? '');
        $horaEntrada   = trim($_POST['hora_entrada'] ?? '');
        $horaSalida    = trim($_POST['hora_salida'] ?? '');
        $duracionHoras = (int)($_POST['duracion_horas'] ?? 3);
        $tipoBloque    = trim($_POST['tipo_bloque'] ?? '3H_INDIVIDUAL');
        $ambiente      = trim($_POST['ambiente'] ?? '');
        $competencia   = trim($_POST['competencia'] ?? '');

        if ($idFicha <= 0 || empty($diasSemana) || empty($horaEntrada) || empty($horaSalida)) {
            $this->jsonResponse(['success' => false, 'error' => 'Todos los campos de día, hora de inicio y fin son obligatorios.'], 400);
        }

        $diasValidos = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'];
        if (!in_array($diasSemana, $diasValidos, true)) {
            $this->jsonResponse(['success' => false, 'error' => 'Día de la semana no válido.'], 400);
        }

        $tiposValidos = ['3H_INDIVIDUAL', '6H_CONTINUO', '6H_PARTICIONADO'];
        if (!in_array($tipoBloque, $tiposValidos, true)) {
            $tipoBloque = ($duracionHoras === 6) ? '6H_CONTINUO' : '3H_INDIVIDUAL';
        }

        try {
            $fichaModel = new FichaModel();
            $resultado = $fichaModel->guardarBloqueHorario(
                $idFicha,
                $idInstructor,
                $diasSemana,
                $horaEntrada,
                $horaSalida,
                $duracionHoras,
                $tipoBloque,
                $ambiente ?: null,
                $competencia ?: null,
                $idHorario
            );

            if ($resultado) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Bloque de horario guardado correctamente.']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudo guardar el bloque de horario.'], 500);
            }
        } catch (Throwable $e) {
            error_log("FichaController::guardarBloque: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Ocurrió un error en el servidor.'], 500);
        }
    }

    /**
     * Elimina (o desactiva) un bloque de horario vía AJAX.
     */
    public function eliminarBloque(): void
    {
        $this->requireAuth(['admin']);

        $idHorario = (int)($_POST['id_horario'] ?? 0);
        if ($idHorario <= 0) {
            $this->jsonResponse(['success' => false, 'error' => 'Identificador de bloque inválido.'], 400);
        }

        try {
            $fichaModel = new FichaModel();
            $resultado  = $fichaModel->eliminarBloqueHorario($idHorario);
            if ($resultado) {
                $this->jsonResponse(['success' => true, 'mensaje' => 'Bloque de horario eliminado correctamente.']);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudo eliminar el bloque de horario.'], 500);
            }
        } catch (Throwable $e) {
            error_log("FichaController::eliminarBloque: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Ocurrió un error en el servidor.'], 500);
        }
    }
}
