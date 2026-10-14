<?php
/**
 * Vista de Impresión y Exportación a PDF de Alta Fidelidad
 * Consolidado General de Asistencia y Auditoría Disciplinaria SENA
 * 
 * Estándar Editorial y de Diseño:
 * - Cumplimiento del Manual de Identidad Visual del SENA (Verde Institucional, Pizarra y Bosque).
 * - Tipografía corporativa legible (Inter / Segoe UI / System-ui).
 * - Grilla responsive para previsualización en pantalla y CSS Paged Media (@page) para PDF e impresión.
 * - Cabeceras de tabla repetibles (table-header-group) y protección de saltos de página (page-break-inside: avoid).
 * - Tarjetas de KPI ejecutivas, badges de estado sutiles y bloque de validación/firmas institucionales.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

$reporte           = $reporte ?? [];
$metricas          = $metricas ?? [];
$desde             = $desde ?? date('Y-m-01');
$hasta             = $hasta ?? date('Y-m-d');
$fichaSeleccionada = $fichaSeleccionada ?? '';
$aprendizFiltro    = $aprendizFiltro ?? '';
$adminArea         = $adminArea ?? 'todas';
$fechaGeneracion   = date('d/m/Y \a \l\a\s h:i A');
$usuarioEmisor     = $_SESSION['nombre_usuario'] ?? ($_SESSION['usuario'] ?? 'Administrador del Sistema');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Oficial de Asistencias SENA (<?= htmlspecialchars($desde) ?> al <?= htmlspecialchars($hasta) ?>)</title>
    
    <!-- Bootstrap 5 y Bootstrap Icons para previsualización -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sena-green: #39A900;
            --sena-dark: #00324D;
            --sena-forest: #0B3B24;
            --sena-slate: #1E293B;
            --sena-light-gray: #F8FAFC;
            --sena-border: #E2E8F0;
            --sena-text: #0F172A;
            --sena-muted: #64748B;
        }

        body {
            background-color: #F1F5F9;
            color: var(--sena-text);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', Roboto, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* BARRA SUPERIOR DE ACCIONES (SOLO PANTALLA) */
        .executive-action-bar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--sena-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 12px 24px;
        }

        /* HOJA PRINCIPAL DEL DOCUMENTO */
        .document-canvas {
            max-width: 1240px;
            margin: 24px auto;
            background: #FFFFFF;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            border: 1px solid var(--sena-border);
        }

        /* ENCABEZADO INSTITUCIONAL */
        .doc-header-border {
            border-bottom: 3px solid var(--sena-green);
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .sena-logo-svg {
            width: 72px;
            height: 72px;
        }

        .doc-title-main {
            color: var(--sena-forest);
            font-weight: 800;
            letter-spacing: -0.3px;
            font-size: 19px;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-subtitle {
            color: var(--sena-dark);
            font-weight: 700;
            font-size: 14px;
            margin: 3px 0 0 0;
        }

        .doc-meta-entity {
            font-size: 11px;
            color: var(--sena-muted);
            font-weight: 500;
        }

        /* MATRIZ DE CONTROL DOCUMENTAL */
        .control-matrix {
            border: 1px solid var(--sena-border);
            border-radius: 6px;
            background: var(--sena-light-gray);
            font-size: 11px;
            padding: 8px 12px;
        }

        .control-matrix-item {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            border-bottom: 1px dashed #CBD5E1;
        }
        .control-matrix-item:last-child {
            border-bottom: none;
        }
        .control-matrix-item strong {
            color: var(--sena-slate);
        }

        /* TARJETAS DE KPIS SUPERIORES */
        .kpi-card {
            background: #FFFFFF;
            border: 1px solid var(--sena-border);
            border-radius: 6px;
            padding: 10px 14px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            border-top: 3px solid var(--sena-dark);
        }
        .kpi-card.kpi-risk {
            border-top-color: #DC2626;
            background: #FEF2F2;
        }
        .kpi-card.kpi-success {
            border-top-color: var(--sena-green);
        }
        .kpi-card.kpi-warning {
            border-top-color: #D97706;
        }
        .kpi-label {
            font-size: 9.5px;
            font-weight: 700;
            color: var(--sena-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .kpi-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--sena-dark);
            line-height: 1.1;
        }
        .kpi-value.text-risk {
            color: #DC2626;
        }

        /* TABLA DE AUDITORÍA EJECUTIVA */
        .table-executive {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 11px;
            margin-bottom: 24px;
            border: 1px solid var(--sena-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .table-executive thead {
            display: table-header-group;
        }

        .table-executive thead th {
            background-color: var(--sena-dark);
            color: #FFFFFF;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 9px 8px;
            border-bottom: 1px solid #1E293B;
            vertical-align: middle;
            text-align: left;
        }

        .table-executive thead th.text-center {
            text-align: center;
        }

        .table-executive tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .table-executive tbody tr:nth-child(even) {
            background-color: #F8FAFC;
        }

        .table-executive tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid var(--sena-border);
            vertical-align: middle;
            color: #1E293B;
        }

        .table-executive tbody tr:hover {
            background-color: #F1F5F9;
        }

        .table-executive tfoot {
            display: table-footer-group;
        }

        .table-executive tfoot td {
            background-color: #E2E8F0;
            font-weight: 800;
            padding: 9px 8px;
            color: var(--sena-dark);
            border-top: 2px solid #94A3B8;
            border-bottom: 2px solid #94A3B8;
        }

        /* PÍLDORAS DE ESTADO */
        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .status-pill.pill-risk {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .status-pill.pill-ok {
            background-color: #DCFCE7;
            color: #166534;
            border: 1px solid #86EFAC;
        }

        /* SECCIÓN DE FIRMAS Y AVAL INSTITUCIONAL */
        .signatures-section {
            page-break-inside: avoid;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--sena-border);
        }

        .signature-box {
            text-align: center;
            padding: 0 20px;
        }

        .signature-line {
            width: 80%;
            margin: 45px auto 8px auto;
            border-top: 1.5px solid #1E293B;
        }

        .signature-name {
            font-weight: 700;
            font-size: 11px;
            color: var(--sena-dark);
            margin-bottom: 2px;
        }

        .signature-role {
            font-size: 10px;
            color: var(--sena-muted);
        }

        /* NOTA LEGAL AL PIE */
        .legal-notice {
            margin-top: 24px;
            padding: 10px 14px;
            background: #F8FAFC;
            border-left: 3px solid var(--sena-green);
            font-size: 9.5px;
            color: #475569;
            line-height: 1.5;
            border-radius: 0 4px 4px 0;
        }

        /* REGLAS ESTRICTAS DE IMPRESIÓN Y PDF */
        @media print {
            @page {
                size: landscape;
                margin: 10mm 12mm 12mm 12mm;
            }

            body {
                background: #FFFFFF !important;
                color: #000000 !important;
                font-size: 10px !important;
            }

            .executive-action-bar {
                display: none !important;
            }

            .document-canvas {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .table-executive thead th {
                background-color: #00324D !important;
                color: #FFFFFF !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .kpi-card {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .status-pill {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .table-executive tbody tr:nth-child(even) {
                background-color: #F8FAFC !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .signatures-section {
                margin-top: 30px !important;
            }

            .legal-notice {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR DE ACCIONES (PANTALLA) -->
    <div class="executive-action-bar d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-bold">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i> Formato de Impresión Oficial
            </span>
            <span class="text-secondary" style="font-size: 12px;">Vista optimizada para guardar como PDF o imprimir en papel horizontal.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-success fw-bold px-3 d-inline-flex align-items-center gap-2" onclick="window.print()" style="background-color: var(--sena-green); border: none;">
                <i class="bi bi-printer-fill fs-6"></i> Imprimir / Guardar como PDF
            </button>
            <a href="<?= BASE_URL ?>/index.php?page=reportes&action=excel&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&ficha=<?= urlencode($fichaSeleccionada) ?>&aprendiz=<?= urlencode($aprendizFiltro) ?>" class="btn btn-outline-success fw-semibold px-3 d-inline-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-excel-fill fs-6 text-success"></i> Descargar Excel (.xlsx)
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=reportes&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&ficha=<?= urlencode($fichaSeleccionada) ?>&aprendiz=<?= urlencode($aprendizFiltro) ?>" class="btn btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver a Reportes
            </a>
        </div>
    </div>

    <!-- CONTENEDOR DEL DOCUMENTO IMPRIMIBLE -->
    <div class="document-canvas">

        <!-- CABECERA INSTITUCIONAL Y MATRIZ DOCUMENTAL -->
        <div class="doc-header-border d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <!-- Logotipo Oficial Vectorial SVG del SENA -->
                <svg class="sena-logo-svg" viewBox="0 0 1000 1000" xmlns="http://www.w3.org/2000/svg">
                    <path fill="#39A900" d="M504.2,20.5c-58.3,0.1-105.6,47.4-105.5,105.8c0.1,58.3,47.4,105.6,105.7,105.6
                        c58.3,0,105.6-47.3,105.6-105.7V126C609.9,67.6,562.6,20.4,504.2,20.5z M155.6,264.6c-18.6,0.1-37.5,1.1-55.2,5.6
                        c-11.7,3-23,7.8-30.3,15.4c-9.2,9.5-10.4,22.3-5.9,33.3c4,9.7,14.8,16.9,26.8,21.1c25.9,8.9,54.6,10.7,81.8,16.3
                        c5,1.2,10.6,2.6,13.7,6c3.2,4.1,1.3,9.7-4,12.2c-8.8,4.5-20.1,4.5-30.4,4.4c-9.4-0.4-19.7-1.2-27.2-5.9c-5.5-3.4-6.5-9.1-5.2-14.1
                        l-60.6,0c-0.2,9.2,1.6,18.9,8.4,26.8c5.6,6.8,14.8,11.5,24.6,14.4c15.7,4.6,32.7,6,49.4,6.4c22.7,0.4,45.8-0.3,67.6-5.4
                        c13-3.2,25.8-8.3,34.1-16.6c14.8-14.8,11.3-38.3-8.3-49.8c-9.8-5.7-21.5-9.2-33.4-11.5c-17.5-3.6-35.3-6.3-52.9-9.2
                        c-6.2-1.2-12.8-2.3-18-5.2c-5.5-2.9-5.9-9.8-0.3-12.9c7.2-4.1,16.8-4,25.4-4c9.1,0.2,19,0.7,26.5,5c4.2,2.3,5.9,6.3,5.9,10.1
                        l57.6-0.1c-0.2-7.3-1.6-14.9-6.9-21.2c-6.2-7.8-17.1-12.7-28.3-15.5C192.8,265.6,174.1,264.7,155.6,264.6L155.6,264.6z M280.6,268.9
                        l0,137.7l168.1,0l0-30H342.3v-26.7h94.9v-29.3h-94.9l0-21.9l102.6,0l-0.1-29.7L280.6,268.9z M557.5,269c0,0-51.9,0-77.9,0l0,137.7
                        l59,0l0-92.7l80.8,92.6l81,0.1l0-137.7l-59.1,0l0.1,92L557.5,269z M805.6,269.2c0,0-63.6,91.9-95.6,137.7l61.9,0l14.9-24.8h95.7
                        l13.9,24.9l68.8,0L874,269.2L805.6,269.2z M836.6,302.1l29.4,49.9l-60.7,0.1L836.6,302.1z M10.6,445.6l0.5,75l280.1-1
                        c14.3,3.1,22.6,12.4,19.7,33.5L138.6,854.7l56.1,52.5l266.9-461.6L10.6,445.6z M545.2,446.2l262.4,459.6l58-52.1L691.3,552.9
                        c-2.9-21.2,5.4-30.6,19.7-33.7l280.2,1l-0.1-73.7L545.2,446.2z M500.9,522.3L254.8,944.7l65.4,31.9L484.4,699
                        c5.7-4.6,11.4-7.1,17.1-7.3c6-0.2,12.2,2,18.3,6.8l163.8,278.4l67.4-35.2L500.9,522.3z"/>
                </svg>
                <div>
                    <span class="doc-meta-entity d-block">REPÚBLICA DE COLOMBIA • SERVICIO NACIONAL DE APRENDIZAJE</span>
                    <h1 class="doc-title-main">Consolidado Oficial de Asistencias</h1>
                    <h2 class="doc-subtitle">Auditoría de Inasistencias, Retardos y Estado Disciplinario</h2>
                </div>
            </div>

            <!-- Matriz de Identificación Documental -->
            <div class="control-matrix" style="width: 290px;">
                <div class="control-matrix-item">
                    <span>Código Formato:</span>
                    <strong>SENA-ASI-REG-04</strong>
                </div>
                <div class="control-matrix-item">
                    <span>Versión / Estado:</span>
                    <strong>3.2 • Vigente</strong>
                </div>
                <div class="control-matrix-item">
                    <span>Emisión:</span>
                    <strong><?= $fechaGeneracion ?></strong>
                </div>
                <div class="control-matrix-item">
                    <span>Auditor en Sesión:</span>
                    <strong><?= htmlspecialchars($usuarioEmisor) ?></strong>
                </div>
            </div>
        </div>

        <!-- FILTRO Y ALCANCE DE LA AUDITORÍA -->
        <div class="row g-2 mb-3">
            <div class="col-12">
                <div class="p-2.5 px-3 rounded" style="background-color: var(--sena-light-gray); border: 1px solid var(--sena-border); font-size: 11.5px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-secondary fw-semibold">Rango Evaluado:</span>
                            <strong class="text-dark ms-1"><?= htmlspecialchars($desde) ?></strong> al <strong class="text-dark"><?= htmlspecialchars($hasta) ?></strong>
                        </div>
                        <?php if (!empty($adminArea) && $adminArea !== 'todas'): ?>
                            <div>
                                <span class="text-secondary fw-semibold">Área Técnica:</span>
                                <span class="badge bg-secondary ms-1"><?= ucfirst(htmlspecialchars($adminArea)) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($fichaSeleccionada)): ?>
                            <div>
                                <span class="text-secondary fw-semibold">Ficha:</span>
                                <strong class="text-dark ms-1"><?= htmlspecialchars($fichaSeleccionada) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($aprendizFiltro)): ?>
                            <div>
                                <span class="text-secondary fw-semibold">Filtro Aprendiz:</span>
                                <strong class="text-dark ms-1"><?= htmlspecialchars($aprendizFiltro) ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- TARJETAS DE KPIS SUPERIORES -->
        <div class="row g-2 mb-4">
            <div class="col">
                <div class="kpi-card">
                    <div class="kpi-label">Aprendices Evaluados</div>
                    <div class="kpi-value"><?= (int)($metricas['totalAprendices'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-risk">
                    <div class="kpi-label">Total Inasistencias</div>
                    <div class="kpi-value text-risk"><?= (int)($metricas['totalInasistencias'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-warning">
                    <div class="kpi-label">Retardos Registrados</div>
                    <div class="kpi-value" style="color: #D97706;"><?= (int)($metricas['totalRetardos'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card">
                    <div class="kpi-label">Minutos de Retardo</div>
                    <div class="kpi-value"><?= (int)($metricas['totalMinutosRetardo'] ?? 0) ?> <span style="font-size: 13px; font-weight: 600;">min</span></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-risk">
                    <div class="kpi-label">En Riesgo (≥ 3 Faltas)</div>
                    <div class="kpi-value text-risk"><?= (int)($metricas['aprendicesEnRiesgo'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- TABLA DE RESULTADOS DE AUDITORÍA -->
        <table class="table-executive">
            <thead>
                <tr>
                    <th style="width: 32px;" class="text-center">#</th>
                    <th style="width: 22%;">Aprendiz</th>
                    <th style="width: 12%;">Documento</th>
                    <th style="width: 22%;">Ficha y Programa Formativo</th>
                    <th style="width: 18%;">Instructores Asignados</th>
                    <th style="width: 8%;" class="text-center">Inasistencias</th>
                    <th style="width: 8%;" class="text-center">Retardos</th>
                    <th style="width: 10%;" class="text-center">Min. Retardo</th>
                    <th style="width: 11%;" class="text-center">Estado Disciplinario</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reporte)): ?>
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted fst-italic">
                        No se registraron novedades ni registros de asistencia para los criterios y fechas especificadas.
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $contador = 1;
                foreach ($reporte as $item): 
                    $inasistencias = (int)($item['diasInasistencia'] ?? 0);
                    $retardos      = (int)($item['totalRetardos'] ?? 0);
                    $minutos       = (int)($item['minutosRetardo'] ?? 0);
                    $esRiesgo      = ($inasistencias >= 3);
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?= $contador++ ?></td>
                        <td>
                            <strong class="text-dark d-block"><?= htmlspecialchars($item['aprendiz'] ?? 'Desconocido') ?></strong>
                            <small class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($item['correo'] ?? '') ?></small>
                        </td>
                        <td><?= htmlspecialchars($item['documento'] ?? 'N/A') ?></td>
                        <td>
                            <strong class="d-block" style="font-size: 11px; color: var(--sena-dark);"><?= htmlspecialchars($item['ficha'] ?? 'Sin Ficha') ?></strong>
                            <small class="text-muted" style="font-size: 10px;">Área: <?= ucfirst(htmlspecialchars($item['area'] ?? 'General')) ?></small>
                        </td>
                        <td><small class="text-secondary"><?= htmlspecialchars($item['instructor'] ?? 'Sin instructor asignado') ?></small></td>
                        <td class="text-center fw-bold <?= $inasistencias > 0 ? 'text-danger' : 'text-success' ?>" style="font-size: 12px;">
                            <?= $inasistencias ?>
                        </td>
                        <td class="text-center"><?= $retardos ?></td>
                        <td class="text-center"><?= $minutos ?> min</td>
                        <td class="text-center">
                            <?php if ($esRiesgo): ?>
                                <span class="status-pill pill-risk"><i class="bi bi-exclamation-triangle-fill me-1"></i>En Riesgo</span>
                            <?php else: ?>
                                <span class="status-pill pill-ok"><i class="bi bi-check-circle-fill me-1"></i>Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($reporte)): ?>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end">TOTALES CONSOLIDADOS DEL PERIODO:</td>
                    <td class="text-center fw-bold text-danger" style="font-size: 12px;"><?= (int)($metricas['totalInasistencias'] ?? 0) ?></td>
                    <td class="text-center fw-bold"><?= (int)($metricas['totalRetardos'] ?? 0) ?></td>
                    <td class="text-center fw-bold"><?= (int)($metricas['totalMinutosRetardo'] ?? 0) ?> min</td>
                    <td class="text-center">
                        <span class="status-pill pill-risk">En Riesgo: <?= (int)($metricas['aprendicesEnRiesgo'] ?? 0) ?></span>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <!-- SECCIÓN DE FIRMAS Y AVAL INSTITUCIONAL -->
        <div class="signatures-section row g-4">
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">COORDINACIÓN ACADÉMICA / MISIONAL</div>
                    <div class="signature-role">Centro de Formación Profesional Integral • SENA</div>
                </div>
            </div>
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name"><?= strtoupper(htmlspecialchars($usuarioEmisor)) ?></div>
                    <div class="signature-role">Auditor y Responsable del Control de Asistencia</div>
                </div>
            </div>
        </div>

        <!-- NOTA LEGAL DE VALIDEZ NORMATIVA -->
        <div class="legal-notice">
            <strong>Fundamento Normativo:</strong> El presente documento es expedido formalmente por el Sistema Integrado de Control de Asistencias del SENA. Goza de validez probatoria ante comités de evaluación y seguimiento académico en estricto cumplimiento del <em>Reglamento del Aprendiz SENA (Acuerdo 007 de 2012, Artículos 22 al 28)</em> sobre causales de deserción y debida justificación de inasistencias médicas o de fuerza mayor.
        </div>

        <!-- PIE DE PÁGINA DEL SISTEMA -->
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-muted" style="font-size: 9.5px; border-top: 1px dashed var(--sena-border);">
            <span>Sistema Integrado de Control de Asistencias • Regional SENA</span>
            <span>Generado automáticamente el <?= $fechaGeneracion ?> • Documento Oficial</span>
        </div>

    </div>

</body>
</html>
