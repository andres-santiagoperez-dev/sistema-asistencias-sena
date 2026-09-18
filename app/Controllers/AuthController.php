<?php
/**
 * Controlador de Autenticación (AuthController)
 * 
 * Responsabilidad: Manejo del ciclo de vida de la sesión (mostrar formulario,
 * autenticar credenciales con UserModel, asignar datos de sesión por rol y logout).
 * No contiene SQL (delega a UserModel) ni HTML directo (delega a renderView).
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/UserModel.php';

class AuthController extends BaseController
{
    /**
     * Muestra la vista de inicio de sesión.
     */
    public function mostrarLogin(): void
    {
        $this->requireGuest();

        $error = $_GET['error'] ?? null;
        $this->renderView('auth/login', [
            'error' => $error
        ]);
    }

    /**
     * Procesa el formulario de login vía POST.
     */
    public function login(): void
    {
        $this->requireGuest();

        // 1. Verificación de intentos fallidos (Fuerza Bruta)
        if (Seguridad::loginBloqueado()) {
            $this->redirect('index.php?page=login&error=' . urlencode('Demasiados intentos fallidos. Acceso bloqueado temporalmente.'));
        }

        // 2. Captura y sanitización de datos de entrada
        $documento = trim($_POST['documento'] ?? '');
        $password  = trim($_POST['password'] ?? '');

        if ($documento === '' || $password === '') {
            $this->redirect('index.php?page=login&error=' . urlencode('Por favor, ingrese su documento y contraseña.'));
        }

        // 3. Consulta de usuario mediante el Modelo
        $userModel = new UserModel();
        try {
            $usuario = $userModel->buscarPorDocumento($documento);
        } catch (PDOException $e) {
            error_log('Error en BD al autenticar: ' . $e->getMessage());
            $this->redirect('index.php?page=login&error=' . urlencode('Error del sistema al conectar con la base de datos.'));
        }

        // 4. Verificación de existencia y coincidencia de hash de contraseña
        if (!$usuario || !password_verify($password, $usuario['contraseña'])) {
            Seguridad::registrarIntentoFallido();
            $this->redirect('index.php?page=login&error=' . urlencode('Credenciales no válidas.'));
        }

        // 5. Verificación de cuenta activa
        if ($usuario['estado'] !== 'Activo') {
            $this->redirect('index.php?page=login&error=' . urlencode('Su cuenta se encuentra inactiva. Contacte al administrador.'));
        }

        // 6. Credenciales correctas: limpiar intentos y regenerar ID de sesión
        Seguridad::limpiarIntentos();
        session_regenerate_id(true);

        // 7. Carga de datos base en $_SESSION
        $_SESSION['id_usuario']       = $usuario['id_usuario'];
        $_SESSION['tipo_documento']   = $usuario['tipo_documento'];
        $_SESSION['numero_documento'] = $usuario['numero_documento'];
        $_SESSION['nombres']          = $usuario['nombres'];
        $_SESSION['apellidos']        = $usuario['apellidos'];
        $_SESSION['nombre_completo']  = trim($usuario['nombres'] . ' ' . $usuario['apellidos']);
        $_SESSION['correo']           = $usuario['correo'];
        $_SESSION['telefono']         = $usuario['telefono'];
        $_SESSION['id_rol']           = $usuario['roles_id_rol'];
        $_SESSION['nombre_rol']       = $usuario['nombre_rol'];

        $nombreRolLower = strtolower($usuario['nombre_rol']);

        // 8. Carga de perfiles complementarios y redirección por rol
        if (str_contains($nombreRolLower, 'aprendiz')) {
            $datosApr = $userModel->obtenerDatosAprendiz((int)$usuario['id_usuario']);
            $_SESSION['id_aprendiz'] = $datosApr['id_aprendiz'] ?? null;
            $_SESSION['id_ficha']    = $datosApr['fichas_id_ficha'] ?? null;

            $this->redirect('index.php?page=aprendiz_horario');

        } elseif (str_contains($nombreRolLower, 'instructor')) {
            $datosIns = $userModel->obtenerDatosInstructor((int)$usuario['id_usuario']);
            $_SESSION['id_instructor'] = $datosIns['id_instructor'] ?? null;
            $_SESSION['area']          = $datosIns['area'] ?? null;

            $this->redirect('index.php?page=instructor_dashboard');

        } elseif (str_contains($nombreRolLower, 'admin')) {
            // Clasificación de área de administración (Comercio vs TICs)
            if (str_contains($nombreRolLower, 'comercio')) {
                $_SESSION['admin_area'] = 'comercio';
            } elseif (str_contains($nombreRolLower, 'tic')) {
                $_SESSION['admin_area'] = 'Tics';
            } else {
                $_SESSION['admin_area'] = 'todas';
            }

            $this->redirect('index.php?page=admin_aprendices');
        } else {
            $this->redirect('index.php?page=login');
        }
    }

    /**
     * Cierra la sesión activa y redirige al login.
     */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->redirect('index.php?page=login&mensaje=' . urlencode('Sesión cerrada correctamente.'));
    }
}
