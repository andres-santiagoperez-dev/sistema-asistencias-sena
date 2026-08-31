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

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="sena-title mb-1">Excusas médicas radicadas</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Gestión de excusas y soportes médicos</p>
        </div>
        <div class="btn-group btn-group-sm">
            <button type="button" class="sena-btn sena-btn-primary">Todas</button>
            <button type="button" class="sena-btn sena-btn-outline-primary" style="border-radius:0;">Pendientes</button>
            <button type="button" class="sena-btn sena-btn-outline-primary" style="border-radius:0;">Aprobadas</button>
            <button type="button" class="sena-btn sena-btn-outline-primary" style="border-radius:0;">Rechazadas</button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
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
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($e['aprendiz']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($e['ficha']) ?></td>
                    <td><?= htmlspecialchars($e['fechaInasistencia']) ?></td>
                    <td><?= htmlspecialchars($e['fechaRadicado']) ?></td>
                    <td>
                        <!-- TODO: enlazar al archivo real almacenado -->
                        <a href="#" class="text-decoration-none text-primary"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                    </td>
                    <td>
                        <?php 
                        $badgeClass = 'bg-secondary';
                        if ($e['estado'] == 'aprobada') $badgeClass = 'bg-success';
                        else if ($e['estado'] == 'pendiente') $badgeClass = 'bg-warning text-dark';
                        else if ($e['estado'] == 'rechazada') $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($e['estado']) ?></span>
                    </td>
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
