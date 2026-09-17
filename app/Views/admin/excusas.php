<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

// Procesar aprobación/rechazo desde el panel de administración
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

$filtroEstado = $_GET['estado'] ?? 'todas';
$excusas = [];

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
            JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha";

    if ($filtroEstado === 'pendientes') {
        $sql .= " WHERE e.estado = 'Pendiente'";
    } elseif ($filtroEstado === 'aprobadas') {
        $sql .= " WHERE e.estado = 'Aprobada'";
    } elseif ($filtroEstado === 'rechazadas') {
        $sql .= " WHERE e.estado = 'Rechazada'";
    }

    $sql .= " ORDER BY e.fecha_radicacion DESC";

    $stmt = $pdo->query($sql);
    $excusas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($excusas as &$row) {
        $row['fechaInasistencia'] = date('d M Y', strtotime($row['fechaInasistencia']));
        $row['fechaRadicado'] = date('d M Y', strtotime($row['fechaRadicado']));
    }
    unset($row);

} catch (Exception $e) {
    // Si la tabla no tiene registros todavía
}

if (empty($excusas)) {
    $excusas = [
        ['id' => 1, 'aprendiz' => 'Julián Castro',    'ficha' => '2670456 - PSW',          'fechaInasistencia' => '10 ago 2026', 'fechaRadicado' => '11 ago 2026', 'soporte' => 'incapacidad.pdf', 'estado' => 'Pendiente'],
        ['id' => 2, 'aprendiz' => 'Andrés Ríos',      'ficha' => '2670456 - PSW',          'fechaInasistencia' => '05 ago 2026', 'fechaRadicado' => '06 ago 2026', 'soporte' => 'cita_medica.jpg', 'estado' => 'Aprobada'],
        ['id' => 3, 'aprendiz' => 'Valentina Torres', 'ficha' => '2670789 - Contabilidad', 'fechaInasistencia' => '02 ago 2026', 'fechaRadicado' => '03 ago 2026', 'soporte' => 'soporte.pdf',     'estado' => 'Rechazada'],
    ];
}

$tituloPagina = 'Auditoría de Excusas Médicas';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(155, 89, 182, 0.15); color: #a569bd;">
                <i class="bi bi-file-earmark-medical-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Auditoría Global de Excusas Médicas</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Supervisión administrativa de soportes médicos radicados por aprendices de todas las fichas.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="excusas.php?estado=pendientes" class="btn btn-sm btn-sena-outline">
                <i class="bi bi-clock-history me-1 text-warning"></i> Solo Pendientes
            </a>
            <a href="reportes.php" class="btn btn-sm btn-sena-green">
                <i class="bi bi-file-earmark-pdf me-1"></i> Exportar Novedades
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['mensaje'])): ?>
<div class="alert <?= ($_GET['mensaje'] == 'aprobada') ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px;">
    <i class="bi <?= ($_GET['mensaje'] == 'aprobada') ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-2"></i> 
    La excusa médica ha sido <strong><?= htmlspecialchars($_GET['mensaje']) ?></strong> por coordinación correctamente.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-folder-check me-2 text-info"></i>Registro de Soportes Médicos</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Previsualiza el archivo adjunto y valida la autenticidad de la incapacidad</p>
        </div>
        <div class="btn-group btn-group-sm">
            <a href="excusas.php?estado=todas" class="btn <?= ($filtroEstado === 'todas') ? 'btn-sena-green' : 'btn-sena-outline' ?>">Todas</a>
            <a href="excusas.php?estado=pendientes" class="btn <?= ($filtroEstado === 'pendientes') ? 'btn-sena-green' : 'btn-sena-outline' ?>">Pendientes</a>
            <a href="excusas.php?estado=aprobadas" class="btn <?= ($filtroEstado === 'aprobadas') ? 'btn-sena-green' : 'btn-sena-outline' ?>">Aprobadas</a>
            <a href="excusas.php?estado=rechazadas" class="btn <?= ($filtroEstado === 'rechazadas') ? 'btn-sena-green' : 'btn-sena-outline' ?>">Rechazadas</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha y Programa</th>
                    <th>Fecha Inasistencia</th>
                    <th>Radicada El</th>
                    <th>Soporte</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($excusas)): ?>
                <tr>
                    <td colspan="7" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        No hay registros de excusas para el filtro seleccionado.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($excusas as $e): 
                $estadoLower = strtolower($e['estado']);
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2.5">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($e['aprendiz']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                            <strong class="text-theme-primary"><?= htmlspecialchars($e['aprendiz']) ?></strong>
                        </div>
                    </td>
                    <td><span class="text-theme-primary fw-medium"><?= htmlspecialchars($e['ficha']) ?></span></td>
                    <td>
                        <span class="badge-sena badge-absent">
                            <i class="bi bi-calendar-x"></i> <?= htmlspecialchars($e['fechaInasistencia']) ?>
                        </span>
                    </td>
                    <td><span class="text-secondary"><?= htmlspecialchars($e['fechaRadicado']) ?></span></td>
                    <td>
                        <?php if (!empty($e['soporte']) && $e['soporte'] !== 'sin_archivo'): ?>
                            <button type="button" class="btn btn-sm btn-sena-outline" onclick="abrirVisorSoporteAdmin(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'])) ?>', '<?= htmlspecialchars(addslashes($e['soporte'])) ?>', '<?= htmlspecialchars(addslashes($e['fechaInasistencia'])) ?>', '<?= $estadoLower ?>')">
                                <i class="bi bi-file-earmark-pdf text-info me-1"></i><?= htmlspecialchars(basename($e['soporte'])) ?>
                            </button>
                        <?php else: ?>
                            <span class="text-secondary small fst-italic">Sin archivo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($estadoLower === 'aprobada'): ?>
                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Aprobada</span>
                        <?php elseif ($estadoLower === 'rechazada'): ?>
                            <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> Rechazada</span>
                        <?php else: ?>
                            <span class="badge-sena badge-pending"><i class="bi bi-hourglass-split"></i> Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-sena-green me-1" title="Auditar y Previsualizar" onclick="abrirVisorSoporteAdmin(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['aprendiz'])) ?>', '<?= htmlspecialchars(addslashes($e['soporte'])) ?>', '<?= htmlspecialchars(addslashes($e['fechaInasistencia'])) ?>', '<?= $estadoLower ?>')">
                            <i class="bi bi-eye me-1"></i> Revisar
                        </button>
                        <?php if ($estadoLower === 'pendiente'): ?>
                            <form method="POST" action="excusas.php" class="d-inline">
                                <input type="hidden" name="action" value="rechazar">
                                <input type="hidden" name="excusa_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Rechazar Excusa" onclick="return confirm('¿Rechazar esta excusa médica?')">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Flotante Oscuro: Visor de Soportes Médicos para Administrador -->
<div class="modal fade" id="modalVisorSoporteAdmin" tabindex="-1" aria-labelledby="modalVisorSoporteAdminLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
      <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
        <div>
            <h5 class="modal-title text-theme-primary fw-bold" id="modalVisorSoporteAdminLabel">
                <i class="bi bi-file-earmark-medical-fill text-success me-2"></i>Auditoría Administrativa de Excusa
            </h5>
            <small class="text-secondary d-block mt-0.5" id="subtituloModalSoporteAdmin"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
      </div>
      <div class="modal-body p-3" style="color: var(--nk-text-primary);">
        <div id="contenedorVisorAdmin" class="doc-preview-container mb-2">
            <!-- Contenido dinámico -->
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-file-earmark-arrow-up fs-1 mb-2 d-block"></i>
                Cargando soporte médico...
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center small text-secondary px-1">
            <span id="nombreArchivoModalAdmin"><i class="bi bi-paperclip me-1"></i> Archivo soporte</span>
            <a id="enlaceDescargaModalAdmin" href="#" target="_blank" class="text-info text-decoration-none">
                <i class="bi bi-box-arrow-up-right me-1"></i> Abrir en ventana externa
            </a>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between align-items-center" style="border-color: var(--nk-border-color) !important;">
        <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cerrar</button>
        <div id="accionesModalEvaluacionAdmin" class="d-flex gap-2">
            <form method="POST" action="excusas.php" class="d-inline">
                <input type="hidden" name="action" value="rechazar">
                <input type="hidden" name="excusa_id" id="excusaIdRechazarAdmin" value="">
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Confirmas el rechazo de esta excusa médica?')">
                    <i class="bi bi-x-circle me-1"></i> Rechazar
                </button>
            </form>
            <form method="POST" action="excusas.php" class="d-inline">
                <input type="hidden" name="action" value="aprobar">
                <input type="hidden" name="excusa_id" id="excusaIdAprobarAdmin" value="">
                <button type="submit" class="btn btn-sena-green">
                    <i class="bi bi-check-circle me-1"></i> Aprobar Justificación
                </button>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function abrirVisorSoporteAdmin(id, aprendiz, soporte, fecha, estado) {
    document.getElementById('subtituloModalSoporteAdmin').textContent = 'Aprendiz: ' + aprendiz + ' | Inasistencia: ' + fecha;
    document.getElementById('excusaIdAprobarAdmin').value = id;
    document.getElementById('excusaIdRechazarAdmin').value = id;
    
    var rutaArchivo = '../../../public/uploads/' + encodeURIComponent(soporte);
    var enlaceDescarga = document.getElementById('enlaceDescargaModalAdmin');
    var nombreArchivo = document.getElementById('nombreArchivoModalAdmin');
    var visor = document.getElementById('contenedorVisorAdmin');
    var contenedorAcciones = document.getElementById('accionesModalEvaluacionAdmin');

    if (estado !== 'pendiente') {
        contenedorAcciones.style.display = 'none';
    } else {
        contenedorAcciones.style.display = 'flex';
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

    var modal = new bootstrap.Modal(document.getElementById('modalVisorSoporteAdmin'));
    modal.show();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

