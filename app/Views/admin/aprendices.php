<?php
/**
 * Vista: Administración de Aprendices (admin/aprendices.php)
 * 
 * Responsabilidad: Solo presentación visual.
 * No realiza consultas SQL ni conexiones directas a la base de datos.
 * Recibe del AprendizController: $aprendices, $fichas, $adminArea, $filtroFicha, $mensaje, $error.
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=aprendices');
    exit;
}

$tituloPagina = 'Aprendices';
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
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-people-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Administración de Aprendices</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">
                    Directorio maestro de aprendices matriculados, asignación de fichas y llaveros RFID.
                    <?php if (($adminArea ?? 'todas') !== 'todas'): ?>
                        <span class="badge bg-success ms-1">Área: <?= strtoupper(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-green" data-bs-toggle="modal" data-bs-target="#modalAprendiz">
                <i class="bi bi-person-plus-fill me-1"></i> Nuevo Aprendiz
            </button>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-list-stars me-2 text-info"></i>Listado General de Aprendices</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Búsqueda por nombre, documento de identidad o filtrado por ficha formativa</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorAprendicesAdmin" class="form-control form-control-sm border-start-0" placeholder="Buscar aprendiz..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
            <select id="filtroFichaAdmin" class="form-select form-select-sm" style="width:250px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichas as $f): ?>
                    <option value="<?= htmlspecialchars($f['numero_ficha']) ?>" <?= ($filtroFicha !== null && (int)$filtroFicha === (int)$f['id_ficha']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['etiqueta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaAprendicesAdmin" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Ficha Asignada</th>
                    <th>Área</th>
                    <th>RFID</th>
                    <th>Correo Electrónico</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="8" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No se encontraron aprendices registrados para esta área técnica.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($aprendices as $a): 
                    $estadoLower = strtolower($a['estado_matricula'] ?? 'matriculado');
                    $esTics      = (strtolower($a['area'] ?? '') === 'tics');
                ?>
                    <tr>
                        <td>
                            <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 font-monospace" style="font-size: 12px;">
                                <?= htmlspecialchars($a['documento'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre'] ?? 'Aprendiz') ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                                <strong class="text-theme-primary"><?= htmlspecialchars($a['nombre'] ?? '') ?></strong>
                            </div>
                        </td>
                        <td>
                            <span class="text-theme-primary fw-medium"><?= htmlspecialchars($a['ficha_completa'] ?? '') ?></span>
                        </td>
                        <td>
                            <span class="badge <?= $esTics ? 'bg-info text-dark' : 'bg-warning text-dark' ?>" style="font-size: 11px; padding: 4px 8px;">
                                <?= htmlspecialchars(strtoupper($a['area'] ?? 'COMERCIO')) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-25 text-info font-monospace" style="font-size: 11px;">
                                <i class="bi bi-upc-scan me-1"></i><?= htmlspecialchars($a['codigo_rfid'] ?? 'Sin RFID') ?>
                            </span>
                        </td>
                        <td><span class="text-secondary small"><?= htmlspecialchars($a['correo'] ?? '') ?></span></td>
                        <td>
                            <?php if ($estadoLower === 'matriculado'): ?>
                                <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Matriculado</span>
                            <?php elseif ($estadoLower === 'aplazado'): ?>
                                <span class="badge-sena badge-late"><i class="bi bi-pause-circle-fill"></i> Aplazado</span>
                            <?php else: ?>
                                <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($a['estado_matricula'] ?? 'Retirado') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/index.php?page=fichas" class="btn btn-sm btn-sena-outline me-1" title="Ver Ficha">
                                <i class="bi bi-journal-bookmark"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Aprendiz -->
<div class="modal fade" id="modalAprendiz" tabindex="-1" aria-labelledby="modalAprendizLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="<?= BASE_URL ?>/index.php?page=aprendices">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalAprendizLabel">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Registrar Nuevo Aprendiz
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="CC">Cédula de Ciudadanía (CC)</option>
                                <option value="TI">Tarjeta de Identidad (TI)</option>
                                <option value="CE">Cédula de Extranjería (CE)</option>
                                <option value="Pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Número de Documento *</label>
                            <input type="text" class="form-control" name="documento" required placeholder="Ej. 1020304050" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Nombres *</label>
                            <input type="text" class="form-control" name="nombres" required placeholder="Nombres del aprendiz" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Apellidos *</label>
                            <input type="text" class="form-control" name="apellidos" required placeholder="Apellidos del aprendiz" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary small fw-bold">Correo Electrónico (Institucional o Personal) *</label>
                            <input type="email" class="form-control" name="correo" required placeholder="ejemplo@soy.sena.edu.co" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono" placeholder="Ej. 3123456789" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary small fw-bold">Ficha y Programa Asignado *</label>
                            <select class="form-select" name="ficha_id" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="">-- Seleccionar Ficha Formativa --</option>
                                <?php foreach ($fichas as $f): ?>
                                    <option value="<?= $f['id_ficha'] ?>">
                                        <?= htmlspecialchars($f['etiqueta']) ?> (<?= strtoupper($f['area']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">Código Llavero RFID</label>
                            <input type="text" class="form-control" name="rfid" placeholder="Opcional (Ej. RFID-88421)" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <div class="form-text text-secondary" style="font-size: 11px;">Si se omite, se generará uno provisional.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Estado de Matrícula *</label>
                            <select class="form-select" name="estado" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="Matriculado">Matriculado</option>
                                <option value="Retirado">Retirado</option>
                                <option value="Aplazado">Aplazado</option>
                            </select>
                        </div>
                    </div>
                    <div class="alert alert-info sena-alert-info mt-3 mb-0 d-flex align-items-center gap-2 border-0" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
                        <i class="bi bi-info-circle text-success fs-5"></i>
                        <div style="font-size: 12.5px;">
                            La contraseña inicial asignada será <strong>123456</strong>. El aprendiz podrá actualizarla al ingresar al sistema.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green"><i class="bi bi-check2-circle me-1"></i> Guardar Aprendiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorAprendicesAdmin');
    var filtroFicha = document.getElementById('filtroFichaAdmin');
    var tabla = document.getElementById('tablaAprendicesAdmin');
    if (!tabla) return;
    var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filtrar() {
        var texto = (buscador ? buscador.value : '').toLowerCase().trim();
        var fichaSel = (filtroFicha ? filtroFicha.value : '').toLowerCase().trim();

        for (var i = 0; i < filas.length; i++) {
            var fila = filas[i];
            if (fila.cells.length < 7) continue; // Ignorar fila de "no hay datos"
            
            var contenidoFila = fila.textContent.toLowerCase();
            var fichaFila = fila.cells[2].textContent.toLowerCase();

            var coincideTexto = !texto || contenidoFila.indexOf(texto) > -1;
            var coincideFicha = !fichaSel || fichaFila.indexOf(fichaSel) > -1;

            if (coincideTexto && coincideFicha) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        }
    }

    if (buscador) buscador.addEventListener('input', filtrar);
    if (filtroFicha) filtroFicha.addEventListener('change', filtrar);
});
</script>

<?php require_once BASE_PATH . '/Views/layouts/footer.php'; ?>
