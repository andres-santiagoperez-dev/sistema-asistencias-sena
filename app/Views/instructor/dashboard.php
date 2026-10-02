<?php
/**
 * Vista de Presentación: Dashboard del Instructor
 * 
 * @var string $tituloPagina
 * @var array  $stats
 * @var array  $misFichas
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=instructor_dashboard');
    exit();
}

$tituloPagina = $tituloPagina ?? 'Mis Fichas';
$stats = $stats ?? [
    'fichasAsignadas'   => 0,
    'aprendicesACargo'  => 0,
    'retardosHoy'       => 0,
    'excusasPendientes' => 0,
];
$misFichas = $misFichas ?? [];

$flashMsg = $_SESSION['flash_msg'] ?? null;
unset($_SESSION['flash_msg']);

require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Flash message alert -->
<?php if (!empty($flashMsg)): ?>
    <div class="alert alert-<?= htmlspecialchars($flashMsg['type'] ?? 'info') ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius:10px;">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flashMsg['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(57, 169, 0, 0.15); color: #39a900;">
                <i class="bi bi-journal-bookmark-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Mis Fichas de Formación</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Gestión de fichas asignadas, horarios de formación y aprendices a cargo.</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/index.php?page=instructor_horario" class="btn btn-sena-outline">
                <i class="bi bi-calendar-check me-1"></i> Mi Horario
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green">
                <i class="bi bi-door-open me-1"></i> Control de Ingresos Hoy
            </a>
            <a href="<?= BASE_URL ?>/index.php?page=instructor_excusas" class="btn btn-sena-outline">
                <i class="bi bi-file-earmark-medical me-1"></i> Ver Excusas
                <?php if ($stats['excusasPendientes'] > 0): ?>
                    <span class="badge bg-danger ms-1"><?= $stats['excusasPendientes'] ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #24b5ca !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">FICHAS ASIGNADAS</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)$stats['fichasAsignadas'] ?></h3>
                    <small class="text-info"><i class="bi bi-journal-bookmark-fill me-1"></i>Activas en sistema</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(36, 181, 202, 0.1); color: #24b5ca;">
                    <i class="bi bi-journal-bookmark-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #24caa1 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">APRENDICES A CARGO</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)$stats['aprendicesACargo'] ?></h3>
                    <small class="text-success"><i class="bi bi-people-fill me-1"></i>Matriculados</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(36, 202, 161, 0.1); color: #24caa1;">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">RETARDOS DE HOY</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)$stats['retardosHoy'] ?></h3>
                    <small class="text-warning"><i class="bi bi-clock-history me-1"></i>Ingresos posteriores</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6">
        <div class="card border-0 p-3 card-stat-ux h-100" style="border-left: 4px solid #9b59b6 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size: 11px;">EXCUSAS PENDIENTES</small>
                    <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)$stats['excusasPendientes'] ?></h3>
                    <small style="color: #9b59b6;"><i class="bi bi-file-earmark-medical-fill me-1"></i>Por revisar</small>
                </div>
                <div class="p-2.5 rounded-3" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                    <i class="bi bi-file-earmark-medical-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fichas y Horarios Card -->
<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-mortarboard me-2 text-success"></i>Mis Fichas de Formación</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Grupos asignados y horarios de jornada formativa con tolerancias configuradas</p>
        </div>
        <span class="badge bg-dark border border-secondary border-opacity-50 px-3 py-2 text-theme-primary" style="font-size:12px;">
            <i class="bi bi-calendar-check text-info me-1"></i> <?= count($misFichas) ?> Ficha(s) Activa(s)
        </span>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Haga clic en el botón <strong class="text-warning"><i class="bi bi-clock-history"></i> Tolerancia</strong> de cualquier ficha para ajustar el permiso de entrada sin retardo o la hora de salida permitida.
        </div>
    </div>

    <div class="w-100" style="overflow-x: hidden;">
        <table class="table table-nalika-dark table-hover w-100 align-middle mb-0" style="table-layout: fixed; width: 100%;">
            <thead>
                <tr>
                    <th style="width: 12%;">Ficha</th>
                    <th style="width: 28%;">Programa de Formación</th>
                    <th style="width: 14%;">Jornada</th>
                    <th style="width: 22%;">Horario Permito</th>
                    <th style="width: 10%;">Aprendices</th>
                    <th style="width: 14%; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($misFichas)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No tienes fichas asignadas todavía.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($misFichas as $f): ?>
                <tr>
                    <td style="padding: 10px 8px;"><strong class="text-theme-primary" style="font-size:13px;"><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td style="padding: 10px 8px;">
                        <span class="fw-semibold text-theme-primary d-block text-truncate" style="font-size:12.5px;" title="<?= htmlspecialchars($f['programa']) ?>"><?= htmlspecialchars($f['programa']) ?></span>
                    </td>
                    <td style="padding: 10px 8px;">
                        <?php if (!empty($f['tiene_clase_hoy'])): ?>
                            <span class="badge" style="background-color: #39a900; color: white; font-size: 11px;">
                                <i class="bi bi-calendar2-check me-1"></i><?= htmlspecialchars($f['jornada']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge" style="background-color: #007934; color: white; font-size: 11px;">
                                <?= htmlspecialchars($f['jornada']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 10px 8px;">
                        <div>
                            <span class="text-info fw-semibold" style="font-size:12px;"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></span>
                            <small class="d-block text-success mt-0.5 text-truncate" style="font-size:11px;" title="Entrada A Tiempo hasta: <?= htmlspecialchars($f['limite_entrada'] ?? $f['entrada']) ?>">
                                <i class="bi bi-shield-check me-1"></i>Hasta: <strong><?= htmlspecialchars($f['limite_entrada'] ?? $f['entrada']) ?></strong> (<?= htmlspecialchars($f['texto_tolerancia_entrada'] ?? '0m') ?>)
                            </small>
                        </div>
                    </td>
                    <td style="padding: 10px 8px;">
                        <span class="badge-sena badge-on-time py-1 px-2" style="font-size:11px;">
                            <i class="bi bi-people-fill"></i> <?= (int)$f['aprendices'] ?> apr.
                        </span>
                    </td>
                    <td class="text-end" style="padding: 10px 8px;">
                        <div class="d-inline-flex gap-1 justify-content-end align-items-center">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_aprendices&ficha=<?= urlencode($f['codigo']) ?>" class="btn btn-sm btn-sena-green py-0.5 px-2 font-size-11" style="font-size:11px;" title="Ver aprendices">
                                <i class="bi bi-people me-1"></i>Apr.
                            </a>
                            <button type="button" 
                                    class="btn btn-sm btn-warning text-dark fw-bold py-0.5 px-2 font-size-11" 
                                    style="font-size:11px;"
                                    onclick="abrirModalTolerancia(<?= (int)$f['id_ficha'] ?>, '<?= htmlspecialchars($f['codigo']) ?>', '<?= htmlspecialchars(addslashes($f['programa'])) ?>', '<?= htmlspecialchars($f['entrada']) ?>', '<?= htmlspecialchars($f['salida']) ?>', '<?= htmlspecialchars($f['raw_hora_entrada'] ?? '07:00:00') ?>', '<?= htmlspecialchars($f['raw_hora_salida'] ?? '13:00:00') ?>', <?= (int)($f['minutos_retardo'] ?? 0) ?>, <?= (int)($f['minutos_salida'] ?? 0) ?>)" 
                                    title="Modificar Tolerancia de Horario">
                                <i class="bi bi-clock-history me-1"></i>Tol.
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&ficha=<?= urlencode($f['codigo'] . ' - ' . $f['programa']) ?>" class="btn btn-sm btn-sena-outline py-0.5 px-2 font-size-11" style="font-size:11px;" title="Monitorear Asistencia">
                                <i class="bi bi-door-open"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Ajustar Tolerancia de Horario (Entrada / Salida) -->
<div class="modal fade" id="modalToleranciaHorario" tabindex="-1" aria-labelledby="modalToleranciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: var(--nk-bg-card, #1b2a47); color: var(--nk-text-primary, #fff); border-radius: 12px;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold" id="modalToleranciaLabel">
                    <i class="bi bi-clock-history text-warning me-2"></i>Ajustar Tolerancia y Horario de Ficha
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formToleranciaHorario" method="POST" action="<?= BASE_URL ?>/index.php?page=<?= htmlspecialchars($_GET['page'] ?? 'instructor_dashboard') ?>&action=guardar_tolerancia">
                <input type="hidden" name="id_ficha" id="modalIdFicha">
                <input type="hidden" name="minutos_retardo" id="modalMinutosRetardoCalculados" value="0">
                <input type="hidden" name="minutos_salida" id="modalMinutosSalidaCalculados" value="0">
                <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($_GET['page'] ?? 'instructor_dashboard') ?>">

                <div class="modal-body p-4">
                    <div class="alert alert-dark border border-secondary border-opacity-25 mb-3" style="background: rgba(0,0,0,0.25); border-radius: 10px;">
                        <span class="badge bg-success mb-1" id="modalBadgeFicha">Ficha: ---</span>
                        <h6 class="fw-bold mb-1 text-white" id="modalProgramaFicha">Programa de Formación</h6>
                        <small class="text-info d-block" id="modalHorarioFicha">
                            <i class="bi bi-clock me-1"></i> Horario oficial: --:-- AM - --:-- PM
                        </small>
                    </div>

                    <!-- 1. TOLERANCIA DE ENTRADA -->
                    <div class="mb-4">
                        <label class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-in-right text-success me-1"></i> Tolerancia de Entrada (Permiso de Retardo):
                        </label>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Horas de tolerancia:</label>
                                <div class="input-group">
                                    <input type="number" id="inputHorasRetardo" min="0" max="12" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutos()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">horas</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Minutos adicionales:</label>
                                <div class="input-group">
                                    <input type="number" id="inputMinutosRetardoInput" min="0" max="59" class="form-control text-white fw-bold bg-dark border-secondary" value="15" oninput="calcularDesdeHorasMinutos()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">min</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selección directa de hora límite -->
                        <div class="mb-2">
                            <label class="form-label text-secondary small mb-1"><i class="bi bi-clock-fill text-warning me-1"></i> O seleccione la Hora Máxima de Entrada A Tiempo:</label>
                            <input type="time" id="inputHoraLimiteEntrada" class="form-control text-white fw-bold bg-dark border-secondary" onchange="calcularDesdeHoraLimiteEntrada()">
                        </div>

                        <div class="form-text text-secondary mt-1 p-2.5 rounded" id="previewEntradaText" style="background: rgba(57,169,0,0.12); border-left: 3px solid #39a900; font-size:12.5px;">
                            Cargando cálculo...
                        </div>
                    </div>

                    <!-- 2. TOLERANCIA DE SALIDA -->
                    <div class="mb-3">
                        <label class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-left text-info me-1"></i> Tolerancia de Salida (Anticipación Permitida):
                        </label>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Horas de anticipación:</label>
                                <div class="input-group">
                                    <input type="number" id="inputHorasSalida" min="0" max="12" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutosSalida()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">horas</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Minutos de anticipación:</label>
                                <div class="input-group">
                                    <input type="number" id="inputMinutosSalidaInput" min="0" max="59" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutosSalida()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">min</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-text text-secondary mt-1 p-2.5 rounded" id="previewSalidaText" style="background: rgba(36,181,202,0.12); border-left: 3px solid #24b5ca; font-size:12.5px;">
                            Cargando cálculo...
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Guardar Tolerancia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rawHoraEntradaGlobal = '07:00:00';
let rawHoraSalidaGlobal  = '13:00:00';

function abrirModalTolerancia(idFicha, numeroFicha, programa, entradaStr, salidaStr, rawEntradaTime, rawSalidaTime, totalMinutosRetardo, totalMinutosSalida) {
    document.getElementById('modalIdFicha').value = idFicha;
    document.getElementById('modalBadgeFicha').textContent = 'Ficha: ' + numeroFicha;
    document.getElementById('modalProgramaFicha').textContent = programa;
    document.getElementById('modalHorarioFicha').innerHTML = '<i class="bi bi-clock me-1"></i> Horario oficial: ' + entradaStr + ' - ' + salidaStr;
    
    rawHoraEntradaGlobal = rawEntradaTime || '07:00:00';
    rawHoraSalidaGlobal  = rawSalidaTime  || '13:00:00';

    // Desglosar minutos de entrada en Horas y Minutos
    const retMins = parseInt(totalMinutosRetardo, 10) || 0;
    const hRet = Math.floor(retMins / 60);
    const mRet = retMins % 60;

    document.getElementById('inputHorasRetardo').value = hRet;
    document.getElementById('inputMinutosRetardoInput').value = mRet;

    // Desglosar minutos de salida en Horas y Minutos
    const salMins = parseInt(totalMinutosSalida, 10) || 0;
    const hSal = Math.floor(salMins / 60);
    const mSal = salMins % 60;

    document.getElementById('inputHorasSalida').value = hSal;
    document.getElementById('inputMinutosSalidaInput').value = mSal;

    calcularDesdeHorasMinutos();
    calcularDesdeHorasMinutosSalida();

    const modal = new bootstrap.Modal(document.getElementById('modalToleranciaHorario'));
    modal.show();
}

function calcularDesdeHorasMinutos() {
    const h = parseInt(document.getElementById('inputHorasRetardo').value, 10) || 0;
    const m = parseInt(document.getElementById('inputMinutosRetardoInput').value, 10) || 0;
    const totalMinutes = (h * 60) + m;

    document.getElementById('modalMinutosRetardoCalculados').value = totalMinutes;

    // Calcular hora límite
    const [hh, mm] = rawHoraEntradaGlobal.split(':').map(n => parseInt(n, 10));
    const fechaRef = new Date();
    fechaRef.setHours(hh, mm, 0, 0);
    fechaRef.setMinutes(fechaRef.getMinutes() + totalMinutes);

    // Formatear HH:MM para el input time
    const inputTimeStr = String(fechaRef.getHours()).padStart(2, '0') + ':' + String(fechaRef.getMinutes()).padStart(2, '0');
    document.getElementById('inputHoraLimiteEntrada').value = inputTimeStr;

    // Formatear 12h AM/PM
    const hora12 = formatTime12(fechaRef.getHours(), fechaRef.getMinutes());

    const preview = document.getElementById('previewEntradaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Entrada Oficial: <strong>${formatTime12(hh, mm)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices que ingresen hasta las <strong>${hora12}</strong> contarán como <span class="badge bg-success">A Tiempo</span>. Después de las <strong>${hora12}</strong> se registrará <span class="badge bg-warning text-dark">Retardo</span>.`;
    }
}

function calcularDesdeHoraLimiteEntrada() {
    const timeVal = document.getElementById('inputHoraLimiteEntrada').value;
    if (!timeVal) return;

    const [hhLim, mmLim] = timeVal.split(':').map(n => parseInt(n, 10));
    const [hhOfi, mmOfi] = rawHoraEntradaGlobal.split(':').map(n => parseInt(n, 10));

    const dateOfi = new Date();
    dateOfi.setHours(hhOfi, mmOfi, 0, 0);

    const dateLim = new Date();
    dateLim.setHours(hhLim, mmLim, 0, 0);
    if (dateLim < dateOfi) {
        dateLim.setDate(dateLim.getDate() + 1);
    }

    const diffMs = dateLim - dateOfi;
    const diffMins = Math.max(0, Math.floor(diffMs / 60000));

    const h = Math.floor(diffMins / 60);
    const m = diffMins % 60;

    document.getElementById('inputHorasRetardo').value = h;
    document.getElementById('inputMinutosRetardoInput').value = m;
    document.getElementById('modalMinutosRetardoCalculados').value = diffMins;

    const hora12 = formatTime12(hhLim, mmLim);
    const preview = document.getElementById('previewEntradaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Entrada Oficial: <strong>${formatTime12(hhOfi, mmOfi)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices que ingresen hasta las <strong>${hora12}</strong> contarán como <span class="badge bg-success">A Tiempo</span>. Después de las <strong>${hora12}</strong> se registrará <span class="badge bg-warning text-dark">Retardo</span>.`;
    }
}

function calcularDesdeHorasMinutosSalida() {
    const h = parseInt(document.getElementById('inputHorasSalida').value, 10) || 0;
    const m = parseInt(document.getElementById('inputMinutosSalidaInput').value, 10) || 0;
    const totalMinutes = (h * 60) + m;

    document.getElementById('modalMinutosSalidaCalculados').value = totalMinutes;

    const [hh, mm] = rawHoraSalidaGlobal.split(':').map(n => parseInt(n, 10));
    const fechaRef = new Date();
    fechaRef.setHours(hh, mm, 0, 0);
    fechaRef.setMinutes(fechaRef.getMinutes() - totalMinutes);

    const hora12 = formatTime12(fechaRef.getHours(), fechaRef.getMinutes());

    const preview = document.getElementById('previewSalidaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Salida Oficial: <strong>${formatTime12(hh, mm)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices podrán marcar salida a partir de las <strong>${hora12}</strong> sin registrar Salida Temprana.`;
    }
}

function formatTime12(h, m) {
    let ampm = h >= 12 ? 'PM' : 'AM';
    let h12 = h % 12;
    h12 = h12 ? h12 : 12;
    let mStr = m < 10 ? '0' + m : m;
    let hStr = h12 < 10 ? '0' + h12 : h12;
    return `${hStr}:${mStr} ${ampm}`;
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
