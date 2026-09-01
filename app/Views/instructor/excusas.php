<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

// Obtener el id_instructor
$idInstructor = null;
try {
    $stmt = $pdo->prepare("SELECT id_instructor FROM instructores WHERE usuarios_id_usuario = :id_usuario");
    $stmt->execute([':id_usuario' => $_SESSION['id_usuario']]);
    $idInstructor = $stmt->fetchColumn() ?: null;
} catch (PDOException $e) {
    die("Error obteniendo datos del instructor: " . $e->getMessage());
}

if (!$idInstructor) {
    die("El usuario actual no es un instructor válido en el sistema.");
}

// Procesar la aprobación/rechazo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['excusa_id'])) {
    $idExcusa = (int)$_POST['excusa_id'];
    $nuevoEstado = ($_POST['action'] === 'aprobar') ? 'Aprobada' : 'Rechazada';
    
    try {
        $stmt = $pdo->prepare("UPDATE excusas_medicas SET estado = :estado, fecha_evaluacion = NOW() WHERE id_excusa = :id_excusa");
        $stmt->execute([':estado' => $nuevoEstado, ':id_excusa' => $idExcusa]);
        header('Location: excusas.php?mensaje=' . strtolower($nuevoEstado));
        exit();
    } catch (PDOException $e) {
        $errorMsg = "Error al actualizar la excusa.";
    }
}

// Consultar excusas pendientes
$excusasPendientes = [];
$excusasHistorial = [];

try {
    $sql = "SELECT DISTINCT
                e.id_excusa as id,
                CONCAT(u.nombres, ' ', u.apellidos) as aprendiz,
                CONCAT(f.numero_ficha, ' - ', f.programa) as ficha,
                e.fecha_inicio as fechaInasistencia,
                e.fecha_radicacion as fechaRadicado,
                e.archivo_soporte as soporte,
                e.estado
            FROM excusas_medicas e
            JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
            JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
            JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
            JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id_instructor
            ORDER BY e.fecha_radicacion DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_instructor' => $idInstructor]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formatear fechas
        $row['fechaInasistencia'] = date('d M Y', strtotime($row['fechaInasistencia']));
        $row['fechaRadicado'] = date('d M Y', strtotime($row['fechaRadicado']));
        
        if ($row['estado'] === 'Pendiente') {
            $excusasPendientes[] = $row;
        } else {
            $excusasHistorial[] = $row;
        }
    }
} catch (PDOException $e) {
    die("Error obteniendo excusas: " . $e->getMessage());
}

$tituloPagina = 'Excusas Médicas';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<div class="row g-4">
    <?php if (isset($_GET['mensaje'])): ?>
    <div class="col-12">
        <div class="alert <?= ($_GET['mensaje'] == 'aprobada') ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> La excusa médica ha sido <strong><?= htmlspecialchars($_GET['mensaje']) ?></strong> correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Excusas Pendientes -->
    <div class="col-12">
        <div class="sena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="sena-title mb-1">Excusas pendientes por aprobar</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Revisa y gestiona los soportes médicos de tus aprendices</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table sena-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha inasistencia</th>
                            <th>Radicada el</th>
                            <th>Soporte</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasPendientes)): ?>
                        <tr>
                            <td colspan="6" class="text-center sena-empty-state py-4">No hay excusas pendientes por revisar.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasPendientes as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <strong><?= htmlspecialchars($e['aprendiz']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($e['ficha']) ?></td>
                            <td><?= htmlspecialchars($e['fechaInasistencia']) ?></td>
                            <td><?= htmlspecialchars($e['fechaRadicado']) ?></td>
                            <td>
                                <?php if ($e['soporte']): ?>
                                <a href="../../../public/uploads/<?= htmlspecialchars($e['soporte']) ?>" target="_blank" class="text-decoration-none text-primary"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                                <?php else: ?>
                                <span class="text-muted">Sin soporte</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-success mx-1" title="Aprobar Excusa" onclick="abrirModalAprobar(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'])) ?>')">
                                    <i class="bi bi-check-lg"></i> Aprobar
                                </button>
                                <form method="POST" action="excusas.php" class="d-inline">
                                    <input type="hidden" name="action" value="rechazar">
                                    <input type="hidden" name="excusa_id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger mx-1" title="Rechazar Excusa" onclick="return confirm('¿Rechazar esta excusa?')">
                                        <i class="bi bi-x-lg"></i> Rechazar
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
        <div class="sena-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="sena-title mb-1">Historial de excusas de mis fichas</h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Registro de excusas aprobadas o rechazadas previamente</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table sena-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Aprendiz</th>
                            <th>Ficha</th>
                            <th>Fecha inasistencia</th>
                            <th>Soporte</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($excusasHistorial)): ?>
                        <tr>
                            <td colspan="5" class="text-center sena-empty-state py-4">No hay historial de excusas.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($excusasHistorial as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <strong><?= htmlspecialchars($e['aprendiz']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($e['ficha']) ?></td>
                            <td><?= htmlspecialchars($e['fechaInasistencia']) ?></td>
                            <td>
                                <?php if ($e['soporte']): ?>
                                <a href="../../../public/uploads/<?= htmlspecialchars($e['soporte']) ?>" target="_blank" class="text-decoration-none text-primary"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($e['soporte']) ?></a>
                                <?php else: ?>
                                <span class="text-muted">Sin soporte</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $badgeClass = 'bg-secondary';
                                if (strtolower($e['estado']) == 'aprobada') $badgeClass = 'bg-success';
                                else if (strtolower($e['estado']) == 'rechazada') $badgeClass = 'bg-danger';
                                ?>
                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($e['estado']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación para Aprobar -->
<div class="modal fade" id="modalAprobarExcusa" tabindex="-1" aria-labelledby="modalAprobarExcusaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background-color: var(--card-bg); border-color: var(--border-color);">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title text-theme-primary" id="modalAprobarExcusaLabel"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Confirmar Aprobación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--btn-close-filter);"></button>
      </div>
      <div class="modal-body" style="color: var(--text-main);">
        ¿Estás seguro de aprobar la excusa médica del aprendiz <strong id="nombreAprendizModal" class="text-theme-primary"></strong>? 
        Esta acción registrará la inasistencia como justificada.
      </div>
      <div class="modal-footer border-top-0">
        <form method="POST" action="excusas.php">
            <input type="hidden" name="action" value="aprobar">
            <input type="hidden" name="excusa_id" id="excusaIdInput" value="">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Sí, aprobar excusa</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function abrirModalAprobar(id, nombre) {
    document.getElementById('excusaIdInput').value = id;
    document.getElementById('nombreAprendizModal').textContent = nombre;
    var modal = new bootstrap.Modal(document.getElementById('modalAprobarExcusa'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

