<?php
/**
 * Vista: Historial de Asistencias (Aprendiz)
 * Sistema de Control de Asistencia SENA
 * 
 * Pura presentación MVC: consume variables procesadas por HistorialController
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=aprendiz_historial');
    exit();
}

$tituloPagina = 'Historial de Asistencia';
$fechaInicio  = $fechaInicio ?? '';
$fechaFin     = $fechaFin ?? '';
$stats        = $stats ?? [
    'a_tiempo'                  => 0,
    'retardos'                  => 0,
    'inasistencias'             => 0,
    'inasistencias_justificadas' => 0
];
$historial    = $historial ?? [];

require_once dirname(__DIR__) . '/layouts/header-aprendiz.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
            <i class="bi bi-clock-history fs-2"></i>
        </div>
        <div>
            <h3 class="text-theme-primary fw-bold mb-1">Historial de Marcaciones</h3>
            <p class="text-secondary mb-0" style="font-size:14px;">Consulta todas tus entradas y salidas registradas con tu llavero RFID.</p>
        </div>
    </div>
</div>

<!-- Filtros de Fecha -->
<div class="card border-0 card-stat-ux mb-4 p-3 shadow-sm">
    <form method="GET" action="<?= BASE_URL ?>/index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="aprendiz_historial">
        <div class="col-md-3">
            <label class="form-label text-secondary fw-bold" style="font-size:12px;">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" class="form-control form-control-sena" value="<?= htmlspecialchars($fechaInicio) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label text-secondary fw-bold" style="font-size:12px;">Fecha Fin</label>
            <input type="date" name="fecha_fin" class="form-control form-control-sena" value="<?= htmlspecialchars($fechaFin) ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-sena-green me-2"><i class="bi bi-filter"></i> Filtrar</button>
            <a href="<?= BASE_URL ?>/index.php?page=aprendiz_historial" class="btn" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border: 1px solid var(--nk-border-color);"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</a>
        </div>
    </form>
</div>

<!-- Resumen Estadístico KPI -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24caa1 !important;">
            <small class="text-secondary fw-bold">TOTAL ASISTENCIAS</small>
            <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['a_tiempo'] ?? 0) ?></h3>
            <small class="text-success"><i class="bi bi-check-circle me-1"></i>A tiempo</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
            <small class="text-secondary fw-bold">RETARDOS</small>
            <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['retardos'] ?? 0) ?></h3>
            <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Acumulados</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #eb4b4b !important;">
            <small class="text-secondary fw-bold">INASISTENCIAS</small>
            <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['inasistencias'] ?? 0) ?></h3>
            <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Sin justificar</small>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #9b59b6 !important;">
            <small class="text-secondary fw-bold text-uppercase" style="font-size: 11.5px;">INAS. JUSTIFICADAS</small>
            <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= (int)($stats['inasistencias_justificadas'] ?? 0) ?></h3>
            <small style="color: #9b59b6;"><i class="bi bi-file-earmark-medical-fill me-1"></i>Excusas aprobadas</small>
        </div>
    </div>
</div>

<!-- Tabla de Historial DataTables -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 card-stat-ux shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="tablaHistorial" class="table table-nalika-dark table-hover w-100 align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora de Entrada</th>
                                <th>Hora de Salida</th>
                                <th>Min. Retardo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historial)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-secondary">
                                        <i class="bi bi-info-circle me-1"></i>No se encontraron registros de marcación para los criterios seleccionados.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historial as $row): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($row['fecha'])) ?></td>
                                        <td>
                                            <?php if (!empty($row['hora_entrada'])): ?>
                                                <span class="text-success fw-bold"><?= date('h:i A', strtotime($row['hora_entrada'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">--:--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['hora_salida'])): ?>
                                                <span class="text-info fw-bold"><?= date('h:i A', strtotime($row['hora_salida'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-secondary">--:--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)($row['minutos_retardo'] ?? 0) ?> min</td>
                                        <td>
                                            <?php if ($row['estado'] === 'Asistio'): ?>
                                                <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                                            <?php elseif ($row['estado'] === 'Retardo'): ?>
                                                <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                                            <?php elseif ($row['estado'] === 'Inasistencia' && isset($row['estado_excusa']) && $row['estado_excusa'] === 'Aprobada'): ?>
                                                <span class="badge-sena badge-excuse"><i class="bi bi-file-earmark-medical-fill"></i> Inasistencia Justificada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($row['estado'] ?? 'Inasistencia') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<!-- jQuery y DataTables Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        if ($('#tablaHistorial tbody tr td').length > 1) {
            $('#tablaHistorial').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "order": [[ 0, "desc" ]],
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            });
        }
    });
</script>
<?php
$scriptsExtra = ob_get_clean();

require_once dirname(__DIR__) . '/layouts/footer-aprendiz.php';
?>
