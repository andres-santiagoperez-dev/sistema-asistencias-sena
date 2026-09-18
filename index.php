<?php
/**
 * Despachador Frontal (Front Controller)
 * 
 * Punto único de entrada al sistema de asistencias.
 * Captura la página solicitada (?page=...) y delega la ejecución
 * al controlador correspondiente.
 */

require_once __DIR__ . '/app/config/init.php';

$page   = $_GET['page'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

switch ($page) {
    case 'login':
        $auth = new AuthController();
        if ($metodo === 'POST') {
            $auth->login();
        } else {
            $auth->mostrarLogin();
        }
        break;

    case 'logout':
        (new AuthController())->logout();
        break;

    default:
        // Si el usuario ya está autenticado, redirigir a su vista correspondiente
        if (isset($_SESSION['id_usuario'])) {
            $rol = strtolower($_SESSION['nombre_rol'] ?? '');
            if (str_contains($rol, 'admin')) {
                header('Location: ' . BASE_URL . '/app/Views/admin/fichas.php');
            } elseif (str_contains($rol, 'instructor')) {
                header('Location: ' . BASE_URL . '/app/Views/instructor/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/app/Views/aprendiz/aprendiz-horario.php');
            }
            exit;
        }

        // Si es visitante sin sesión, mostrar el formulario de login
        (new AuthController())->mostrarLogin();
        break;
}
