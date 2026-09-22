<?php
/**
 * Controlador de Dashboard (DashboardController)
 * 
 * Responsabilidad:
 * 1. Proteger el acceso a los paneles de control según el rol autenticado.
 * 2. Orquestar la obtención de métricas, gráficas y registros recientes desde DashboardModel.
 * 3. Filtrar los datos según el área técnica del administrador en sesión.
 * 4. Renderizar la vista correspondiente ('admin/dashboard' o 'instructor/dashboard').
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/DashboardModel.php';

class DashboardController extends BaseController
{
    /**
     * Punto de entrada principal que redirige al dashboard adecuado según el rol.
     */
    public function index(): void
    {
        $this->requireAuth();
        $rol = strtolower($_SESSION['nombre_rol'] ?? '');

        if (str_contains($rol, 'admin')) {
            $this->admin();
        } elseif (str_contains($rol, 'instructor')) {
            $this->instructor();
        } else {
            $this->redirect('index.php?page=aprendiz_horario');
        }
    }

    /**
     * Dashboard del Administrador.
     * Carga KPIs, gráficas y registros recientes con filtro de área técnica.
     */
    public function admin(): void
    {
        $this->requireAuth(['admin']);

        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        $stats = [
            'aprendices'        => 0,
            'retardosHoy'       => 0,
            'inasistenciasHoy'  => 0,
            'excusasPendientes' => 0,
        ];
        $graficoSemanal = [
            'categorias'    => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
            'a_tiempo'      => [0, 0, 0, 0, 0],
            'retardos'      => [0, 0, 0, 0, 0],
            'inasistencias' => [0, 0, 0, 0, 0]
        ];
        $graficoExcusas = [0, 0, 0];
        $ultimosIngresos = [];

        try {
            $model = new DashboardModel();
            $stats           = $model->obtenerEstadisticasAdmin($filtroArea);
            $graficoSemanal  = $model->obtenerAsistenciaSemanalAdmin($filtroArea);
            $graficoExcusas  = $model->obtenerBalanceExcusasAdmin($filtroArea);
            $ultimosIngresos = $model->obtenerUltimosIngresosAdmin($filtroArea, 6);
        } catch (Throwable $e) {
            error_log('Error en DashboardController::admin: ' . $e->getMessage());
        }

        $this->renderView('admin/dashboard', [
            'stats'           => $stats,
            'graficoSemanal'  => $graficoSemanal,
            'graficoExcusas'  => $graficoExcusas,
            'ultimosIngresos' => $ultimosIngresos,
            'adminArea'       => $adminArea,
            'nombreUsuario'   => $_SESSION['nombre_usuario'] ?? 'Administrador',
            'mensaje'         => $_GET['mensaje'] ?? null,
            'error'           => $_GET['error'] ?? null
        ]);
    }

    /**
     * Dashboard del Instructor (preparado para la fase del portal docente).
     */
    public function instructor(): void
    {
        $this->requireAuth(['instructor']);
        $this->redirect('app/Views/instructor/dashboard.php');
    }
}
