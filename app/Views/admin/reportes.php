<?php
/**
 * @var array $reporte
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$reporte = [
    ['aprendiz' => 'Andrés Ríos',      'ficha' => '2670456 - PSW',          'instructor' => 'Diana Marcela Ríos',  'diasInasistencia' => 2, 'minutosRetardo' => 45],
    ['aprendiz' => 'Julián Castro',    'ficha' => '2670456 - PSW',          'instructor' => 'Diana Marcela Ríos',  'diasInasistencia' => 5, 'minutosRetardo' => 0],
    ['aprendiz' => 'Valentina Torres', 'ficha' => '2670789 - Contabilidad', 'instructor' => 'Juan Camilo Vanegas', 'diasInasistencia' => 0, 'minutosRetardo' => 20],
    ['aprendiz' => 'Laura Gómez',      'ficha' => '2670123 - ADSO',         'instructor' => 'Juan Camilo Vanegas', 'diasInasistencia' => 0, 'minutosRetardo' => 0],
];

$tituloPagina = 'Reportes';
require __DIR__ . '/../layouts/header.php';
?>

<div class="white-box mb-3">
    <div class="card-title-row">
        <h3>Filtros del reporte</h3>
    </div>
    <!-- TODO: enviar a GET /admin/reportes con los filtros -->
    <form class="row g-3" method="get">
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" name="desde">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" name="hasta">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ficha</label>
            <select class="form-select" name="ficha">
                <option value="">Todas</option>
                <option>2670123 - ADSO</option>
                <option>2670456 - PSW</option>
                <option>2670789 - Contabilidad</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Aprendiz</label>
            <input type="text" class="form-control" name="aprendiz" placeholder="Nombre o documento">
        </div>
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary btn-sm">Por día</button>
                <button type="button" class="btn btn-outline-secondary btn-sm">Por semana</button>
                <button type="button" class="btn btn-sena btn-sm">Rango personalizado</button>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sena btn-sm"><i class="bi bi-search me-1"></i>Generar reporte</button>
                <!-- TODO: exportar resultados actuales -->
                <button type="button" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
                <button type="button" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Excel</button>
            </div>
        </div>
    </form>
</div>

<div class="white-box">
    <div class="card-title-row">
        <h3>Resultados</h3>
        <span class="text-muted" style="font-size:12px;"><?= count($reporte) ?> aprendices encontrados</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha</th>
                    <th>Instructor</th>
                    <th>Días de inasistencia</th>
                    <th>Minutos de retardo</th>
                    <th class="text-end">Detalle</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($reporte)): ?>
                <tr>
                    <td colspan="6" class="text-center sena-empty-state py-4">No se encontraron resultados para los filtros seleccionados.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($reporte as $r): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($r['aprendiz']) ?>" alt="">
                            <?= htmlspecialchars($r['aprendiz']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($r['ficha']) ?></td>
                    <td><?= htmlspecialchars($r['instructor']) ?></td>
                    <td>
                        <?php if ($r['diasInasistencia'] > 0): ?>
                            <span class="estado-badge estado-inasistencia"><?= (int)$r['diasInasistencia'] ?> días</span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['minutosRetardo'] > 0): ?>
                            <span class="estado-badge estado-retardo"><?= (int)$r['minutosRetardo'] ?> min</span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Ver días</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>