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
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-teal);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Fichas asignadas</p>
                    <h3><?= (int)$stats['fichasAsignadas'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-teal);"><i class="bi bi-journal-bookmark-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-blue, #4a90e2);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Aprendices a cargo</p>
                    <h3><?= (int)$stats['aprendicesACargo'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-blue, #4a90e2);"><i class="bi bi-people-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-orange);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Retardos de hoy</p>
                    <h3><?= (int)$stats['retardosHoy'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-orange);"><i class="bi bi-clock-history"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="nk-metric-tile" style="border-left-color:var(--nk-accent-purple);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-muted" style="font-size:13px;">Excusas pendientes</p>
                    <h3><?= (int)$stats['excusasPendientes'] ?></h3>
                </div>
                <div class="stat-icon" style="background:var(--nk-accent-purple);"><i class="bi bi-file-earmark-medical-fill"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="white-box">
    <div class="card-title-row">
        <h3>Mis fichas y horario</h3>
        <span class="text-muted" style="font-size:12px;">Fichas asignadas por el administrador</span>
    </div>

    <p class="text-muted mb-3" style="font-size:13px;">
        <i class="bi bi-info-circle me-1"></i>
        Solo el administrador puede asignarte o quitarte fichas. Si crees que falta alguna, comunícate con coordinación.
    </p>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Código de ficha</th>
                    <th>Programa de formación</th>
                    <th>Jornada</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Ver</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($misFichas)): ?>
                <tr>
                    <td colspan="6" class="text-center sena-empty-state py-4">No tienes fichas asignadas todavía.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($misFichas as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td><?= htmlspecialchars($f['programa']) ?></td>
                    <td><?= htmlspecialchars($f['jornada']) ?></td>
                    <td><?= htmlspecialchars($f['entrada']) ?> &ndash; <?= htmlspecialchars($f['salida']) ?></td>
                    <td><span class="badge rounded-pill text-bg-light border"><?= (int)$f['aprendices'] ?> aprendices</span></td>
                    <td class="text-end">
                        <a href="aprendices.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-people"></i> Ver aprendices
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>