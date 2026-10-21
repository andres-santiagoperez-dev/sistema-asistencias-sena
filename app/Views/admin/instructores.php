<?php
/**
 * Vista: Directorio de Instructores (admin/instructores.php)
 * 
 * Responsabilidad: Solo presentación visual.
 * No realiza consultas SQL ni conexiones manuales.
 * Recibe del InstructorController: $instructores, $adminArea, $mensaje, $error.
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=instructores');
    exit;
}

$tituloPagina = 'Instructores';
require_once BASE_PATH . '/Views/layouts/header.php';
?>

<!-- Alertas de estado -->
<?php if (!empty($mensaje)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-person-badge-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Directorio de Instructores</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">
                    Gestión de la planta docente, asignación académica de fichas y cuentas de acceso.
                    <?php if (($adminArea ?? 'todas') !== 'todas'): ?>
                        <span class="badge ms-1 badge-area-pill" style="background-color: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; font-weight: 700;">Área: <?= strtoupper(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </p>
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
            <p class="text-secondary mb-0" style="font-size:13px;">Supervisa instructores activos, área técnica y cantidad de fichas a su cargo</p>
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
                    <th>Área</th>
                    <th>Especialidad</th>
                    <th>Correo Electrónico</th>
                    <th>Fichas Asignadas</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($instructores)): ?>
                <?php foreach ($instructores as $i): 
                    $esActivo = (strtolower($i['estado'] ?? '') === 'activo');
                    $esTics   = (strtolower($i['area'] ?? '') === 'tics');
                ?>
                    <tr>
                        <td>
                            <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 font-monospace" style="font-size: 12px;">
                                <?= htmlspecialchars($i['documento'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['nombre'] ?? 'Docente') ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                                <strong class="text-theme-primary"><?= htmlspecialchars($i['nombre'] ?? '') ?></strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $esTics ? 'bg-info text-dark' : 'bg-warning text-dark' ?>" style="font-size: 11px; padding: 5px 10px;">
                                <?= htmlspecialchars(strtoupper($i['area'] ?? 'COMERCIO')) ?>
                            </span>
                        </td>
                        <td>
                            <span class="text-theme-primary small"><?= htmlspecialchars($i['especialidad'] ?? 'General') ?></span>
                        </td>
                        <td><span class="text-secondary small"><?= htmlspecialchars($i['correo'] ?? '') ?></span></td>
                        <td>
                            <span class="badge-sena badge-on-time">
                                <i class="bi bi-journal-bookmark-fill"></i> <?= (int)($i['total_fichas'] ?? 0) ?> fichas
                            </span>
                            <?php if (!empty($i['fichas_asignadas']) && $i['fichas_asignadas'] !== 'Sin fichas'): ?>
                                <small class="d-block text-secondary mt-1" style="font-size: 11px;">
                                    <?= htmlspecialchars($i['fichas_asignadas']) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($esActivo): ?>
                                <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Activo</span>
                            <?php else: ?>
                                <span class="badge-sena badge-absent"><i class="bi bi-slash-circle"></i> Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/index.php?page=fichas" class="btn btn-sm btn-sena-outline" title="Ver Fichas">
                                <i class="bi bi-journal-text"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i> No se encontraron instructores registrados para esta área.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Instructor -->
<div class="modal fade" id="modalInstructor" tabindex="-1" aria-labelledby="modalInstructorLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="<?= BASE_URL ?>/index.php?page=instructores">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalInstructorLabel">
                        <i class="bi bi-person-badge-fill text-success me-2"></i>Registrar Nuevo Instructor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="CC">Cédula de Ciudadanía (CC)</option>
                                <option value="CE">Cédula de Extranjería (CE)</option>
                                <option value="PEP">Permiso Especial de Permanencia (PEP)</option>
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
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Correo Institucional SENA *</label>
                            <input type="email" class="form-control" name="correo" required placeholder="ejemplo@sena.edu.co" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono" placeholder="Ej. 3101234567" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Especialidad Técnica</label>
                            <input type="text" class="form-control" name="especialidad" placeholder="Ej. Programación de Software" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Área Técnica *</label>
                            <?php if (($adminArea ?? 'todas') !== 'todas'): ?>
                                <input type="hidden" name="area" value="<?= htmlspecialchars($adminArea) ?>">
                                <input type="text" class="form-control" value="<?= strtoupper(htmlspecialchars($adminArea)) ?>" readonly style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <?php else: ?>
                                <select class="form-select" name="area" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="comercio">Comercio</option>
                                    <option value="Tics">TICs</option>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="alert alert-info sena-alert-info mt-3 mb-0 d-flex align-items-center gap-2 border-0" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
                        <i class="bi bi-info-circle text-success fs-5"></i>
                        <div style="font-size: 12.5px;">
                            La contraseña de acceso inicial será <strong>123456</strong>. La asignación de fichas y tolerancias se gestiona desde el módulo de <strong>Fichas y Horarios</strong>.
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

<?php require_once BASE_PATH . '/Views/layouts/footer.php'; ?>
