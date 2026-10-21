<?php
/**
 * Vista de Presentación: Gestión y Evaluación de Excusas Médicas (Instructor)
 * Sistema de Control de Asistencia SENA
 * 
 * @var string      $tituloPagina
 * @var array       $excusasPendientes
 * @var array       $excusasHistorial
 * @var string|null $mensaje
 * @var string|null $error
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=instructor_excusas');
    exit();
}

$tituloPagina      = $tituloPagina ?? 'Gestión de Excusas Médicas';
$excusasPendientes = $excusasPendientes ?? [];
$excusasHistorial  = $excusasHistorial ?? [];
$mensajeNotif      = $mensaje ?? $_GET['mensaje'] ?? null;
$errorNotif        = $error ?? $_GET['error'] ?? null;

require_once __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(155, 89, 182, 0.15); color: #a569bd;">
                <i class="bi bi-file-earmark-medical fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Gestión de Excusas Médicas</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Auditoría, validación de soportes médicos y justificación de inasistencias de aprendices.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge-sena badge-pending">
                <i class="bi bi-clock-history"></i> <?= count($excusasPendientes) ?> Pendientes
            </span>
            <span class="badge-sena badge-on-time">
                <i class="bi bi-check-circle-fill"></i> <?= count($excusasHistorial) ?> Evaluadas
            </span>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if (!empty($mensajeNotif)): ?>
    <div class="col-12">
        <?php if ($mensajeNotif === 'aprobada' || stripos($mensajeNotif, 'aprob') !== false): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                <i class="bi bi-check-circle-fill me-2"></i> 
                La excusa médica ha sido <strong>aprobada</strong> con éxito y las inasistencias asociadas fueron justificadas.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($mensajeNotif === 'rechazada' || stripos($mensajeNotif, 'rechaz') !== false): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                La excusa médica ha sido <strong>rechazada</strong>. El registro de inasistencia se mantiene activo.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                <i class="bi bi-info-circle-fill me-2"></i> 
                <?= htmlspecialchars($mensajeNotif) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errorNotif)): ?>
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px;">
            <i class="bi bi-x-circle-fill me-2"></i> 
            Error al procesar la evaluación de la excusa médica. Intente nuevamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Excusas Pendientes -->
    <div class="col-12">
        <div class="card border-0 card-stat-ux p-4" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-hourglass-split me-2 text-warning"></i>Excusas Pendientes por Evaluar</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Revisa el soporte médico adjunto y emite tu concepto de aprobación o rechazo</p>
                </div>
                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-1.5 rounded-pill" style="font-size: 12px; font-weight: 600;">
                    Requieren Acción Inmediata
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha Inasistencia</th>
                            <th>Radicada El</th>
                            <th>Soporte Adjunto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasPendientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-shield-check fs-1 text-success mb-2 d-block"></i>
                                ¡Al día! No hay excusas médicas pendientes por revisar.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasPendientes as $e): 
                        $fechaInasist = !empty($e['fechaInasistencia']) && strtotime($e['fechaInasistencia']) ? date('d M Y', strtotime($e['fechaInasistencia'])) : ($e['fechaInasistencia'] ?? 'N/A');
                        $fechaRadic = !empty($e['fechaRadicado']) && strtotime($e['fechaRadicado']) ? date('d M Y', strtotime($e['fechaRadicado'])) : ($e['fechaRadicado'] ?? 'N/A');
                        $soporteArchivo = $e['soporte'] ?? 'sin_archivo';
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz'] ?? 'Aprendiz') ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                                    <div>
                                        <strong class="text-theme-primary d-block"><?= htmlspecialchars($e['aprendiz'] ?? 'Sin nombre') ?></strong>
                                        <?php if (!empty($e['motivo'])): ?>
                                            <span class="badge bg-secondary text-light small" style="font-size: 10px;"><?= htmlspecialchars($e['motivo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><span class="text-theme-primary"><?= htmlspecialchars($e['ficha'] ?? 'N/A') ?></span></td>
                            <td>
                                <span class="badge-sena badge-absent">
                                    <i class="bi bi-calendar-x"></i> <?= htmlspecialchars($fechaInasist) ?>
                                </span>
                            </td>
                            <td><span class="text-secondary"><?= htmlspecialchars($fechaRadic) ?></span></td>
                            <td>
                                <?php if ($soporteArchivo && $soporteArchivo !== 'sin_archivo'): ?>
                                    <button type="button" class="btn btn-sm btn-sena-outline" onclick="abrirVisorSoporte(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'] ?? 'Aprendiz')) ?>', '<?= htmlspecialchars(addslashes($soporteArchivo)) ?>', '<?= htmlspecialchars(addslashes($fechaInasist)) ?>', false, '<?= htmlspecialchars(addslashes($e['observaciones_aprendiz'] ?? '')) ?>')">
                                        <i class="bi bi-file-earmark-text text-info me-1"></i> <?= htmlspecialchars(basename($soporteArchivo)) ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-secondary small fst-italic">Sin archivo adjunto</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-sena-green me-1" title="Revisar Excusa" onclick="abrirVisorSoporte(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'] ?? 'Aprendiz')) ?>', '<?= htmlspecialchars(addslashes($soporteArchivo)) ?>', '<?= htmlspecialchars(addslashes($fechaInasist)) ?>', false, '<?= htmlspecialchars(addslashes($e['observaciones_aprendiz'] ?? '')) ?>')">
                                    <i class="bi bi-eye me-1"></i> Revisar
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_excusas" class="d-inline">
                                    <input type="hidden" name="action" value="rechazar">
                                    <input type="hidden" name="excusa_id" value="<?= (int)$e['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Rechazar Excusa" onclick="return confirm('¿Rechazar esta excusa médica?')">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historial de Excusas -->
    <div class="col-12">
        <div class="card border-0 card-stat-ux p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-journal-text me-2 text-success"></i>Historial de Excusas Evaluadas</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Registro histórico de justificaciones médicas aprobadas o rechazadas</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha Inasistencia</th>
                            <th>Soporte</th>
                            <th>Estado Final</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasHistorial)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">No hay historial de excusas evaluadas todavía.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasHistorial as $e): 
                        $fechaInasist = !empty($e['fechaInasistencia']) && strtotime($e['fechaInasistencia']) ? date('d M Y', strtotime($e['fechaInasistencia'])) : ($e['fechaInasistencia'] ?? 'N/A');
                        $soporteArchivo = $e['soporte'] ?? 'sin_archivo';
                        $estadoExc = strtolower($e['estado'] ?? '');
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz'] ?? 'Aprendiz') ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <div>
                                        <strong class="text-theme-primary d-block"><?= htmlspecialchars($e['aprendiz'] ?? 'Aprendiz') ?></strong>
                                        <?php if (!empty($e['motivo'])): ?>
                                            <span class="badge bg-secondary text-light small" style="font-size: 10px;"><?= htmlspecialchars($e['motivo']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($e['ficha'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($fechaInasist) ?></td>
                            <td>
                                <?php if ($soporteArchivo && $soporteArchivo !== 'sin_archivo'): ?>
                                    <button type="button" class="btn btn-sm btn-sena-outline" onclick="abrirVisorSoporte(<?= (int)$e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'] ?? 'Aprendiz')) ?>', '<?= htmlspecialchars(addslashes($soporteArchivo)) ?>', '<?= htmlspecialchars(addslashes($fechaInasist)) ?>', true, '<?= htmlspecialchars(addslashes($e['observaciones_aprendiz'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($e['observaciones_instructor'] ?? '')) ?>')">
                                        <i class="bi bi-paperclip me-1"></i><?= htmlspecialchars(basename($soporteArchivo)) ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-secondary small">Sin soporte</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (stripos($estadoExc, 'aprob') !== false): ?>
                                    <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Aprobada</span>
                                <?php elseif (stripos($estadoExc, 'rechaz') !== false): ?>
                                    <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> Rechazada</span>
                                <?php else: ?>
                                    <span class="badge-sena badge-pending"><i class="bi bi-clock"></i> <?= htmlspecialchars($e['estado']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Flotante Oscuro: Visor de Soportes Médicos y Evaluación Inmediata -->
<div class="modal fade" id="modalVisorSoporte" tabindex="-1" aria-labelledby="modalVisorSoporteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
      <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
        <div>
            <h5 class="modal-title text-theme-primary fw-bold" id="modalVisorSoporteLabel">
                <i class="bi bi-file-earmark-medical-fill text-success me-2"></i>Auditoría de Soporte Médico
            </h5>
            <small class="text-secondary d-block mt-0.5" id="subtituloModalSoporte"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
      </div>
      <div class="modal-body p-3" style="color: var(--nk-text-primary);">
        <div id="contenedorVisor" class="doc-preview-container mb-3">
            <!-- El contenido dinámico (iframe o img) se insertará aquí por JS -->
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-file-earmark-arrow-up fs-1 mb-2 d-block"></i>
                Cargando vista previa del documento...
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center small text-secondary px-1 mb-3">
            <span id="nombreArchivoModal"><i class="bi bi-paperclip me-1"></i> Archivo soporte</span>
            <a id="enlaceDescargaModal" href="#" target="_blank" class="text-info text-decoration-none">
                <i class="bi bi-box-arrow-up-right me-1"></i> Abrir en ventana externa
            </a>
        </div>

        <!-- Observaciones del Aprendiz (si existen) -->
        <div id="contenedorObsAprendiz" class="p-2 mb-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--nk-border-color); display: none;">
            <span class="small fw-bold text-secondary d-block"><i class="bi bi-chat-left-quote me-1"></i>Comentario del Aprendiz:</span>
            <p id="textoObsAprendiz" class="small mb-0 text-theme-primary"></p>
        </div>

        <!-- Observaciones previas del Instructor (en Historial) -->
        <div id="contenedorObsInstructor" class="p-2 mb-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid var(--nk-border-color); display: none;">
            <span class="small fw-bold text-secondary d-block"><i class="bi bi-person-check me-1"></i>Concepto del Instructor:</span>
            <p id="textoObsInstructor" class="small mb-0 text-theme-primary"></p>
        </div>

        <!-- Input de Observaciones para la Evaluación -->
        <div id="contenedorInputObservaciones" class="mt-2">
            <label for="observacionesEvaluacion" class="form-label small text-secondary mb-1">
                <i class="bi bi-pencil-square me-1"></i>Observaciones o Justificación de la Decisión:
            </label>
            <textarea class="form-control form-control-sm text-theme-primary" id="observacionesEvaluacion" rows="2" placeholder="Escriba aquí sus comentarios o motivo del concepto..." style="background-color: rgba(255,255,255,0.05); border-color: var(--nk-border-color); color: inherit;"></textarea>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between align-items-center" style="border-color: var(--nk-border-color) !important;">
        <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cerrar</button>
        <div id="accionesModalEvaluacion" class="d-flex gap-2">
            <!-- Formularios para aprobar o rechazar directamente desde el visor -->
            <form method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_excusas" class="d-inline" onsubmit="sincronizarObservaciones(this)">
                <input type="hidden" name="action" value="rechazar">
                <input type="hidden" name="excusa_id" id="excusaIdRechazarModal" value="">
                <input type="hidden" name="observaciones" value="">
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Confirmas el rechazo de esta excusa médica?')">
                    <i class="bi bi-x-circle me-1"></i> Rechazar Excusa
                </button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_excusas" class="d-inline" onsubmit="sincronizarObservaciones(this)">
                <input type="hidden" name="action" value="aprobar">
                <input type="hidden" name="excusa_id" id="excusaIdAprobarModal" value="">
                <input type="hidden" name="observaciones" value="">
                <button type="submit" class="btn btn-sena-green">
                    <i class="bi bi-check-circle me-1"></i> Aprobar y Justificar
                </button>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function sincronizarObservaciones(form) {
    var texto = document.getElementById('observacionesEvaluacion').value;
    if (form.querySelector('input[name="observaciones"]')) {
        form.querySelector('input[name="observaciones"]').value = texto;
    }
}

function abrirVisorSoporte(id, aprendiz, soporte, fecha, esHistorial, obsAprendiz, obsInstructor) {
    document.getElementById('subtituloModalSoporte').textContent = 'Aprendiz: ' + aprendiz + ' | Inasistencia: ' + fecha;
    document.getElementById('excusaIdAprobarModal').value = id;
    document.getElementById('excusaIdRechazarModal').value = id;
    document.getElementById('observacionesEvaluacion').value = '';
    
    var baseUrl = '<?= BASE_URL ?>';
    var rutaArchivo = baseUrl + '/public/uploads/' + encodeURIComponent(soporte);
    var enlaceDescarga = document.getElementById('enlaceDescargaModal');
    var nombreArchivo = document.getElementById('nombreArchivoModal');
    var visor = document.getElementById('contenedorVisor');
    var contenedorAcciones = document.getElementById('accionesModalEvaluacion');
    var contenedorInputObs = document.getElementById('contenedorInputObservaciones');

    // Manejo de Observaciones del Aprendiz
    var contObsApr = document.getElementById('contenedorObsAprendiz');
    var txtObsApr = document.getElementById('textoObsAprendiz');
    if (obsAprendiz && obsAprendiz.trim() !== '') {
        txtObsApr.textContent = obsAprendiz;
        contObsApr.style.display = 'block';
    } else {
        contObsApr.style.display = 'none';
    }

    // Manejo de Observaciones previas del Instructor (Historial)
    var contObsInst = document.getElementById('contenedorObsInstructor');
    var txtObsInst = document.getElementById('textoObsInstructor');
    if (obsInstructor && obsInstructor.trim() !== '') {
        txtObsInst.textContent = obsInstructor;
        contObsInst.style.display = 'block';
    } else {
        contObsInst.style.display = 'none';
    }

    if (esHistorial) {
        contenedorAcciones.style.display = 'none';
        contenedorInputObs.style.display = 'none';
    } else {
        contenedorAcciones.style.display = 'flex';
        contenedorInputObs.style.display = 'block';
    }

    nombreArchivo.innerHTML = '<i class="bi bi-paperclip me-1"></i> ' + soporte;
    enlaceDescarga.href = rutaArchivo;

    if (!soporte || soporte === 'sin_archivo') {
        visor.innerHTML = '<div class="text-center py-5 text-secondary"><i class="bi bi-exclamation-triangle fs-1 text-warning mb-2 d-block"></i>No se adjuntó archivo soporte digital para esta excusa.</div>';
    } else {
        var extension = soporte.split('.').pop().toLowerCase();
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(extension) !== -1) {
            visor.innerHTML = '<img src="' + rutaArchivo + '" alt="Soporte Médico" class="img-fluid rounded" style="max-height: 480px; object-fit: contain;">';
        } else if (extension === 'pdf') {
            visor.innerHTML = '<iframe src="' + rutaArchivo + '#toolbar=0" style="width: 100%; height: 480px; border: none; border-radius: 6px;"></iframe>';
        } else {
            visor.innerHTML = '<div class="text-center py-5 text-secondary"><i class="bi bi-file-earmark-arrow-down fs-1 text-info mb-2 d-block"></i>Formato no previsualizable directamente.<br><a href="' + rutaArchivo + '" target="_blank" class="btn btn-sm btn-sena-green mt-3">Descargar Archivo</a></div>';
        }
    }

    var modal = new bootstrap.Modal(document.getElementById('modalVisorSoporte'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
