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

    /**
     * Endpoint AJAX para solicitar código de verificación por correo
     */
    public function solicitarCodigoRecuperacion(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $correo = trim($_POST['correo'] ?? '');

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, ingresa un correo electrónico válido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $loginModel = new LoginModel();
        $usuario = $loginModel->buscarUsuarioPorCorreo($correo);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'No encontramos ninguna cuenta registrada con ese correo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($usuario['estado'] !== 'Activo') {
            echo json_encode(['success' => false, 'message' => 'Tu cuenta se encuentra inactiva. Comunícate con el administrador.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Generar código numérico de 6 dígitos
        $codigo = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $minutosValidez = 15;

        // Guardar código en la BD
        $guardado = $loginModel->guardarCodigoRecuperacion((int)$usuario['id_usuario'], $codigo, $minutosValidez);

        if (!$guardado) {
            echo json_encode(['success' => false, 'message' => 'No fue posible generar el código temporal. Inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Enviar por correo con MailService
        $mailService = new \App\Services\MailService();
        $nombreCompleto = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
        $resultadoEnvio = $mailService->enviarCodigoRecuperacion($usuario['correo'], $nombreCompleto ?: 'Usuario SENA', $codigo, $minutosValidez);

        if (!$resultadoEnvio['success']) {
            echo json_encode([
                'success' => false,
                'message' => $resultadoEnvio['message']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => '¡Código enviado! Revisa tu bandeja de entrada o spam.',
            'correo'  => $usuario['correo']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Endpoint AJAX para validar el código y cambiar la contraseña
     */
    public function restablecerContraseña(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $correo          = trim($_POST['correo'] ?? '');
        $codigo          = trim($_POST['codigo'] ?? '');
        $password        = trim($_POST['password'] ?? '');
        $passwordConfirm = trim($_POST['password_confirm'] ?? '');

        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Correo electrónico no válido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($codigo === '' || strlen($codigo) !== 6 || !ctype_digit($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Ingresa el código numérico de 6 dígitos que recibiste.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($password !== $passwordConfirm) {
            echo json_encode(['success' => false, 'message' => 'Las contraseñas no coinciden. Verifícalas e inténtalo de nuevo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $loginModel = new LoginModel();
        $usuario = $loginModel->buscarUsuarioPorCorreo($correo);

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'El usuario asociado no fue encontrado.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validar token en la tabla
        $registroRecuperacion = $loginModel->validarCodigoRecuperacion((int)$usuario['id_usuario'], $codigo);

        if (!$registroRecuperacion) {
            echo json_encode([
                'success' => false, 
                'message' => 'El código de verificación es incorrecto o ha expirado. Solicita un nuevo código.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Encriptar nueva contraseña
        $hashNuevo = password_hash($password, PASSWORD_BCRYPT);
        $actualizado = $loginModel->actualizarContraseña((int)$usuario['id_usuario'], $hashNuevo);

        if (!$actualizado) {
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error al actualizar la contraseña en la base de datos.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Inhabilitar código
        $loginModel->marcarCodigoUtilizado((int)$registroRecuperacion['id']);

        echo json_encode([
            'success' => true,
            'message' => '¡Tu contraseña ha sido restablecida exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
