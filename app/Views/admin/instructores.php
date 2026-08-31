<?php
/**
 * @var array $instructores
 */

// Datos de ejemplo (sin conexión a base de datos todavía)
$instructores = [
    ['documento' => '10203040', 'nombre' => 'Juan Camilo Vanegas', 'correo' => 'jvanegas@sena.edu.co', 'fichas' => 3, 'estado' => 'activo'],
    ['documento' => '20304050', 'nombre' => 'Diana Marcela Ríos',  'correo' => 'dmrios@sena.edu.co',    'fichas' => 2, 'estado' => 'activo'],
    ['documento' => '30405060', 'nombre' => 'Héctor Fabio Ortiz',  'correo' => 'hfortiz@sena.edu.co',    'fichas' => 0, 'estado' => 'inactivo'],
];

$tituloPagina = 'Instructores';
require __DIR__ . '/../layouts/header.php';
?>

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="sena-title mb-1">Instructores registrados</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Gestión de personal docente</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" style="width:220px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);" placeholder="Buscar por nombre o documento...">
            <button type="button" class="sena-btn btn-sm" data-bs-toggle="modal" data-bs-target="#modalInstructor">
                <i class="bi bi-plus-lg me-1"></i> Nuevo instructor
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre completo</th>
                    <th>Correo</th>
                    <th>Fichas a cargo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($instructores as $i): ?>
                <tr>
                    <td><?= htmlspecialchars($i['documento']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['nombre']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($i['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($i['correo']) ?></td>
                    <td><span class="sena-badge-tag"><?= (int)$i['fichas'] ?> fichas</span></td>
                    <td>
                        <?php 
                        $badgeClass = $i['estado'] === 'activo' ? 'bg-success' : 'bg-secondary';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($i['estado']) ?></span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInstructor" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($i['estado'] === 'activo'): ?>
                            <!-- TODO: enviar PUT /admin/instructores/{id}/estado (inactivar) -->
                            <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" title="Inactivar">
                                <i class="bi bi-toggle-on text-warning"></i>
                            </button>
                        <?php else: ?>
                            <!-- TODO: enviar PUT /admin/instructores/{id}/estado (activar) -->
                            <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" title="Activar">
                                <i class="bi bi-toggle-off"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear / editar instructor -->
<div class="modal fade" id="modalInstructor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--sena-card-bg); color: var(--text-main); border: 1px solid var(--border-color);">
            <!-- TODO: apuntar a POST /admin/instructores (crear) o PUT /admin/instructores/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title sena-title mb-0">Nuevo instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Tipo de documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option>Cédula de Ciudadanía</option>
                                <option>Cédula de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Número de documento</label>
                            <input type="text" class="form-control" name="documento" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Nombres</label>
                            <input type="text" class="form-control" name="nombres" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary" style="font-size:13px;">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary" style="font-size:13px;">Estado</label>
                            <select class="form-select" name="estado" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary" style="font-size:13px;">Contraseña temporal</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio en el primer ingreso" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size:12.5px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Para asignarle fichas a este instructor, ve a <strong>Fichas y Horarios</strong> y selecciónalo al crear o editar una ficha.
                    </p>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="sena-btn">Guardar instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
