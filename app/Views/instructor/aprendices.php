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

<style>
/* Estilos para badges interactivos y modal sin scroll horizontal */
.cursor-pointer { cursor: pointer !important; }
.hover-scale { transition: transform 0.15s ease, box-shadow 0.15s ease; }
.hover-scale:hover { transform: translateY(-1px) scale(1.03); box-shadow: 0 4px 10px rgba(0,0,0,0.12) !important; }
#modalHistorialGlobalAprendiz .modal-dialog { max-width: 95vw; width: 1140px; }
#tablaModalHistorial { table-layout: fixed !important; width: 100% !important; border-collapse: separate !important; }
#tablaModalHistorial th, #tablaModalHistorial td { overflow-wrap: break-word !important; word-wrap: break-word !important; white-space: normal !important; vertical-align: middle !important; }
#tablaModalHistorial thead th { background-color: var(--bg-inset, #f8fafc) !important; color: var(--text-muted, #475569) !important; font-size: 11.5px !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border-bottom: 2px solid var(--border-color, #e2e8f0) !important; position: sticky; top: 0; z-index: 5; }
.btn-xs { padding: 2px 7px; font-size: 11px; line-height: 1.3; border-radius: 4px; }
</style>

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
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-person-lines-fill me-2 text-success"></i>Directorio de Aprendices</h5>
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
                            <div role="button" 
                                 class="badge bg-danger px-2.5 py-1.5 fw-bold cursor-pointer text-decoration-none shadow-sm hover-scale d-inline-flex align-items-center gap-1" 
                                 style="font-size:11.5px; cursor: pointer;" 
                                 onclick="abrirModalHistorialGlobal(<?= (int)$a['id_aprendiz'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>', '<?= htmlspecialchars($a['documento']) ?>', '<?= htmlspecialchars(addslashes($a['ficha'])) ?>', 'inasistencia')"
                                 title="Clic para ver detalle de las inasistencias de <?= htmlspecialchars($a['nombre']) ?>">
                                <i class="bi bi-x-circle-fill"></i>
                                <span><?= $totalFaltas ?> Falta(s)</span>
                                <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size:9.5px;"></i>
                            </div>
                            <small class="d-block text-secondary mt-1" style="font-size:10.5px;">
                                <?= $sinJustif ?> sin justif. | <?= $excusadas ?> excusada(s)
                            </small>
                        <?php else: ?>
                            <div role="button" 
                                 class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1 cursor-pointer" 
                                 style="font-size:11.5px; cursor: pointer;"
                                 onclick="abrirModalHistorialGlobal(<?= (int)$a['id_aprendiz'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>', '<?= htmlspecialchars($a['documento']) ?>', '<?= htmlspecialchars(addslashes($a['ficha'])) ?>', 'todos')"
                                 title="Ver historial de asistencias">
                                <i class="bi bi-check-circle-fill me-1"></i>0 Faltas
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($retardos > 0): ?>
                            <div role="button" 
                                 class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2.5 py-1.5 fw-bold cursor-pointer shadow-sm hover-scale d-inline-flex align-items-center gap-1" 
                                 style="font-size:11.5px; cursor: pointer;" 
                                 onclick="abrirModalHistorialGlobal(<?= (int)$a['id_aprendiz'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>', '<?= htmlspecialchars($a['documento']) ?>', '<?= htmlspecialchars(addslashes($a['ficha'])) ?>', 'retardo')"
                                 title="Clic para ver detalle de retardos de <?= htmlspecialchars($a['nombre']) ?>">
                                <i class="bi bi-clock-history"></i>
                                <span><?= $retardos ?> Retardo(s)</span>
                                <i class="bi bi-box-arrow-up-right ms-0.5" style="font-size:9.5px;"></i>
                            </div>
                        <?php else: ?>
                            <span class="text-secondary" style="font-size:11.5px;">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                            <button type="button" 
                                    class="btn btn-sm btn-warning text-dark fw-bold py-0.5 px-2 font-size-11" 
                                    style="font-size:11px;"
                                    onclick="abrirModalHistorialGlobal(<?= (int)$a['id_aprendiz'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>', '<?= htmlspecialchars($a['documento']) ?>', '<?= htmlspecialchars(addslashes($a['ficha'])) ?>', 'todos')" 
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

<!-- Modal para Ver Faltas e Inasistencias Globales del Aprendiz (Sin Scroll Horizontal) -->
<div class="modal fade" id="modalHistorialGlobalAprendiz" tabindex="-1" aria-labelledby="modalHistorialGlobalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="background: var(--bg-panel, #ffffff); color: var(--text-main, #0f172a); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0) !important;">
            <div class="modal-header border-bottom border-secondary border-opacity-10 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded-circle" style="background: var(--pastel-warning-bg, #fef3c7); color: var(--pastel-warning-text, #92400e);">
                        <i class="bi bi-journal-x fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-theme-primary mb-0" id="modalHistorialGlobalLabel">
                            Historial Institucional de Asistencias y Faltas
                        </h5>
                        <small class="text-theme-secondary" style="font-size: 11.5px;">
                            Registros consolidados de todas las fichas e instructores del centro formativo
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4" style="overflow-x: hidden !important;">
                <!-- Tarjeta Resumen del Aprendiz -->
                <div class="card border border-secondary border-opacity-10 p-3 mb-3 shadow-sm" style="background: var(--bg-inset, #f8fafc); border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <img id="mAvatarAprendiz" src="" alt="Avatar" class="avatar-sm rounded-circle border border-2 border-success" style="width: 44px; height: 44px; object-fit: cover;">
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <span class="badge bg-success" id="mFichaAprendiz" style="font-size: 11px;">Ficha: ---</span>
                                    <span class="badge bg-dark font-monospace" id="mDocAprendiz" style="font-size: 11px;">Doc: ---</span>
                                </div>
                                <h5 class="fw-bold mb-0 text-theme-primary" id="mNombreAprendiz">Nombre Aprendiz</h5>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center flex-wrap" id="mBadgesResumen">
                            <!-- Inyectado dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Barra de Filtros Rápidos (Todos / Inasistencias / Retardos / Asistencias) -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div class="btn-group btn-group-sm" role="group" id="grupoFiltrosModal" aria-label="Filtros del Historial">
                        <button type="button" class="btn btn-outline-light active px-3" id="btnFiltro_todos" onclick="filtrarHistorialModal('todos')">
                            <i class="bi bi-list-ul me-1"></i> Todos <span class="badge bg-secondary ms-1" id="badgeCount_todos">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-danger px-3" id="btnFiltro_inasistencia" onclick="filtrarHistorialModal('inasistencia')">
                            <i class="bi bi-x-circle-fill me-1"></i> Inasistencias <span class="badge bg-danger ms-1" id="badgeCount_inasistencia">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-warning px-3" id="btnFiltro_retardo" onclick="filtrarHistorialModal('retardo')">
                            <i class="bi bi-clock-history me-1"></i> Retardos <span class="badge bg-warning text-dark ms-1" id="badgeCount_retardo">0</span>
                        </button>
                        <button type="button" class="btn btn-outline-success px-3" id="btnFiltro_asistio" onclick="filtrarHistorialModal('asistio')">
                            <i class="bi bi-check-circle-fill me-1"></i> Asistió <span class="badge bg-success ms-1" id="badgeCount_asistio">0</span>
                        </button>
                    </div>
                    <div class="text-secondary small d-flex align-items-center gap-1">
                        <i class="bi bi-info-circle text-info"></i>
                        <span>Clic en <strong class="text-warning">+ Nota</strong> para registrar o modificar la justificación.</span>
                    </div>
                </div>

                <!-- Contenedor de la Tabla: Ancho 100% y CERO scroll horizontal -->
                <div style="max-height: 420px; overflow-y: auto; overflow-x: hidden !important; border: 1px solid rgba(255,255,255,0.08); border-radius: 8px;">
                    <table class="table table-nalika-dark table-hover w-100 align-middle mb-0" id="tablaModalHistorial" style="table-layout: fixed; width: 100%; border-collapse: separate;">
                        <colgroup>
                            <col style="width: 17%;">
                            <col style="width: 14%;">
                            <col style="width: 21%;">
                            <col style="width: 20%;">
                            <col style="width: 28%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th style="width: 17%;">Día y Fecha</th>
                                <th style="width: 14%;">Estado</th>
                                <th style="width: 21%;">Instructor Docente</th>
                                <th style="width: 20%;">Horario / Marcación</th>
                                <th style="width: 28%;">Nota / Justificación</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyModalHistorial">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-secondary">
                                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                                    Cargando historial de inasistencias y retardos del aprendiz...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer border-top border-secondary border-opacity-25 py-2.5 px-4 d-flex justify-content-between align-items-center">
                <span class="text-secondary small">
                    <i class="bi bi-check2-all text-success me-1"></i>Sincronizado con base de datos institucional
                </span>
                <button type="button" class="btn btn-sm btn-sena-outline px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar / Agregar Nota de Asistencia -->
<div class="modal fade" id="modalEditarNotaAsistencia" tabindex="-1" aria-labelledby="modalEditarNotaLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg" style="background: var(--bg-panel, #ffffff); color: var(--text-main, #0f172a); border-radius: 12px; border: 1px solid var(--border-color, #e2e8f0) !important;">
            <div class="modal-header border-bottom border-secondary border-opacity-10 py-2.5 px-3">
                <h6 class="modal-title fw-bold text-theme-primary mb-0" id="modalEditarNotaLabel">
                    <i class="bi bi-pencil-square text-warning me-2"></i>Nota / Motivo de Falta o Retardo
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body p-3">
                <div class="alert border border-secondary border-opacity-10 py-2 px-3 mb-3" style="background: var(--bg-inset, #f8fafc); border-radius: 8px;">
                    <div class="small text-theme-primary mb-1">
                        <i class="bi bi-calendar3 me-1 text-info"></i><strong id="lblNotaFecha">--</strong>
                    </div>
                    <div class="small text-theme-secondary">
                        <i class="bi bi-person-badge me-1 text-success"></i><span id="lblNotaInstructor">--</span>
                    </div>
                </div>
                <input type="hidden" id="inputNotaIdRegistro" value="0">
                <div class="mb-2">
                    <label for="txtObservacionAsistencia" class="form-label small fw-bold text-theme-primary mb-1">
                        Observación / Motivo informado:
                    </label>
                    <textarea class="form-control" id="txtObservacionAsistencia" rows="4" 
                              placeholder="Ej: Calamidad doméstica informada por el aprendiz / Cita médica no programada / Transporte averiado..." 
                              style="background: var(--input-bg, #ffffff); color: var(--input-text, #0f172a); border-color: var(--border-color, #cbd5e1); font-size: 13px;"></textarea>
                    <small class="text-theme-secondary d-block mt-1" style="font-size: 11px;">
                        Esta nota quedará vinculada permanentemente al registro de asistencia del aprendiz.
                    </small>
                </div>
            </div>
            <div class="modal-footer border-top border-secondary border-opacity-10 py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-sena-green fw-bold px-3" id="btnGuardarNotaAsistencia" onclick="guardarNotaAsistencia()">
                    <i class="bi bi-check2-circle me-1"></i> Guardar Nota
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window._historialActual = [];
window._filtroActivo = 'todos';

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

    // Asegurar scroll del modal principal cuando se cierra el submodal de notas
    var modalNotaEl = document.getElementById('modalEditarNotaAsistencia');
    if (modalNotaEl) {
        modalNotaEl.addEventListener('hidden.bs.modal', function () {
            var modalHistEl = document.getElementById('modalHistorialGlobalAprendiz');
            if (modalHistEl && modalHistEl.classList.contains('show')) {
                document.body.classList.add('modal-open');
            }
        });
    }
});

function abrirModalHistorialGlobal(idAprendiz, nombre, documento, ficha, filtroInicial) {
    window._filtroActivo = filtroInicial || 'todos';
    window._historialActual = [];

    document.getElementById('mNombreAprendiz').textContent = nombre;
    document.getElementById('mDocAprendiz').innerHTML = '<i class="bi bi-card-heading me-1"></i>Doc: ' + documento;
    document.getElementById('mFichaAprendiz').textContent = 'Ficha: ' + ficha;
    document.getElementById('mAvatarAprendiz').src = 'https://ui-avatars.com/api/?background=random&name=' + encodeURIComponent(nombre);

    const tbody = document.getElementById('tbodyModalHistorial');
    const badgesCont = document.getElementById('mBadgesResumen');

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center py-4 text-secondary">
                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                Cargando historial de inasistencias y retardos del aprendiz...
            </td>
        </tr>
    `;
    badgesCont.innerHTML = '';

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalHistorialGlobalAprendiz'));
    modal.show();

    fetch('<?= BASE_URL ?>/index.php?page=instructor_aprendices&action=obtener_historial&id_aprendiz=' + idAprendiz, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.historial || data.historial.length === 0) {
            window._historialActual = [];
            actualizarContadoresFiltros(0, 0, 0, 0);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                        <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                        Este aprendiz no cuenta con faltas ni marcaciones de asistencia registradas.
                    </td>
                </tr>
            `;
            badgesCont.innerHTML = '<span class="badge bg-success px-3 py-2"><i class="bi bi-check2-all me-1"></i>0 Faltas Registradas</span>';
            return;
        }

        window._historialActual = data.historial;

        let faltasCount = 0;
        let retardosCount = 0;
        let asistioCount = 0;

        data.historial.forEach(h => {
            let estLower = (h.estado || '').toLowerCase().trim();
            if (estLower === 'inasistencia' || estLower === 'justificada') {
                faltasCount++;
            } else if (estLower === 'retardo' || estLower === 'salida temprana') {
                retardosCount++;
            } else if (estLower === 'asistio' || estLower === 'asistió' || estLower === 'presente') {
                asistioCount++;
            }
        });

        actualizarContadoresFiltros(data.historial.length, faltasCount, retardosCount, asistioCount);

        badgesCont.innerHTML = `
            <span class="badge bg-danger px-2.5 py-1.5"><i class="bi bi-x-circle me-1"></i>${faltasCount} Faltas Totales</span>
            <span class="badge bg-warning text-dark px-2.5 py-1.5"><i class="bi bi-clock-history me-1"></i>${retardosCount} Retardo(s)</span>
            <span class="badge bg-success px-2.5 py-1.5"><i class="bi bi-check2 me-1"></i>${asistioCount} Asistencias</span>
        `;

        filtrarHistorialModal(window._filtroActivo);
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

function actualizarContadoresFiltros(total, faltas, retardos, asistio) {
    const elTodos = document.getElementById('badgeCount_todos');
    const elFaltas = document.getElementById('badgeCount_inasistencia');
    const elRetardos = document.getElementById('badgeCount_retardo');
    const elAsistio = document.getElementById('badgeCount_asistio');

    if (elTodos) elTodos.textContent = total;
    if (elFaltas) elFaltas.textContent = faltas;
    if (elRetardos) elRetardos.textContent = retardos;
    if (elAsistio) elAsistio.textContent = asistio;
}

function filtrarHistorialModal(filtro) {
    window._filtroActivo = filtro || 'todos';

    // Actualizar botones de filtro
    const botones = ['todos', 'inasistencia', 'retardo', 'asistio'];
    botones.forEach(b => {
        const btnEl = document.getElementById('btnFiltro_' + b);
        if (btnEl) {
            if (b === window._filtroActivo) {
                btnEl.classList.add('active');
            } else {
                btnEl.classList.remove('active');
            }
        }
    });

    const tbody = document.getElementById('tbodyModalHistorial');
    if (!tbody) return;

    if (!window._historialActual || window._historialActual.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-secondary">
                    <i class="bi bi-folder-x fs-3 text-muted d-block mb-1"></i>
                    No hay registros disponibles.
                </td>
            </tr>
        `;
        return;
    }

    let filtrados = window._historialActual.filter(h => {
        let est = (h.estado || '').toLowerCase().trim();
        if (window._filtroActivo === 'inasistencia') {
            return est === 'inasistencia' || est === 'justificada';
        }
        if (window._filtroActivo === 'retardo') {
            return est === 'retardo' || est === 'salida temprana';
        }
        if (window._filtroActivo === 'asistio') {
            return est === 'asistio' || est === 'asistió' || est === 'presente';
        }
        return true; // 'todos'
    });

    if (filtrados.length === 0) {
        let mensajeFiltro = 'No se encontraron registros ';
        if (window._filtroActivo === 'inasistencia') mensajeFiltro += 'de inasistencia para este aprendiz.';
        else if (window._filtroActivo === 'retardo') mensajeFiltro += 'de retardos para este aprendiz.';
        else if (window._filtroActivo === 'asistio') mensajeFiltro += 'de asistencias registradas.';
        else mensajeFiltro += 'en el historial.';

        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-secondary">
                    <i class="bi bi-check2-circle fs-3 text-success d-block mb-1"></i>
                    ${mensajeFiltro}
                </td>
            </tr>
        `;
        return;
    }

    let htmlRows = '';
    filtrados.forEach(h => {
        let estLower = (h.estado || '').toLowerCase().trim();
        let badgeEst = '';

        if (estLower === 'inasistencia') {
            badgeEst = '<span class="badge bg-danger py-1 px-2" style="font-size:11px;"><i class="bi bi-x-circle-fill me-1"></i> Inasistencia</span>';
        } else if (estLower === 'justificada') {
            badgeEst = '<span class="badge bg-info text-dark py-1 px-2" style="font-size:11px;"><i class="bi bi-file-earmark-medical-fill me-1"></i> Justificada</span>';
        } else if (estLower === 'retardo') {
            badgeEst = '<span class="badge bg-warning text-dark py-1 px-2" style="font-size:11px;"><i class="bi bi-clock-history me-1"></i> Retardo</span>';
        } else if (estLower === 'salida temprana') {
            badgeEst = '<span class="badge bg-warning text-dark py-1 px-2" style="font-size:11px;"><i class="bi bi-box-arrow-left me-1"></i> Salida Temp.</span>';
        } else {
            badgeEst = `<span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 py-1 px-2" style="font-size:11px;"><i class="bi bi-check-circle-fill me-1"></i> ${escapeHtml(h.estado || 'Asistió')}</span>`;
        }

        let horaEnt = h.hora_entrada ? formatearHora12(h.hora_entrada) : '--:--';
        let horaSal = h.hora_salida ? formatearHora12(h.hora_salida) : '--:--';
        let fechaInfo = formatearFechaConDia(h.fecha);

        // Bloque Horario / Marcación
        let celdaHorario = '';
        if (estLower === 'inasistencia') {
            celdaHorario = `
                <span class="text-danger fw-bold d-block" style="font-size:11.5px;">
                    <i class="bi bi-slash-circle me-1"></i>No asistió
                </span>
                <small class="text-theme-secondary d-block" style="font-size:10.5px;">
                    Clase: ${escapeHtml(h.horario_entrada || '--')} a ${escapeHtml(h.horario_salida || '--')}
                </small>
            `;
        } else if (estLower === 'retardo') {
            let extraMin = parseInt(h.minutos_retardo, 10);
            let badgeMin = extraMin > 0 ? `<span class="text-warning fw-semibold">(+${extraMin} min)</span>` : '';
            celdaHorario = `
                <span class="text-warning fw-bold d-block" style="font-size:11.5px;">
                    <i class="bi bi-box-arrow-in-right me-1"></i>${horaEnt}
                </span>
                <small class="text-theme-secondary d-block" style="font-size:10.5px;">
                    Turno: ${escapeHtml(h.horario_entrada || '--')} ${badgeMin}
                </small>
            `;
        } else {
            celdaHorario = `
                <span class="text-success fw-bold d-block" style="font-size:11.5px;">
                    <i class="bi bi-check2-circle me-1"></i>${horaEnt}
                </span>
                <small class="text-theme-secondary d-block" style="font-size:10.5px;">
                    Salida: ${horaSal}
                </small>
            `;
        }

        // Bloque Nota / Justificación
        let celdaNota = '';
        if (h.excusa_motivo) {
            celdaNota += `
                <div class="p-1.5 rounded mb-1 border border-info border-opacity-25" style="background: var(--pastel-info-bg, #e0f2fe);">
                    <div class="d-flex align-items-center justify-content-between mb-0.5">
                        <span class="badge bg-info" style="font-size:9.5px;">Excusa: ${escapeHtml(h.excusa_estado)}</span>
                        ${h.excusa_soporte ? `<a href="<?= BASE_URL ?>/uploads/excusas/${encodeURIComponent(h.excusa_soporte)}" target="_blank" class="badge bg-secondary text-decoration-none" style="font-size:9.5px;" title="Ver archivo de soporte"><i class="bi bi-paperclip"></i> Soporte</a>` : ''}
                    </div>
                    <strong class="text-theme-primary d-block" style="font-size:11px;">${escapeHtml(h.excusa_motivo)}</strong>
                    ${h.excusa_observacion ? `<small class="text-theme-secondary d-block fst-italic" style="font-size:10px;">"${escapeHtml(h.excusa_observacion)}"</small>` : ''}
                </div>
            `;
        }

        let tieneObs = Boolean(h.observaciones && h.observaciones.trim() !== '');
        let cardStyle = tieneObs ? 'background: var(--pastel-success-bg, #dcfce7); border-left: 3px solid var(--pastel-success-text, #166534);' : '';
        let cardClass = tieneObs ? 'p-1.5 rounded mb-1' : '';

        celdaNota += `
            <div id="obs-card-${h.id_registro}" class="${cardClass}" style="${cardStyle}">
                ${tieneObs ? `
                    <div class="d-flex align-items-center gap-1 text-success fw-bold" style="font-size:10px;">
                        <i class="bi bi-chat-left-quote-fill"></i> Nota de Asistencia:
                    </div>
                    <div class="text-theme-primary small" style="font-size:11px;" id="obs-text-${h.id_registro}">
                        ${escapeHtml(h.observaciones)}
                    </div>
                ` : `
                    <span class="text-theme-secondary fst-italic small" id="obs-text-${h.id_registro}">Sin nota registrada</span>
                `}
            </div>
            <button type="button" 
                    class="btn btn-xs btn-outline-secondary py-0.5 px-2 mt-0.5 rounded-pill" 
                    style="font-size: 10px;" 
                    onclick="abrirModalNota(${h.id_registro}, '${escapeAttr(h.observaciones || '')}', '${escapeAttr(fechaInfo.diaSemana + ', ' + fechaInfo.fechaFormateada)}', '${escapeAttr(h.instructor_nombre)}')">
                <i class="bi bi-pencil-fill me-1 text-warning"></i>${tieneObs ? 'Modificar Nota' : '+ Agregar Nota'}
            </button>
        `;

        htmlRows += `
            <tr>
                <td>
                    <strong class="text-theme-primary d-block" style="font-size:12.5px;">${fechaInfo.diaSemana}</strong>
                    <span class="text-theme-secondary" style="font-size:11px;"><i class="bi bi-calendar3 me-1"></i>${fechaInfo.fechaFormateada}</span>
                </td>
                <td>${badgeEst}</td>
                <td>
                    <div class="d-flex align-items-center gap-1.5">
                        <i class="bi bi-person-badge text-info fs-6"></i>
                        <strong class="text-theme-primary text-truncate d-inline-block" style="max-width: 170px;" title="${escapeHtml(h.instructor_nombre)}">
                            ${escapeHtml(h.instructor_nombre)}
                        </strong>
                    </div>
                    ${h.instructor_correo ? `<small class="text-theme-secondary d-block text-truncate" style="font-size:10.5px;" title="${escapeHtml(h.instructor_correo)}">${escapeHtml(h.instructor_correo)}</small>` : ''}
                    ${h.numero_ficha ? `<small class="text-theme-secondary d-block" style="font-size:10px;">Ficha: ${escapeHtml(h.numero_ficha)}</small>` : ''}
                </td>
                <td>${celdaHorario}</td>
                <td>${celdaNota}</td>
            </tr>
        `;
    });

    tbody.innerHTML = htmlRows;
}

function abrirModalNota(idRegistro, observacionActual, fechaLabel, instructorLabel) {
    document.getElementById('inputNotaIdRegistro').value = idRegistro;
    document.getElementById('txtObservacionAsistencia').value = observacionActual || '';
    document.getElementById('lblNotaFecha').textContent = fechaLabel || '--';
    document.getElementById('lblNotaInstructor').textContent = instructorLabel ? 'Instructor: ' + instructorLabel : 'Instructor Registrado';

    const modalNota = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarNotaAsistencia'));
    modalNota.show();
}

function guardarNotaAsistencia() {
    const idRegistro = document.getElementById('inputNotaIdRegistro').value;
    const observacion = document.getElementById('txtObservacionAsistencia').value.trim();
    const btn = document.getElementById('btnGuardarNotaAsistencia');

    if (!idRegistro || idRegistro == '0') return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';

    const formData = new FormData();
    formData.append('id_registro', idRegistro);
    formData.append('observacion', observacion);

    fetch('<?= BASE_URL ?>/index.php?page=instructor_aprendices&action=guardar_observacion', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Guardar Nota';

        if (res.success) {
            // Actualizar en el array en memoria
            if (window._historialActual) {
                const item = window._historialActual.find(x => x.id_registro == idRegistro);
                if (item) {
                    item.observaciones = observacion;
                }
            }

            // Re-renderizar filas manteniendo el filtro activo
            filtrarHistorialModal(window._filtroActivo || 'todos');

            // Cerrar modal de nota
            const modalEl = document.getElementById('modalEditarNotaAsistencia');
            const modalObj = bootstrap.Modal.getInstance(modalEl);
            if (modalObj) modalObj.hide();
        } else {
            alert(res.message || 'Error al guardar la nota.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Guardar Nota';
        console.error("Error al guardar nota:", err);
        alert('Error al comunicarse con el servidor al guardar la nota.');
    });
}

function formatearFechaConDia(fechaStr) {
    if (!fechaStr) return { diaSemana: '--', fechaFormateada: '--' };
    const partes = String(fechaStr).split('-');
    if (partes.length !== 3) return { diaSemana: '', fechaFormateada: fechaStr };
    const anio = parseInt(partes[0], 10);
    const mes = parseInt(partes[1], 10) - 1;
    const dia = parseInt(partes[2], 10);
    const fechaObj = new Date(anio, mes, dia);
    
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    
    const diaSemana = dias[fechaObj.getDay()] || '';
    const fechaFormateada = `${dia} ${meses[mes]} ${anio}`;
    return { diaSemana, fechaFormateada };
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
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeAttr(str) {
    return String(str || '')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '\\\'')
        .replace(/\n/g, ' ');
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

