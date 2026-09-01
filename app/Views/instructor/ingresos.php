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

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="sena-title mb-1">Registros de ingreso en tiempo real</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Monitorea las entradas, salidas y retardos de tus grupos</p>
        </div>
        <form class="d-flex gap-2 flex-wrap" method="get">
            <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fecha) ?>" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
            <select class="form-select form-select-sm" name="ficha" style="width:200px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                <option value="">Todas mis fichas</option>
                <?php foreach ($misFichas as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>" <?= ($f === $fichaSeleccionada) ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="sena-btn sena-btn-outline-primary btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
        </form>
    </div>

    <div class="alert alert-info sena-alert-info mb-3 d-flex align-items-center gap-2 border-0" role="alert" style="background:rgba(57,169,0,0.1); color:var(--text-main);">
        <i class="bi bi-info-circle-fill text-success fs-5"></i>
        <div style="font-size: 13px;">
            En esta pantalla puedes consultar en tiempo real qué aprendices han marcado su ingreso mediante RFID en la fecha seleccionada.
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Aprendiz</th>
                    <th>Ficha</th>
                    <th>Hora de entrada</th>
                    <th>Hora de salida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $etiquetas = [
                'asistio'          => 'A tiempo',
                'retardo'          => 'Retardo',
                'salida temprana'  => 'Salida temprana',
                'inasistencia'     => 'Inasistencia',
            ];
            ?>
            <?php if (empty($ingresos)): ?>
                <tr>
                    <td colspan="5" class="text-center sena-empty-state py-5">
                        <i class="bi bi-door-closed fs-1 text-muted mb-2 d-block"></i>
                        No hay novedades de ingreso para esta fecha.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ingresos as $i): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['aprendiz']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($i['aprendiz']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($i['ficha']) ?></td>
                    <td><?= $i['entrada'] ? htmlspecialchars(date('h:i A', strtotime($i['entrada']))) : '--:--' ?></td>
                    <td><?= $i['salida'] ? htmlspecialchars(date('h:i A', strtotime($i['salida']))) : '--:--' ?></td>
                    <td>
                        <?php 
                        $estadoFormat = strtolower($i['estado']);
                        $badgeClass = 'bg-secondary';
                        if ($estadoFormat == 'asistio') $badgeClass = 'bg-success';
                        else if ($estadoFormat == 'retardo') $badgeClass = 'bg-warning text-dark';
                        else if ($estadoFormat == 'inasistencia' || $estadoFormat == 'salida temprana') $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= $etiquetas[$estadoFormat] ?? ucfirst($i['estado']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>

