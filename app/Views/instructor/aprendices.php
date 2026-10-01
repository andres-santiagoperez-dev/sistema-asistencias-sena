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
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($filtroFicha)): ?>
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-3 py-2" style="font-size:13px;">
                    <i class="bi bi-funnel-fill me-1"></i> Ficha: <strong><?= htmlspecialchars($filtroFicha) ?></strong>
                </span>
            <?php endif; ?>
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
            <?php if (!empty($filtroFicha)): ?>
                <a href="<?= BASE_URL ?>/index.php?page=instructor_aprendices" class="btn btn-sm btn-sena-outline d-inline-flex align-items-center gap-1" title="Quitar filtro y ver todas mis fichas">
                    <i class="bi bi-x-circle"></i> Ver todas
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Este listado es de consulta formativa. La vinculación oficial o cambios de ficha son realizados por coordinación mediante el panel de Administrador.
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaAprendices" class="table table-nalika-dark table-hover w-100 align-middle mb-0" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 14%;">Documento</th>
                    <th style="width: 22%;">Nombre Completo</th>
                    <th style="width: 20%;">Ficha Asignada</th>
                    <th style="width: 16%;">Faltas (Globales)</th>
                    <th style="width: 11%;">Retardos</th>
                    <th style="width: 17%; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No tienes aprendices asignados todavía.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): 
                $totalFaltas = (int)($a['total_inasistencias'] ?? 0);
                $sinJustif   = (int)($a['inasistencias_sin_justificar'] ?? 0);
                $excusadas   = (int)($a['inasistencias_justificadas'] ?? 0);
                $retardos    = (int)($a['total_retardos'] ?? 0);
            ?>
                <tr>
                    <td>
                        <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2 py-1 font-monospace" style="font-size: 11.5px;">
                            <?= htmlspecialchars($a['documento']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm flex-shrink-0" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="" style="width: 30px; height: 30px; border-radius: 50%; border: 2px solid #39a900;">
                            <div>
                                <strong class="text-theme-primary d-block text-truncate" style="font-size:12.5px;" title="<?= htmlspecialchars($a['nombre']) ?>"><?= htmlspecialchars($a['nombre']) ?></strong>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="text-theme-primary fw-medium text-truncate d-block" style="font-size:12px;" title="<?= htmlspecialchars($a['ficha']) ?>"><?= htmlspecialchars($a['ficha']) ?></span>
                    </td>
                    <td>
                        <?php if ($totalFaltas > 0): ?>
                            <span class="badge bg-danger px-2 py-1 fw-bold" style="font-size:11.5px;" title="Total de faltas sin importar el instructor">
                                <i class="bi bi-x-circle-fill me-1"></i><?= $totalFaltas ?> Falta(s)
                            </span>
                            <small class="d-block text-secondary mt-0.5" style="font-size:10.5px;">
                                <?= $sinJustif ?> sin justif. | <?= $excusadas ?> excusada(s)
                            </small>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1" style="font-size:11.5px;">
                                <i class="bi bi-check-circle-fill me-1"></i>0 Faltas
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($retardos > 0): ?>
                            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-25 px-2 py-1" style="font-size:11.5px;">
                                <i class="bi bi-clock-history me-1"></i><?= $retardos ?> Retardo(s)
                            </span>
                        <?php else: ?>
                            <span class="text-secondary" style="font-size:11.5px;">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                            <button type="button" 
                                    class="btn btn-sm btn-warning text-dark fw-bold py-0.5 px-2 font-size-11" 
                                    style="font-size:11px;"
                                    onclick="abrirModalHistorialGlobal(<?= (int)$a['id_aprendiz'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>', '<?= htmlspecialchars($a['documento']) ?>', '<?= htmlspecialchars(addslashes($a['ficha'])) ?>')" 
                                    title="Ver todas las faltas registradas por cualquier instructor">
                                <i class="bi bi-eye-fill me-1"></i>Faltas Globales
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&ficha=<?= urlencode($a['ficha']) ?>" class="btn btn-sm btn-sena-outline py-0.5 px-2 font-size-11" style="font-size:11px;" title="Ver Ingresos Hoy">
                                <i class="bi bi-clock-history"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Ver Faltas e Inasistencias Globales del Aprendiz -->
<div class="modal fade" id="modalHistorialGlobalAprendiz" tabindex="-1" aria-labelledby="modalHistorialGlobalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background: var(--nk-bg-card, #1b2a47); color: var(--nk-text-primary, #fff); border-radius: 12px;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold" id="modalHistorialGlobalLabel">
                    <i class="bi bi-journal-x text-warning me-2"></i>Historial de Faltas e Inasistencias Globales
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Header Aprendiz -->
                <div class="alert alert-dark border border-secondary border-opacity-25 mb-3 p-3" style="background: rgba(0,0,0,0.25); border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="badge bg-success mb-1" id="mFichaAprendiz">Ficha: ---</span>
                            <h5 class="fw-bold mb-0 text-white" id="mNombreAprendiz">Nombre Aprendiz</h5>
                            <small class="text-info d-block mt-0.5" id="mDocAprendiz"><i class="bi bi-card-heading me-1"></i>Doc: ---</small>
                        </div>
                        <div class="d-flex gap-2" id="mBadgesResumen">
                            <!-- Inyectado dinámicamente -->
                        </div>
                    </div>
                </div>

                <p class="text-secondary small mb-3">
                    <i class="bi bi-info-circle-fill text-info me-1"></i> Este reporte muestra todas las inasistencias y marcaciones registradas para el aprendiz por <strong>cualquier instructor</strong> en la institución.
                </p>

                <!-- Tabla de Historial Global -->
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-nalika-dark table-hover w-100 align-middle mb-0" id="tablaModalHistorial">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Instructor Docente</th>
                                <th>Horario (Entrada/Salida)</th>
                                <th>Novedad / Excusa</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyModalHistorial">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                                    Cargando faltas e inasistencias del aprendiz...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
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
            if (fila.cells.length < 6) continue;
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
    if (filtroFicha) {
        filtroFicha.addEventListener('change', function() {
            var val = this.value;
            var numFicha = val ? val.split(' - ')[0].trim() : '';
            window.location.href = '<?= BASE_URL ?>/index.php?page=instructor_aprendices' + (numFicha ? '&ficha=' + encodeURIComponent(numFicha) : '');
        });
    }
});

function abrirModalHistorialGlobal(idAprendiz, nombre, documento, ficha) {
    document.getElementById('mNombreAprendiz').textContent = nombre;
    document.getElementById('mDocAprendiz').innerHTML = '<i class="bi bi-card-heading me-1"></i>Documento: ' + documento;
    document.getElementById('mFichaAprendiz').textContent = 'Ficha: ' + ficha;

    const tbody = document.getElementById('tbodyModalHistorial');
    const badgesCont = document.getElementById('mBadgesResumen');

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-secondary">
                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                Cargando historial de faltas de todos los instructores...
            </td>
        </tr>
    `;
    badgesCont.innerHTML = '';

    const modal = new bootstrap.Modal(document.getElementById('modalHistorialGlobalAprendiz'));
    modal.show();

    fetch('<?= BASE_URL ?>/index.php?page=instructor_aprendices&action=obtener_historial&id_aprendiz=' + idAprendiz, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.historial || data.historial.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                        <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                        Este aprendiz no cuenta con faltas ni marcaciones de asistencia registradas.
                    </td>
                </tr>
            `;
            badgesCont.innerHTML = '<span class="badge bg-success px-3 py-2">0 Faltas Registradas</span>';
            return;
        }

        let faltasCount = 0;
        let retardosCount = 0;
        let justificadasCount = 0;
        let htmlRows = '';

        data.historial.forEach(h => {
            let estLower = (h.estado || '').toLowerCase();
            let badgeEst = '';

            if (estLower === 'inasistencia') {
                faltasCount++;
                badgeEst = '<span class="badge bg-danger py-1 px-2"><i class="bi bi-x-circle-fill me-1"></i> Inasistencia</span>';
            } else if (estLower === 'justificada') {
                justificadasCount++;
                badgeEst = '<span class="badge bg-info text-dark py-1 px-2"><i class="bi bi-file-earmark-medical-fill me-1"></i> Justificada</span>';
            } else if (estLower === 'retardo') {
                retardosCount++;
                badgeEst = '<span class="badge bg-warning text-dark py-1 px-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Retardo</span>';
            } else if (estLower === 'salida temprana') {
                retardosCount++;
                badgeEst = '<span class="badge bg-warning text-dark py-1 px-2"><i class="bi bi-arrow-left-circle-fill me-1"></i> Salida Temprana</span>';
            } else {
                badgeEst = `<span class="badge bg-success py-1 px-2"><i class="bi bi-check-circle-fill me-1"></i> ${escapeHtml(h.estado)}</span>`;
            }

            let horaEnt = h.hora_entrada ? formatearHora12(h.hora_entrada) : '--:--';
            let horaSal = h.hora_salida ? formatearHora12(h.hora_salida) : '--:--';
            let novText = h.excusa_motivo ? `Excusa: <strong>${escapeHtml(h.excusa_motivo)}</strong> (${escapeHtml(h.excusa_estado)})` : '<span class="text-secondary">Sin novedad</span>';

            htmlRows += `
                <tr>
                    <td><strong>${escapeHtml(h.fecha)}</strong></td>
                    <td>${badgeEst}</td>
                    <td><strong class="text-theme-primary"><i class="bi bi-person-badge text-info me-1"></i>${escapeHtml(h.instructor_nombre)}</strong></td>
                    <td><small class="text-secondary">${horaEnt} - ${horaSal}</small></td>
                    <td><small>${novText}</small></td>
                </tr>
            `;
        });

        tbody.innerHTML = htmlRows;

        let totalFaltasGlobales = faltasCount + justificadasCount;
        badgesCont.innerHTML = `
            <span class="badge bg-danger px-2.5 py-2">${totalFaltasGlobales} Faltas Totales</span>
            <span class="badge bg-warning text-dark px-2.5 py-2">${retardosCount} Retardos</span>
        `;
    })
    .catch(err => {
        console.error("Error al obtener historial:", err);
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i> Error de conexión al consultar las faltas del aprendiz.
                </td>
            </tr>
        `;
    });
}

function formatearHora12(horaStr) {
    if (!horaStr) return '--:--';
    const partes = horaStr.split(':');
    if (partes.length < 2) return horaStr;
    let hh = parseInt(partes[0], 10);
    let mm = partes[1];
    let ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12;
    hh = hh ? hh : 12;
    return (hh < 10 ? '0' + hh : hh) + ':' + mm + ' ' + ampm;
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

