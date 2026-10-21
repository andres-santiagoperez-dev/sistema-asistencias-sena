<?php
/**
 * Formato Oficial de Impresión: Llamado de Atención Escrito por Falta de Carné/Llavero RFID
 * Sistema de Control de Asistencia y Permanencia SENA
 */

defined('BASE_PATH') or exit('Acceso denegado.');

$llamado = $llamado ?? [];
if (empty($llamado)) {
    die('Información de llamado de atención no disponible.');
}

$meses = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
];

$tsFecha = strtotime($llamado['fecha_generacion'] ?? date('Y-m-d H:i:s'));
$dia = date('d', $tsFecha);
$mes = $meses[(int)date('m', $tsFecha)] ?? 'enero';
$anio = date('Y', $tsFecha);
$fechaLegible = "{$dia} de {$mes} de {$anio}";
$horaLegible = date('h:i A', $tsFecha);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Llamado de Atención - <?= htmlspecialchars($llamado['numero_folio']) ?> - SENA</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sena-green: #39a900;
            --sena-dark-green: #007934;
            --sena-soft-green: #eef7ee;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            font-size: 13px;
            margin: 0;
            padding: 20px 0;
        }

        .document-container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .header-sena {
            border-bottom: 2px solid var(--sena-green);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo-sena {
            width: 75px;
            height: auto;
        }

        .badge-infraccion {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
        }

        .badge-folio {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 13px;
            font-weight: 700;
        }

        .table-datos {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .table-datos th {
            background-color: #f8fafc;
            color: #475569;
            width: 25%;
            font-weight: 600;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .table-datos td {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            font-weight: 500;
        }

        .box-legal {
            background-color: #f8fafc;
            border-left: 4px solid var(--sena-green);
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.55;
            color: #334155;
        }

        .box-descargos {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            min-height: 80px;
            padding: 10px;
            background-color: #fafbfc;
            margin-bottom: 25px;
        }

        .linea-descargo {
            border-bottom: 1px dotted #cbd5e1;
            height: 22px;
        }

        .signatures-section {
            margin-top: 40px;
            padding-top: 20px;
        }

        .signature-box {
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            margin: 0 15px;
            font-size: 12px;
        }

        .no-print-bar {
            max-width: 800px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                font-size: 12px !important;
            }

            .document-container {
                box-shadow: none !important;
                border: none !important;
                padding: 15px !important;
                max-width: 100% !important;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Control Flotante (No Imprimible) -->
    <div class="no-print-bar">
        <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a Control de Ingresos
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-success fw-bold px-3" style="background-color: var(--sena-green); border-color: var(--sena-green);">
                <i class="bi bi-printer-fill me-1"></i> Imprimir Formato / Guardar PDF
            </button>
        </div>
    </div>

    <!-- Contenedor Oficial del Documento -->
    <div class="document-container">
        
        <!-- Cabecera Institucional -->
        <div class="header-sena d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= BASE_URL ?>/public/assets/img/logo/logo_sena_verde.svg" alt="Logo SENA" class="logo-sena" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/8/83/Sena_Colombia_logo.svg'">
                <div>
                    <h5 class="fw-bold mb-0 text-uppercase" style="color: var(--sena-dark-green); letter-spacing: 0.5px;">
                        SERVICIO NACIONAL DE APRENDIZAJE - SENA
                    </h5>
                    <div class="text-secondary" style="font-size: 12px;">
                        Centro de Comercio y Servicios · Regional Risaralda / Caldas / Nacional
                    </div>
                    <div class="fw-semibold text-dark" style="font-size: 12px;">
                        Coordinación Académica · Control de Asistencia y Permanencia
                    </div>
                </div>
            </div>
            <div class="text-end">
                <span class="badge-folio d-block mb-1"><?= htmlspecialchars($llamado['numero_folio']) ?></span>
                <span class="badge-infraccion">Infracción N° <?= (int)$llamado['numero_infraccion'] ?></span>
            </div>
        </div>

        <!-- Título del Formato -->
        <div class="text-center mb-4">
            <h5 class="fw-bold text-uppercase mb-1" style="color: #0f172a; letter-spacing: 0.5px;">
                LLAMADO DE ATENCIÓN ESCRITO POR REINCIDENCIA SIN CARNÉ / DISPOSITIVO RFID
            </h5>
            <small class="text-muted">
                Emitido el <?= $fechaLegible ?> a las <?= $horaLegible ?>
            </small>
        </div>

        <!-- Tabla de Datos del Aprendiz y Sesión -->
        <table class="table-datos">
            <tr>
                <th>Nombre del Aprendiz:</th>
                <td><strong><?= htmlspecialchars($llamado['aprendiz_nombre']) ?></strong></td>
                <th>Documento de Identidad:</th>
                <td><?= htmlspecialchars($llamado['tipo_documento'] ?? 'CC') ?> - <?= htmlspecialchars($llamado['numero_documento']) ?></td>
            </tr>
            <tr>
                <th>Programa de Formación:</th>
                <td><?= htmlspecialchars($llamado['programa']) ?></td>
                <th>Número de Ficha:</th>
                <td><strong><?= htmlspecialchars($llamado['numero_ficha']) ?></strong></td>
            </tr>
            <tr>
                <th>Instructor Solicitante:</th>
                <td><?= htmlspecialchars($llamado['instructor_nombre']) ?></td>
                <th>Área / Especialidad:</th>
                <td><?= htmlspecialchars($llamado['area'] ?? 'Comercio y Servicios / TICs') ?></td>
            </tr>
            <tr>
                <th>Fecha de Infracción:</th>
                <td><?= date('d/m/Y', strtotime($llamado['fecha_asistencia'] ?? $llamado['fecha_generacion'])) ?></td>
                <th>Hora de Entrada:</th>
                <td><?= !empty($llamado['hora_entrada']) ? date('h:i A', strtotime($llamado['hora_entrada'])) : $horaLegible ?></td>
            </tr>
        </table>

        <!-- Marco Normativo Institucional -->
        <div class="box-legal">
            <strong class="d-block mb-1 text-dark">
                <i class="bi bi-shield-exclamation text-danger me-1"></i> FUNDAMENTO REGLAMENTARIO (Acuerdo 0007 / Reglamento del Aprendiz SENA):
            </strong>
            <p class="mb-1">
                <strong>Capítulo VII (Deberes del Aprendiz) - Artículo 9, Numeral 15:</strong> 
                <em>«Portar permanentemente el carné de identificación institucional en lugar visible durante su permanencia en las instalaciones del SENA o en los ambientes de aprendizaje externos concertados, y presentarlo cada vez que le sea requerido por el personal institucional o de control de acceso.»</em>
            </p>
            <p class="mb-0">
                <strong>Capítulo IX (Medidas Formativas y Sanciones):</strong> 
                El llamado de atención escrito es una medida de carácter sancionatorio y formativo que se impone al aprendiz cuando incurre de manera reiterada en el incumplimiento de sus deberes formativos y normativos, constituyendo antecedente disciplinario en su hoja de vida académica.
            </p>
        </div>

        <!-- Descripción de los Hechos -->
        <div class="mb-3">
            <h6 class="fw-bold text-dark mb-1">1. DESCRIPCIÓN DE LOS HECHOS:</h6>
            <p class="text-secondary mb-0" style="text-align: justify; line-height: 1.6;">
                El/La aprendiz <strong><?= htmlspecialchars($llamado['aprendiz_nombre']) ?></strong> se presentó a la sesión formativa 
                en la fecha indicada sin portar su carné institucional con llavero o tarjeta RFID reglamentaria, requiriendo registro 
                manual en el sistema. Con este registro, el aprendiz <strong>ha superado el límite máximo permitido de dos (2) registros manuales</strong> 
                con el instructor <strong><?= htmlspecialchars($llamado['instructor_nombre']) ?></strong>, acumulando un total reiterativo de marcaciones manuales 
                sin justificación médica o de fuerza mayor validada.
            </p>
        </div>

        <!-- Descargos del Aprendiz -->
        <div class="mb-3">
            <h6 class="fw-bold text-dark mb-1">2. DESCARGOS O MANIFESTACIÓN DEL APRENDIZ:</h6>
            <div class="box-descargos">
                <div class="linea-descargo"></div>
                <div class="linea-descargo"></div>
                <div class="linea-descargo"></div>
            </div>
        </div>

        <!-- Compromiso Formatvo -->
        <div class="mb-4">
            <h6 class="fw-bold text-dark mb-1">3. COMPROMISO FORMATIVO:</h6>
            <p class="text-secondary mb-0" style="text-align: justify; line-height: 1.6;">
                El/La aprendiz se compromete a partir de la fecha a portar permanentemente su identificación RFID en todos los ingresos al ambiente de aprendizaje. 
                Se le advierte que la reincidencia continua en esta falta dará lugar al traslado inmediato del caso ante el 
                <strong>Comité de Evaluación y Seguimiento</strong> para la aplicación de medidas sancionatorias de mayor grado conforme al Reglamento del Aprendiz.
            </p>
        </div>

        <!-- Firmas Oficiales -->
        <div class="signatures-section row g-4 text-center">
            <div class="col-4">
                <div class="signature-box">
                    <strong class="d-block"><?= htmlspecialchars($llamado['aprendiz_nombre']) ?></strong>
                    <span class="text-muted d-block" style="font-size: 11px;">Firma del Aprendiz</span>
                    <span class="text-muted" style="font-size: 11px;">C.C. / T.I. <?= htmlspecialchars($llamado['numero_documento']) ?></span>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <strong class="d-block"><?= htmlspecialchars($llamado['instructor_nombre']) ?></strong>
                    <span class="text-muted d-block" style="font-size: 11px;">Firma del Instructor Titular</span>
                    <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($llamado['instructor_correo'] ?? 'Instructor SENA') ?></span>
                </div>
            </div>
            <div class="col-4">
                <div class="signature-box">
                    <strong class="d-block">Coordinación Académica</strong>
                    <span class="text-muted d-block" style="font-size: 11px;">Vo.Bo. Seguimiento y Control</span>
                    <span class="text-muted" style="font-size: 11px;">Centro de Comercio y Servicios</span>
                </div>
            </div>
        </div>

        <!-- Pie de Página Institucional -->
        <div class="mt-4 pt-3 border-top text-center text-secondary" style="font-size: 10px;">
            Este documento constituye notificación formal disciplinaria interna. Copia archivada en el Sistema de Control de Asistencia SENA bajo el folio <?= htmlspecialchars($llamado['numero_folio']) ?>.
        </div>

    </div>

</body>
</html>
