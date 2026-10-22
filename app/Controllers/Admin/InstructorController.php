<?php
/**
 * Controlador de Instructores (InstructorController)
 * 
 * Responsabilidad:
 * 1. Proteger el acceso administrativo a la gestión de instructores.
 * 2. Filtrar el listado según el área técnica ('comercio' o 'Tics') del administrador.
 * 3. Enviar datos limpios a la vista 'admin/instructores'.
 * 4. Procesar el registro de nuevos instructores vía POST.
 * 5. Gestionar la actualización de tolerancia de retardo por ficha.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/InstructorModel.php';

class InstructorController extends BaseController
{
    /**
     * Muestra la vista de administración de instructores.
     */
    public function index(): void
    {
        $this->requireAuth(['admin']);

        // Detección del área asignada al administrador
        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        $instructores = [];
        try {
            // Consulta de instructores mediante el modelo
            $model        = new InstructorModel();
            $instructores = $model->listarInstructores($filtroArea);
        } catch (Throwable $e) {
            error_log("InstructorController::index: " . $e->getMessage());
        }

        $this->renderView('admin/instructores', [
            'instructores' => $instructores,
            'adminArea'    => $adminArea,
            'mensaje'      => $_GET['mensaje'] ?? null,
            'error'        => $_GET['error'] ?? null
        ]);
    }

    /**
     * Procesa la creación de un nuevo instructor vía POST.
     */
    public function guardar(): void
    {
        $this->requireAuth(['admin']);

        $documento    = trim($_POST['documento'] ?? '');
        $tipoDoc      = trim($_POST['tipo_documento'] ?? 'CC');
        $nombres      = trim($_POST['nombres'] ?? '');
        $apellidos    = trim($_POST['apellidos'] ?? '');
        $correo       = trim($_POST['correo'] ?? '');
        $telefono     = trim($_POST['telefono'] ?? '');
        $especialidad = trim($_POST['especialidad'] ?? 'General');
        $adminArea    = $_SESSION['admin_area'] ?? 'todas';

        // Si el admin tiene área fija, se respeta; si no, toma la seleccionada
        $area = ($adminArea !== 'todas') ? $adminArea : trim($_POST['area'] ?? 'comercio');

        // Validación de campos obligatorios
        if ($documento === '' || $nombres === '' || $apellidos === '' || $correo === '') {
            $this->redirect('index.php?page=instructores&error=' . urlencode('Por favor complete todos los campos obligatorios.'));
        }

        $model = new InstructorModel();
        // 1. Validación preventiva de Documento
        if ($model->existeDocumento($documento)) {
            $this->redirect('index.php?page=instructores&error=' . urlencode("El número de documento '{$documento}' ya se encuentra registrado en el sistema."));
        }
        // 2. Validación preventiva de Correo Electrónico
        if ($model->existeCorreo($correo)) {
            $this->redirect('index.php?page=instructores&error=' . urlencode("El correo electrónico '{$correo}' ya se encuentra registrado en el sistema."));
        }
        try {
            $model->crearInstructor([
                'tipo_documento' => $tipoDoc,
                'documento'      => $documento,
                'nombres'        => $nombres,
                'apellidos'      => $apellidos,
                'correo'         => $correo,
                'telefono'       => $telefono,
                'password'       => '123456' // Contraseña inicial por defecto
            ], $area, $especialidad);

            $this->redirect('index.php?page=instructores&mensaje=' . urlencode('Instructor registrado correctamente con contraseña inicial 123456.'));
        } catch (Throwable $e) {
            error_log('Error al registrar instructor: ' . $e->getMessage());
            $this->redirect('index.php?page=instructores&error=' . urlencode('Error al registrar instructor. Verifique que el documento o correo no estén ya registrados.'));
        }
    }

    /**
     * Actualiza la tolerancia de minutos de retardo de un instructor para una ficha.
     */
    public function actualizarTolerancia(): void
    {
        $this->requireAuth(['admin', 'instructor']);

        $idInstructor   = (int)($_POST['id_instructor'] ?? 0);
        $idFicha        = (int)($_POST['id_ficha'] ?? 0);
        $minutosRetardo = (int)($_POST['minutos_retardo'] ?? 0);

        if ($idInstructor > 0 && $idFicha > 0) {
            try {
                $model = new InstructorModel();
                $model->actualizarToleranciaFicha($idInstructor, $idFicha, $minutosRetardo);
                $this->jsonResponse(['success' => true, 'mensaje' => 'Tolerancia actualizada']);
            } catch (Throwable $e) {
                error_log("InstructorController::actualizarTolerancia: " . $e->getMessage());
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al guardar la tolerancia'], 500);
            }
        } else {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Datos inválidos'], 400);
        }
    }
}
