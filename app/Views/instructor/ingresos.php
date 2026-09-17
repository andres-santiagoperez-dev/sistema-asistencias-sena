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

$fecha = $_GET['fecha'] ?? date('Y-m-d');
$fichaSeleccionada = $_GET['ficha'] ?? '';

// Obtener las fichas asignadas al instructor
$misFichas = [];
try {
    $stmt = $pdo->prepare("SELECT numero_ficha, programa FROM fichas WHERE Instructores_id_instructor1 = :id_instructor");
    $stmt->execute([':id_instructor' => $idInstructor]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $misFichas[] = $row['numero_ficha'] . ' - ' . $row['programa'];
    }
} catch (PDOException $e) {
    // Ignorar si falla
}

// Obtener los ingresos (asistencia)
$ingresos = [];
try {
    $sql = "SELECT 
                CONCAT(u.nombres, ' ', u.apellidos) as aprendiz,
                CONCAT(f.numero_ficha, ' - ', f.programa) as ficha,
                a.hora_entrada as entrada,
                a.hora_salida as salida,
                a.estado as estado
            FROM asistencia a
            JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
            JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
            JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
            WHERE f.Instructores_id_instructor1 = :id_instructor
            AND a.fecha = :fecha";
            
    $params = [
        ':id_instructor' => $idInstructor,
        ':fecha' => $fecha
    ];
    
    if ($fichaSeleccionada) {
        $sql .= " AND CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha";
        $params[':ficha'] = $fichaSeleccionada;
    }
    
    $sql .= " ORDER BY a.hora_entrada DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Error silently handled or log
}

$tituloPagina = 'Control de Ingresos';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-door-open fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Control de Ingresos y Asistencia</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Monitoreo de entradas, salidas y puntualidad registrado para tus fichas formativas.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark border border-secondary border-opacity-50 px-3 py-2 text-theme-primary" style="font-size:13px;">
                <i class="bi bi-calendar3 text-info me-1"></i> Fecha: <strong><?= date('d/m/Y', strtotime($fecha)) ?></strong>
            </span>
            <span class="badge-sena badge-on-time">
                <i class="bi bi-person-check-fill"></i> <?= count($ingresos) ?> Registros
            </span>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-clock-history me-2 text-info"></i>Registros de Asistencia</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Filtrar marcaciones por fecha específica y programa de formación</p>
        </div>
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fecha) ?>" style="width: 160px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            <select class="form-select form-select-sm" name="ficha" style="width:220px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <option value="">Todas mis fichas</option>
                <?php foreach ($misFichas as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>" <?= ($f === $fichaSeleccionada) ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-sena-green"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if (!empty($fichaSeleccionada) || $fecha !== date('Y-m-d')): ?>
                <a href="ingresos.php" class="btn btn-sm btn-sena-outline"><i class="bi bi-arrow-counterclockwise"></i> Hoy</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--nk-text-primary); border-radius: 8px;">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            Marcaciones sincronizadas en tiempo real. Los aprendices que no registren entrada dentro de los límites de su ficha figurarán con retardo o inasistencia.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha Asignada</th>
                    <th>Hora de Entrada</th>
                    <th>Hora de Salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ingresos)): ?>
                <tr>
                    <td colspan="5" class="text-center text-secondary py-5">
                        <i class="bi bi-door-closed fs-1 text-muted mb-2 d-block"></i>
                        No hay novedades de ingreso registradas para los criterios seleccionados.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ingresos as $i): 
                $estadoFormat = strtolower($i['estado']);
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2.5">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['aprendiz']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                            <strong class="text-theme-primary"><?= htmlspecialchars($i['aprendiz']) ?></strong>
                        </div>
                    </td>
                    <td><span class="text-theme-primary fw-medium"><?= htmlspecialchars($i['ficha']) ?></span></td>
                    <td>
                        <?php if ($i['entrada']): ?>
                            <span class="text-success fw-bold"><i class="bi bi-box-arrow-in-right me-1"></i><?= date('h:i A', strtotime($i['entrada'])) ?></span>
                        <?php else: ?>
                            <span class="text-secondary">--:--</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($i['salida']): ?>
                            <span class="text-info fw-bold"><i class="bi bi-box-arrow-left me-1"></i><?= date('h:i A', strtotime($i['salida'])) ?></span>
                        <?php else: ?>
                            <span class="text-secondary">--:--</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($estadoFormat == 'asistio'): ?>
                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                        <?php elseif ($estadoFormat == 'retardo'): ?>
                            <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                        <?php elseif ($estadoFormat == 'inasistencia'): ?>
                            <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> Inasistencia</span>
                        <?php elseif ($estadoFormat == 'salida temprana'): ?>
                            <span class="badge-sena badge-late"><i class="bi bi-arrow-left-circle-fill"></i> Salida Temprana</span>
                        <?php else: ?>
                            <span class="badge-sena badge-pending"><?= ucfirst($i['estado']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>


