<?php
/**
 * Vista: Centro de Reportes y Auditoría (admin/reportes.php)
 * 
 * Responsabilidad: Solo presentación visual desacoplada de la base de datos.
 * No realiza conexiones directas PDO ni consultas SQL manuales.
 * 
 * Recibe desde ReporteController::index():
 * - $reporte: array con las filas de aprendices, inasistencias y retardos
 * - $fichas: array de fichas formativas disponibles para el filtro
 * - $metricas: array con 'totalInasistencias', 'totalMinutosRetardo', 'totalRetardos', 'aprendicesEnRiesgo', 'totalAprendices'
 * - $desde, $hasta: fechas inicial y final formateadas Y-m-d
 * - $fichaSeleccionada: string de la ficha filtrada o vacío
 * - $aprendizFiltro: string con término de búsqueda
 * - $adminArea: string ('todas', 'comercio', 'Tics')
 * - $mensaje, $error: alertas opcionales
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=reportes');
    exit;
}

$tituloPagina = 'Reportes Consolidados';
require_once BASE_PATH . '/Views/layouts/header.php';

// Valores por defecto seguros para prevenir advertencias
$totalAprendices     = (int)($metricas['totalAprendices'] ?? 0);
$totalInasistencias  = (int)($metricas['totalInasistencias'] ?? 0);
$totalMinutosRetardo = (int)($metricas['totalMinutosRetardo'] ?? 0);
$totalRetardos       = (int)($metricas['totalRetardos'] ?? 0);
$aprendicesEnRiesgo  = (int)($metricas['aprendicesEnRiesgo'] ?? 0);

$porcentajeCumplimiento = ($totalAprendices > 0)
    ? round((($totalAprendices - $aprendicesEnRiesgo) / $totalAprendices) * 100)
    : 100;
?>

<!-- Alertas Flash -->
<?php if (!empty($mensaje)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-file-earmark-bar-graph-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">
                    Centro de Reportes y Auditoría
                    <?php if (!empty($adminArea) && $adminArea !== 'todas'): ?>
                        <span class="badge bg-sena-green text-white ms-2 fs-6">Área: <?= ucfirst(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Consolidado de inasistencias, tiempos de retardo y desempeño de asistencia por rango de fechas.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-outline text-danger border-danger" onclick="exportarReporte('pdf')" title="Imprimir o guardar reporte en PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
            </button>
            <button type="button" class="btn btn-sena-outline text-success border-success" onclick="exportarReporte('excel')" title="Descargar archivo compatible con Excel">
                <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
            </button>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card-stat-ux h-100" style="border-left: 4px solid #24caa1;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary fw-semibold" style="font-size:13px;">APRENDICES EVALUADOS</span>
                    <h2 class="text-theme-primary fw-bold my-1"><?= $totalAprendices ?></h2>
                    <span class="text-secondary" style="font-size:12px;">En los criterios actuales</span>
                </div>
                <div class="p-2 rounded" style="background: rgba(36, 202, 161, 0.1); color: #24caa1;">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-stat-ux h-100" style="border-left: 4px solid #e74c3c;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary fw-semibold" style="font-size:13px;">DÍAS DE INASISTENCIA</span>
                    <h2 class="text-theme-primary fw-bold my-1"><?= $totalInasistencias ?></h2>
                    <span class="text-danger fw-semibold" style="font-size:12px;"><?= $aprendicesEnRiesgo ?> aprendices en riesgo</span>
                </div>
                <div class="p-2 rounded" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                    <i class="bi bi-x-circle-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-stat-ux h-100" style="border-left: 4px solid #f39c12;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary fw-semibold" style="font-size:13px;">MINUTOS DE RETARDO</span>
                    <h2 class="text-theme-primary fw-bold my-1"><?= $totalMinutosRetardo ?> <span style="font-size:14px; font-weight:normal;">min</span></h2>
                    <span class="text-secondary" style="font-size:12px;">(<?= $totalRetardos ?> retardos registrados)</span>
                </div>
                <div class="p-2 rounded" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                    <i class="bi bi-alarm-fill fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card-stat-ux h-100" style="border-left: 4px solid #39A900;">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="text-secondary fw-semibold" style="font-size:13px;">ESTADO DISCIPLINARIO</span>
                    <h2 class="text-theme-primary fw-bold my-1"><?= $porcentajeCumplimiento ?>%</h2>
                    <span class="text-success fw-semibold" style="font-size:12px;">Cumplimiento regular</span>
                </div>
                <div class="p-2 rounded" style="background: rgba(57, 169, 0, 0.1); color: #39A900;">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros del Reporte -->
<div class="card-stat-ux mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--nk-border-color) !important;">
        <h5 class="text-theme-primary fw-bold mb-0"><i class="bi bi-funnel-fill text-sena me-2"></i>Parámetros de Filtrado</h5>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-sena-outline" onclick="aplicarRango('hoy')">Hoy</button>
            <button type="button" class="btn btn-sena-outline" onclick="aplicarRango('semana')">Esta Semana</button>
            <button type="button" class="btn btn-sena-outline active" onclick="aplicarRango('mes')">Este Mes</button>
        </div>
    </div>

    <form class="row g-3 align-items-end" method="GET" action="<?= BASE_URL ?>/index.php" id="formReportes">
        <input type="hidden" name="page" value="reportes">

        <div class="col-md-3 col-sm-6">
            <label class="form-label text-secondary fw-semibold" style="font-size:13px;">Fecha Desde</label>
            <input type="date" class="form-control" name="desde" id="inputDesde" value="<?= htmlspecialchars($desde) ?>"
                   style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-secondary fw-semibold" style="font-size:13px;">Fecha Hasta</label>
            <input type="date" class="form-control" name="hasta" id="inputHasta" value="<?= htmlspecialchars($hasta) ?>"
                   style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-secondary fw-semibold" style="font-size:13px;">Ficha de Formación</label>
            <select class="form-select" name="ficha" id="selectFicha"
                    style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichas as $f): 
                    $valFicha = is_array($f) ? ($f['numero_ficha'] ?? $f['etiqueta'] ?? '') : $f;
                    $txtFicha = is_array($f) ? ($f['etiqueta'] ?? $f['numero_ficha'] ?? '') : $f;
                    $selected = ($fichaSeleccionada === (string)$valFicha || $fichaSeleccionada === (string)$txtFicha) ? 'selected' : '';
                ?>
                    <option value="<?= htmlspecialchars($valFicha) ?>" <?= $selected ?>>
                        <?= htmlspecialchars($txtFicha) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 col-sm-6">
            <label class="form-label text-secondary fw-semibold" style="font-size:13px;">Aprendiz o Documento</label>
            <div class="input-group">
                <input type="text" class="form-control" name="aprendiz" id="inputAprendiz" placeholder="Buscar..." value="<?= htmlspecialchars($aprendizFiltro) ?>"
                       style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <button type="submit" class="btn btn-sena-green" title="Aplicar filtros"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de Resultados -->
<div class="card-stat-ux">
    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2" style="border-color: var(--nk-border-color) !important;">
        <div>
            <h5 class="text-theme-primary fw-bold mb-0">Resultados Consolidados</h5>
            <span class="text-secondary" style="font-size:13px;">
                Periodo: <strong><?= htmlspecialchars($desde) ?></strong> al <strong><?= htmlspecialchars($hasta) ?></strong>
            </span>
        </div>
        <span class="badge-sena badge-sena-info" style="font-size:13px;">
            <?= count($reporte) ?> registros encontrados
        </span>
    </div>

    <div class="table-responsive">
        <table class="table-nalika-dark align-middle mb-0 w-100">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha Asignada</th>
                    <th>Instructores a Cargo</th>
                    <th class="text-center">Días Inasistencia</th>
                    <th class="text-center">Retardos</th>
                    <th class="text-center">Minutos Retardo</th>
                    <th class="text-center">Estado Alerta</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reporte)): ?>
                <tr>
                    <td colspan="8" class="text-center text-secondary py-5">
                        <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-muted"></i>
                        No se encontraron registros de aprendices con los filtros especificados en el periodo actual.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($reporte as $r): 
                    $inasistencias = (int)($r['diasInasistencia'] ?? 0);
                    $retardos      = (int)($r['totalRetardos'] ?? 0);
                    $minutos       = (int)($r['minutosRetardo'] ?? 0);

                    $alertaClass = 'badge-sena-success';
                    $alertaTexto = 'Normal';
                    if ($inasistencias >= 3) {
                        $alertaClass = 'badge-sena-danger';
                        $alertaTexto = 'Crítico (Comité)';
                    } elseif ($inasistencias > 0 || $minutos >= 30) {
                        $alertaClass = 'badge-sena-warning';
                        $alertaTexto = 'Seguimiento';
                    }

                    $aprendizJson = htmlspecialchars(json_encode([
                        'aprendiz'      => $r['aprendiz'] ?? 'Desconocido',
                        'documento'     => $r['documento'] ?? 'N/A',
                        'correo'        => $r['correo'] ?? 'N/A',
                        'ficha'         => $r['ficha'] ?? 'Sin Ficha',
                        'area'          => $r['area'] ?? 'General',
                        'instructor'    => $r['instructor'] ?? 'Sin instructor asignado',
                        'inasistencias' => $inasistencias,
                        'retardos'      => $retardos,
                        'minutos'       => $minutos
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img class="avatar-sm rounded-circle" src="https://ui-avatars.com/api/?background=24caa1&color=fff&name=<?= urlencode($r['aprendiz'] ?? 'Aprendiz') ?>" alt="" width="36" height="36">
                                <div>
                                    <div class="fw-bold text-theme-primary"><?= htmlspecialchars($r['aprendiz'] ?? 'Desconocido') ?></div>
                                    <span class="text-secondary" style="font-size:11px;">Doc: <?= htmlspecialchars($r['documento'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold text-theme-primary" style="font-size:13px;"><?= htmlspecialchars($r['ficha'] ?? 'Sin Ficha') ?></span>
                        </td>
                        <td>
                            <span class="text-secondary" style="font-size:13px;"><?= htmlspecialchars($r['instructor'] ?? 'Sin instructor asignado') ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($inasistencias > 0): ?>
                                <span class="badge-sena badge-sena-danger fw-bold"><?= $inasistencias ?> días</span>
                            <?php else: ?>
                                <span class="badge-sena badge-sena-success">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($retardos > 0): ?>
                                <span class="badge-sena badge-sena-warning fw-bold"><?= $retardos ?></span>
                            <?php else: ?>
                                <span class="text-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($minutos > 0): ?>
                                <span class="badge-sena badge-sena-warning fw-bold"><?= $minutos ?> min</span>
                            <?php else: ?>
                                <span class="text-secondary">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge-sena <?= $alertaClass ?>"><?= $alertaTexto ?></span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-sena-outline" onclick='verDetalleAprendiz(<?= $aprendizJson ?>)'>
                                <i class="bi bi-eye me-1"></i>Detalle
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle del Aprendiz en Reporte -->
<div class="modal fade" id="modalDetalleReporte" tabindex="-1" aria-labelledby="modalDetalleReporteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-nalika shadow">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold text-theme-primary" id="modalDetalleReporteLabel">
                    <i class="bi bi-person-lines-fill text-sena me-2"></i>Historial Consolidado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="avatar-lg rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width:64px; height:64px; background: rgba(36,202,161,0.15); color: #24caa1; font-size:28px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <h5 class="fw-bold text-theme-primary mb-0" id="detNombre">-</h5>
                    <span class="text-secondary d-block" id="detDoc" style="font-size:12px;">-</span>
                    <span class="badge bg-secondary mt-1" id="detFicha">-</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-4">
                        <div class="p-2.5 rounded text-center" style="background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                            <small class="text-secondary d-block" style="font-size:11px;">Inasistencias</small>
                            <strong class="fs-5 text-danger" id="detInasistencias">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2.5 rounded text-center" style="background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                            <small class="text-secondary d-block" style="font-size:11px;">Retardos</small>
                            <strong class="fs-5 text-warning" id="detRetardos">0</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2.5 rounded text-center" style="background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                            <small class="text-secondary d-block" style="font-size:11px;">Minutos</small>
                            <strong class="fs-5 text-warning" id="detMinutos">0</strong>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded" style="background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                    <h6 class="fw-bold text-theme-primary mb-2" style="font-size:13px;"><i class="bi bi-info-circle me-1 text-sena"></i>Recomendación Coordinación</h6>
                    <p class="text-secondary mb-0" style="font-size:12px;" id="detRecomendacion">
                        No se requiere citación a comité por el momento. El aprendiz mantiene regularidad.
                    </p>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function aplicarRango(tipo) {
    const hoy = new Date();
    const inputDesde = document.getElementById('inputDesde');
    const inputHasta = document.getElementById('inputHasta');

    const formatoFecha = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (tipo === 'hoy') {
        inputDesde.value = formatoFecha(hoy);
        inputHasta.value = formatoFecha(hoy);
    } else if (tipo === 'semana') {
        const diaSemana = hoy.getDay() || 7;
        const primerDia = new Date(hoy);
        primerDia.setDate(hoy.getDate() - diaSemana + 1);
        inputDesde.value = formatoFecha(primerDia);
        inputHasta.value = formatoFecha(hoy);
    } else if (tipo === 'mes') {
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        inputDesde.value = formatoFecha(primerDiaMes);
        inputHasta.value = formatoFecha(hoy);
    }

    document.getElementById('formReportes').submit();
}

function verDetalleAprendiz(data) {
    if (!data) return;

    document.getElementById('detNombre').textContent = data.aprendiz || '-';
    document.getElementById('detDoc').textContent = data.documento ? 'Documento: ' + data.documento : '-';
    document.getElementById('detFicha').textContent = data.ficha || '-';
    document.getElementById('detInasistencias').textContent = (data.inasistencias || 0) + ' días';
    document.getElementById('detRetardos').textContent = (data.retardos || 0);
    document.getElementById('detMinutos').textContent = (data.minutos || 0) + ' min';

    const detRec = document.getElementById('detRecomendacion');
    const inasistencias = parseInt(data.inasistencias, 10) || 0;
    const minutos = parseInt(data.minutos, 10) || 0;

    if (inasistencias >= 3) {
        detRec.textContent = 'ALERTA CRÍTICA: El aprendiz supera el umbral de 3 inasistencias injustificadas en el periodo. Se debe programar citación a Comité de Evaluación y Seguimiento según el Reglamento del Aprendiz SENA.';
        detRec.className = 'text-danger fw-semibold mb-0';
    } else if (inasistencias > 0 || minutos >= 30) {
        detRec.textContent = 'ATENCIÓN DE SEGUIMIENTO: Se sugiere verificar con el instructor o con el módulo de Excusas Médicas si existen incapacidades radicadas pendientes de auditar.';
        detRec.className = 'text-warning mb-0';
    } else {
        detRec.textContent = 'REGULARIDAD ACADÉMICA: Asistencia satisfactoria sin llamados de atención ni alertas en el periodo evaluado.';
        detRec.className = 'text-success mb-0';
    }

    const modalEl = document.getElementById('modalDetalleReporte');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}

function exportarReporte(tipo) {
    const inputDesde = document.getElementById('inputDesde');
    const inputHasta = document.getElementById('inputHasta');
    const selectFicha = document.getElementById('selectFicha');
    const inputAprendiz = document.getElementById('inputAprendiz');

    const params = new URLSearchParams({
        page: 'reportes',
        action: tipo,
        desde: inputDesde ? inputDesde.value : '',
        hasta: inputHasta ? inputHasta.value : '',
        ficha: selectFicha ? selectFicha.value : '',
        aprendiz: inputAprendiz ? inputAprendiz.value : ''
    });

    const url = '<?= BASE_URL ?>/index.php?' + params.toString();

    if (tipo === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
}
</script>

<?php require_once BASE_PATH . '/Views/layouts/footer.php'; ?>