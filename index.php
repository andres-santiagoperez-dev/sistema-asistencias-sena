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
$accion = $_GET['action'] ?? $_POST['action'] ?? '';
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ==============================================================================
// PROTECCIÓN CENTRALIZADA CONTRA ATAQUES CSRF (CROSS-SITE REQUEST FORGERY)
// ==============================================================================
if ($metodo === 'POST') {
    // Rutas exentas de verificación de token (ej. revalidación offline que autentica con credenciales directas)
    $esExento = ($page === 'instructor_ingresos' && ($accion === 'revalidar_sesion_offline'));

    if (!$esExento) {
        $tokenRecibido = $_POST['csrf_token'] 
                      ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                      ?? $_SERVER['HTTP_X_XSRF_TOKEN'] 
                      ?? null;

        if (!Seguridad::validarCsrf($tokenRecibido)) {
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   || !empty($_SERVER['HTTP_X_CSRF_TOKEN'])
                   || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'json'))
                   || isset($_POST['ajax']);

            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Token de seguridad CSRF inválido o sesión expirada. Por favor recargue la página.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            http_response_code(403);
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Solicitud no válida (CSRF) - SENA</title>
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <style>
                    body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                    .card { background: #1e293b; padding: 2rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); max-width: 420px; text-align: center; border: 1px solid #334155; }
                    h2 { color: #ef4444; margin-top: 0; font-size: 1.25rem; }
                    p { color: #94a3b8; font-size: 0.9rem; line-height: 1.5; }
                    a { display: inline-block; margin-top: 1rem; background: #16a34a; color: white; padding: 0.6rem 1.2rem; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
                    a:hover { background: #15803d; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>Error de Seguridad (CSRF)</h2>
                    <p>La solicitud no pudo ser validada porque su sesión ha expirado o el token de seguridad no es válido.</p>
                    <a href="javascript:history.back()">Recargar y Reintentar</a>
                </div>
            </body>
            </html>';
            exit;
        }
    }
}

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

    case 'ver_soporte':
        (new SoporteController())->ver();
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
        } elseif ($accion === 'solicitar_suplencia') {
            $ingresosCtrl->solicitarSuplencia();
        } elseif ($accion === 'responder_suplencia') {
            $ingresosCtrl->responderSuplencia();
        } elseif ($accion === 'obtener_directorio_offline') {
            $ingresosCtrl->obtenerDirectorioOffline();
        } elseif ($accion === 'sincronizar_offline') {
            $ingresosCtrl->sincronizarOffline();
        } elseif ($accion === 'revalidar_sesion_offline') {
            $ingresosCtrl->revalidarSesionOffline();
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
