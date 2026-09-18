<?php
/**
 * Controlador de Inicio de Sesión (LoginController)
 * 
 * Responsabilidad:
 * 1. Validación de entradas del formulario de inicio de sesión.
 * 2. Manejo de errores (fuerza bruta, datos vacíos, credenciales incorrectas, cuenta inactiva).
 * 3. Delegación de consultas de autenticación a LoginModel.
 * 4. Asignación de variables de sesión según el rol (aprendiz, instructor con área, admin con área).
 * 5. Cierre seguro de sesión (logout).
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/LoginModel.php';

class LoginController extends BaseController
{
    /**
     * Muestra la pantalla de inicio de sesión.
     */
    public function mostrarLogin(): void
    {
        $this->requireGuest();

        $error   = $_GET['error'] ?? null;
        $mensaje = $_GET['mensaje'] ?? null;

        $this->renderView('auth/login', [
            'error'   => $error,
            'mensaje' => $mensaje
        ]);
    }

    /**
     * Procesa la solicitud de inicio de sesión vía POST.
     */
    public function login(): void
    {
        $this->requireGuest();

        // 1. Verificación de intentos de fuerza bruta
        if (Seguridad::loginBloqueado()) {
            $this->redirect('index.php?page=login&error=' . urlencode('Demasiados intentos fallidos. Acceso bloqueado temporalmente por seguridad.'));
        }

        // 2. Validación de entradas (limpieza y verificación de campos obligatorios)
        $documento = trim($_POST['documento'] ?? '');
        $password  = trim($_POST['password'] ?? '');

        if ($documento === '' || $password === '') {
            $this->redirect('index.php?page=login&error=' . urlencode('Por favor, ingrese su documento y contraseña.'));
        }

        // 3. Consulta de datos mediante LoginModel
        $loginModel = new LoginModel();
        try {
            $usuario = $loginModel->buscarUsuarioPorDocumento($documento);
        } catch (PDOException $e) {
            error_log('Error de base de datos en autenticación: ' . $e->getMessage());
            $this->redirect('index.php?page=login&error=' . urlencode('Error interno del servidor al procesar la solicitud.'));
        }

        // 4. Verificación de credenciales (existencia y contraseña hash)
        if (!$usuario || !password_verify($password, $usuario['contraseña'])) {
            Seguridad::registrarIntentoFallido();
            $this->redirect('index.php?page=login&error=' . urlencode('Credenciales no válidas.'));
        }

        // 5. Verificación del estado de la cuenta
        if ($usuario['estado'] !== 'Activo') {
            $this->redirect('index.php?page=login&error=' . urlencode('Su cuenta se encuentra inactiva. Contacte al administrador.'));
        }

        // 6. Éxito: limpiar intentos y prevenir fijación de sesión
        Seguridad::limpiarIntentos();
        session_regenerate_id(true);

        // 7. Carga de datos base en la sesión
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

        // 8. Carga de datos complementarios y redirección según rol
        if (str_contains($nombreRolLower, 'aprendiz')) {
            $datosApr = $loginModel->obtenerDetalleAprendiz((int)$usuario['id_usuario']);
            $_SESSION['id_aprendiz'] = $datosApr['id_aprendiz'] ?? null;
            $_SESSION['id_ficha']    = $datosApr['fichas_id_ficha'] ?? null;

            $this->redirect('index.php?page=aprendiz_horario');

        } elseif (str_contains($nombreRolLower, 'instructor')) {
            $datosIns = $loginModel->obtenerDetalleInstructor((int)$usuario['id_usuario']);
            $_SESSION['id_instructor'] = $datosIns['id_instructor'] ?? null;
            $_SESSION['area']          = $datosIns['area'] ?? null;

            $this->redirect('index.php?page=instructor_dashboard');

        } elseif (str_contains($nombreRolLower, 'admin')) {
            // Clasificación por área técnica
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
     * Cierra la sesión activa de forma segura.
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
