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

require __DIR__ . '/../layouts/header-instructor.php';
?>

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
            <p class="text-secondary mb-0" style="font-size:13px;">Grupos asignados y horarios de jornada formativa</p>
        </div>
        <span class="badge bg-dark border border-secondary border-opacity-50 px-3 py-2 text-theme-primary" style="font-size:12px;">
            <i class="bi bi-calendar-check text-info me-1"></i> <?= count($misFichas) ?> Ficha(s) Activa(s)
        </span>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            La vinculación y desvinculación de fichas es administrada por coordinación académica. Para ajustes, comunícate con el administrador.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Código de Ficha</th>
                    <th>Programa de Formación</th>
                    <th>Jornada</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acciones</th>
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
                    <td><strong class="text-theme-primary fs-6"><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td>
                        <span class="fw-semibold text-theme-primary"><?= htmlspecialchars($f['programa']) ?></span>
                    </td>
                    <td><span class="badge" style="background-color: #007934; color: white; font-size: 11.5px;"><?= htmlspecialchars($f['jornada']) ?></span></td>
                    <td>
                        <span class="text-info"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></span>
                    </td>
                    <td>
                        <span class="badge-sena badge-on-time">
                            <i class="bi bi-people-fill"></i> <?= (int)$f['aprendices'] ?> aprendices
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/index.php?page=instructor_aprendices&ficha=<?= urlencode($f['codigo']) ?>" class="btn btn-sm btn-sena-green me-1" title="Ver lista de aprendices">
                            <i class="bi bi-people me-1"></i> Aprendices
                        </a>
                        <button type="button" 
                                class="btn btn-sm btn-warning text-dark fw-bold me-1" 
                                onclick="abrirModalTolerancia(<?= (int)$f['id_ficha'] ?>, '<?= htmlspecialchars($f['codigo']) ?>', '<?= htmlspecialchars(addslashes($f['programa'])) ?>', '<?= htmlspecialchars($f['entrada']) ?>', '<?= htmlspecialchars($f['salida']) ?>', <?= (int)($f['minutos_retardo'] ?? 15) ?>, <?= (int)($f['minutos_salida'] ?? 0) ?>)" 
                                title="Modificar Tolerancia de Horario">
                            <i class="bi bi-clock-history me-1"></i> Tolerancia
                        </button>
                        <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos&ficha=<?= urlencode($f['codigo'] . ' - ' . $f['programa']) ?>" class="btn btn-sm btn-sena-outline" title="Monitorear Asistencia">
                            <i class="bi bi-door-open"></i>
                        </a>
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
                    <i class="bi bi-clock-history text-warning me-2"></i>Ajustar Tolerancia de Horario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formToleranciaHorario" method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_dashboard&action=guardar_tolerancia">
                <input type="hidden" name="id_ficha" id="modalIdFicha">
                <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($_GET['page'] ?? 'instructor_dashboard') ?>">

                <div class="modal-body p-4">
                    <div class="alert alert-dark border border-secondary border-opacity-25 mb-3" style="background: rgba(0,0,0,0.25); border-radius: 10px;">
                        <span class="badge bg-success mb-1" id="modalBadgeFicha">Ficha: ---</span>
                        <h6 class="fw-bold mb-1 text-white" id="modalProgramaFicha">Programa de Formación</h6>
                        <small class="text-info d-block" id="modalHorarioFicha">
                            <i class="bi bi-clock me-1"></i> Horario oficial: --:-- AM - --:-- PM
                        </small>
                    </div>

                    <!-- Minutos de tolerancia ENTRADA -->
                    <div class="mb-4">
                        <label for="inputMinutosRetardo" class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-in-right text-success me-1"></i> Tolerancia de Entrada (Minutos de Retardo):
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" 
                                   id="inputMinutosRetardo" 
                                   name="minutos_retardo" 
                                   class="form-control text-white fw-bold" 
                                   min="0" 
                                   max="120" 
                                   value="15" 
                                   style="background-color: var(--nk-bg-inset, #131e3a); border-color: var(--nk-border-color, #2d3d5a);"
                                   oninput="actualizarPrevisualizacionTolerancia()">
                            <span class="input-group-text bg-dark border-secondary text-secondary">minutos</span>
                        </div>
                        <div class="form-text text-secondary mt-1 p-2 rounded" id="previewEntradaText" style="background: rgba(57,169,0,0.1); border-left: 3px solid #39a900; font-size:12px;">
                            Los aprendices que ingresen dentro de este rango contarán como <strong>A Tiempo</strong>.
                        </div>
                    </div>

                    <!-- Minutos de tolerancia SALIDA -->
                    <div class="mb-3">
                        <label for="inputMinutosSalida" class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-left text-info me-1"></i> Tolerancia de Salida (Minutos de Anticipación):
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" 
                                   id="inputMinutosSalida" 
                                   name="minutos_salida" 
                                   class="form-control text-white fw-bold" 
                                   min="0" 
                                   max="120" 
                                   value="0" 
                                   style="background-color: var(--nk-bg-inset, #131e3a); border-color: var(--nk-border-color, #2d3d5a);"
                                   oninput="actualizarPrevisualizacionTolerancia()">
                            <span class="input-group-text bg-dark border-secondary text-secondary">minutos</span>
                        </div>
                        <div class="form-text text-secondary mt-1 p-2 rounded" id="previewSalidaText" style="background: rgba(36,181,202,0.1); border-left: 3px solid #24b5ca; font-size:12px;">
                            Permite marcar la salida antes de la hora oficial sin registrar "Salida Temprana".
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Guardar Ajustes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rawHoraEntradaGlobal = '';
let rawHoraSalidaGlobal  = '';

function abrirModalTolerancia(idFicha, numeroFicha, programa, entradaStr, salidaStr, minutosRetardo, minutosSalida) {
    document.getElementById('modalIdFicha').value = idFicha;
    document.getElementById('modalBadgeFicha').textContent = 'Ficha: ' + numeroFicha;
    document.getElementById('modalProgramaFicha').textContent = programa;
    document.getElementById('modalHorarioFicha').innerHTML = '<i class="bi bi-clock me-1"></i> Horario oficial: ' + entradaStr + ' - ' + salidaStr;
    
    document.getElementById('inputMinutosRetardo').value = minutosRetardo || 0;
    document.getElementById('inputMinutosSalida').value = minutosSalida || 0;

    rawHoraEntradaGlobal = entradaStr;
    rawHoraSalidaGlobal  = salidaStr;

    actualizarPrevisualizacionTolerancia();

    const modal = new bootstrap.Modal(document.getElementById('modalToleranciaHorario'));
    modal.show();
}

function actualizarPrevisualizacionTolerancia() {
    const minsRetardo = parseInt(document.getElementById('inputMinutosRetardo').value, 10) || 0;
    const minsSalida  = parseInt(document.getElementById('inputMinutosSalida').value, 10) || 0;

    const previewEntrada = document.getElementById('previewEntradaText');
    const previewSalida  = document.getElementById('previewSalidaText');

    if (previewEntrada) {
        previewEntrada.innerHTML = `Entrada oficial: <strong>${rawHoraEntradaGlobal || '06:00 AM'}</strong> ➔ Con <strong>${minsRetardo} min</strong> de tolerancia, los aprendices que ingresen hasta <strong>${minsRetardo} minutos después</strong> contarán como <strong>A Tiempo</strong>.`;
    }

    if (previewSalida) {
        previewSalida.innerHTML = `Salida oficial: <strong>${rawHoraSalidaGlobal || '12:00 PM'}</strong> ➔ Con <strong>${minsSalida} min</strong> de tolerancia de salida, los aprendices podrán marcar salida hasta <strong>${minsSalida} minutos antes</strong> sin figurar con Salida Temprana.`;
    }
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

