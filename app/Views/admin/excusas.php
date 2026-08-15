<?php
/**
 * @var array $excusas
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$excusas = [
    ['aprendiz' => 'Julián Castro',    'ficha' => '2670456 - PSW',          'fechaInasistencia' => '10 ago 2026', 'fechaRadicado' => '11 ago 2026', 'soporte' => 'incapacidad.pdf', 'estado' => 'pendiente'],
    ['aprendiz' => 'Andrés Ríos',      'ficha' => '2670456 - PSW',          'fechaInasistencia' => '05 ago 2026', 'fechaRadicado' => '06 ago 2026', 'soporte' => 'cita_medica.jpg', 'estado' => 'aprobada'],
    ['aprendiz' => 'Valentina Torres', 'ficha' => '2670789 - Contabilidad', 'fechaInasistencia' => '02 ago 2026', 'fechaRadicado' => '03 ago 2026', 'soporte' => 'soporte.pdf',     'estado' => 'rechazada'],
];

$tituloPagina = 'Excusas Médicas';
require __DIR__ . '/../layouts/header.php';
?>

<div class="white-box">
    <div class="card-title-row">
        <h3>Excusas médicas radicadas</h3>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-sena">Todas</button>
            <button type="button" class="btn btn-outline-secondary">Pendientes</button>
            <button type="button" class="btn btn-outline-secondary">Aprobadas</button>
            <button type="button" class="btn btn-outline-secondary">Rechazadas</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha</th>
                    <th>Fecha de inasistencia</th>
                    <th>Radicada el</th>
                    <th>Soporte</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($excusas as $e): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="">
                            <?= htmlspecialchars($e['aprendiz']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($e['ficha']) ?></td>
                    <td><?= htmlspecialchars($e['fechaInasistencia']) ?></td>
                    <td><?= htmlspecialchars($e['fechaRadicado']) ?></td>
                    <td>
                        <!-- TODO: enlazar al archivo real almacenado -->
                        <a href="#" class="text-decoration-none"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                    </td>
                    <td><span class="estado-badge estado-<?= $e['estado'] ?>"><?= ucfirst($e['estado']) ?></span></td>
                    <td class="text-end">
                        <?php if ($e['estado'] === 'pendiente'): ?>
                            <!-- TODO: enviar PUT /admin/excusas/{id}/aprobar y /rechazar -->
                            <button type="button" class="btn btn-sm btn-outline-success" title="Aprobar"><i class="bi bi-check-lg"></i></button>
                            <button type="button" class="btn btn-sm btn-outline-danger" title="Rechazar"><i class="bi bi-x-lg"></i></button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Ver detalle"><i class="bi bi-eye"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>