<?php
/**
 * Controlador de Aprendices (AprendizController)
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de aprendices para roles administrativos.
 * 2. Filtrar aprendices y fichas formativas según el área técnica del administrador.
 * 3. Inyectar datos limpios y procesados a la vista 'admin/aprendices'.
 * 4. Procesar la creación de nuevos aprendices vía peticiones POST.
 * 5. Gestionar la actualización de estados de matrícula.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/AprendizModel.php';

class AprendizController extends BaseController
{
    /**
     * Muestra la vista de administración de aprendices con filtros aplicados.
     */
    public function index(): void
    {
        $this->requireAuth(['admin']);

        // Detección de área técnica del administrador en sesión
        $adminArea   = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea  = ($adminArea !== 'todas') ? $adminArea : null;
        $filtroFicha = !empty($_GET['ficha']) ? (int)$_GET['ficha'] : null;

        $aprendices = [];
        $fichas     = [];

        try {
            // Consultas mediante el modelo
            $model      = new AprendizModel();
            $aprendices = $model->listarAprendices($filtroArea, $filtroFicha);
            $fichas     = $model->obtenerFichasParaSelect($filtroArea);
        } catch (Throwable $e) {
            error_log("AprendizController::index: " . $e->getMessage());
        }

        $this->renderView('admin/aprendices', [
            'aprendices'  => $aprendices,
            'fichas'      => $fichas,
            'adminArea'   => $adminArea,
            'filtroFicha' => $filtroFicha,
            'mensaje'     => $_GET['mensaje'] ?? null,
            'error'       => $_GET['error'] ?? null
        ]);
    }

    /**
     * Procesa la creación de un nuevo aprendiz vía POST.
     */
    public function guardar(): void
    {
        $this->requireAuth(['admin']);

        $tipoDoc         = trim($_POST['tipo_documento'] ?? 'CC');
        $documento       = trim($_POST['documento'] ?? '');
        $nombres         = trim($_POST['nombres'] ?? '');
        $apellidos       = trim($_POST['apellidos'] ?? '');
        $correo          = trim($_POST['correo'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $codigoRfid      = trim($_POST['rfid'] ?? '');
        $fichaId         = (int)($_POST['ficha_id'] ?? 0);
        $estadoMatricula = trim($_POST['estado'] ?? 'Matriculado');

        // Validación de campos obligatorios
        if ($documento === '' || $nombres === '' || $apellidos === '' || $correo === '' || $fichaId <= 0) {
            $this->redirect('index.php?page=aprendices&error=' . urlencode('Por favor complete todos los campos obligatorios y seleccione una ficha válida.'));
        }

        // Si no se suministra un RFID específico, se genera un identificador provisional
        if ($codigoRfid === '') {
            $codigoRfid = 'RFID-' . $documento;
        }

        $model = new AprendizModel();
        // 1. Validación preventiva de Documento
        if ($model->existeDocumento($documento)) {
            $this->redirect('index.php?page=aprendices&error=' . urlencode("El número de documento '{$documento}' ya se encuentra registrado en el sistema."));
        }
        // 2. Validación preventiva de Correo Electrónico
        if ($model->existeCorreo($correo)) {
            $this->redirect('index.php?page=aprendices&error=' . urlencode("El correo electrónico '{$correo}' ya se encuentra registrado en el sistema."));
        }
        // 3. Validación preventiva de Código RFID
        if ($model->existeRfid($codigoRfid)) {
            $this->redirect('index.php?page=aprendices&error=' . urlencode("El código RFID '{$codigoRfid}' ya se encuentra asignado a otro aprendiz."));
        }
        try {
            $model->crearAprendiz([
                'tipo_documento' => $tipoDoc,
                'documento'      => $documento,
                'nombres'        => $nombres,
                'apellidos'      => $apellidos,
                'correo'         => $correo,
                'telefono'       => $telefono,
                'password'       => '123456' // Contraseña de acceso inicial por defecto
            ], $fichaId, $codigoRfid, $estadoMatricula);

            $this->redirect('index.php?page=aprendices&mensaje=' . urlencode('Aprendiz registrado exitosamente con contraseña temporal 123456.'));
        } catch (Throwable $e) {
            error_log('Error al registrar aprendiz: ' . $e->getMessage());
            $this->redirect('index.php?page=aprendices&error=' . urlencode('Error al registrar aprendiz. Verifique que el número de documento o correo no estén ya registrados.'));
        }
    }

    /**
     * Actualiza el estado de matrícula de un aprendiz vía POST.
     */
    public function cambiarEstado(): void
    {
        $this->requireAuth(['admin']);

        $idAprendiz  = (int)($_POST['id_aprendiz'] ?? 0);
        $nuevoEstado = trim($_POST['estado'] ?? '');

        $estadosValidos = ['Matriculado', 'Retirado', 'Aplazado'];

        if ($idAprendiz > 0 && in_array($nuevoEstado, $estadosValidos, true)) {
            try {
                $model = new AprendizModel();
                $model->actualizarEstadoMatricula($idAprendiz, $nuevoEstado);
                $this->redirect('index.php?page=aprendices&mensaje=' . urlencode('Estado de matrícula actualizado.'));
            } catch (Throwable $e) {
                error_log("AprendizController::cambiarEstado: " . $e->getMessage());
                $this->redirect('index.php?page=aprendices&error=' . urlencode('Error al actualizar el estado de matrícula.'));
            }
        } else {
            $this->redirect('index.php?page=aprendices&error=' . urlencode('Datos inválidos para actualizar el estado.'));
        }
    }
}
