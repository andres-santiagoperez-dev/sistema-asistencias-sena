<?php
/**
 * Controlador de Administradores (AdminController)
 *
 * Responsabilidad:
 * 1. Proteger el acceso a la gestión de cuentas de Administrador.
 * 2. Listar los administradores registrados (Comercio y TICs) para la vista 'admin/administradores'.
 * 3. Procesar el registro de nuevas cuentas de administrador vía POST.
 * 4. Gestionar la activación/inactivación de cuentas, evitando que un admin se desactive a sí mismo.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/adminModals.php';

class AdminController extends BaseController
{
    /** Roles de administrador válidos en el sistema. */
    private const ROLES_VALIDOS = ['Administrador Comercio', 'Administrador TICs'];

    /**
     * Muestra la vista de administración de cuentas de Administrador.
     */
    public function index(): void
    {
        $this->requireAuth(['admin']);

        $administradores = [];
        try {
            $model           = new AdminModel();
            $administradores = $model->listarAdministradores();
        } catch (Throwable $e) {
            error_log("AdminController::index: " . $e->getMessage());
        }

        $this->renderView('admin/administradores', [
            'administradores' => $administradores,
            'mensaje'         => $_GET['mensaje'] ?? null,
            'error'           => $_GET['error'] ?? null,
        ]);
    }

    /**
     * Procesa la creación de una nueva cuenta de Administrador vía POST.
     */
    public function guardar(): void
    {
        $this->requireAuth(['admin']);

        $tipoDoc   = trim($_POST['tipo_documento'] ?? 'CC');
        $documento = trim($_POST['documento'] ?? '');
        $nombres   = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $correo    = trim($_POST['correo'] ?? '');
        $telefono  = trim($_POST['telefono'] ?? '');
        $nombreRol = trim($_POST['nombre_rol'] ?? '');

        if ($documento === '' || $nombres === '' || $apellidos === '' || $correo === '') {
            $this->redirect('index.php?page=administradores&error=' . urlencode('Por favor complete todos los campos obligatorios.'));
        }

        if (!in_array($nombreRol, self::ROLES_VALIDOS, true)) {
            $this->redirect('index.php?page=administradores&error=' . urlencode('Seleccione un tipo de administrador válido.'));
        }

        try {
            $model = new AdminModel();
            $model->crearAdministrador([
                'tipo_documento' => $tipoDoc,
                'documento'      => $documento,
                'nombres'        => $nombres,
                'apellidos'      => $apellidos,
                'correo'         => $correo,
                'telefono'       => $telefono,
                'password'       => '123456' // Contraseña inicial por defecto
            ], $nombreRol);

            $this->redirect('index.php?page=administradores&mensaje=' . urlencode('Administrador registrado exitosamente con contraseña temporal 123456.'));
        } catch (Throwable $e) {
            error_log('Error al registrar administrador: ' . $e->getMessage());
            $this->redirect('index.php?page=administradores&error=' . urlencode('Error al registrar administrador. Verifique que el documento o correo no estén ya registrados.'));
        }
    }

    /**
     * Activa o inactiva la cuenta de un administrador vía POST.
     */
    public function cambiarEstado(): void
    {
        $this->requireAuth(['admin']);

        $idUsuario   = (int)($_POST['id_usuario'] ?? 0);
        $nuevoEstado = trim($_POST['estado'] ?? '');

        $estadosValidos = ['Activo', 'Inactivo'];

        if ($idUsuario <= 0 || !in_array($nuevoEstado, $estadosValidos, true)) {
            $this->redirect('index.php?page=administradores&error=' . urlencode('Datos inválidos para actualizar el estado.'));
        }

        // Ningún administrador puede inactivar su propia cuenta
        if ($idUsuario === (int)($_SESSION['id_usuario'] ?? 0)) {
            $this->redirect('index.php?page=administradores&error=' . urlencode('No puedes cambiar el estado de tu propia cuenta.'));
        }

        try {
            $model = new AdminModel();
            $model->actualizarEstado($idUsuario, $nuevoEstado);
            $this->redirect('index.php?page=administradores&mensaje=' . urlencode('Estado del administrador actualizado.'));
        } catch (Throwable $e) {
            error_log("AdminController::cambiarEstado: " . $e->getMessage());
            $this->redirect('index.php?page=administradores&error=' . urlencode('Error al actualizar el estado.'));
        }
    }
}