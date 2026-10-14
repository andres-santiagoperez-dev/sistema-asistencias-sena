<?php
/**
 * Vista de Impresión y Planilla Oficial Diaria de Asistencia (Instructor)
 * Sistema de Control de Asistencia y Permanencia SENA
 */

defined('BASE_PATH') or exit('Acceso denegado.');

$fecha             = $fecha ?? date('Y-m-d');
$fichaSeleccionada = $fichaSeleccionada ?? '';
$ingresos          = $ingresos ?? [];
$misFichas         = $misFichas ?? [];
$instructorNombre  = $_SESSION['nombre_usuario'] ?? ($_SESSION['usuario'] ?? 'Instructor Titular');

// Buscar información detallada de la ficha seleccionada si aplica
$fichaDetalle = [
    'numero_ficha'   => !empty($fichaSeleccionada) ? $fichaSeleccionada : 'Todas las Fichas',
    'programa'       => 'Formación Profesional Integral',
    'jornada'        => 'Diurna / Mixta',
    'horario'        => '07:00 AM - 01:00 PM'
];

foreach ($misFichas as $f) {
    if (($f['numero_ficha'] ?? '') === $fichaSeleccionada || ($f['ficha_completa'] ?? '') === $fichaSeleccionada) {
        $fichaDetalle['numero_ficha'] = $f['numero_ficha'];
        $fichaDetalle['programa']     = $f['programa'];
        $fichaDetalle['horario']      = ($f['entrada'] ?? '07:00 AM') . ' - ' . ($f['salida'] ?? '01:00 PM');
        break;
    }
}

// Cálculo de métricas de la sesión
$totalRegistros = count($ingresos);
$totalPuntuales = 0;
$totalRetardos  = 0;
$totalInasist   = 0;

foreach ($ingresos as $ing) {
    $est = strtolower($ing['estado'] ?? 'a_tiempo');
    if ($est === 'retardo') {
        $totalRetardos++;
    } elseif ($est === 'inasistencia') {
        $totalInasist++;
    } else {
        $totalPuntuales++;
    }
}

$fechaFormateada = date('d/m/Y', strtotime($fecha));
$horaEmision     = date('h:i A');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla Oficial de Asistencias - Ficha <?= htmlspecialchars($fichaDetalle['numero_ficha']) ?> (<?= $fechaFormateada ?>)</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --sena-green: #39A900;
            --sena-dark: #00324D;
            --sena-forest: #0B3B24;
            --sena-border: #E2E8F0;
            --sena-text: #0F172A;
            --sena-muted: #64748B;
        }

        body {
            background-color: #F1F5F9;
            color: var(--sena-text);
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Inter', Roboto, sans-serif;
            font-size: 11.5px;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .executive-action-bar {
            background: #FFFFFF;
            border-bottom: 1px solid var(--sena-border);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 12px 24px;
        }

        .document-canvas {
            max-width: 1100px;
            margin: 24px auto;
            background: #FFFFFF;
            padding: 35px 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            border: 1px solid var(--sena-border);
        }

        .doc-header-border {
            border-bottom: 3px solid var(--sena-green);
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .sena-logo-svg {
            width: 64px;
            height: 64px;
        }

        .doc-title-main {
            color: var(--sena-forest);
            font-weight: 800;
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-subtitle {
            color: var(--sena-dark);
            font-weight: 700;
            font-size: 13.5px;
            margin: 2px 0 0 0;
        }

        .control-matrix {
            border: 1px solid var(--sena-border);
            border-radius: 6px;
            background: #F8FAFC;
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

        .kpi-card {
            background: #FFFFFF;
            border: 1px solid var(--sena-border);
            border-radius: 6px;
            padding: 8px 12px;
            text-align: center;
            border-top: 3px solid var(--sena-dark);
        }
        .kpi-card.kpi-success { border-top-color: var(--sena-green); }
        .kpi-card.kpi-warning { border-top-color: #D97706; }
        .kpi-card.kpi-risk { border-top-color: #DC2626; }
        .kpi-label {
            font-size: 9px;
            font-weight: 700;
            color: var(--sena-muted);
            text-transform: uppercase;
        }
        .kpi-value {
            font-size: 18px;
            font-weight: 800;
            color: var(--sena-dark);
            line-height: 1.1;
        }

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
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px;
            border-bottom: 1px solid #1E293B;
            vertical-align: middle;
        }

        .table-executive tbody tr {
            page-break-inside: avoid;
        }

        .table-executive tbody tr:nth-child(even) {
            background-color: #F8FAFC;
        }

        .table-executive tbody td {
            padding: 6.5px 8px;
            border-bottom: 1px solid var(--sena-border);
            vertical-align: middle;
        }

        .status-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }
        .status-pill.pill-ok { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
        .status-pill.pill-warn { background: #FEF3C7; color: #92400E; border: 1px solid #FCD34D; }
        .status-pill.pill-risk { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }

        .signature-box {
            text-align: center;
            padding: 0 20px;
        }

        .signature-line {
            width: 75%;
            margin: 40px auto 6px auto;
            border-top: 1.5px solid #1E293B;
        }

        @media print {
            @page {
                size: portrait;
                margin: 10mm 12mm 12mm 12mm;
            }
            body { background: #FFFFFF !important; font-size: 10px !important; }
            .executive-action-bar { display: none !important; }
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
            .kpi-card, .status-pill {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .table-executive tbody tr:nth-child(even) {
                background-color: #F8FAFC !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <div class="executive-action-bar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1.5 fw-bold">
                <i class="bi bi-file-earmark-check-fill me-1"></i> Planilla Oficial Diaria
            </span>
            <span class="text-secondary" style="font-size: 12px;">Planilla de clase para firma y archivo físico o digital.</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-success fw-bold px-3 d-inline-flex align-items-center gap-1.5" onclick="window.print()" style="background-color: var(--sena-green); border: none;">
                <i class="bi bi-printer-fill fs-6"></i> Imprimir Planilla
            </button>
            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&action=excel&fecha=<?= urlencode($fecha) ?>&ficha=<?= urlencode($fichaSeleccionada) ?>" class="btn btn-outline-success fw-semibold px-3 d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-file-earmark-excel-fill fs-6"></i> Descargar Excel
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&fecha=<?= urlencode($fecha) ?>&ficha=<?= urlencode($fichaSeleccionada) ?>" class="btn btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Volver a Ingresos
            </a>
        </div>
    </div>

    <div class="document-canvas">
        <!-- ENCABEZADO -->
        <div class="doc-header-border d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
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
                    <span class="text-secondary d-block" style="font-size: 10px; font-weight: 600;">SERVICIO NACIONAL DE APRENDIZAJE - SENA</span>
                    <h1 class="doc-title-main">Planilla de Control de Asistencias</h1>
                    <h2 class="doc-subtitle">Registro Diario de Formación Presencial / Laboratorio</h2>
                </div>
            </div>
            <div class="control-matrix" style="width: 250px;">
                <div class="control-matrix-item">
                    <span>Fecha Sesión:</span>
                    <strong><?= $fechaFormateada ?></strong>
                </div>
                <div class="control-matrix-item">
                    <span>Hora Emisión:</span>
                    <strong><?= $horaEmision ?></strong>
                </div>
                <div class="control-matrix-item">
                    <span>Formato:</span>
                    <strong>SENA-REG-ASI-02</strong>
                </div>
            </div>
        </div>

        <!-- METADATOS DE LA FORMACIÓN -->
        <div class="p-2.5 px-3 rounded mb-3" style="background: #F8FAFC; border: 1px solid var(--sena-border); font-size: 11.5px;">
            <div class="row g-2">
                <div class="col-md-3">
                    <span class="text-secondary fw-semibold">Ficha Formativa:</span>
                    <strong class="text-dark d-block"><?= htmlspecialchars($fichaDetalle['numero_ficha']) ?></strong>
                </div>
                <div class="col-md-5">
                    <span class="text-secondary fw-semibold">Programa de Formación:</span>
                    <strong class="text-dark d-block"><?= htmlspecialchars($fichaDetalle['programa']) ?></strong>
                </div>
                <div class="col-md-4">
                    <span class="text-secondary fw-semibold">Instructor Titular:</span>
                    <strong class="text-dark d-block"><?= htmlspecialchars($instructorNombre) ?></strong>
                </div>
            </div>
        </div>

        <!-- RESUMEN DE LA SESIÓN -->
        <div class="row g-2 mb-3">
            <div class="col">
                <div class="kpi-card">
                    <div class="kpi-label">Marcaciones Registradas</div>
                    <div class="kpi-value"><?= $totalRegistros ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-success">
                    <div class="kpi-label">A Tiempo</div>
                    <div class="kpi-value text-success"><?= $totalPuntuales ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-warning">
                    <div class="kpi-label">Retardos</div>
                    <div class="kpi-value" style="color: #D97706;"><?= $totalRetardos ?></div>
                </div>
            </div>
            <div class="col">
                <div class="kpi-card kpi-risk">
                    <div class="kpi-label">Inasistencias</div>
                    <div class="kpi-value text-danger"><?= $totalInasist ?></div>
                </div>
            </div>
        </div>

        <!-- TABLA DE INGRESOS -->
        <table class="table-executive">
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">#</th>
                    <th style="width: 18%;">Documento / RFID</th>
                    <th style="width: 32%;">Aprendiz</th>
                    <th style="width: 18%;">Ficha</th>
                    <th style="width: 12%;" class="text-center">Hora Entrada</th>
                    <th style="width: 12%;" class="text-center">Hora Salida</th>
                    <th style="width: 14%;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ingresos)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted fst-italic">
                        No se han registrado marcaciones de ingreso para esta fecha y ficha.
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $i = 1;
                foreach ($ingresos as $ing): 
                    $est = strtolower($ing['estado'] ?? 'a_tiempo');
                ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $i++ ?></td>
                        <td><?= htmlspecialchars($ing['codigo_rfid'] ?? ($ing['documento'] ?? 'N/A')) ?></td>
                        <td><strong class="text-dark"><?= htmlspecialchars($ing['aprendiz'] ?? 'Desconocido') ?></strong></td>
                        <td><?= htmlspecialchars($ing['ficha'] ?? 'N/A') ?></td>
                        <td class="text-center fw-semibold"><?= !empty($ing['hora_entrada']) ? date('h:i A', strtotime($ing['hora_entrada'])) : '--:--' ?></td>
                        <td class="text-center text-muted"><?= !empty($ing['hora_salida']) ? date('h:i A', strtotime($ing['hora_salida'])) : '--:--' ?></td>
                        <td class="text-center">
                            <?php if ($est === 'retardo'): ?>
                                <span class="status-pill pill-warn"><i class="bi bi-clock-history me-1"></i>Retardo</span>
                            <?php elseif ($est === 'inasistencia'): ?>
                                <span class="status-pill pill-risk"><i class="bi bi-x-circle-fill me-1"></i>Inasistencia</span>
                            <?php else: ?>
                                <span class="status-pill pill-ok"><i class="bi bi-check-circle-fill me-1"></i>A Tiempo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- SECCIÓN DE FIRMAS -->
        <div class="row g-4 mt-4 pt-2" style="page-break-inside: avoid;">
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <strong class="text-dark d-block"><?= strtoupper(htmlspecialchars($instructorNombre)) ?></strong>
                    <span class="text-muted" style="font-size: 10px;">Instructor(a) Responsable de la Sesión</span>
                </div>
            </div>
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <strong class="text-dark d-block">REPRESENTANTE / VOCERO DE FICHA</strong>
                    <span class="text-muted" style="font-size: 10px;">Control Social y Concurrencia de Formación</span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-2 text-muted" style="font-size: 9px; border-top: 1px dashed var(--sena-border);">
            <span>Sistema Integrado de Control de Asistencias SENA • Planilla Diaria</span>
            <span>Generado el <?= date('d/m/Y h:i A') ?></span>
        </div>
    </div>

</body>
</html>
