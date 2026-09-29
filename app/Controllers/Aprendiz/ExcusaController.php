<?php
/**
 * Controlador de Excusas Médicas para Aprendices (AprendizExcusaController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de excusas para el rol 'aprendiz'.
 * 2. Recuperar de forma segura el id_aprendiz de la sesión o base de datos.
 * 3. Consultar las inasistencias pendientes por justificar y el historial de excusas radicadas.
 * 4. Gestionar la subida y almacenamiento local de soportes médicos (PDF, JPG, PNG) en /public/uploads/.
 * 5. Procesar la radicación mediante AprendizModel::radicarExcusa() vía transacción atómica.
 * 6. Delegar a la vista aprendiz/aprendiz-excusas con inyección segura de variables y alertas.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';
require_once BASE_PATH . '/Models/AprendizModel.php';

class AprendizExcusaController extends BaseController
{
    private AprendizModel $aprendizModel;
    private string $uploadDir;

    public function __construct()
    {
        $this->aprendizModel = new AprendizModel();
        $this->uploadDir = dirname(BASE_PATH) . '/public/uploads/';
    }

    /**
     * Muestra la interfaz de radicación de excusas y el historial del aprendiz.
     */
    public function index(): void
    {
        $this->requireAuth(['aprendiz']);

        $idUsuario  = (int)$_SESSION['id_usuario'];
        $idAprendiz = $_SESSION['id_aprendiz'] ?? null;

        $infoAprendiz            = [];
        $marcacionHoy            = [];
        $inasistenciasPendientes = [];
        $historialExcusas        = [];

        try {
            // Recuperar id_aprendiz si no está en sesión
            if (empty($idAprendiz)) {
                $datosSesion = $this->aprendizModel->obtenerIdAprendizPorUsuario($idUsuario);
                if ($datosSesion) {
                    $idAprendiz = (int)$datosSesion['id_aprendiz'];
                    $_SESSION['id_aprendiz'] = $idAprendiz;
                    $_SESSION['id_ficha']    = $datosSesion['fichas_id_ficha'];
                    $_SESSION['codigo_rfid'] = $datosSesion['codigo_rfid'];
                }
            }

            if (!empty($idAprendiz)) {
                $infoAprendiz            = $this->aprendizModel->obtenerHorarioPorId($idAprendiz) ?: [];
                $marcacionHoy            = $this->aprendizModel->obtenerMarcacionHoy($idAprendiz) ?: [];
                $inasistenciasPendientes = $this->aprendizModel->obtenerInasistenciasSinExcusa($idAprendiz);
                $historialExcusas        = $this->aprendizModel->obtenerHistorialExcusas($idAprendiz);
            }
        } catch (Throwable $e) {
            error_log("AprendizExcusaController::index: " . $e->getMessage());
        }

        $this->renderView('aprendiz/aprendiz-excusas', [
            'tituloPagina'            => 'Mis Excusas Médicas',
            'infoAprendiz'            => $infoAprendiz,
            'marcacionHoy'            => $marcacionHoy,
            'inasistenciasPendientes' => $inasistenciasPendientes,
            'historialExcusas'        => $historialExcusas,
            'mensaje'                 => $_GET['mensaje'] ?? null,
            'error'                   => $_GET['error'] ?? null,
        ]);
    }

    /**
     * Procesa la radicación de una nueva excusa médica con carga de soporte local.
     */
    public function radicar(): void
    {
        $this->requireAuth(['aprendiz']);

        $idUsuario  = (int)$_SESSION['id_usuario'];
        $idAprendiz = $_SESSION['id_aprendiz'] ?? null;

        if (empty($idAprendiz)) {
            $datosSesion = $this->aprendizModel->obtenerIdAprendizPorUsuario($idUsuario);
            if ($datosSesion) {
                $idAprendiz = (int)$datosSesion['id_aprendiz'];
                $_SESSION['id_aprendiz'] = $idAprendiz;
            } else {
                $this->redirect('index.php?page=aprendiz_excusas&error=aprendiz_no_encontrado');
            }
        }

        $inasistencias = $_POST['inasistencias'] ?? [];
        $motivo        = trim($_POST['motivo'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');

        if (empty($inasistencias) || empty($motivo)) {
            $this->redirect('index.php?page=aprendiz_excusas&error=datos_incompletos');
        }

        // Procesar archivo soporte físico local
        $nombreArchivoSoporte = 'sin_archivo';

        if (isset($_FILES['soporte']) && $_FILES['soporte']['error'] === UPLOAD_ERR_OK) {
            $nombreOriginal = $_FILES['soporte']['name'];
            $tmpPath        = $_FILES['soporte']['tmp_name'];
            $tamano         = $_FILES['soporte']['size'];

            // Validar extensión
            $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $extensionesPermitidas, true)) {
                $this->redirect('index.php?page=aprendiz_excusas&error=formato_invalido');
            }

            // Validar tamaño máximo (5MB)
            if ($tamano > 5 * 1024 * 1024) {
                $this->redirect('index.php?page=aprendiz_excusas&error=archivo_muy_grande');
            }

            // Asegurar directorio destino local
            if (!is_dir($this->uploadDir)) {
                mkdir($this->uploadDir, 0777, true);
            }

            // Generar nombre de archivo único sanitizado
            $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
            $nombreBaseSanitizado = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nombreBase);
            $nombreArchivoSoporte = 'excusa_' . time() . '_' . $nombreBaseSanitizado . '.' . $extension;
            $destinoFinal = $this->uploadDir . $nombreArchivoSoporte;

            if (!move_uploaded_file($tmpPath, $destinoFinal)) {
                error_log("AprendizExcusaController::radicar: No se pudo mover el archivo a {$destinoFinal}");
                $nombreArchivoSoporte = 'sin_archivo';
            }
        }

        // Ejecutar radicación atómica en modelo
        $exito = $this->aprendizModel->radicarExcusa(
            (int)$idAprendiz,
            (array)$inasistencias,
            $motivo,
            $nombreArchivoSoporte,
            $observaciones
        );

        if ($exito) {
            $this->redirect('index.php?page=aprendiz_excusas&mensaje=radicada');
        } else {
            $this->redirect('index.php?page=aprendiz_excusas&error=error_radicacion');
        }
    }
}
