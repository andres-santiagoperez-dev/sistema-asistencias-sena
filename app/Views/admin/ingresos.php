<?php
/**
 * @var array  $ingresos
 * @var string $fecha
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$fecha = $_GET['fecha'] ?? date('Y-m-d');

$ingresos = [
    ['aprendiz' => 'Andrés Ríos',       'ficha' => '2670456 - PSW',          'entrada' => '07:16 AM', 'salida' => '01:00 PM', 'estado' => 'retardo'],
    ['aprendiz' => 'Julián Castro',     'ficha' => '2670456 - PSW',          'entrada' => '--:--',    'salida' => '--:--',    'estado' => 'inasistencia'],
    ['aprendiz' => 'Valentina Torres',  'ficha' => '2670789 - Contabilidad', 'entrada' => '06:58 AM', 'salida' => '11:30 AM', 'estado' => 'salida-temprana'],
];

$tituloPagina = 'Control de Ingresos';
require __DIR__ . '/../layouts/header.php';
?>

<div class="white-box">
    <div class="card-title-row">
        <h3>Registros de ingreso</h3>
        <!-- TODO: filtrar vía GET /admin/ingresos?fecha=&ficha= -->
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
            <select class="form-select form-select-sm" name="ficha" style="width:200px;">
                <option value="">Todas las fichas</option>
                <option>2670123 - ADSO</option>
                <option>2670456 - PSW</option>
                <option>2670789 - Contabilidad</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-funnel"></i> Filtrar</button>
        </form>
    </div>

    <p class="text-muted mb-3" style="font-size:13px;">
        <i class="bi bi-info-circle me-1"></i>
        Al finalizar la jornada, el sistema elimina automáticamente los registros de entrada/salida cumplidos correctamente; solo permanecen aquí los retardos, salidas anticipadas e inasistencias.
    </p>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                    <td colspan="5" class="text-center sena-empty-state py-4">No hay novedades de ingreso para esta fecha.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ingresos as $i): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['aprendiz']) ?>" alt="">
                            <?= htmlspecialchars($i['aprendiz']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($i['ficha']) ?></td>
                    <td><?= htmlspecialchars($i['entrada']) ?></td>
                    <td><?= htmlspecialchars($i['salida']) ?></td>
                    <td><span class="estado-badge estado-<?= $i['estado'] ?>"><?= $etiquetas[$i['estado']] ?? $i['estado'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>