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
        $idHorario = (int)($_POST['horario_id'] ?? 1);

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
            $fichaModel->crearFicha($codigo, $programa, $area, $idHorario, $instructoresIds);
            $this->redirect('index.php?page=fichas&mensaje=' . urlencode('Ficha registrada correctamente con sus instructores asociados.'));
        } catch (Throwable $e) {
            error_log('Error al registrar ficha: ' . $e->getMessage());
            $this->redirect('index.php?page=fichas&error=' . urlencode('No se pudo registrar la ficha. Verifique que el número no esté duplicado.'));
        }
    }
}
