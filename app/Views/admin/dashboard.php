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
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-teal);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Aprendices activos</p>
                    <h3><?= (int)$stats['aprendices'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-teal);"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-orange);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Retardos de hoy</p>
                    <h3><?= (int)$stats['retardosHoy'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-orange);"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-red);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Inasistencias de hoy</p>
                    <h3><?= (int)$stats['inasistenciasHoy'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-red);"><i class="bi bi-person-x-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-purple);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Excusas pendientes</p>
                    <h3><?= (int)$stats['excusasPendientes'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-purple);"><i class="bi bi-file-earmark-medical-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="white-box h-100">
            <div class="card-title-row">
                <h3>Asistencia de la semana</h3>
                <span class="text-muted" style="font-size:12px;">Aprendices a tiempo vs. retardos vs. inasistencias</span>
            </div>
            <div id="grafico-asistencia-semanal" style="min-height:300px;"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="white-box h-100">
            <div class="card-title-row">
                <h3>Excusas médicas</h3>
            </div>
            <div id="grafico-excusas" style="min-height:300px;"></div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-12">
        <div class="white-box">
            <div class="card-title-row">
                <h3>Últimos registros de ingreso</h3>
                <a href="#" class="btn btn-sm btn-outline-secondary">Ver control de ingresos</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($registro['aprendiz']) ?>" alt="">
                                    <?= htmlspecialchars($registro['aprendiz']) ?>
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
                                ?>
                                <span class="estado-badge estado-<?= $registro['estado'] ?>"><?= $etiquetas[$registro['estado']] ?? $registro['estado'] ?></span>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    // TODO: reemplazar las series de ejemplo por los datos reales del reporte semanal
    new ApexCharts(document.querySelector('#grafico-asistencia-semanal'), {
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        series: [
            { name: 'A tiempo', data: [120, 118, 125, 122, 119] },
            { name: 'Retardos', data: [8, 10, 6, 9, 7] },
            { name: 'Inasistencias', data: [4, 3, 5, 2, 6] }
        ],
        xaxis: { categories: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie'] },
        colors: ['#24caa1', '#f8ac59', '#ec4445'],
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        legend: { position: 'top' },
        dataLabels: { enabled: false }
    }).render();

    new ApexCharts(document.querySelector('#grafico-excusas'), {
        chart: { type: 'donut', height: 300 },
        series: [12, 3, 2],
        labels: ['Aprobadas', 'Pendientes', 'Rechazadas'],
        colors: ['#24caa1', '#f8ac59', '#ec4445'],
        legend: { position: 'bottom' }
    }).render();
});
</script>
HTML;
require __DIR__ . '/../layouts/footer.php';