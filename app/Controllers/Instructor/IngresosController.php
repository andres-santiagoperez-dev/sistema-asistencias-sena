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

        $codigo    = trim($_POST['codigo'] ?? $_GET['codigo'] ?? '');
        $fecha     = $_POST['fecha'] ?? $_GET['fecha'] ?? date('Y-m-d');
        $ficha     = $_POST['ficha'] ?? $_GET['ficha'] ?? '';
        $rawManual = $_POST['es_manual'] ?? $_GET['es_manual'] ?? 0;
        $esManual  = ($rawManual === '1' || $rawManual === 1 || $rawManual === true || $rawManual === 'true');

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

    /**
     * Retorna el directorio de aprendices y el perfil de sesión en JSON para IndexedDB (Modo Offline).
     */
    public function obtenerDirectorioOffline(): void
    {
        if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nombre_rol']) || !str_contains(strtolower($_SESSION['nombre_rol']), 'instructor')) {
            http_response_code(401);
            $this->jsonResponse([
                'success'         => false,
                'session_expired' => true,
                'message'         => 'Sesión no activa.'
            ]);
            return;
        }

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;
        if (empty($idInstructor)) {
            $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
            if ($idInstructor) {
                $_SESSION['id_instructor'] = (int)$idInstructor;
            }
        }

        $aprendices = $this->instructorModel->obtenerDirectorioOfflineInstructor((int)$idInstructor);
        $misFichas  = $this->instructorModel->obtenerFichasConConteo((int)$idInstructor);

        $perfil = [
            'id_instructor'   => (int)$idInstructor,
            'id_usuario'      => $idUsuario,
            'nombre_completo' => $_SESSION['nombre_completo'] ?? 'Instructor',
            'correo'          => $_SESSION['correo'] ?? '',
            'fichas'          => $misFichas
        ];

        $this->jsonResponse([
            'success'    => true,
            'perfil'     => $perfil,
            'aprendices' => $aprendices
        ]);
    }

    /**
     * Procesa un lote de marcaciones encoladas en el navegador durante la desconexión a internet.
     */
    public function sincronizarOffline(): void
    {
        if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nombre_rol']) || !str_contains(strtolower($_SESSION['nombre_rol']), 'instructor')) {
            http_response_code(401);
            $this->jsonResponse([
                'success'         => false,
                'session_expired' => true,
                'message'         => 'Su sesión ha caducado durante la desconexión. Por favor revalide sus credenciales.'
            ]);
            return;
        }

        $idUsuario    = (int)$_SESSION['id_usuario'];
        $idInstructor = $_SESSION['id_instructor'] ?? null;
        if (empty($idInstructor)) {
            $idInstructor = $this->instructorModel->obtenerIdInstructorPorUsuario($idUsuario);
            if ($idInstructor) {
                $_SESSION['id_instructor'] = (int)$idInstructor;
            }
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $lote = $data['lote'] ?? [];

        if (!is_array($lote) || empty($lote)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No se recibieron registros para sincronizar.'
            ]);
            return;
        }

        $procesados = [];
        $fallidos   = [];
        $llamadosGenerados = [];

        foreach ($lote as $item) {
            $idOffline = $item['id_offline'] ?? null;
            $codigo    = trim($item['codigo'] ?? '');
            $esManual  = !empty($item['es_manual']);
            $fecha     = $item['fecha'] ?? date('Y-m-d');
            $hora      = $item['hora'] ?? date('H:i:s');

            if (empty($codigo)) {
                $fallidos[] = ['id_offline' => $idOffline, 'message' => 'Código vacío'];
                continue;
            }

            // Invocar el registro de asistencia preservando la fecha y hora de captura física original
            $res = $this->instructorModel->registrarAsistenciaPorCodigo((int)$idInstructor, $codigo, $esManual, $fecha, $hora);

            $procesados[] = [
                'id_offline' => $idOffline,
                'success'    => $res['success'],
                'message'    => $res['message'],
                'aprendiz'   => $res['aprendiz'] ?? null,
                'estado'     => $res['estado'] ?? null,
                'tipo'       => $res['type'] ?? 'info'
            ];

            if (!empty($res['alerta_llamado']) && !empty($res['datos_llamado'])) {
                $llamadosGenerados[] = $res['datos_llamado'];
            }
        }

        // Cargar la lista completa actualizada de ingresos de hoy
        $ingresosActualizados = $this->instructorModel->obtenerIngresosPorInstructor((int)$idInstructor, date('Y-m-d'));

        $this->jsonResponse([
            'success'            => true,
            'total_procesados'   => count($procesados),
            'procesados'         => $procesados,
            'fallidos'           => $fallidos,
            'llamados_generados' => $llamadosGenerados,
            'ingresos'           => $ingresosActualizados,
            'message'            => 'Lote de marcaciones sincronizado correctamente.'
        ]);
    }

    /**
     * Revalida la sesión del instructor mediante contraseña tras una desconexión prolongada,
     * sin recargar la página ni perder las marcaciones almacenadas en el navegador.
     */
    public function revalidarSesionOffline(): void
    {
        $login    = trim($_POST['login'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($login) || empty($password)) {
            $rawInput = file_get_contents('php://input');
            $jsonData = json_decode($rawInput, true);
            if ($jsonData) {
                $login    = trim($jsonData['login'] ?? '');
                $password = trim($jsonData['password'] ?? '');
            }
        }

        if (empty($login) || empty($password)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Por favor ingrese su usuario/correo y contraseña.'
            ]);
            return;
        }

        try {
            $pdo = Conexion::obtener();
            $sql = "SELECT u.id_usuario, u.numero_documento, u.nombres, u.apellidos, u.correo, u.contraseña, u.estado,
                           r.nombre_rol, i.id_instructor
                    FROM usuarios u
                    INNER JOIN roles r ON u.roles_id_rol = r.id_rol
                    LEFT JOIN instructores i ON u.id_usuario = i.usuarios_id_usuario
                    WHERE (u.correo = :login OR u.numero_documento = :login)
                      AND u.estado = 'Activo'
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([':login' => $login]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario || !password_verify($password, $usuario['contraseña'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Credenciales incorrectas. Verifique su contraseña.'
                ]);
                return;
            }

            if (!str_contains(strtolower($usuario['nombre_rol']), 'instructor')) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El usuario autenticado no posee rol de instructor.'
                ]);
                return;
            }

            // Restaurar sesión de PHP
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['id_usuario']      = (int)$usuario['id_usuario'];
            $_SESSION['nombre_completo'] = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
            $_SESSION['nombre_rol']      = $usuario['nombre_rol'];
            $_SESSION['correo']          = $usuario['correo'];
            $_SESSION['id_instructor']   = (int)($usuario['id_instructor'] ?? 0);

            $this->jsonResponse([
                'success'         => true,
                'id_instructor'   => $_SESSION['id_instructor'],
                'nombre_completo' => $_SESSION['nombre_completo'],
                'message'         => 'Sesión revalidada con éxito.'
            ]);
        } catch (Throwable $e) {
            error_log("InstructorIngresosController::revalidarSesionOffline: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al revalidar la sesión: ' . $e->getMessage()
            ]);
        }
    }
}
