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

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(57,169,0,0.1); color:#39A900;">
                <i class="bi bi-journal-bookmark-fill"></i>
            </div>
            <h4 class="sena-kpi-value"><?= (int)$stats['fichasAsignadas'] ?></h4>
            <p class="sena-kpi-label">Fichas asignadas</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(0,121,52,0.1); color:#007934;">
                <i class="bi bi-people-fill"></i>
            </div>
            <h4 class="sena-kpi-value"><?= (int)$stats['aprendicesACargo'] ?></h4>
            <p class="sena-kpi-label">Aprendices a cargo</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(255,165,0,0.1); color:#FFA500;">
                <i class="bi bi-clock-history"></i>
            </div>
            <h4 class="sena-kpi-value text-warning"><?= (int)$stats['retardosHoy'] ?></h4>
            <p class="sena-kpi-label">Retardos de hoy</p>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="sena-kpi-card text-center h-100 p-3">
            <div class="sena-kpi-icon mx-auto mb-2" style="background-color:rgba(123,94,234,0.1); color:#7b5eea;">
                <i class="bi bi-file-earmark-medical-fill"></i>
            </div>
            <h4 class="sena-kpi-value" style="color: #7b5eea;"><?= (int)$stats['excusasPendientes'] ?></h4>
            <p class="sena-kpi-label">Excusas pendientes</p>
        </div>
    </div>
</div>

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="sena-title mb-1">Mis fichas y horario</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Fichas asignadas por el administrador</p>
        </div>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--text-main);">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Solo el administrador puede asignarte o quitarte fichas. Si crees que falta alguna, comunícate con coordinación.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle">
            <thead>
                <tr>
                    <th>Código de ficha</th>
                    <th>Programa de formación</th>
                    <th>Jornada</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($misFichas)): ?>
                <tr>
                    <td colspan="6" class="text-center sena-empty-state py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No tienes fichas asignadas todavía.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($misFichas as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td><span class="badge" style="background-color: #007934; color: white;"><?= htmlspecialchars($f['jornada']) ?></span></td>
                    <td><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></td>
                    <td><span class="sena-badge-tag"><?= (int)$f['aprendices'] ?> aprendices</span></td>
                    <td class="text-end">
                        <a href="aprendices.php" class="sena-btn sena-btn-outline-primary btn-sm">
                            <i class="bi bi-people"></i> Ver aprendices
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
