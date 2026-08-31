<?php
/**
 * @var array $fichas
 * @var array $instructores
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$fichas = [
    ['codigo' => '2670123', 'programa' => 'Análisis y Desarrollo de Software', 'instructor' => 'Juan Camilo Vanegas', 'entrada' => '06:00 AM', 'salida' => '12:00 PM', 'aprendices' => 32],
    ['codigo' => '2670456', 'programa' => 'Programación de Software',          'instructor' => 'Diana Marcela Ríos',  'entrada' => '01:00 PM', 'salida' => '06:00 PM', 'aprendices' => 28],
    ['codigo' => '2670789', 'programa' => 'Contabilidad y Finanzas',           'instructor' => 'Juan Camilo Vanegas', 'entrada' => '06:00 AM', 'salida' => '12:00 PM', 'aprendices' => 25],
];

// Nombres de instructores para el <select> del modal (luego vendrá de la BD)
$instructores = ['Juan Camilo Vanegas', 'Diana Marcela Ríos'];

$tituloPagina = 'Fichas';
require __DIR__ . '/../layouts/header.php';
?>

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="sena-title mb-1">Fichas registradas</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Gestión de programas de formación y horarios</p>
        </div>
        <button type="button" class="sena-btn btn-sm" data-bs-toggle="modal" data-bs-target="#modalFicha">
            <i class="bi bi-plus-lg me-1"></i> Nueva ficha
        </button>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Código de ficha</th>
                    <th>Programa de formación</th>
                    <th>Instructor</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fichas as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($f['instructor']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <?= htmlspecialchars($f['instructor']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></td>
                    <td><span class="sena-badge-tag"><?= (int)$f['aprendices'] ?> aprendices</span></td>
                    <td class="text-end">
                        <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalFicha" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <!-- TODO: confirmar y enviar DELETE /admin/fichas/{id} -->
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear / editar ficha -->
<div class="modal fade" id="modalFicha" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--sena-card-bg); color: var(--text-main); border: 1px solid var(--border-color);">
            <!-- TODO: apuntar a POST /admin/fichas (crear) o PUT /admin/fichas/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title sena-title mb-0">Nueva ficha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Código de ficha</label>
                            <input type="text" class="form-control" name="codigo" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Programa de formación</label>
                            <input type="text" class="form-control" name="programa" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-secondary" style="font-size:13px;">Instructor a cargo</label>
                            <select class="form-select" name="instructor_id" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option value="">Selecciona un instructor...</option>
                                <?php foreach ($instructores as $ins): ?>
                                    <option><?= htmlspecialchars($ins) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Hora de entrada</label>
                            <input type="time" class="form-control" name="hora_entrada" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Hora de salida</label>
                            <input type="time" class="form-control" name="hora_salida" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-secondary" style="font-size:13px;">Jornada</label>
                            <select class="form-select" name="jornada" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option>Mañana</option>
                                <option>Tarde</option>
                                <option>Noche</option>
                                <option>Fin de semana</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="sena-btn">Guardar ficha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
