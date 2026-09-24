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
     * Genera y descarga un archivo en formato Excel/CSV con el filtrado actual.
     * Incorpora UTF-8 BOM para garantizar correcta visualización de tildes y caracteres especiales.
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
        } catch (Throwable $e) {
            error_log("ReporteController::exportarExcel: " . $e->getMessage());
        }

        // Configuración de cabeceras HTTP para forzar la descarga de la hoja de cálculo
        $nombreArchivo = "Reporte_Asistencias_SENA_{$desde}_al_{$hasta}.csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $salida = fopen('php://output', 'w');

        // Escritura del BOM UTF-8 (permite compatibilidad nativa con Microsoft Excel en Windows)
        fprintf($salida, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeceras descriptivas del informe
        fputcsv($salida, ['SISTEMA DE GESTIÓN DE ASISTENCIAS SENA - CONSOLIDADO OFICIAL'], ';');
        fputcsv($salida, ['Rango de Fechas:', "Desde: {$desde}", "Hasta: {$hasta}"], ';');
        if (!empty($filtroArea)) {
            fputcsv($salida, ['Área Técnica:', ucfirst($filtroArea)], ';');
        }
        if (!empty($fichaSeleccionada)) {
            fputcsv($salida, ['Filtro de Ficha:', $fichaSeleccionada], ';');
        }
        fputcsv($salida, [], ';'); // Línea en blanco

        // Encabezados de columnas de la tabla
        fputcsv($salida, [
            '#',
            'Aprendiz',
            'Documento',
            'Correo Institucional',
            'Ficha y Programa',
            'Área',
            'Instructores Asignados',
            'Días de Inasistencia',
            'Retardos Registrados',
            'Minutos de Retardo',
            'Estado Disciplinario'
        ], ';');

        // Filas de datos
        $contador = 1;
        foreach ($reporte as $item) {
            $inasistencias = (int)($item['diasInasistencia'] ?? 0);
            $estadoRiesgo  = ($inasistencias >= 3) ? 'EN RIESGO (>= 3 Faltas)' : 'Normal';

            fputcsv($salida, [
                $contador++,
                $item['aprendiz'] ?? 'Desconocido',
                $item['documento'] ?? 'N/A',
                $item['correo'] ?? 'N/A',
                $item['ficha'] ?? 'Sin Ficha',
                $item['area'] ?? 'General',
                $item['instructor'] ?? 'Sin instructor asignado',
                $inasistencias,
                (int)($item['totalRetardos'] ?? 0),
                (int)($item['minutosRetardo'] ?? 0),
                $estadoRiesgo
            ], ';');
        }

        // Fila de resumen cuantitativo
        fputcsv($salida, [], ';');
        fputcsv($salida, [
            'TOTALES:',
            "Total Aprendices: {$metricas['totalAprendices']}",
            '',
            '',
            '',
            '',
            '',
            $metricas['totalInasistencias'],
            $metricas['totalRetardos'],
            $metricas['totalMinutosRetardo'],
            "Aprendices en Riesgo: {$metricas['aprendicesEnRiesgo']}"
        ], ';');

        fclose($salida);
        exit;
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

        // Renderizado del formato imprimible limpio
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Asistencias SENA (<?= htmlspecialchars($desde) ?> a <?= htmlspecialchars($hasta) ?>)</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                body { background-color: #fff; color: #212529; font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; }
                .header-sena { border-bottom: 3px solid #39a900; padding-bottom: 12px; margin-bottom: 20px; }
                .tabla-reporte th { background-color: #f8f9fa; color: #111; font-weight: 700; border-bottom: 2px solid #dee2e6; }
                .badge-riesgo { color: #d32f2f; font-weight: bold; background: #ffebee; padding: 3px 8px; border-radius: 4px; }
                .badge-ok { color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 3px 8px; border-radius: 4px; }
                @media print {
                    .no-imprimir { display: none !important; }
                    body { font-size: 11px; }
                    @page { margin: 1cm; size: landscape; }
                }
            </style>
        </head>
        <body class="p-4">
            <div class="no-imprimir d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded border">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>Vista de Impresión / Exportación PDF</h5>
                    <small class="text-muted">Utilice el botón de abajo para imprimir o guardar directamente como PDF.</small>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success fw-bold" onclick="window.print();" style="background:#39a900; border:none;">
                        <i class="bi bi-printer me-1"></i> Imprimir / Guardar como PDF
                    </button>
                    <a href="<?= BASE_URL ?>/index.php?page=reportes&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&ficha=<?= urlencode($fichaSeleccionada) ?>&aprendiz=<?= urlencode($aprendizFiltro) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Volver a Reportes
                    </a>
                </div>
            </div>

            <div class="header-sena d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #39a900;">SERVICIO NACIONAL DE APRENDIZAJE - SENA</h3>
                    <h5 class="fw-bold text-dark mb-0">Consolidado de Asistencia y Novedades Disciplinarias</h5>
                    <small class="text-secondary">Generado el <?= date('d/m/Y \a \l\a\s h:i A') ?></small>
                </div>
                <div class="text-end">
                    <span class="badge bg-dark fs-6 d-block mb-1">Periodo: <?= htmlspecialchars($desde) ?> al <?= htmlspecialchars($hasta) ?></span>
                    <?php if (!empty($filtroArea)): ?>
                        <span class="badge bg-secondary">Área: <?= ucfirst(htmlspecialchars($filtroArea)) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumen de Métricas -->
            <div class="row g-2 mb-3 text-center">
                <div class="col">
                    <div class="border p-2 rounded bg-light">
                        <small class="text-muted d-block">Total Aprendices</small>
                        <strong class="fs-5 text-dark"><?= $metricas['totalAprendices'] ?></strong>
                    </div>
                </div>
                <div class="col">
                    <div class="border p-2 rounded bg-light">
                        <small class="text-muted d-block">Total Inasistencias</small>
                        <strong class="fs-5 text-danger"><?= $metricas['totalInasistencias'] ?></strong>
                    </div>
                </div>
                <div class="col">
                    <div class="border p-2 rounded bg-light">
                        <small class="text-muted d-block">Retardos Registrados</small>
                        <strong class="fs-5 text-warning"><?= $metricas['totalRetardos'] ?></strong>
                    </div>
                </div>
                <div class="col">
                    <div class="border p-2 rounded bg-light">
                        <small class="text-muted d-block">Minutos de Retardo</small>
                        <strong class="fs-5 text-primary"><?= $metricas['totalMinutosRetardo'] ?> min</strong>
                    </div>
                </div>
                <div class="col">
                    <div class="border p-2 rounded bg-light">
                        <small class="text-muted d-block">Aprendices en Riesgo</small>
                        <strong class="fs-5 text-danger"><?= $metricas['aprendicesEnRiesgo'] ?></strong>
                    </div>
                </div>
            </div>

            <!-- Tabla de Datos -->
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Aprendiz</th>
                            <th>Documento</th>
                            <th>Ficha y Programa</th>
                            <th>Instructores</th>
                            <th class="text-center">Inasistencias</th>
                            <th class="text-center">Retardos</th>
                            <th class="text-center">Min. Retardo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($reporte)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No se encontraron registros de asistencia para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $contador = 1;
                        foreach ($reporte as $item): 
                            $inasistencias = (int)($item['diasInasistencia'] ?? 0);
                            $esRiesgo = ($inasistencias >= 3);
                        ?>
                            <tr>
                                <td class="text-center"><?= $contador++ ?></td>
                                <td><strong><?= htmlspecialchars($item['aprendiz']) ?></strong></td>
                                <td><?= htmlspecialchars($item['documento']) ?></td>
                                <td><?= htmlspecialchars($item['ficha']) ?></td>
                                <td><small class="text-secondary"><?= htmlspecialchars($item['instructor']) ?></small></td>
                                <td class="text-center fw-bold <?= $inasistencias > 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $inasistencias ?>
                                </td>
                                <td class="text-center"><?= (int)($item['totalRetardos'] ?? 0) ?></td>
                                <td class="text-center"><?= (int)($item['minutosRetardo'] ?? 0) ?> min</td>
                                <td class="text-center">
                                    <?php if ($esRiesgo): ?>
                                        <span class="badge-riesgo">En Riesgo</span>
                                    <?php else: ?>
                                        <span class="badge-ok">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 pt-3 border-top text-muted small d-flex justify-content-between">
                <span>Centro de Formación SENA - Sistema de Control de Asistencias</span>
                <span>Página 1 de 1</span>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}
