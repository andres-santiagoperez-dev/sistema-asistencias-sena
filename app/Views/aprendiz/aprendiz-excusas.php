<?php 
session_start(); 

require_once '../../config/database.php'; 
$pdo = Database::conectar(); 

$id_aprendiz = $_SESSION['id_aprendiz'];
$mensaje_exito = "";
$mensaje_error = "";

// --- PROCESAMIENTO DEL FORMULARIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inasistencias'])) {
    $inasistencias_seleccionadas = $_POST['inasistencias']; // Array de IDs
    $motivo = $_POST['motivo'] ?? '';
    
    // Obtener archivo (solo guardamos el nombre simulado sin mover físicamente)
    $archivo_nombre = "sin_archivo";
    if (isset($_FILES['soporte']) && $_FILES['soporte']['error'] === UPLOAD_ERR_OK) {
        $archivo_nombre = $_FILES['soporte']['name'];
    }

    if (empty($inasistencias_seleccionadas) || empty($motivo)) {
        $mensaje_error = "Debe seleccionar al menos una inasistencia y proporcionar un motivo.";
    } else {
        try {
            $pdo->beginTransaction();

            $in_placeholders = implode(',', array_fill(0, count($inasistencias_seleccionadas), '?'));
            
            // 1. Obtener la fecha mínima y máxima de las inasistencias seleccionadas
            $sql_fechas = "SELECT MIN(fecha) as fecha_inicio, MAX(fecha) as fecha_fin FROM asistencia WHERE id_registro IN ($in_placeholders)";
            $stmt_fechas = $pdo->prepare($sql_fechas);
            $stmt_fechas->execute($inasistencias_seleccionadas);
            $fechas = $stmt_fechas->fetch(PDO::FETCH_ASSOC);

            $fecha_inicio = $fechas['fecha_inicio'] ?? date('Y-m-d');
            $fecha_fin = $fechas['fecha_fin'] ?? date('Y-m-d');

            // 2. Insertar una sola excusa que cubre todas las inasistencias
            $sql_insert_excusa = "INSERT INTO excusas_medicas (motivo, fecha_inicio, fecha_fin, estado, fecha_radicacion, archivo_soporte) 
                                  VALUES (?, ?, ?, 'Pendiente', NOW(), ?)";
            $stmt_insert = $pdo->prepare($sql_insert_excusa);
            $stmt_insert->execute([$motivo, $fecha_inicio, $fecha_fin, $archivo_nombre]);
            $id_excusa = $pdo->lastInsertId();

            // 3. Vincular las inasistencias seleccionadas a esta excusa
            $sql_update_asistencia = "UPDATE asistencia SET excusas_medicas_id_excusa = ? WHERE id_registro IN ($in_placeholders)";
            $params = array_merge([$id_excusa], $inasistencias_seleccionadas);
            $stmt_update = $pdo->prepare($sql_update_asistencia);
            $stmt_update->execute($params);

            $pdo->commit();
            $mensaje_exito = "¡Excusa radicada exitosamente! Se vinculó a " . count($inasistencias_seleccionadas) . " inasistencia(s).";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje_error = "Ocurrió un error al radicar la excusa: " . $e->getMessage();
        }
    }
}

try {
    // 1. Consultar Información Básica para Sidebar
    $sql_info = "SELECT a.codigo_rfid, f.numero_ficha, f.programa
                 FROM aprendices a
                 LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                 WHERE a.id_aprendiz = :id_aprendiz";
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id_aprendiz' => $id_aprendiz]);
    $info_aprendiz = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $rfid = $info_aprendiz['codigo_rfid'] ?? 'Sin Asignar';
    $numero_ficha = $info_aprendiz['numero_ficha'] ?? 'Sin Ficha';
    $programa = $info_aprendiz['programa'] ?? 'Sin Programa';

    // 2. Consultar Inasistencias sin excusa
    $sql_inasistencias = "SELECT id_registro, fecha, hora_entrada 
                          FROM asistencia 
                          WHERE aprendices_id_aprendiz = :id_aprendiz 
                          AND estado = 'Inasistencia' 
                          AND excusas_medicas_id_excusa IS NULL
                          ORDER BY fecha DESC";
    $stmt_inasistencias = $pdo->prepare($sql_inasistencias);
    $stmt_inasistencias->execute([':id_aprendiz' => $id_aprendiz]);
    $inasistencias_pendientes = $stmt_inasistencias->fetchAll(PDO::FETCH_ASSOC);

    // 3. Consultar Historial de Excusas
    $excusas_list = [];
    try {
        $sql_excusas = "SELECT DISTINCT e.id_excusa, e.motivo, e.fecha_inicio, e.fecha_fin, e.estado, e.fecha_radicacion 
                        FROM excusas_medicas e
                        INNER JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                        WHERE a.aprendices_id_aprendiz = :id_aprendiz 
                        ORDER BY e.fecha_radicacion DESC";
        $stmt_excusas = $pdo->prepare($sql_excusas);
        $stmt_excusas->execute([':id_aprendiz' => $id_aprendiz]);
        $excusas_list = $stmt_excusas->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        $excusas_list = [];
    }
} catch (PDOException $e) {
    die("Error al cargar datos básicos: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mis Excusas Médicas | Control de Ingreso SENA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/nalika-icon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.css">
    <link rel="stylesheet" href="../../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link rel="stylesheet" href="../../../public/assets/css/responsive.css">
    <link rel="stylesheet" href="../../../public/assets/css/aprendiz-custom.css">
    <style>
        .timeline-excusa {
            position: relative;
            padding-left: 30px;
            margin-bottom: 20px;
            border-left: 2px solid var(--nk-border-color);
        }
        .timeline-excusa::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--nk-card-bg);
            border: 2px solid var(--nk-border-color);
        }
        .timeline-excusa.aprobada::before { border-color: #24caa1; background: #24caa1; }
        .timeline-excusa.pendiente::before { border-color: #f39c12; background: #f39c12; }
        .timeline-excusa.rechazada::before { border-color: #eb4b4b; background: #eb4b4b; }
        
        .form-control-sena {
            background-color: var(--nk-bg-main);
            border: 1px solid var(--nk-border-color);
            color: #fff;
        }
        .form-control-sena:focus {
            background-color: var(--nk-card-bg);
            border-color: #24b5ca;
            color: #fff;
            box-shadow: none;
        }
    </style>
</head>

<body>
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="aprendiz-dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <img class="main-logo" src="img/logo/logo.png" alt="SENA" style="max-height: 38px;" />
                    <span class="text-theme-primary fw-bold d-none d-sm-inline" style="font-size:13px; letter-spacing:0.5px;">ADSO SENA</span>
                </a>
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile"><i class="bi bi-x-lg"></i></button>
            </div>
            
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="aprendiz-perfil.php">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Foto" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 10px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Aprendiz') ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Aprendiz ADSO - Ficha <?= htmlspecialchars($numero_ficha) ?></small>
                </div>
            </div>

            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li><a href="aprendiz-dashboard.php"><i class="bi bi-speedometer2 icon-wrap"></i><span class="mini-click-non">Dashboard Principal</span></a></li>
                        <li><a href="aprendiz-horario.php"><i class="bi bi-calendar3 icon-wrap"></i><span class="mini-click-non">Mi Horario & Calendario</span></a></li>
                        <li><a href="aprendiz-historial.php"><i class="bi bi-clock-history icon-wrap"></i><span class="mini-click-non">Historial de Asistencia</span></a></li>
                        <li class="active"><a href="aprendiz-excusas.php"><i class="bi bi-file-earmark-medical icon-wrap"></i><span class="mini-click-non">Mis Excusas Médicas</span></a></li>
                        <li><a href="aprendiz-perfil.php"><i class="bi bi-person-badge icon-wrap"></i><span class="mini-click-non">Mi Perfil & Ficha</span></a></li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>

    <div class="all-content-wrapper">
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="nk-header-bar d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" onclick="toggleSidebarGlobal(event)"><i class="bi bi-list"></i></button>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <!-- Selector de Temas -->
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon d-flex align-items-center text-decoration-none text-theme-primary" data-bs-toggle="dropdown" title="Cambiar Tema">
                                    <i class="bi bi-palette fs-5"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><h6 class="dropdown-header">Elegir Tema</h6></li>
                                    <li><a class="dropdown-item" href="#" data-theme-option="nalika"><i class="bi bi-moon-stars me-2 text-info"></i>Nalika (Original)</a></li>
                                    <li><a class="dropdown-item" href="#" data-theme-option="dark"><i class="bi bi-moon me-2 text-secondary"></i>Oscuro Puro</a></li>
                                    <li><a class="dropdown-item" href="#" data-theme-option="light"><i class="bi bi-sun me-2 text-warning"></i>Claro</a></li>
                                </ul>
                            </div>
                            
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline"><?= htmlspecialchars($_SESSION['nombres'] ?? 'Usuario') ?></span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-item" href="aprendiz-perfil.php"><i class="bi bi-person me-2 text-success"></i>Mi Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sena-breadcrumb-bar d-flex align-items-center justify-content-between">
                <div>
                    <a href="aprendiz-dashboard.php"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-theme-primary fw-semibold">Mis Excusas Médicas</span>
                </div>
            </div>
        </div>

        <div class="section-admin container-fluid mt-4">
            
            <div class="sena-welcome-banner mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: rgba(155, 89, 182, 0.15); color: #a569bd;">
                        <i class="bi bi-file-earmark-medical fs-2"></i>
                    </div>
                    <div>
                        <h3 class="text-theme-primary fw-bold mb-1">Gestión de Excusas Médicas</h3>
                        <p class="text-secondary mb-0" style="font-size:14px;">Radica tus incapacidades para justificar inasistencias y consulta su estado.</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- Columna Izquierda: Formulario de Radicación -->
                <div class="col-lg-7 col-md-12">
                    <div class="card border-0 card-stat-ux h-100">
                        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3">
                            <h5 class="text-theme-primary mb-0"><i class="bi bi-cloud-upload me-2 text-info"></i>Radicar Nueva Excusa</h5>
                        </div>
                        <div class="card-body p-4">
                            <?php if(!empty($mensaje_exito)): ?>
                                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $mensaje_exito ?></div>
                            <?php endif; ?>
                            <?php if(!empty($mensaje_error)): ?>
                                <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><?= $mensaje_error ?></div>
                            <?php endif; ?>

                            <form action="aprendiz-excusas.php" method="POST" enctype="multipart/form-data">
                                <div class="row g-3">
                                    <div class="col-12 mb-2">
                                        <label class="form-label text-secondary fw-bold">Selecciona las inasistencias a justificar <span class="text-danger">*</span></label>
                                        
                                        <?php if(empty($inasistencias_pendientes)): ?>
                                            <div class="alert alert-info py-2"><i class="bi bi-info-circle me-2"></i>No tienes inasistencias pendientes por excusar.</div>
                                        <?php else: ?>
                                            <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: var(--input-bg); border-color: var(--border-color) !important;">
                                                <?php foreach($inasistencias_pendientes as $inasistencia): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" name="inasistencias[]" value="<?= $inasistencia['id_registro'] ?>" id="inasistencia_<?= $inasistencia['id_registro'] ?>">
                                                        <label class="form-check-label text-theme-primary" for="inasistencia_<?= $inasistencia['id_registro'] ?>" style="cursor: pointer;">
                                                            <i class="bi bi-calendar-x text-danger me-1"></i>
                                                            Fecha: <strong><?= date('d/m/Y', strtotime($inasistencia['fecha'])) ?></strong>
                                                            <small class="text-muted ms-2">(Horario Ficha)</small>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label text-secondary small">Motivo / Diagnóstico Breve <span class="text-danger">*</span></label>
                                        <input type="text" name="motivo" class="form-control form-control-sena" placeholder="Ej. Cita odontológica, Virosis..." required <?= empty($inasistencias_pendientes) ? 'disabled' : '' ?>>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label text-secondary small">Adjuntar Soporte (PDF o Imagen) <span class="text-danger">*</span></label>
                                        <input type="file" name="soporte" class="form-control form-control-sena" accept=".pdf, .jpg, .png, .jpeg" required <?= empty($inasistencias_pendientes) ? 'disabled' : '' ?>>
                                        <small class="text-muted mt-1 d-block"><i class="bi bi-info-circle me-1"></i>Tamaño máximo: 5MB.</small>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-sena-green w-100 py-2 fw-bold" <?= empty($inasistencias_pendientes) ? 'disabled' : '' ?>>
                                            <i class="bi bi-send me-2"></i>Enviar Excusa para Aprobación
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Timeline de Excusas -->
                <div class="col-lg-5 col-md-12">
                    <div class="card border-0 card-stat-ux h-100">
                        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-theme-primary mb-0"><i class="bi bi-list-task me-2 text-success"></i>Estado de Mis Excusas</h5>
                            <span class="badge bg-secondary"><?= count($excusas_list) ?> Registros</span>
                        </div>
                        <div class="card-body p-4">
                            <?php if(empty($excusas_list)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-folder2-open display-4 text-secondary mb-3"></i>
                                    <p class="text-secondary">No has radicado ninguna excusa médica aún.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($excusas_list as $excusa): 
                                    $clase_estado = strtolower($excusa['estado']);
                                    $color_text = ($clase_estado == 'aprobada') ? 'text-success' : (($clase_estado == 'pendiente') ? 'text-warning' : 'text-danger');
                                ?>
                                    <div class="timeline-excusa <?= $clase_estado ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <strong class="text-theme-primary"><?= htmlspecialchars($excusa['motivo']) ?></strong>
                                            <span class="badge" style="background: rgba(255,255,255,0.1);"><?= date('d M', strtotime($excusa['fecha_radicacion'])) ?></span>
                                        </div>
                                        <p class="text-secondary small mb-1">
                                            <i class="bi bi-calendar-event me-1"></i> Del <?= date('d M', strtotime($excusa['fecha_inicio'])) ?> al <?= date('d M', strtotime($excusa['fecha_fin'])) ?>
                                        </p>
                                        <span class="small fw-bold <?= $color_text ?>">
                                            <i class="bi bi-record-circle me-1"></i><?= $excusa['estado'] ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/main.js"></script>
    <script src="../../../public/assets/js/aprendiz-custom.js"></script>
</body>
</html>

