<?php
/**
 * @var array  $ingresos
 * @var string $fecha
 * @var array  $misFichas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos de ejemplo (sin conexión a base de datos todavía)
$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Simulando las fichas del instructor actual
$misFichas = [
    '2670123 - ADSO',
    '2670456 - PSW'
];

// Simulando registros para las fichas del instructor
$ingresos = [
    ['aprendiz' => 'Andrés Ríos',       'ficha' => '2670456 - PSW',          'entrada' => '07:16 AM', 'salida' => '01:00 PM', 'estado' => 'retardo'],
    ['aprendiz' => 'Julián Castro',     'ficha' => '2670456 - PSW',          'entrada' => '--:--',    'salida' => '--:--',    'estado' => 'inasistencia'],
    ['aprendiz' => 'Laura Gómez',       'ficha' => '2670123 - ADSO',         'entrada' => '06:05 AM', 'salida' => '12:00 PM', 'estado' => 'a-tiempo'],
];

$tituloPagina = 'Control de Ingresos';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="sena-title mb-1">Registros de ingreso en tiempo real</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Monitorea las entradas, salidas y retardos de tus grupos</p>
        </div>
        <!-- TODO: filtrar vía GET /instructor/ingresos?fecha=&ficha= -->
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fecha) ?>" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
            <select class="form-select form-select-sm" name="ficha" style="width:200px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                <option value="">Todas mis fichas</option>
                <?php foreach ($misFichas as $f): ?>
                    <option><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="sena-btn sena-btn-outline-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
        </form>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--text-main);">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            En esta pantalla puedes consultar en tiempo real qué aprendices han marcado su ingreso mediante RFID.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha</th>
                    <th>Hora de entrada</th>
                    <th>Hora de salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $etiquetas = [
                'a-tiempo'         => 'A tiempo',
                'retardo'          => 'Retardo',
                'salida-temprana'  => 'Salida temprana',
                'inasistencia'     => 'Inasistencia',
            ];
            ?>
            <?php if (empty($ingresos)): ?>
                <tr>
                    <td colspan="5" class="text-center sena-empty-state py-5">
                        <i class="bi bi-door-closed fs-1 text-muted mb-2 d-block"></i>
                        No hay novedades de ingreso para esta fecha.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ingresos as $i): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($i['aprendiz']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($i['ficha']) ?></td>
                    <td><?= htmlspecialchars($i['entrada']) ?></td>
                    <td><?= htmlspecialchars($i['salida']) ?></td>
                    <td>
                        <?php 
                        $badgeClass = 'bg-secondary';
                        if ($i['estado'] == 'a-tiempo') $badgeClass = 'bg-success';
                        else if ($i['estado'] == 'retardo') $badgeClass = 'bg-warning text-dark';
                        else if ($i['estado'] == 'inasistencia' || $i['estado'] == 'salida-temprana') $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= $etiquetas[$i['estado']] ?? ucfirst($i['estado']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
