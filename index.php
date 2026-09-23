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
        $loginCtrl = new LoginController();
        if ($metodo === 'POST') {
            $loginCtrl->login();
        } else {
            $loginCtrl->mostrarLogin();
        }
        break;

    case 'logout':
        (new LoginController())->logout();
        break;

    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'fichas':
        $fichaCtrl = new FichaController();
        if ($metodo === 'POST') {
            $fichaCtrl->guardar();
        } else {
            $fichaCtrl->index();
        }
        break;

    case 'instructores':
        $instCtrl = new InstructorController();
        if ($metodo === 'POST') {
            $instCtrl->guardar();
        } else {
            $instCtrl->index();
        }
        break;

    case 'aprendices':
        $aprCtrl = new AprendizController();
        if ($metodo === 'POST') {
            $aprCtrl->guardar();
        } else {
            $aprCtrl->index();
        }
        break;

    case 'reportes':
        $repCtrl = new ReporteController();
        $action  = $_GET['action'] ?? '';
        if ($action === 'excel') {
            $repCtrl->exportarExcel();
        } elseif ($action === 'pdf') {
            $repCtrl->exportarPdf();
        } else {
            $repCtrl->index();
        }
        break;

    case 'aprendiz_horario':
        (new HorarioController())->index();
        break;

    default:
        // Si el usuario ya está autenticado, redirigir a su vista correspondiente
        if (isset($_SESSION['id_usuario'])) {
            $rol = strtolower($_SESSION['nombre_rol'] ?? '');
            if (str_contains($rol, 'admin')) {
                header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            } elseif (str_contains($rol, 'instructor')) {
                header('Location: ' . BASE_URL . '/app/Views/instructor/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php?page=aprendiz_horario');
            }
            exit;
        }

        // Si es visitante sin sesión, mostrar el formulario de login
        (new LoginController())->mostrarLogin();
        break;
}
