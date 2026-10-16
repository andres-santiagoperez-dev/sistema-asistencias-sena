<?php
/**
 * Vista de Presentación: Mis Excusas Médicas (Aprendiz)
 * Sistema de Control de Asistencia SENA
 * 
 * @var string      $tituloPagina
 * @var array       $infoAprendiz
 * @var array       $marcacionHoy
 * @var array       $inasistenciasPendientes
 * @var array       $historialExcusas
 * @var string|null $mensaje
 * @var string|null $error
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=aprendiz_excusas');
    exit();
}

$tituloPagina            = $tituloPagina ?? 'Mis Excusas Médicas';
$infoAprendiz            = $infoAprendiz ?? [];
$marcacionHoy            = $marcacionHoy ?? [];
$inasistenciasPendientes = $inasistenciasPendientes ?? [];
$historialExcusas        = $historialExcusas ?? [];
$mensajeNotif            = $mensaje ?? $_GET['mensaje'] ?? null;
$errorNotif              = $error ?? $_GET['error'] ?? null;

require_once dirname(__DIR__) . '/layouts/header-aprendiz.php';
?>

<style>
    .timeline-excusa {
        position: relative;
        padding-left: 28px;
        margin-bottom: 22px;
        border-left: 2px solid var(--nk-border-color);
    }
    .timeline-excusa:last-child {
        margin-bottom: 0;
    }
    .timeline-excusa::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 2px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: var(--nk-card-bg);
        border: 2px solid var(--nk-border-color);
    }
    .timeline-excusa.aprobada::before, .timeline-excusa.aprobado::before { border-color: #24caa1; background: #24caa1; }
    .timeline-excusa.pendiente::before { border-color: #f39c12; background: #f39c12; }
    .timeline-excusa.rechazada::before, .timeline-excusa.rechazado::before { border-color: #eb4b4b; background: #eb4b4b; }
    
    .form-control-sena {
        background-color: rgba(255, 255, 255, 0.04);
        border: 1px solid var(--nk-border-color);
        color: var(--nk-text-primary, #fff);
    }
    .form-control-sena:focus {
        background-color: rgba(255, 255, 255, 0.08);
        border-color: #24caa1;
        color: var(--nk-text-primary, #fff);
        box-shadow: none;
    }
    .form-control-sena option {
        background-color: var(--nk-card-bg, #1a202c);
        color: var(--nk-text-primary, #fff);
    }
</style>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(155, 89, 182, 0.15); color: #a569bd;">
                <i class="bi bi-file-earmark-medical fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Gestión de Excusas Médicas</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Radica tus soportes e incapacidades para justificar inasistencias y consulta su trazabilidad.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge-sena badge-pending">
                <i class="bi bi-calendar-x"></i> <?= count($inasistenciasPendientes) ?> Inasistencias por Justificar
            </span>
            <span class="badge-sena badge-on-time">
                <i class="bi bi-archive"></i> <?= count($historialExcusas) ?> Radicadas
            </span>
        </div>
    </div>
</div>

<!-- Alertas y Mensajes de Retroalimentación -->
<div class="row g-4 mb-4">
    <?php if ($mensajeNotif === 'radicada'): ?>
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
            <i class="bi bi-check-circle-fill me-2"></i> 
            <strong>¡Excusa radicada exitosamente!</strong> Tu soporte quedó registrado con estado <strong>Pendiente</strong> y será evaluado por tu instructor asignado.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errorNotif)): ?>
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 8px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php 
                switch ($errorNotif) {
                    case 'datos_incompletos':
                        echo 'Por favor selecciona al menos una inasistencia y especifica el motivo de tu excusa.';
                        break;
                    case 'formato_invalido':
                        echo 'Formato de archivo no válido. Solo se admiten archivos PDF o imágenes (JPG, JPEG, PNG, WEBP).';
                        break;
                    case 'archivo_muy_grande':
                        echo 'El archivo soporte supera el límite máximo permitido de 5 Megabytes (MB).';
                        break;
                    case 'aprendiz_no_encontrado':
                        echo 'No fue posible identificar tu matrícula activa de aprendiz en el sistema.';
                        break;
                    default:
                        echo 'Ocurrió un error al intentar radicar la excusa médica. Intenta nuevamente.';
                        break;
                }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-5">
    <!-- Columna Izquierda: Formulario de Radicación de Excusas -->
    <div class="col-lg-7 col-md-12">
        <div class="card border-0 card-stat-ux h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-cloud-arrow-up text-success me-2"></i>Radicar Nueva Excusa</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Diligencia los datos y adjunta tu soporte médico digital</p>
                </div>
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-1 rounded-pill" style="font-size: 11px;">
                    Portal Aprendiz
                </span>
            </div>

            <form action="<?= BASE_URL ?>/index.php?page=aprendiz_excusas" method="POST" enctype="multipart/form-data" onsubmit="return validarRadicacion(this)">
                <div class="row g-3">
                    <!-- Selección de Inasistencias -->
                    <div class="col-12">
                        <label class="form-label text-secondary fw-semibold small mb-1">
                            <i class="bi bi-check2-square me-1 text-info"></i>Selecciona las Inasistencias a Justificar: <span class="text-danger">*</span>
                        </label>

                        <?php if (empty($inasistenciasPendientes)): ?>
                            <div class="p-4 text-center rounded border border-secondary border-opacity-25 my-2" style="background: rgba(255,255,255,0.02);">
                                <i class="bi bi-shield-check fs-2 text-success mb-2 d-block"></i>
                                <span class="text-theme-primary fw-semibold d-block">¡Estás al día!</span>
                                <small class="text-secondary">No registras inasistencias pendientes por excusar en el sistema.</small>
                            </div>
                        <?php else: ?>
                            <div class="border rounded p-3 mb-2" style="max-height: 210px; overflow-y: auto; background-color: rgba(255,255,255,0.02); border-color: var(--nk-border-color) !important;">
                                <?php foreach ($inasistenciasPendientes as $ina): 
                                    $fechaFormato = !empty($ina['fecha']) && strtotime($ina['fecha']) ? date('d M Y', strtotime($ina['fecha'])) : $ina['fecha'];
                                    $horaEntrada  = !empty($ina['hora_entrada']) ? date('h:i A', strtotime($ina['hora_entrada'])) : 'Jornada';
                                ?>
                                    <div class="form-check p-2 rounded mb-1 d-flex align-items-center" style="transition: background 0.2s;">
                                        <input class="form-check-input ms-0 me-2.5" type="checkbox" name="inasistencias[]" value="<?= (int)$ina['id_registro'] ?>" id="ina_<?= (int)$ina['id_registro'] ?>" style="cursor: pointer;">
                                        <label class="form-check-label text-theme-primary w-100 d-flex justify-content-between align-items-center" for="ina_<?= (int)$ina['id_registro'] ?>" style="cursor: pointer;">
                                            <span>
                                                <i class="bi bi-calendar-x text-danger me-1"></i>
                                                Sesión: <strong><?= htmlspecialchars($fechaFormato) ?></strong>
                                            </span>
                                            <span class="badge bg-secondary text-light small" style="font-size:10px;">
                                                <?= htmlspecialchars($horaEntrada) ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-secondary d-block"><i class="bi bi-info-circle me-1"></i>Puedes marcar varias inasistencias si corresponden a una misma incapacidad continua.</small>
                        <?php endif; ?>
                    </div>

                    <!-- Motivo -->
                    <div class="col-md-6 col-12">
                        <label for="motivo" class="form-label text-secondary fw-semibold small mb-1">
                            <i class="bi bi-card-checklist me-1 text-info"></i>Motivo Principal: <span class="text-danger">*</span>
                        </label>
                        <select name="motivo" id="motivo" class="form-select form-control-sena text-theme-primary" required <?= empty($inasistenciasPendientes) ? 'disabled' : '' ?>>
                            <option value="" disabled selected>Selecciona un motivo...</option>
                            <option value="Enfermedad">Incapacidad Médica / Enfermedad</option>
                            <option value="Cita Medica">Cita Médica Especializada</option>
                            <option value="Calamidad">Calamidad Doméstica</option>
                            <option value="Otro">Otro Motivo de Fuerza Mayor</option>
                        </select>
                    </div>

                    <!-- Adjuntar Soporte Local -->
                    <div class="col-md-6 col-12">
                        <label for="soporte" class="form-label text-secondary fw-semibold small mb-1">
                            <i class="bi bi-paperclip me-1 text-info"></i>Soporte Digital: <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="soporte" id="soporte" class="form-control form-control-sena text-theme-primary" accept=".pdf,.jpg,.jpeg,.png,.webp" required <?= empty($inasistenciasPendientes) ? 'disabled' : '' ?>>
                        <small class="text-secondary mt-1 d-block" style="font-size: 11px;">Formatos: PDF, JPG, PNG (Máx 5MB)</small>
                    </div>

                    <!-- Observaciones Adicionales -->
                    <div class="col-12">
                        <label for="observaciones" class="form-label text-secondary fw-semibold small mb-1">
                            <i class="bi bi-chat-text me-1 text-info"></i>Observaciones o Detalles del Diagnóstico (Opcional):
                        </label>
                        <textarea name="observaciones" id="observaciones" rows="3" class="form-control form-control-sena text-theme-primary" placeholder="Describe brevemente la situación o entidad médica que expidió el soporte..." <?= empty($inasistenciasPendientes) ? 'disabled' : '' ?>></textarea>
                    </div>

                    <!-- Botón Enviar -->
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-sena-green w-100 py-2.5 fw-bold" <?= empty($inasistenciasPendientes) ? 'disabled' : '' ?>>
                            <i class="bi bi-send-check me-2"></i>Radicar Excusa Médica
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Columna Derecha: Timeline Histórico de Excusas Radicadas -->
    <div class="col-lg-5 col-md-12">
        <div class="card border-0 card-stat-ux h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-clock-history text-info me-2"></i>Estado de Mis Excusas</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Trazabilidad y conceptos emitidos por tus instructores</p>
                </div>
                <span class="badge bg-secondary text-light px-2.5 py-1 rounded-pill" style="font-size:11px;">
                    <?= count($historialExcusas) ?> Total
                </span>
            </div>

            <div class="card-body p-0 mt-2" style="max-height: 520px; overflow-y: auto;">
                <?php if (empty($historialExcusas)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-folder-x fs-1 mb-2 d-block text-secondary opacity-50"></i>
                        <span class="fw-semibold text-theme-primary d-block">Sin Radicaciones Previas</span>
                        <small>Aún no has radicado ninguna excusa médica en la plataforma.</small>
                    </div>
                <?php else: ?>
                    <div class="timeline-container ps-1">
                        <?php foreach ($historialExcusas as $exc): 
                            $estadoClase = strtolower($exc['estado'] ?? 'pendiente');
                            $esAprobada  = stripos($estadoClase, 'aprob') !== false;
                            $esRechazada = stripos($estadoClase, 'rechaz') !== false;
                            $claseTimeline = $esAprobada ? 'aprobada' : ($esRechazada ? 'rechazada' : 'pendiente');

                            $fechaRadicado = !empty($exc['fecha_radicado']) && strtotime($exc['fecha_radicado']) ? date('d M Y', strtotime($exc['fecha_radicado'])) : 'N/A';
                            $fechaInicio   = !empty($exc['fecha_inicio']) && strtotime($exc['fecha_inicio']) ? date('d M Y', strtotime($exc['fecha_inicio'])) : 'N/A';
                            $fechaFin      = !empty($exc['fecha_fin']) && strtotime($exc['fecha_fin']) ? date('d M Y', strtotime($exc['fecha_fin'])) : $fechaInicio;
                            $soporte       = $exc['soporte'] ?? 'sin_archivo';
                        ?>
                            <div class="timeline-excusa <?= $claseTimeline ?>">
                                <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                    <strong class="text-theme-primary" style="font-size:14px;"><?= htmlspecialchars($exc['motivo'] ?? 'Excusa Médica') ?></strong>
                                    <span class="badge text-secondary small" style="background: rgba(255,255,255,0.06); font-size:10px;">
                                        <?= htmlspecialchars($fechaRadicado) ?>
                                    </span>
                                </div>

                                <p class="text-secondary small mb-1">
                                    <i class="bi bi-calendar-range me-1 text-info"></i>
                                    Del <strong><?= htmlspecialchars($fechaInicio) ?></strong> al <strong><?= htmlspecialchars($fechaFin) ?></strong>
                                    <?php if (!empty($exc['total_inasistencias'])): ?>
                                        <span class="ms-1 text-light opacity-75">(<?= (int)$exc['total_inasistencias'] ?> sesión<?= (int)$exc['total_inasistencias'] > 1 ? 'es' : '' ?>)</span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($exc['instructor_evaluador'])): ?>
                                    <div class="small mb-1">
                                        <span class="text-secondary" style="font-size:11px;"><i class="bi bi-person-badge me-1 text-info"></i>Instructor del bloque: </span>
                                        <strong class="text-theme-primary" style="font-size:12px;"><?= htmlspecialchars($exc['instructor_evaluador']) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                                    <!-- Badge de Estado -->
                                    <?php if ($esAprobada): ?>
                                        <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill me-1"></i>Aprobada</span>
                                    <?php elseif ($esRechazada): ?>
                                        <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill me-1"></i>Rechazada</span>
                                    <?php else: ?>
                                        <span class="badge-sena badge-pending"><i class="bi bi-clock-history me-1"></i>Pendiente por Evaluar</span>
                                    <?php endif; ?>

                                    <!-- Enlace a Soporte Físico Local -->
                                    <?php if ($soporte && $soporte !== 'sin_archivo'): ?>
                                        <a href="<?= BASE_URL ?>/public/uploads/<?= rawurlencode($soporte) ?>" target="_blank" class="btn btn-sm btn-sena-outline py-0.5 px-2" style="font-size:11px;" title="Ver documento soporte">
                                            <i class="bi bi-paperclip me-1 text-info"></i>Ver Soporte
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Observación del Aprendiz si existe -->
                                <?php if (!empty($exc['observaciones_aprendiz'])): ?>
                                    <div class="mt-2 p-2 rounded small" style="background: rgba(255,255,255,0.02); border-left: 2px solid #a569bd;">
                                        <span class="text-secondary d-block" style="font-size:10px;"><i class="bi bi-chat-quote me-1"></i>Mi Nota:</span>
                                        <span class="text-theme-primary" style="font-size:12px;"><?= htmlspecialchars($exc['observaciones_aprendiz']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Retroalimentación / Concepto del Instructor si fue evaluada -->
                                <?php if (!empty($exc['observaciones_instructor'])): ?>
                                    <div class="mt-2 p-2 rounded small" style="background: rgba(255,255,255,0.03); border-left: 2px solid <?= $esAprobada ? '#24caa1' : '#eb4b4b' ?>;">
                                        <span class="text-secondary d-block" style="font-size:10px;">
                                            <i class="bi bi-person-check me-1"></i>Concepto Instructor<?= !empty($exc['instructor_evaluador']) ? ' (' . htmlspecialchars($exc['instructor_evaluador']) . ')' : '' ?>:
                                        </span>
                                        <span class="text-theme-primary" style="font-size:12px;"><?= htmlspecialchars($exc['observaciones_instructor']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function validarRadicacion(form) {
    var checkboxes = form.querySelectorAll('input[name="inasistencias[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Por favor selecciona al menos una inasistencia para justificar con tu excusa médica.');
        return false;
    }
    return true;
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer-aprendiz.php'; ?>
