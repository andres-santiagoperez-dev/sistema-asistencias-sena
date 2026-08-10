<?php
// loginAuth.php - Procesamiento de inicio de sesión seguro y gestión de sesión por rol

session_start();
header('Content-Type: application/json; charset=utf-8');

// Configuración de respuesta por defecto
$response = [
    'success' => false,
    'message' => ''
];

// Requerir archivo de conexión a la base de datos y obtener la instancia PDO
require_once '../config/database.php'; 
$pdo = Database::conectar(); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no permitido.';
    echo json_encode($response);
    exit();
}

// Obtener datos del formulario (soporta nombres 'documento'/'usuario' y 'password'/'contrasena')
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$login_usuario = trim($input['documento'] ?? $input['usuario'] ?? ''); 
$contrasena    = trim($input['password'] ?? $input['contrasena'] ?? '');

// 1. Validaciones de campos obligatorios
if (empty($login_usuario) || empty($contrasena)) {
    $response['message'] = 'Por favor, ingrese su usuario (correo/documento) y contraseña.';
    echo json_encode($response);
    exit();
}

try {
    // Consultar usuario y su rol uniendo las tablas con alias (u, r) y soporte para documento o correo
    $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres, u.apellidos, 
                   u.correo, u.contraseña, u.estado, u.roles_id_rol, r.nombre_rol
            FROM usuarios u
            INNER JOIN roles r ON u.roles_id_rol = r.id_rol
            WHERE u.numero_documento = :usuario
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usuario' => $login_usuario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificación de existencia del usuario y contraseña encriptada (password_verify)
    if (!$usuario || !password_verify($contrasena, $usuario['contraseña'])) {
        $response['message'] = 'Credenciales no válidas. Por favor verifique sus datos.';
        echo json_encode($response);
        exit();
    }

    // Verificación de estado del usuario 
    if ($usuario['estado'] !== 'Activo') {
        $response['message'] = 'Su cuenta se encuentra inactiva. Contacte al administrador.';
        echo json_encode($response);
        exit();
    }

    // 5. Cargar datos específicos del rol (Instructores o Aprendices)
    $id_especifico = null;
    $id_ficha = null;
    $nombre_rol_lower = strtolower($usuario['nombre_rol']);

    if (strpos($nombre_rol_lower, 'instructor') !== false) {
        // Obtener datos de la tabla 'instructores'
        $stmt_inst = $pdo->prepare("SELECT id_instructor FROM instructores WHERE usuarios_id_usuario = :id_usuario LIMIT 1");
        $stmt_inst->execute([':id_usuario' => $usuario['id_usuario']]);
        $instructor = $stmt_inst->fetch(PDO::FETCH_ASSOC);
        
        if ($instructor) {
            $id_especifico = $instructor['id_instructor'];
        }
    } elseif (strpos($nombre_rol_lower, 'aprendiz') !== false) {
        // Obtener datos de la tabla 'aprendices'
        $stmt_apr = $pdo->prepare("SELECT id_aprendiz, fichas_id_ficha, estado_matricula FROM aprendices WHERE usuarios_id_usuario = :id_usuario LIMIT 1");
        $stmt_apr->execute([':id_usuario' => $usuario['id_usuario']]);
        $aprendiz = $stmt_apr->fetch(PDO::FETCH_ASSOC);

        if ($aprendiz) {
            if ($aprendiz['estado_matricula'] !== 'En Formacion' && $aprendiz['estado_matricula'] !== 'Activo') {
                $response['message'] = 'Su estado de matrícula no le permite acceder al sistema.';
                echo json_encode($response);
                exit();
            }
            $id_especifico = $aprendiz['id_aprendiz'];
            $id_ficha      = $aprendiz['fichas_id_ficha'];
        }
    }

    // 6. Mapeo de rutas de redirección según el rol
    $redirect_url = 'views/login.php';

    if (strpos($nombre_rol_lower, 'administrador') !== false || strpos($nombre_rol_lower, 'admin') !== false) {
        $redirect_url = 'views/admin/dashboard.php';
    } elseif (strpos($nombre_rol_lower, 'instructor') !== false) {
        $redirect_url = 'views/instructor/dashboard.php';
    } elseif (strpos($nombre_rol_lower, 'aprendiz') !== false) {
        $redirect_url = 'views/aprendiz/dashboard.php';
    }

    // 7. Regenerar ID de sesión por seguridad (Previene ataques de Session Fixation)
    session_regenerate_id(true);

    // 8. Establecer variables de sesión
    $_SESSION['id_usuario']       = $usuario['id_usuario'];
    $_SESSION['nombres']          = $usuario['nombres'];
    $_SESSION['apellidos']        = $usuario['apellidos'];
    $_SESSION['nombre_completo']  = $usuario['nombres'] . ' ' . $usuario['apellidos'];
    $_SESSION['correo']           = $usuario['correo'];
    $_SESSION['id_rol']           = $usuario['roles_id_rol'];
    $_SESSION['nombre_rol']       = $usuario['nombre_rol'];
    $_SESSION['id_instructor']    = $id_especifico; // Solo se llena si es Instructor
    $_SESSION['id_aprendiz']      = $id_especifico; // Solo se llena si es Aprendiz
    $_SESSION['id_ficha']         = $id_ficha;       // Solo se llena si es Aprendiz

} catch (PDOException $e) {
    // Log interno del error y mensaje genérico al usuario por seguridad
    error_log("Error de autenticación: " . $e->getMessage());
    $response['message'] = 'Ocurrió un error. Intente más tarde.';
}

echo json_encode($response);
exit();