<?php
/**
 * Vista: Dashboard General del Administrador (admin/dashboard.php)
 * 
 * Responsabilidad: Solo presentación visual desacoplada de la base de datos.
 * No realiza consultas SQL directas ni manipula conexiones PDO.
 * 
 * Recibe desde DashboardController::admin():
 * - $stats: array con 'aprendices', 'retardosHoy', 'inasistenciasHoy', 'excusasPendientes'
 * - $graficoSemanal: array con 'categorias', 'a_tiempo', 'retardos', 'inasistencias'
 * - $graficoExcusas: array con conteos [aprobadas, pendientes, rechazadas]
 * - $ultimosIngresos: array con últimas marcaciones en sede
 * - $adminArea: string ('todas', 'comercio', 'Tics')
 * - $nombreUsuario: string
 * - $mensaje, $error
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=dashboard');
    exit;
}

$tituloPagina = 'Dashboard General';
require_once BASE_PATH . '/Views/layouts/header.php';
?>

<!-- Alertas de estado -->
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
            <div class="p-3 rounded-circle" style="background: rgba(57, 169, 0, 0.15); color: #39a900;">
                <i class="bi bi-shield-check fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">
                    Panel de Control del Administrador
                    <?php if (!empty($adminArea) && $adminArea !== 'todas'): ?>
                        <span class="badge bg-sena-green text-white ms-2 fs-6">Área: <?= ucfirst(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Supervisión global de ingresos, gestión de aprendices, instructores y novedades del sistema.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?page=aprendices" class="btn btn-sena-green">
                <i class="bi bi-person-plus me-1"></i> Gestionar Aprendices
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=reportes" class="btn btn-sena-outline">
                <i class="bi bi-file-earmark-bar-graph me-1"></i> Generar Reporte
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #24caa1 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">APRENDICES ACTIVOS</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['aprendices'] ?? 0) ?></h3>
                    <small class="text-success"><i class="bi bi-people-fill me-1"></i>Matriculados en sede</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(36, 202, 161, 0.1); color: #24caa1;">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">RETARDOS DE HOY</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['retardosHoy'] ?? 0) ?></h3>
                    <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Con retraso registrado</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #eb4b4b !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">INASISTENCIAS HOY</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['inasistenciasHoy'] ?? 0) ?></h3>
                    <small class="text-danger"><i class="bi bi-person-x-fill me-1"></i>Sin marcación de entrada</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(235, 75, 75, 0.1); color: #eb4b4b;">
                    <i class="bi bi-person-x-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #9b59b6 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">EXCUSAS PENDIENTES</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['excusasPendientes'] ?? 0) ?></h3>
                    <small style="color: #9b59b6;"><i class="bi bi-file-earmark-medical-fill me-1"></i>Por auditar</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="bi bi-file-earmark-medical-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos Centrales ApexCharts -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 card-stat-ux p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-bar-chart-line-fill me-2 text-success"></i>Tendencia de Asistencia Semanal</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Comparativa diaria de aprendices a tiempo vs. retardos vs. inasistencias</p>
                </div>
                <span class="badge bg-dark border border-secondary border-opacity-50 text-secondary px-2.5 py-1.5 font-monospace">
                    Semana en curso
                </span>
            </div>
            <div id="grafico-asistencia-semanal" style="min-height:310px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 card-stat-ux p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-pie-chart-fill me-2 text-info"></i>Balance de Excusas</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Distribución de estados médicos</p>
                </div>
            </div>
            <div id="grafico-excusas" style="min-height:310px;"></div>
        </div>
    </div>
</div>

<!-- Tabla de Últimos Registros de Ingreso en Tiempo Real -->
<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-door-open-fill me-2 text-info"></i>Últimos Registros de Ingreso en Sede</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Marcaciones y novedades recientes registradas en el sistema</p>
        </div>
        <a href="<?= BASE_URL ?>/index.php?page=reportes" class="btn btn-sm btn-sena-outline">
            <i class="bi bi-list-check me-1"></i> Ver Todos los Registros
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha y Programa</th>
                    <th>Hora de Marcación</th>
                    <th>Estado</th>
                    <th class="text-end">Detalle</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ultimosIngresos)): ?>
                <tr>
                    <td colspan="5" class="text-center text-secondary py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                        <span>No hay registros de ingreso recientes para mostrar.</span>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($ultimosIngresos as $registro): 
                    $estadoFormat = strtolower($registro['estado'] ?? '');
                    $registroJson = htmlspecialchars(json_encode($registro), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($registro['aprendiz'] ?? 'Aprendiz') ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                                <div>
                                    <strong class="text-theme-primary"><?= htmlspecialchars($registro['aprendiz'] ?? 'Desconocido') ?></strong>
                                    <?php if (!empty($registro['documento'])): ?>
                                        <small class="text-secondary d-block" style="font-size:11px;">Doc: <?= htmlspecialchars($registro['documento']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="text-theme-primary fw-medium"><?= htmlspecialchars($registro['ficha'] ?? 'Sin Ficha') ?></span></td>
                        <td>
                            <?php if (!empty($registro['hora'])): ?>
                                <span class="text-success fw-bold"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($registro['hora'])) ?></span>
                            <?php else: ?>
                                <span class="text-secondary">--:--</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($estadoFormat == 'asistio' || $estadoFormat == 'a-tiempo' || $estadoFormat == 'a tiempo'): ?>
                                <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                            <?php elseif ($estadoFormat == 'retardo'): ?>
                                <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                            <?php elseif ($estadoFormat == 'inasistencia'): ?>
                                <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> Inasistencia</span>
                            <?php else: ?>
                                <span class="badge-sena badge-pending"><?= ucfirst(htmlspecialchars($registro['estado'] ?? 'N/A')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-info" onclick='verDetalleIngreso(<?= $registroJson ?>)' title="Ver detalle de marcación">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Detalle de Ingreso -->
<div class="modal fade" id="modalDetalleIngreso" tabindex="-1" aria-labelledby="modalDetalleIngresoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 card-stat-ux shadow">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-theme-primary fw-bold" id="modalDetalleIngresoLabel">
                    <i class="bi bi-person-badge-fill me-2 text-success"></i>Detalle de Marcación
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 text-center pb-2 border-bottom border-secondary border-opacity-25">
                        <h5 class="fw-bold text-theme-primary mb-1" id="modalAprendiz">--</h5>
                        <span class="badge bg-secondary" id="modalDocumento">--</span>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Ficha y Programa</small>
                        <strong class="text-theme-primary" id="modalFicha">--</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Área</small>
                        <strong class="text-theme-primary" id="modalArea">--</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Fecha</small>
                        <strong class="text-theme-primary" id="modalFecha">--</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Estado</small>
                        <strong class="text-theme-primary" id="modalEstado">--</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Hora Entrada</small>
                        <strong class="text-success" id="modalEntrada">--:--</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-secondary d-block">Hora Salida</small>
                        <strong class="text-info" id="modalSalida">--:--</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$categoriasJson    = json_encode($graficoSemanal['categorias'] ?? ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes']);
$aTiempoJson       = json_encode($graficoSemanal['a_tiempo'] ?? [0, 0, 0, 0, 0]);
$retardosJson      = json_encode($graficoSemanal['retardos'] ?? [0, 0, 0, 0, 0]);
$inasistenciasJson = json_encode($graficoSemanal['inasistencias'] ?? [0, 0, 0, 0, 0]);
$excusasJson       = json_encode($graficoExcusas ?? [0, 0, 0]);

$scriptsExtra = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    var esModoClaro = document.documentElement.getAttribute('data-theme') === 'light';
    var themeMode = esModoClaro ? 'light' : 'dark';

    // Gráfico de Barras: Asistencia Semanal
    new ApexCharts(document.querySelector('#grafico-asistencia-semanal'), {
        chart: { 
            type: 'bar', 
            height: 310, 
            toolbar: { show: false }, 
            background: 'transparent',
            fontFamily: 'Roboto, sans-serif'
        },
        series: [
            { name: 'A tiempo', data: {$aTiempoJson} },
            { name: 'Retardos', data: {$retardosJson} },
            { name: 'Inasistencias', data: {$inasistenciasJson} }
        ],
        xaxis: { 
            categories: {$categoriasJson},
            labels: { style: { colors: '#8A9BB5' } },
            axisBorder: { color: '#253A5C' },
            axisTicks: { color: '#253A5C' }
        },
        yaxis: {
            labels: { style: { colors: '#8A9BB5' } }
        },
        grid: {
            borderColor: 'rgba(37, 58, 92, 0.4)',
            strokeDashArray: 3
        },
        colors: ['#24caa1', '#f39c12', '#eb4b4b'],
        plotOptions: { 
            bar: { 
                columnWidth: '45%', 
                borderRadius: 4 
            } 
        },
        legend: { 
            position: 'top', 
            labels: { colors: '#8A9BB5' },
            markers: { radius: 12 }
        },
        dataLabels: { enabled: false },
        theme: { mode: themeMode },
        tooltip: {
            theme: themeMode
        }
    }).render();

    // Gráfico de Donut: Excusas Médicas
    new ApexCharts(document.querySelector('#grafico-excusas'), {
        chart: { 
            type: 'donut', 
            height: 310, 
            background: 'transparent',
            fontFamily: 'Roboto, sans-serif'
        },
        series: {$excusasJson},
        labels: ['Aprobadas', 'Pendientes', 'Rechazadas'],
        colors: ['#24caa1', '#f39c12', '#eb4b4b'],
        legend: { 
            position: 'bottom', 
            labels: { colors: '#8A9BB5' } 
        },
        theme: { mode: themeMode },
        stroke: { colors: [esModoClaro ? '#ffffff' : '#1B2A47'], width: 3 },
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#8A9BB5',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    }).render();
});

function verDetalleIngreso(data) {
    if (!data) return;
    document.getElementById('modalAprendiz').innerText = data.aprendiz || 'N/A';
    document.getElementById('modalDocumento').innerText = data.documento ? 'Doc: ' + data.documento : 'Doc: N/A';
    document.getElementById('modalFicha').innerText = data.ficha || 'Sin Ficha';
    document.getElementById('modalArea').innerText = data.area ? data.area.toUpperCase() : 'N/A';
    document.getElementById('modalFecha').innerText = data.fecha || 'N/A';
    document.getElementById('modalEntrada').innerText = data.hora ? data.hora : '--:--';
    document.getElementById('modalSalida').innerText = data.hora_salida ? data.hora_salida : '--:--';
    document.getElementById('modalEstado').innerText = data.estado || 'N/A';

    var modalElement = document.getElementById('modalDetalleIngreso');
    if (modalElement && typeof bootstrap !== 'undefined') {
        var modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}
</script>
HTML;

require_once BASE_PATH . '/Views/layouts/footer.php';
