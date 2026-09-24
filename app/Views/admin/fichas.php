<?php
/**
 * Vista: Gestión de Fichas y Horarios (admin/fichas.php)
 * 
 * Responsabilidad: Solo presentación visual (HTML + Blade PHP).
 * No realiza consultas SQL ni maneja lógica de conexión.
 * Recibe del FichaController: $fichas, $instructores, $horarios, $adminArea, $mensaje, $error.
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=fichas');
    exit;
}

$tituloPagina = 'Fichas y Horarios';
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
                <i class="bi bi-journal-bookmark-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Gestión de Fichas y Horarios</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">
                    Administración de programas formativos, asignación múltiple de instructores y jornadas.
                    <?php if ($adminArea !== 'todas'): ?>
                        <span class="badge bg-success ms-1">Área: <?= strtoupper(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-green" data-bs-toggle="modal" data-bs-target="#modalFicha">
                <i class="bi bi-plus-circle me-1"></i> Nueva Ficha
            </button>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-collection me-2 text-info"></i>Fichas Registradas en el Sistema</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Listado maestro con instructores asignados y aprendices vinculados</p>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorFichas" class="form-control form-control-sm border-start-0" placeholder="Buscar ficha o programa..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaFichas" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Código Ficha</th>
                    <th>Programa de Formación</th>
                    <th>Área</th>
                    <th>Instructores Asignados</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($fichas)): ?>
                <?php foreach ($fichas as $f): ?>
                    <tr>
                        <td><strong class="text-theme-primary fs-6"><?= htmlspecialchars($f['codigo']) ?></strong></td>
                        <td>
                            <span class="fw-semibold text-theme-primary"><?= htmlspecialchars($f['programa']) ?></span>
                        </td>
                        <td>
                            <?php $esTics = (strtolower($f['area']) === 'tics'); ?>
                            <span class="badge <?= $esTics ? 'bg-info text-dark' : 'bg-warning text-dark' ?>" style="font-size: 11px; padding: 5px 10px;">
                                <?= htmlspecialchars(strtoupper($f['area'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge-fill text-success fs-5"></i>
                                <span class="text-theme-primary small"><?= htmlspecialchars($f['instructores']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="text-info small"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($f['entrada'] ?? '06:00 AM') ?> &ndash; <?= htmlspecialchars($f['salida'] ?? '12:00 PM') ?></span>
                        </td>
                        <td>
                            <span class="badge-sena badge-on-time">
                                <i class="bi bi-people-fill"></i> <?= (int)$f['aprendices'] ?> aprendices
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/index.php?page=aprendices&ficha=<?= (int)$f['id_ficha'] ?>" class="btn btn-sm btn-sena-outline me-1" title="Ver Aprendices">
                                <i class="bi bi-people"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i> No se encontraron fichas registradas para esta área.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Ficha -->
<div class="modal fade" id="modalFicha" tabindex="-1" aria-labelledby="modalFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="<?= BASE_URL ?>/index.php?page=fichas">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalFichaLabel">
                        <i class="bi bi-journal-plus text-success me-2"></i>Registrar Nueva Ficha Formativa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Código o Número de Ficha *</label>
                            <input type="text" class="form-control" name="codigo" required placeholder="Ej. 2694521" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Programa de Formación *</label>
                            <input type="text" class="form-control" name="programa" required placeholder="Ej. Análisis y Desarrollo de Software" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>

                        <!-- Área Técnica -->
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Área Técnica *</label>
                            <?php if ($adminArea !== 'todas'): ?>
                                <input type="hidden" name="area" value="<?= htmlspecialchars($adminArea) ?>">
                                <input type="text" class="form-control" value="<?= strtoupper(htmlspecialchars($adminArea)) ?>" readonly style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <?php else: ?>
                                <select class="form-select" name="area" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="comercio">Comercio</option>
                                    <option value="Tics">TICs</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Horario -->
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Horario de Formación *</label>
                            <select class="form-select" name="horario_id" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <?php foreach ($horarios as $h): ?>
                                    <option value="<?= $h['id_horario'] ?>">
                                        <?= htmlspecialchars($h['dias_semana']) ?> (<?= $h['entrada'] ?> - <?= $h['salida'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Instructores Asignados (Relación N:M) -->
                        <div class="col-md-12">
                            <label class="form-label text-secondary small fw-bold">Instructores Asignados (Selección múltiple) *</label>
                            <select class="form-select" name="instructor_ids[]" multiple size="4" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <?php foreach ($instructores as $ins): ?>
                                    <option value="<?= $ins['id_instructor'] ?>">
                                        <?= htmlspecialchars($ins['nombre']) ?> &mdash; Área: <?= strtoupper($ins['area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-secondary" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i>Mantén presionada la tecla <strong>Ctrl</strong> (o Cmd en Mac) para seleccionar más de un instructor.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green"><i class="bi bi-check-circle me-1"></i> Guardar Ficha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorFichas');
    var tabla = document.getElementById('tablaFichas');
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
