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

    case 'recuperar_password':
        $loginCtrl = new LoginController();
        $accion = $_GET['action'] ?? '';
        if ($accion === 'solicitar_codigo') {
            $loginCtrl->solicitarCodigoRecuperacion();
        } elseif ($accion === 'restablecer') {
            $loginCtrl->restablecerContraseña();
        } else {
            $loginCtrl->mostrarLogin();
        }
        break;

    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'fichas':
        $fichaCtrl = new FichaController();
        $accion    = $_GET['action'] ?? '';
        if ($accion === 'obtener_bloques') {
            $fichaCtrl->obtenerBloques();
        } elseif ($accion === 'guardar_bloque') {
            $fichaCtrl->guardarBloque();
        } elseif ($accion === 'eliminar_bloque') {
            $fichaCtrl->eliminarBloque();
        } elseif ($accion === 'importar_excel_horarios') {
            $fichaCtrl->importarHorariosExcel();
        } elseif ($metodo === 'POST') {
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
        case 'administradores':
    $adminCtrl = new AdminController();
    $accion    = $_GET['action'] ?? '';
    if ($metodo === 'POST' && $accion === 'cambiarEstado') {
        $adminCtrl->cambiarEstado();
    } elseif ($metodo === 'POST') {
        $adminCtrl->guardar();
    } else {
        $adminCtrl->index();
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

    case 'aprendiz_historial':
        $histCtrl = new HistorialController();
        $accion   = $_GET['action'] ?? '';
        if ($accion === 'pdf') {
            $histCtrl->exportarPdf();
        } else {
            $histCtrl->index();
        }
        break;

    case 'aprendiz_perfil':
        $perfilCtrl = new PerfilController();
        if ($metodo === 'POST') {
            $perfilCtrl->actualizar();
        } else {
            $perfilCtrl->index();
        }
        break;

    case 'aprendiz_excusas':
        $excusaCtrl = new AprendizExcusaController();
        if ($metodo === 'POST') {
            $excusaCtrl->radicar();
        } else {
            $excusaCtrl->index();
        }
        break;

    case 'instructor_dashboard':
        $dashCtrl = new InstructorDashboardController();
        $accion   = $_GET['action'] ?? '';
        if ($metodo === 'POST' && $accion === 'guardar_tolerancia') {
            $dashCtrl->guardarTolerancia();
        } else {
            $dashCtrl->index();
        }
        break;

    case 'instructor_horario':
        $horarioCtrl = new InstructorHorarioController();
        $accion      = $_GET['action'] ?? '';
        if ($metodo === 'POST' && $accion === 'guardarNota') {
            $horarioCtrl->guardarNota();
        } elseif ($accion === 'eliminarNota') {
            $horarioCtrl->eliminarNota();
        } else {
            $horarioCtrl->index();
        }
        break;

    case 'instructor_aprendices':
        $instAprCtrl = new InstructorAprendizController();
        $accion      = $_GET['action'] ?? '';
        if ($accion === 'obtener_historial') {
            $instAprCtrl->obtenerHistorialGlobal();
        } elseif ($accion === 'guardar_observacion') {
            $instAprCtrl->guardarObservacion();
        } else {
            $instAprCtrl->index();
        }
        break;

    case 'instructor_excusas':
        $excusaCtrl = new InstructorExcusaController();
        if ($metodo === 'POST') {
            $excusaCtrl->evaluar();
        } else {
            $excusaCtrl->index();
        }
        break;

    case 'instructor_ingresos':
        $ingresosCtrl = new InstructorIngresosController();
        $accion       = $_GET['action'] ?? '';
        if ($accion === 'guardar_tolerancia') {
            $ingresosCtrl->guardarTolerancia();
        } elseif ($accion === 'imprimir_llamado') {
            $ingresosCtrl->imprimirLlamado();
        } elseif ($metodo === 'POST' || $accion === 'registrar_escaneo') {
            $ingresosCtrl->registrarEscaneo();
        } elseif ($accion === 'excel') {
            $ingresosCtrl->exportarExcel();
        } elseif ($accion === 'pdf') {
            $ingresosCtrl->exportarPdf();
        } else {
            $ingresosCtrl->index();
        }
        break;


    default:
        // Si el usuario ya está autenticado, redirigir a su vista correspondiente
        if (isset($_SESSION['id_usuario'])) {
            $rol = strtolower($_SESSION['nombre_rol'] ?? '');
            if (str_contains($rol, 'admin')) {
                header('Location: ' . BASE_URL . '/index.php?page=dashboard');
            } elseif (str_contains($rol, 'instructor')) {
                header('Location: ' . BASE_URL . '/index.php?page=instructor_dashboard');
            } else {
                header('Location: ' . BASE_URL . '/index.php?page=aprendiz_horario');
            }
            exit;
        }

        // Si es visitante sin sesión, mostrar el formulario de login
        (new LoginController())->mostrarLogin();
        break;
}
