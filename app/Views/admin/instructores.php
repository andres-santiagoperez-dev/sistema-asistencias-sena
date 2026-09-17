<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

$instructores = [];

try {
    $stmt = $pdo->query("
        SELECT 
            i.id_instructor,
            u.numero_documento as documento,
            CONCAT(u.nombres, ' ', u.apellidos) as nombre,
            u.correo,
            u.estado,
            COUNT(f.id_ficha) as fichas
        FROM instructores i
        JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
        LEFT JOIN fichas f ON f.Instructores_id_instructor1 = i.id_instructor
        GROUP BY i.id_instructor, u.numero_documento, u.nombres, u.apellidos, u.correo, u.estado
        ORDER BY u.apellidos, u.nombres
    ");
    $instructores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback
}

if (empty($instructores)) {
    $instructores = [
        ['id_instructor' => 1, 'documento' => '10203040', 'nombre' => 'Juan Camilo Vanegas', 'correo' => 'jvanegas@sena.edu.co', 'fichas' => 3, 'estado' => 'Activo'],
        ['id_instructor' => 2, 'documento' => '20304050', 'nombre' => 'Diana Marcela Ríos',  'correo' => 'dmrios@sena.edu.co',    'fichas' => 2, 'estado' => 'Activo'],
        ['id_instructor' => 3, 'documento' => '30405060', 'nombre' => 'Héctor Fabio Ortiz',  'correo' => 'hfortiz@sena.edu.co',    'fichas' => 0, 'estado' => 'Inactivo'],
    ];
}

$tituloPagina = 'Instructores';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-person-badge-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Directorio de Instructores</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Gestión de la planta docente, asignación académica de fichas y cuentas de acceso.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-green" data-bs-toggle="modal" data-bs-target="#modalInstructor">
                <i class="bi bi-person-plus-fill me-1"></i> Nuevo Instructor
            </button>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-mortarboard me-2 text-success"></i>Personal Docente Registrado</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Supervisa instructores activos y cantidad de fichas a su cargo</p>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorInstructores" class="form-control form-control-sm border-start-0" placeholder="Buscar instructor..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaInstructores" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Correo Electrónico</th>
                    <th>Fichas Asignadas</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($instructores as $i): 
                $esActivo = (strtolower($i['estado'] ?? '') === 'activo');
            ?>
                <tr>
                    <td>
                        <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 font-monospace" style="font-size: 12px;">
                            <?= htmlspecialchars($i['documento']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2.5">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['nombre']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                            <strong class="text-theme-primary"><?= htmlspecialchars($i['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><span class="text-secondary small"><?= htmlspecialchars($i['correo']) ?></span></td>
                    <td>
                        <span class="badge-sena badge-on-time">
                            <i class="bi bi-journal-bookmark-fill"></i> <?= (int)$i['fichas'] ?> fichas
                        </span>
                    </td>
                    <td>
                        <?php if ($esActivo): ?>
                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Activo</span>
                        <?php else: ?>
                            <span class="badge-sena badge-absent"><i class="bi bi-slash-circle"></i> Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-sena-outline me-1" data-bs-toggle="modal" data-bs-target="#modalInstructor" title="Editar datos">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="fichas.php?instructor=<?= urlencode($i['nombre']) ?>" class="btn btn-sm btn-sena-outline" title="Ver sus Fichas">
                            <i class="bi bi-journal-text"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear / Editar Instructor -->
<div class="modal fade" id="modalInstructor" tabindex="-1" aria-labelledby="modalInstructorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="#">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalInstructorLabel">
                        <i class="bi bi-person-badge-fill text-success me-2"></i>Gestión de Instructor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option>Cédula de Ciudadanía</option>
                                <option>Cédula de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Número de Documento *</label>
                            <input type="text" class="form-control" name="documento" required placeholder="Ej. 10203040" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Nombres *</label>
                            <input type="text" class="form-control" name="nombres" required placeholder="Nombres del instructor" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Apellidos *</label>
                            <input type="text" class="form-control" name="apellidos" required placeholder="Apellidos del instructor" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary small fw-bold">Correo Institucional SENA *</label>
                            <input type="email" class="form-control" name="correo" required placeholder="ejemplo@sena.edu.co" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">Estado en Sistema</label>
                            <select class="form-select" name="estado" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold">Contraseña Temporal de Acceso</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio obligatorio en el primer ingreso" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                    </div>
                    <div class="alert alert-info sena-alert-info mt-3 mb-0 d-flex align-items-center gap-2 border-0" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
                        <i class="bi bi-info-circle text-success fs-5"></i>
                        <div style="font-size: 12.5px;">
                            Para asignarle fichas académicas a este instructor, dirígete a <strong>Fichas y Horarios</strong> y elígelo como docente responsable.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green"><i class="bi bi-check-circle me-1"></i> Guardar Instructor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorInstructores');
    var tabla = document.getElementById('tablaInstructores');
    if (!tabla || !buscador) return;
    var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    buscador.addEventListener('input', function() {
        var texto = this.value.toLowerCase();
        for (var i = 0; i < filas.length; i++) {
            var fila = filas[i];
            if (fila.cells.length < 5) continue;
            var contenido = fila.textContent.toLowerCase();
            fila.style.display = (!texto || contenido.indexOf(texto) > -1) ? '' : 'none';
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

