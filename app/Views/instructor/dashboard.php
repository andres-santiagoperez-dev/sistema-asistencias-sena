<?php
/**
 * @var array $stats
 * @var array $misFichas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

// Obtener el id_instructor correspondiente al usuario logueado
$idInstructor = null;
try {
    $stmt = $pdo->prepare("SELECT id_instructor FROM instructores WHERE usuarios_id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
    $idInstructor = $stmt->fetchColumn() ?: null;
} catch (PDOException $e) {
    $idInstructor = null;
}

$stats = [
    'fichasAsignadas'   => 0,
    'aprendicesACargo'  => 0,
    'retardosHoy'       => 0,
    'excusasPendientes' => 0,
];
$misFichas = [];

if ($idInstructor) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fichas WHERE Instructores_id_instructor1 = :id");
        $stmt->execute([':id' => $idInstructor]);
        $stats['fichasAsignadas'] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM aprendices a
            INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id
        ");
        $stmt->execute([':id' => $idInstructor]);
        $stats['aprendicesACargo'] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM asistencia asi
            INNER JOIN aprendices a ON asi.aprendices_id_aprendiz = a.id_aprendiz
            INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id AND asi.estado = 'Retardo' AND asi.fecha = CURDATE()
        ");
        $stmt->execute([':id' => $idInstructor]);
        $stats['retardosHoy'] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
    }

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM excusas_medicas e
            INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
            INNER JOIN aprendices a ON asi.aprendices_id_aprendiz = a.id_aprendiz
            INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id AND e.estado = 'Pendiente'
        ");
        $stmt->execute([':id' => $idInstructor]);
        $stats['excusasPendientes'] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
    }

    try {
        $stmt = $pdo->prepare("
            SELECT f.numero_ficha                          AS codigo,
                   f.programa                               AS programa,
                   h.dias_semana                            AS jornada,
                   TIME_FORMAT(h.hora_entrada, '%h:%i %p')  AS entrada,
                   TIME_FORMAT(h.hora_salida, '%h:%i %p')   AS salida,
                   COUNT(a.id_aprendiz)                     AS aprendices
            FROM fichas f
            INNER JOIN horarios h ON f.Horarios_id_horario = h.id_horario
            LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id
            GROUP BY f.id_ficha, f.numero_ficha, f.programa, h.dias_semana, h.hora_entrada, h.hora_salida
            ORDER BY f.numero_ficha
        ");
        $stmt->execute([':id' => $idInstructor]);
        $misFichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $misFichas = [];
    }
}

$tituloPagina = 'Mi Dashboard';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(57, 169, 0, 0.15); color: #39a900;">
                <i class="bi bi-speedometer2 fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Panel del Instructor</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Monitoreo en tiempo real de asistencia, ingresos y novedades de tus fichas asignadas.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="ingresos.php" class="btn btn-sena-green">
                <i class="bi bi-door-open me-1"></i> Control de Ingresos Hoy
            </a>
            <a href="excusas.php" class="btn btn-sena-outline">
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
                        <a href="aprendices.php?ficha=<?= urlencode($f['codigo']) ?>" class="btn btn-sm btn-sena-green me-1" title="Ver lista de aprendices">
                            <i class="bi bi-people me-1"></i> Aprendices
                        </a>
                        <a href="ingresos.php?ficha=<?= urlencode($f['codigo'] . ' - ' . $f['programa']) ?>" class="btn btn-sm btn-sena-outline" title="Monitorear Asistencia">
                            <i class="bi bi-door-open"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

