<?php
/**
 * Controlador de Ingresos del Instructor (InstructorIngresosController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de control de ingresos para el rol 'instructor'.
 * 2. Cargar fichas del instructor y registros de marcación.
 * 3. Procesar las marcaciones escaneadas con lector de código de barras/RFID.
 * 4. Gestionar el flujo de solicitud y aprobación de suplencias entre instructores.
 * 5. Responder mediante peticiones síncronas o JSON/AJAX para actualización dinámica en tiempo real.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/InstructorModel.php';
require_once BASE_PATH . '/Services/ExcelExportService.php';

use App\Services\ExcelExportService;

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

        $misFichas               = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);
        $ingresos                = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, $fecha, $fichaSeleccionada);
        $suplenciasPendientes    = $this->instructorModel->obtenerSuplenciasPendientes((int)$idInstructor);
        $instructoresDisponibles = $this->instructorModel->obtenerInstructoresParaSuplencia((int)$idInstructor);
        $misSuplencias           = $this->instructorModel->obtenerMisSuplencias((int)$idInstructor);

        $mensajeScan = $_SESSION['mensaje_scan'] ?? null;
        unset($_SESSION['mensaje_scan']);

        $this->renderView('instructor/ingresos', [
            'tituloPagina'            => 'Control de Ingresos',
            'misFichas'               => $misFichas,
            'ingresos'                => $ingresos,
            'fecha'                   => $fecha,
            'fichaSeleccionada'       => $fichaSeleccionada,
            'mensajeScan'             => $mensajeScan,
            'suplenciasPendientes'    => $suplenciasPendientes,
            'instructoresDisponibles' => $instructoresDisponibles,
            'misSuplencias'           => $misSuplencias,
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

        $codigo   = trim($_POST['codigo'] ?? $_GET['codigo'] ?? '');
        $fecha    = $_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');
        $ficha    = $_POST['ficha'] ?? $_GET['ficha'] ?? '';
        $esManual = !empty($_POST['es_manual']) || !empty($_GET['es_manual']);

        $resultado = $this->instructorModel->registrarAsistenciaPorCodigo((int)$idInstructor, $codigo, $esManual);

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

    /**
     * Guarda la tolerancia de retardo (entrada) y salida de una ficha específica.
     */
    public function guardarTolerancia(): void
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

        $idFicha        = (int)($_POST['id_ficha'] ?? 0);
        $minutosRetardo = (int)($_POST['minutos_retardo'] ?? 0);
        $minutosSalida  = (int)($_POST['minutos_salida'] ?? 0);

        $ok = false;
        if ($idInstructor && $idFicha > 0) {
            $ok = $this->instructorModel->actualizarToleranciaFicha((int)$idInstructor, $idFicha, $minutosRetardo, $minutosSalida);
        }

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || isset($_POST['ajax']);

        if ($isAjax) {
            $this->jsonResponse([
                'success' => $ok,
                'message' => $ok ? 'Tolerancia de horario actualizada correctamente.' : 'Error al guardar la tolerancia.'
            ]);
        } else {
            $_SESSION['mensaje_scan'] = [
                'type'    => $ok ? 'success' : 'danger',
                'message' => $ok ? 'Tolerancia de horario actualizada correctamente.' : 'Error al guardar la tolerancia.'
            ];
            $this->redirect('index.php?page=instructor_ingresos');
        }
    }

    /**
     * Procesa la solicitud de una nueva suplencia emitida por el instructor titular.
     */
    public function solicitarSuplencia(): void
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

        $idFicha     = (int)($_POST['fichas_id_ficha'] ?? 0);
        $idSuplente  = (int)($_POST['instructor_suplente_id'] ?? 0);
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin    = trim($_POST['fecha_fin'] ?? '');
        $motivo      = trim($_POST['motivo'] ?? 'Permiso Personal');
        $descripcion = trim($_POST['descripcion_motivo'] ?? '');

        $resultado = $this->instructorModel->solicitarSuplencia([
            'instructor_titular_id'  => (int)$idInstructor,
            'instructor_suplente_id' => $idSuplente,
            'fichas_id_ficha'        => $idFicha,
            'fecha_inicio'           => $fechaInicio,
            'fecha_fin'              => $fechaFin,
            'motivo'                 => $motivo,
            'descripcion_motivo'     => $descripcion
        ]);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || isset($_POST['ajax']);

        if ($isAjax) {
            $this->jsonResponse($resultado);
        } else {
            $_SESSION['mensaje_scan'] = [
                'type'    => $resultado['success'] ? 'success' : 'danger',
                'message' => $resultado['message']
            ];
            $this->redirect('index.php?page=instructor_ingresos');
        }
    }

    /**
     * Procesa la aceptación o rechazo de una suplencia por parte del instructor suplente.
     */
    public function responderSuplencia(): void
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

        $idSuplencia = (int)($_POST['id_suplencia'] ?? 0);
        $accion      = trim($_POST['accion'] ?? '');
        $observacion = trim($_POST['observaciones_respuesta'] ?? '');

        $resultado = $this->instructorModel->responderSuplencia($idSuplencia, (int)$idInstructor, $accion, $observacion);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || isset($_POST['ajax']);

        if ($isAjax) {
            $this->jsonResponse($resultado);
        } else {
            $_SESSION['mensaje_scan'] = [
                'type'    => $resultado['success'] ? 'success' : 'danger',
                'message' => $resultado['message']
            ];
            $this->redirect('index.php?page=instructor_ingresos');
        }
    }

    /**
     * Exporta la planilla diaria oficial a un archivo Microsoft Excel (.xlsx) institucional.
     */
    public function exportarExcel(): void
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

        $fecha             = $_GET['fecha'] ?? date('Y-m-d');
        $fichaSeleccionada = $_GET['ficha'] ?? '';
        $instructorNombre  = $_SESSION['nombre_usuario'] ?? ($_SESSION['usuario'] ?? 'Instructor Titular');

        $misFichas = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);
        $ingresos  = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, $fecha, $fichaSeleccionada);

        $fichaInfo = [
            'numero_ficha' => !empty($fichaSeleccionada) ? $fichaSeleccionada : 'Todas las Fichas',
            'programa'     => 'Formación Profesional Integral'
        ];
        foreach ($misFichas as $f) {
            if (($f['numero_ficha'] ?? '') === $fichaSeleccionada || ($f['ficha_completa'] ?? '') === $fichaSeleccionada) {
                $fichaInfo['numero_ficha'] = $f['numero_ficha'];
                $fichaInfo['programa']     = $f['programa'];
                break;
            }
        }

        $excelService = new ExcelExportService();
        $excelService->exportarIngresosDia($ingresos, $fichaInfo, $fecha, $instructorNombre);
    }

    /**
     * Muestra la vista de impresión/PDF oficial para la planilla diaria de asistencias.
     */
    public function exportarPdf(): void
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

        $fecha             = $_GET['fecha'] ?? date('Y-m-d');
        $fichaSeleccionada = $_GET['ficha'] ?? '';

        $misFichas = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);
        $ingresos  = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, $fecha, $fichaSeleccionada);

        require_once BASE_PATH . '/Views/instructor/ingresos_impresion.php';
        exit;
    }

    /**
     * Genera la vista imprimible oficial de la citación / llamado de atención por falta de carné/llavero.
     */
    public function imprimirLlamado(): void
    {
        $this->requireAuth(['instructor', 'admin']);

        $idLlamado = (int)($_GET['id'] ?? 0);
        if ($idLlamado <= 0) {
            die('Identificador de llamado de atención no válido.');
        }

        $idInstructor = null;
        if (str_contains(strtolower($_SESSION['nombre_rol'] ?? ''), 'instructor')) {
            $idInstructor = (int)($_SESSION['id_instructor'] ?? 0);
            if (!$idInstructor) {
                $idInstructor = (int)$this->instructorModel->obtenerIdInstructorPorUsuario((int)$_SESSION['id_usuario']);
            }
        }

        $llamado = $this->instructorModel->obtenerLlamadoPorId($idLlamado, $idInstructor);
        if (!$llamado) {
            die('Llamado de atención no encontrado o sin permisos para visualizarlo.');
        }

        // Renderizado del formato imprimible oficial con membrete SENA
        require_once BASE_PATH . '/Views/instructor/llamado_impresion.php';
        exit;
    }
}
