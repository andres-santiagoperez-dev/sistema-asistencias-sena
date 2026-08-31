<?php
/**
 * @var array $excusas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Datos de ejemplo (sin conexión a base de datos todavía, filtradas para el instructor actual)
$excusasPendientes = [
    ['aprendiz' => 'Julián Castro', 'ficha' => '2670456 - PSW', 'fechaInasistencia' => '10 ago 2026', 'fechaRadicado' => '11 ago 2026', 'soporte' => 'incapacidad.pdf', 'estado' => 'pendiente'],
    ['aprendiz' => 'Mariana Lopera', 'ficha' => '2670123 - ADSO', 'fechaInasistencia' => '14 ago 2026', 'fechaRadicado' => '15 ago 2026', 'soporte' => 'formula_medica.png', 'estado' => 'pendiente'],
];

$excusasHistorial = [
    ['aprendiz' => 'Andrés Ríos', 'ficha' => '2670456 - PSW', 'fechaInasistencia' => '05 ago 2026', 'fechaRadicado' => '06 ago 2026', 'soporte' => 'cita_medica.jpg', 'estado' => 'aprobada'],
    ['aprendiz' => 'Carlos Mendoza', 'ficha' => '2670123 - ADSO', 'fechaInasistencia' => '02 ago 2026', 'fechaRadicado' => '03 ago 2026', 'soporte' => 'soporte_invalido.pdf', 'estado' => 'rechazada'],
];

$tituloPagina = 'Excusas Médicas';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<div class="row g-4">
    <!-- Excusas Pendientes -->
    <div class="col-12">
        <div class="sena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="sena-title mb-1">Excusas pendientes por aprobar</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Revisa y gestiona los soportes médicos de tus aprendices</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table sena-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha inasistencia</th>
                            <th>Radicada el</th>
                            <th>Soporte</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasPendientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center sena-empty-state py-4">No hay excusas pendientes por revisar.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasPendientes as $e): ?>
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
                                <a href="#" class="text-decoration-none text-primary"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-success mx-1" title="Aprobar Excusa"><i class="bi bi-check-lg"></i> Aprobar</button>
                                <button type="button" class="btn btn-sm btn-outline-danger mx-1" title="Rechazar Excusa"><i class="bi bi-x-lg"></i> Rechazar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historial de Excusas -->
    <div class="col-12">
        <div class="sena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="sena-title mb-1">Historial de excusas de mis fichas</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Registro de excusas aprobadas o rechazadas previamente</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table sena-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha inasistencia</th>
                            <th>Soporte</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasHistorial)): ?>
                        <tr>
                            <td colspan="6" class="text-center sena-empty-state py-4">No hay historial de excusas.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasHistorial as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <strong><?= htmlspecialchars($e['aprendiz']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($e['ficha']) ?></td>
                            <td><?= htmlspecialchars($e['fechaInasistencia']) ?></td>
                            <td>
                                <a href="#" class="text-decoration-none text-primary"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = 'bg-secondary';
                                if ($e['estado'] == 'aprobada') $badgeClass = 'bg-success';
                                else if ($e['estado'] == 'rechazada') $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($e['estado']) ?></span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Ver detalle"><i class="bi bi-eye"></i> Detalles</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
