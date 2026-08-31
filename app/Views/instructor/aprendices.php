<?php
/**
 * @var array $aprendices
 * @var array $misFichas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

$idInstructor = null;
try {
    $stmt = $pdo->prepare("SELECT id_instructor FROM instructores WHERE usuarios_id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
    $idInstructor = $stmt->fetchColumn() ?: null;
} catch (PDOException $e) {
    $idInstructor = null;
}

$aprendices = [];
$misFichas  = [];

if ($idInstructor) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.numero_documento                        AS documento,
                   CONCAT(u.nombres, ' ', u.apellidos)        AS nombre,
                   CONCAT(f.numero_ficha, ' - ', f.programa)  AS ficha,
                   a.estado_matricula                         AS estado
            FROM aprendices a
            INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
            INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id
            ORDER BY u.apellidos, u.nombres
        ");
        $stmt->execute([':id' => $idInstructor]);
        $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($aprendices as &$a) {
            $a['estado'] = mb_strtolower($a['estado']);
        }
        unset($a);
    } catch (PDOException $e) {
        $aprendices = [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT CONCAT(numero_ficha, ' - ', programa) AS ficha
            FROM fichas
            WHERE Instructores_id_instructor1 = :id
            ORDER BY numero_ficha
        ");
        $stmt->execute([':id' => $idInstructor]);
        $misFichas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $misFichas = [];
    }
}

$tituloPagina = 'Mis Aprendices';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="sena-title mb-1">Aprendices de mis fichas</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Listado general de aprendices asignados a ti</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" style="width:220px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);" placeholder="Buscar por nombre o documento...">
            <select class="form-select form-select-sm" style="width:200px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                <option value="">Todas mis fichas</option>
                <?php foreach ($misFichas as $ficha): ?>
                    <option><?= htmlspecialchars($ficha) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--text-main);">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Este listado es de solo lectura: la vinculación de aprendices a una ficha la gestiona el administrador.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre completo</th>
                    <th>Ficha</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="5" class="text-center sena-empty-state py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No tienes aprendices asignados todavía.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['documento']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($a['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($a['ficha']) ?></td>
                    <td>
                        <?php 
                        $badgeClass = 'bg-secondary';
                        if ($a['estado'] == 'matriculado') $badgeClass = 'bg-success';
                        else if ($a['estado'] == 'aplazado') $badgeClass = 'bg-warning text-dark';
                        else if ($a['estado'] == 'retirado') $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($a['estado']) ?></span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" title="Ver historial de asistencia">
                            <i class="bi bi-clock-history"></i> Historial
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
