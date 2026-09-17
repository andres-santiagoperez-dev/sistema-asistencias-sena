<?php
// loginAuth.php - Autenticación y Sesión Optimizada (Modelo Híbrido)

session_start();

// Configuración de respuesta base (Soporte AJAX / Fetch o Formulario Tradicional)
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
          || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

$response = [
    'success' => false,
    'message' => ''
];

// 1. Conexión a la base de datos
require_once '../config/database.php'; 
$pdo = Database::conectar(); 

// --- PROTECCIÓN CONTRA FUERZA BRUTA ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SESSION['login_attempts'] >= 5) {
    $time_passed = time() - $_SESSION['last_attempt_time'];
    if ($time_passed < 900) { // 15 minutos
        $minutos_restantes = ceil((900 - $time_passed) / 60);
        $response['message'] = "Demasiados intentos fallidos. Por favor, espere $minutos_restantes minutos.";
        if ($isAjax) { echo json_encode($response); exit(); }
        header("Location: ../views/auth/login.php?error=" . urlencode($response['message']));
        exit();
    } else {
        $_SESSION['login_attempts'] = 0; // Resetear después del tiempo de castigo
    }
}
// --------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no permitido.';
    if ($isAjax) { echo json_encode($response); exit(); }
    header("Location: ../views/auth/login.php");
    exit();
}

// 2. Captura y limpieza de credenciales
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$login_usuario = trim($input['documento'] ?? $input['usuario'] ?? ''); 
$contrasena    = trim($input['password'] ?? $input['contrasena'] ?? '');

if (empty($login_usuario) || empty($contrasena)) {
    $response['message'] = 'Por favor, ingrese su usuario (número de documento o correo) y contraseña.';
    if ($isAjax) { echo json_encode($response); exit(); }
    header("Location: ../views/auth/login.php?error=" . urlencode($response['message']));
    exit();
}

try {
    // 3. Consultar usuario y rol principal
    $sql_usuario = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres, u.apellidos, 
                           u.correo, u.telefono, u.contraseña, u.estado, u.roles_id_rol, r.nombre_rol
                    FROM usuarios u
                    INNER JOIN roles r ON u.roles_id_rol = r.id_rol
                    WHERE u.numero_documento = :documento OR u.correo = :correo
                    LIMIT 1";

    $stmt = $pdo->prepare($sql_usuario);
    $stmt->execute([
        ':documento' => $login_usuario,
        ':correo'    => $login_usuario
    ]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // 4. Validación de existencia y hash de contraseña
    if (!$usuario || !password_verify($contrasena, $usuario['contraseña'])) {
        $_SESSION['login_attempts']++; // Sumar intento fallido
        $_SESSION['last_attempt_time'] = time();
        
        $intentos_restantes = 5 - $_SESSION['login_attempts'];
        $mensaje_error = 'Credenciales no válidas.';
        if ($intentos_restantes > 0) {
            $mensaje_error .= ' Intentos restantes: ' . $intentos_restantes;
        } else {
            $mensaje_error = 'Demasiados intentos fallidos. Su cuenta ha sido bloqueada temporalmente.';
        }
        
        $response['message'] = $mensaje_error;
        if ($isAjax) { echo json_encode($response); exit(); }
        header("Location: ../views/auth/login.php?error=" . urlencode($response['message']));
        exit();
    }

    // Si la contraseña es CORRECTA, reseteamos los intentos
    $_SESSION['login_attempts'] = 0;

    // 5. Validación de estado activo en el sistema base
    if ($usuario['estado'] !== 'Activo') {
        $response['message'] = 'Su cuenta se encuentra inactiva. Contacte al administrador.';
        if ($isAjax) { echo json_encode($response); exit(); }
        header("Location: ../views/auth/login.php?error=" . urlencode($response['message']));
        exit();
    }

    // Prevenir fijación de sesión
    session_regenerate_id(true);

    // 6. Carga de datos base en $_SESSION
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

    $nombre_rol_lower = strtolower($usuario['nombre_rol']);
    $redirect_url     = '../views/auth/login.php';

    // 7. CARGA DE IDs ESPECÍFICOS Y RUTAS DE REDIRECCIÓN SEGÚN EL ROL

    // ==========================================
    // ROL: APRENDIZ
    // ==========================================
    if (strpos($nombre_rol_lower, 'aprendiz') !== false) {

        // Consulta mínima: solo traemos el ID del aprendiz y su ficha para enlazarlos
        $sql_aprendiz = "SELECT id_aprendiz, fichas_id_ficha, estado_matricula 
                         FROM aprendices 
                         WHERE usuarios_id_usuario = :id_usuario LIMIT 1";
        
        $stmt_apr = $pdo->prepare($sql_aprendiz);
        $stmt_apr->execute([':id_usuario' => $usuario['id_usuario']]);
        $datos_aprendiz = $stmt_apr->fetch(PDO::FETCH_ASSOC);

        if ($datos_aprendiz) {
            // Validamos que su matrícula esté activa (tolerante a campo vacío por pruebas)
            if ($datos_aprendiz['estado_matricula'] !== 'Matriculado' && $datos_aprendiz['estado_matricula'] !== '') {
                $response['message'] = 'Su estado de matrícula (' . $datos_aprendiz['estado_matricula'] . ') no le permite ingresar.';
                if ($isAjax) { echo json_encode($response); exit(); }
                header("Location: ../Views/auth/login.php?error=" . urlencode($response['message']));
                exit();
            }

            // Guardamos exclusivamente las llaves foráneas necesarias para hacer consultas posteriores
            $_SESSION['id_aprendiz'] = $datos_aprendiz['id_aprendiz'];
            $_SESSION['id_ficha']    = $datos_aprendiz['fichas_id_ficha'];
        } else {
            $_SESSION['id_aprendiz'] = null;
            $_SESSION['id_ficha']    = null;
        }

        $redirect_url = '../Views/aprendiz/aprendiz-horario.php';

    // ==========================================
    // ROL: INSTRUCTOR
    // ==========================================
    } elseif (strpos($nombre_rol_lower, 'instructor') !== false) {

        // Consulta mínima: solo traemos el ID del instructor
        $sql_inst = "SELECT id_instructor FROM instructores WHERE usuarios_id_usuario = :id_usuario LIMIT 1";
        $stmt_inst = $pdo->prepare($sql_inst);
        $stmt_inst->execute([':id_usuario' => $usuario['id_usuario']]);
        $datos_inst = $stmt_inst->fetch(PDO::FETCH_ASSOC);

        if ($datos_inst) {
            $_SESSION['id_instructor'] = $datos_inst['id_instructor'];
        }

        $redirect_url = '../Views/instructor/dashboard.php';

    // ==========================================
    // ROL: ADMINISTRADOR
    // ==========================================
  } elseif (strpos($nombre_rol_lower, 'admin') !== false) {
        $redirect_url = '../Views/admin/aprendices.php';
    }

    // 8. Respuesta de éxito
    $response['success']  = true;
    $response['message']  = 'Inicio de sesión exitoso.';
    $response['redirect'] = $redirect_url;
    $response['user']     = [
        'id_usuario'      => $usuario['id_usuario'],
        'nombre_completo' => $_SESSION['nombre_completo'],
        'rol'             => $usuario['nombre_rol'],
        'redirect'        => $redirect_url
    ];

    if ($isAjax) {
        echo json_encode($response);
        exit();
    } else {
        header("Location: " . $redirect_url);
        exit();
    }

} catch (PDOException $e) {
    error_log("Error en loginAuth.php: " . $e->getMessage());
    $response['message'] = 'Error interno en el servidor al autenticar.';
    
    if ($isAjax) {
        echo json_encode($response);
        exit();
    } else {
        header("Location: ../views/auth/login.php?error=" . urlencode($response['message']));
        exit();
    }
}