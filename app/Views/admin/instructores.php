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

<div class="white-box">
    <div class="card-title-row">
        <h3>Instructores registrados</h3>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" style="width:220px;" placeholder="Buscar por nombre o documento...">
            <button type="button" class="btn btn-sena btn-sm" data-bs-toggle="modal" data-bs-target="#modalInstructor">
                <i class="bi bi-plus-lg me-1"></i> Nuevo instructor
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['nombre']) ?>" alt="">
                            <?= htmlspecialchars($i['nombre']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($i['correo']) ?></td>
                    <td><span class="badge rounded-pill text-bg-light border"><?= (int)$i['fichas'] ?> fichas</span></td>
                    <td><span class="estado-badge estado-<?= $i['estado'] === 'activo' ? 'activo' : 'retirado' ?>"><?= ucfirst($i['estado']) ?></span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalInstructor" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($i['estado'] === 'activo'): ?>
                            <!-- TODO: enviar PUT /admin/instructores/{id}/estado (inactivar) -->
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Inactivar">
                                <i class="bi bi-toggle-on"></i>
                            </button>
                        <?php else: ?>
                            <!-- TODO: enviar PUT /admin/instructores/{id}/estado (activar) -->
                            <button type="button" class="btn btn-sm btn-outline-secondary" title="Activar">
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
        <div class="modal-content">
            <!-- TODO: apuntar a POST /admin/instructores (crear) o PUT /admin/instructores/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo instructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de documento</label>
                            <select class="form-select" name="tipo_documento">
                                <option>Cédula de Ciudadanía</option>
                                <option>Cédula de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de documento</label>
                            <input type="text" class="form-control" name="documento" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" name="nombres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contraseña temporal</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio en el primer ingreso">
                        </div>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size:12.5px;">
                        <i class="bi bi-info-circle me-1"></i>
                        Para asignarle fichas a este instructor, ve a <strong>Fichas y Horarios</strong> y selecciónalo al crear o editar una ficha.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena">Guardar instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>