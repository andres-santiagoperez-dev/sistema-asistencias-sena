<?php
/**
 * @var array $stats
 * @var array $ultimosIngresos
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$stats = [
    'aprendices'        => 128,
    'retardosHoy'       => 7,
    'inasistenciasHoy'  => 4,
    'excusasPendientes' => 3,
];

$ultimosIngresos = [
    ['aprendiz' => 'Carlos Mendoza',    'ficha' => '2670123 - ADSO',        'hora' => '06:58 AM', 'estado' => 'a-tiempo'],
    ['aprendiz' => 'Laura Gómez',       'ficha' => '2670123 - ADSO',        'hora' => '07:02 AM', 'estado' => 'a-tiempo'],
    ['aprendiz' => 'Andrés Ríos',       'ficha' => '2670456 - PSW',         'hora' => '07:16 AM', 'estado' => 'retardo'],
    ['aprendiz' => 'Valentina Torres',  'ficha' => '2670789 - Contabilidad','hora' => '07:00 AM', 'estado' => 'a-tiempo'],
    ['aprendiz' => 'Julián Castro',     'ficha' => '2670456 - PSW',         'hora' => '--:--',    'estado' => 'inasistencia'],
];

$tituloPagina = 'Dashboard';
require __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(57,169,0,0.1); color:#39A900;">
                <i class="bi bi-people-fill"></i>
            </div>
            <h4 class="sena-kpi-value"><?= (int)$stats['aprendices'] ?></h4>
            <p class="sena-kpi-label">Aprendices activos</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(255,165,0,0.1); color:#FFA500;">
                <i class="bi bi-clock-history"></i>
            </div>
            <h4 class="sena-kpi-value text-warning"><?= (int)$stats['retardosHoy'] ?></h4>
            <p class="sena-kpi-label">Retardos de hoy</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(220,53,69,0.1); color:#dc3545;">
                <i class="bi bi-person-x-fill"></i>
            </div>
            <h4 class="sena-kpi-value text-danger"><?= (int)$stats['inasistenciasHoy'] ?></h4>
            <p class="sena-kpi-label">Inasistencias de hoy</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(123,94,234,0.1); color:#7b5eea;">
                <i class="bi bi-file-earmark-medical-fill"></i>
            </div>
            <h4 class="sena-kpi-value" style="color:#7b5eea;"><?= (int)$stats['excusasPendientes'] ?></h4>
            <p class="sena-kpi-label">Excusas pendientes</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="sena-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="sena-title mb-1">Asistencia de la semana</h5>
                    <span class="text-secondary" style="font-size:13px;">Aprendices a tiempo vs. retardos vs. inasistencias</span>
                </div>
            </div>
            <div id="grafico-asistencia-semanal" style="min-height:300px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="sena-card p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="sena-title mb-1">Excusas médicas</h5>
            </div>
            <div id="grafico-excusas" style="min-height:300px;"></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-12">
        <div class="sena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="sena-title mb-1">Últimos registros de ingreso</h5>
                <a href="#" class="sena-btn sena-btn-outline-primary btn-sm">Ver control de ingresos</a>
            </div>
            <div class="table-responsive">
                <table class="table sena-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Hora de entrada</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ultimosIngresos as $registro): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($registro['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <strong><?= htmlspecialchars($registro['aprendiz']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($registro['ficha']) ?></td>
                            <td><?= htmlspecialchars($registro['hora']) ?></td>
                            <td>
                                <?php
                                $etiquetas = [
                                    'a-tiempo'     => 'A tiempo',
                                    'retardo'      => 'Retardo',
                                    'inasistencia' => 'Inasistencia',
                                ];
                                $badgeClass = 'bg-secondary';
                                if ($registro['estado'] == 'a-tiempo') $badgeClass = 'bg-success';
                                else if ($registro['estado'] == 'retardo') $badgeClass = 'bg-warning text-dark';
                                else if ($registro['estado'] == 'inasistencia') $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= $etiquetas[$registro['estado']] ?? $registro['estado'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$scriptsExtra = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    // TODO: reemplazar las series de ejemplo por los datos reales del reporte semanal
    new ApexCharts(document.querySelector('#grafico-asistencia-semanal'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false }, background: 'transparent' },
        series: [
            { name: 'A tiempo', data: [120, 118, 125, 122, 119] },
            { name: 'Retardos', data: [8, 10, 6, 9, 7] },
            { name: 'Inasistencias', data: [4, 3, 5, 2, 6] }
        ],
        xaxis: { categories: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'] },
        colors: ['#39A900', '#FFA500', '#dc3545'],
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        legend: { position: 'top', labels: { colors: 'var(--text-main)' } },
        dataLabels: { enabled: false },
        theme: { mode: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark' }
    }).render();

    new ApexCharts(document.querySelector('#grafico-excusas'), {
        chart: { type: 'donut', height: 300, background: 'transparent' },
        series: [12, 3, 2],
        labels: ['Aprobadas', 'Pendientes', 'Rechazadas'],
        colors: ['#39A900', '#FFA500', '#dc3545'],
        legend: { position: 'bottom', labels: { colors: 'var(--text-main)' } },
        theme: { mode: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark' },
        stroke: { colors: ['transparent'] }
    }).render();
});
</script>
HTML;
require __DIR__ . '/../layouts/footer.php';
