<?php
/**
 * Controlador de Ingresos del Instructor (InstructorIngresosController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de control de ingresos para el rol 'instructor'.
 * 2. Cargar fichas del instructor y registros de marcación.
 * 3. Procesar las marcaciones escaneadas con lector de código de barras/RFID.
 * 4. Responder mediante peticiones síncronas o JSON/AJAX para actualización dinámica en tiempo real.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorIngresosController extends BaseController
{
    private InstructorModel $instructorModel;

    public function __construct()
    {
        $this->instructorModel = new InstructorModel();
    }

    /**
     * Muestra la pantalla principal de Control de Ingresos.
     */
    public function index(): void
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

        if (empty($idInstructor)) {
            $this->redirect('index.php?page=no_autorizado');
        }

        $fecha             = $_GET['fecha'] ?? date('Y-m-d');
        $fichaSeleccionada = $_GET['ficha'] ?? '';

        $misFichas = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);
        $ingresos  = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, $fecha, $fichaSeleccionada);

        $mensajeScan = $_SESSION['mensaje_scan'] ?? null;
        unset($_SESSION['mensaje_scan']);

        $this->renderView('instructor/ingresos', [
            'tituloPagina'      => 'Control de Ingresos',
            'misFichas'         => $misFichas,
            'ingresos'          => $ingresos,
            'fecha'             => $fecha,
            'fichaSeleccionada' => $fichaSeleccionada,
            'mensajeScan'       => $mensajeScan,
        ]);
    }

    /**
     * Procesa la lectura de un código de barras o RFID proveniente del lector.
     */
    public function registrarEscaneo(): void
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

        $codigo = trim($_POST['codigo'] ?? $_GET['codigo'] ?? '');
        $fecha  = $_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');
        $ficha  = $_POST['ficha'] ?? $_GET['ficha'] ?? '';

        $resultado = $this->instructorModel->registrarAsistenciaPorCodigo((int)$idInstructor, $codigo);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || isset($_GET['ajax']) 
                  || isset($_POST['ajax']);

        if ($isAjax) {
            // Cargar lista actualizada de todos los ingresos de hoy para el instructor
            $ingresosActualizados = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, date('Y-m-d'));
            $resultado['ingresos'] = $ingresosActualizados;
            $this->jsonResponse($resultado);
        } else {
            $_SESSION['mensaje_scan'] = $resultado;
            $queryStr = http_build_query([
                'page'  => 'instructor_ingresos',
                'fecha' => $fecha,
                'ficha' => $ficha
            ]);
            $this->redirect('index.php?' . $queryStr);
        }
    }
}
