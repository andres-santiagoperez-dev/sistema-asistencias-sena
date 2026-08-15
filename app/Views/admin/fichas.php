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

<div class="white-box">
    <div class="card-title-row">
        <h3>Fichas registradas</h3>
        <button type="button" class="btn btn-sena btn-sm" data-bs-toggle="modal" data-bs-target="#modalFicha">
            <i class="bi bi-plus-lg me-1"></i> Nueva ficha
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                    <td><?= htmlspecialchars($f['instructor']) ?></td>
                    <td><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></td>
                    <td><span class="badge rounded-pill text-bg-light border"><?= (int)$f['aprendices'] ?> aprendices</span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFicha" title="Editar">
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
        <div class="modal-content">
            <!-- TODO: apuntar a POST /admin/fichas (crear) o PUT /admin/fichas/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva ficha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Código de ficha</label>
                            <input type="text" class="form-control" name="codigo" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Programa de formación</label>
                            <input type="text" class="form-control" name="programa" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Instructor a cargo</label>
                            <select class="form-select" name="instructor_id" required>
                                <option value="">Selecciona un instructor...</option>
                                <?php foreach ($instructores as $ins): ?>
                                    <option><?= htmlspecialchars($ins) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora de entrada</label>
                            <input type="time" class="form-control" name="hora_entrada" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora de salida</label>
                            <input type="time" class="form-control" name="hora_salida" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Jornada</label>
                            <select class="form-select" name="jornada">
                                <option>Mañana</option>
                                <option>Tarde</option>
                                <option>Noche</option>
                                <option>Fin de semana</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena">Guardar ficha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>