<?php
/**
 * Vista de Presentación: Mis Aprendices (Instructor)
 * 
 * @var string $tituloPagina
 * @var array  $aprendices
 * @var array  $misFichas
 * @var string $filtroFicha
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=instructor_aprendices');
    exit();
}

$tituloPagina = $tituloPagina ?? 'Mis Aprendices';
$aprendices   = $aprendices ?? [];
$misFichas    = $misFichas ?? [];
$filtroFicha  = $filtroFicha ?? '';

require __DIR__ . '/../layouts/header-instructor.php';
?>


<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-people fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Mis Aprendices</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Directorio y estado académico de los aprendices pertenecientes a tus fichas formativas.</p>
            </div>
        </div>
        <div>
            <span class="badge bg-dark border border-secondary border-opacity-50 px-3 py-2 text-theme-primary" style="font-size:13px;">
                <i class="bi bi-person-check-fill text-success me-1"></i> Total: <strong><?= count($aprendices) ?></strong> Aprendices
            </span>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-person-lines-fill me-2 text-info"></i>Directorio de Aprendices</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Información de contacto, documento de identidad y ficha asignada</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorAprendices" class="form-control form-control-sm border-start-0" placeholder="Buscar aprendiz..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
            <select id="filtroFicha" class="form-select form-select-sm" style="width:220px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <option value="">Todas mis fichas</option>
                <?php foreach ($misFichas as $ficha): ?>
                    <option value="<?= htmlspecialchars($ficha) ?>" <?= (!empty($filtroFicha) && ($filtroFicha === $ficha || str_contains($ficha, $filtroFicha))) ? 'selected' : '' ?>><?= htmlspecialchars($ficha) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Este listado es de consulta formativa. La vinculación oficial o cambios de ficha son realizados por coordinación mediante el panel de Administrador.
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaAprendices" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Ficha Asignada</th>
                    <th>Estado Matrícula</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="5" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No tienes aprendices asignados todavía.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): ?>
                <tr>
                    <td>
                        <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 font-monospace" style="font-size: 12px;">
                            <?= htmlspecialchars($a['documento']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2.5">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                            <div>
                                <strong class="text-theme-primary d-block"><?= htmlspecialchars($a['nombre']) ?></strong>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-theme-primary fw-medium"><?= htmlspecialchars($a['ficha']) ?></span>
                    </td>
                    <td>
                        <?php if ($a['estado'] == 'matriculado'): ?>
                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Matriculado</span>
                        <?php elseif ($a['estado'] == 'aplazado'): ?>
                            <span class="badge-sena badge-late"><i class="bi bi-pause-circle-fill"></i> Aplazado</span>
                        <?php else: ?>
                            <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> <?= ucfirst($a['estado']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&ficha=<?= urlencode($a['ficha']) ?>" class="btn btn-sm btn-sena-outline" title="Ver control de ingresos">
                            <i class="bi bi-clock-history me-1"></i> Asistencia
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorAprendices');
    var filtroFicha = document.getElementById('filtroFicha');
    var tabla = document.getElementById('tablaAprendices');
    if (!tabla) return;
    var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filtrar() {
        var texto = (buscador ? buscador.value : '').toLowerCase();
        var fichaSel = (filtroFicha ? filtroFicha.value : '').toLowerCase();

        for (var i = 0; i < filas.length; i++) {
            var fila = filas[i];
            if (fila.cells.length < 5) continue;
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
    if (filtroFicha && filtroFicha.value) filtrar();
});
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

