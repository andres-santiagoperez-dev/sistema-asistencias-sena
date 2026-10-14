<?php
/**
 * Controlador de Reportes (ReporteController)
 * 
 * Responsabilidad:
 * 1. Proteger el acceso al módulo de reportería exclusivamente para administradores.
 * 2. Procesar filtros de auditoría (rango de fechas, ficha formativa, aprendiz, área técnica).
 * 3. Consultar datos consolidados y calcular métricas mediante ReporteModel.
 * 4. Despachar la vista interactiva 'admin/reportes'.
 * 5. Generar y exportar archivos estructurados para Excel (CSV con codificación UTF-8 BOM).
 * 6. Generar vista maquetada lista para impresión y descarga en PDF.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Models/ReporteModel.php';
require_once BASE_PATH . '/Services/ExcelExportService.php';

use App\Services\ExcelExportService;

class ReporteController extends BaseController
{
    /**
     * Muestra la interfaz principal de reportes con filtros y métricas aplicadas.
     */
    public function index(): void
    {
        $this->requireAuth(['admin']);

        // 1. Detección y validación de filtros de entrada
        $desde = trim($_GET['desde'] ?? date('Y-m-01'));
        $hasta = trim($_GET['hasta'] ?? date('Y-m-d'));

        // Si las fechas son anómalas o invertidas, se normalizan
        if ($desde > $hasta) {
            $temp  = $desde;
            $desde = $hasta;
            $hasta = $temp;
        }

        $fichaSeleccionada = trim($_GET['ficha'] ?? '');
        $aprendizFiltro    = trim($_GET['aprendiz'] ?? '');

        // Detección de área técnica del administrador en sesión
        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        // 2. Variables de contingencia defensiva
        $reporte  = [];
        $fichas   = [];
        $metricas = [
            'totalInasistencias'  => 0,
            'totalMinutosRetardo' => 0,
            'totalRetardos'       => 0,
            'aprendicesEnRiesgo'  => 0,
            'totalAprendices'     => 0
        ];

        // 3. Consulta de datos mediante el Modelo
        try {
            $model    = new ReporteModel();
            $fichas   = $model->obtenerFichasParaFiltro($filtroArea);
            $reporte  = $model->obtenerConsolidadoAsistencia(
                $desde,
                $hasta,
                $fichaSeleccionada,
                $aprendizFiltro,
                $filtroArea
            );
            $metricas = $model->calcularMetricas($reporte);
        } catch (Throwable $e) {
            error_log("ReporteController::index: " . $e->getMessage());
        }

        // 4. Renderizado desacoplado de la vista
        $this->renderView('admin/reportes', [
            'reporte'           => $reporte,
            'fichas'            => $fichas,
            'metricas'          => $metricas,
            'desde'             => $desde,
            'hasta'             => $hasta,
            'fichaSeleccionada' => $fichaSeleccionada,
            'aprendizFiltro'    => $aprendizFiltro,
            'adminArea'         => $adminArea,
            'mensaje'           => $_GET['mensaje'] ?? null,
            'error'             => $_GET['error'] ?? null
        ]);
    }

    /**
     * Genera y descarga un libro nativo de Microsoft Excel (.xlsx) con diseño corporativo SENA.
     */
    public function exportarExcel(): void
    {
        $this->requireAuth(['admin']);

        $desde             = trim($_GET['desde'] ?? date('Y-m-01'));
        $hasta             = trim($_GET['hasta'] ?? date('Y-m-d'));
        $fichaSeleccionada = trim($_GET['ficha'] ?? '');
        $aprendizFiltro    = trim($_GET['aprendiz'] ?? '');

        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        $reporte  = [];
        $metricas = [
            'totalInasistencias'  => 0,
            'totalMinutosRetardo' => 0,
            'totalRetardos'       => 0,
            'aprendicesEnRiesgo'  => 0,
            'totalAprendices'     => 0
        ];

        try {
            $model    = new ReporteModel();
            $reporte  = $model->obtenerConsolidadoAsistencia(
                $desde,
                $hasta,
                $fichaSeleccionada,
                $aprendizFiltro,
                $filtroArea
            );
            $metricas = $model->calcularMetricas($reporte);

            $excelService = new ExcelExportService();
            $excelService->exportarConsolidadoGeneral($reporte, $metricas, [
                'desde' => $desde,
                'hasta' => $hasta,
                'ficha' => $fichaSeleccionada,
                'area'  => $filtroArea,
            ]);
        } catch (Throwable $e) {
            error_log("ReporteController::exportarExcel: " . $e->getMessage());
            $this->redirect('index.php?page=reportes&error=' . urlencode('Ocurrió un error al generar el archivo Excel: ' . $e->getMessage()));
        }
    }

    /**
     * Genera una vista maquetada con membrete oficial del SENA lista para imprimir o guardar en PDF.
     */
    public function exportarPdf(): void
    {
        $this->requireAuth(['admin']);

        $desde             = trim($_GET['desde'] ?? date('Y-m-01'));
        $hasta             = trim($_GET['hasta'] ?? date('Y-m-d'));
        $fichaSeleccionada = trim($_GET['ficha'] ?? '');
        $aprendizFiltro    = trim($_GET['aprendiz'] ?? '');

        $adminArea  = $_SESSION['admin_area'] ?? 'todas';
        $filtroArea = ($adminArea !== 'todas') ? $adminArea : null;

        $reporte  = [];
        $metricas = [
            'totalInasistencias'  => 0,
            'totalMinutosRetardo' => 0,
            'totalRetardos'       => 0,
            'aprendicesEnRiesgo'  => 0,
            'totalAprendices'     => 0
        ];

        try {
            $model    = new ReporteModel();
            $reporte  = $model->obtenerConsolidadoAsistencia(
                $desde,
                $hasta,
                $fichaSeleccionada,
                $aprendizFiltro,
                $filtroArea
            );
            $metricas = $model->calcularMetricas($reporte);
        } catch (Throwable $e) {
            error_log("ReporteController::exportarPdf: " . $e->getMessage());
        }

        // Renderizado del formato imprimible limpio con estándares editoriales
        require_once BASE_PATH . '/Views/admin/reporte_impresion.php';
        exit;
    }
}
