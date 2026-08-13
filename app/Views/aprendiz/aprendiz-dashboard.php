<?php 
session_start(); 

// Validar que el usuario haya iniciado sesión y sea Aprendiz
/* if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_aprendiz'])) {
    header("Location: ../auth/login.php");
    exit();
} */

// 1. Conexión a la base de datos
// Ajusta esta ruta si tu archivo de conexión está en otra ubicación
require_once '../../config/database.php'; 
$pdo = Database::conectar(); 

$id_aprendiz = $_SESSION['id_aprendiz'];

try {
    // 2. Consultar Información de Ficha, Horario, Instructor y RFID
    $sql_info = "SELECT a.codigo_rfid, f.numero_ficha, f.programa, h.hora_entrada, h.hora_salida,
                        i.id_instructor, u_inst.nombres AS inst_nombres, u_inst.apellidos AS inst_apellidos, u_inst.correo AS inst_correo
                 FROM aprendices a
                 LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                 LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                 LEFT JOIN instructores i ON f.Instructores_id_instructor1 = i.id_instructor
                 LEFT JOIN usuarios u_inst ON i.usuarios_id_usuario = u_inst.id_usuario
                 WHERE a.id_aprendiz = :id_aprendiz";
    
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id_aprendiz' => $id_aprendiz]);
    $info_aprendiz = $stmt_info->fetch(PDO::FETCH_ASSOC);

    // Asignación de variables de información general con valores por defecto
    $rfid = $info_aprendiz['codigo_rfid'] ?? 'Sin Asignar';
    $numero_ficha = $info_aprendiz['numero_ficha'] ?? 'Sin Ficha';
    $programa = $info_aprendiz['programa'] ?? 'Sin Programa';
    $horario_str = ($info_aprendiz['hora_entrada'] ?? '00:00') . ' - ' . ($info_aprendiz['hora_salida'] ?? '00:00');
    $instructor_nombre = trim(($info_aprendiz['inst_nombres'] ?? 'Sin Asignar') . ' ' . ($info_aprendiz['inst_apellidos'] ?? ''));
    $instructor_correo = $info_aprendiz['inst_correo'] ?? 'No disponible';

    // 3. Consultar Estadísticas de Asistencia (Globales y del Mes Actual)
    $sql_stats = "SELECT 
                    -- Estadísticas Globales (Todo el tiempo)
                    SUM(CASE WHEN estado = 'Asistio' THEN 1 ELSE 0 END) AS total_a_tiempo,
                    SUM(CASE WHEN estado = 'Retardo' THEN 1 ELSE 0 END) AS total_retardos,
                    SUM(minutos_retardo) AS total_minutos_retardo,
                    SUM(CASE WHEN estado = 'Inasistencia' THEN 1 ELSE 0 END) AS total_inasistencias,
                    
                    -- Estadísticas del Mes Actual para el Porcentaje
                    SUM(CASE WHEN MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) AS dias_registrados_mes,
                    SUM(CASE WHEN MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE()) AND estado IN ('Asistio', 'Retardo', 'Salida Temprana') THEN 1 ELSE 0 END) AS dias_asistidos_mes
                  FROM asistencia
                  WHERE aprendices_id_aprendiz = :id_aprendiz";
    
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute([':id_aprendiz' => $id_aprendiz]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Cálculos y asignaciones de métricas
    $total_a_tiempo = $stats['total_a_tiempo'] ?? 0;
    $total_retardos = $stats['total_retardos'] ?? 0;
    $total_minutos_retardo = $stats['total_minutos_retardo'] ?? 0;
    $total_inasistencias = $stats['total_inasistencias'] ?? 0;

    $dias_registrados_mes = $stats['dias_registrados_mes'] ?? 0;
    $dias_asistidos_mes = $stats['dias_asistidos_mes'] ?? 0;
    $porcentaje_asistencia_mes = ($dias_registrados_mes > 0) ? round(($dias_asistidos_mes / $dias_registrados_mes) * 100, 1) : 0;

    // 4. Consultar Estado de Marcación de HOY
    $sql_hoy = "SELECT hora_entrada, hora_salida, estado, minutos_retardo 
                FROM asistencia 
                WHERE aprendices_id_aprendiz = :id_aprendiz AND fecha = CURRENT_DATE() LIMIT 1";
    $stmt_hoy = $pdo->prepare($sql_hoy);
    $stmt_hoy->execute([':id_aprendiz' => $id_aprendiz]);
    $marcacion_hoy = $stmt_hoy->fetch(PDO::FETCH_ASSOC);

    $estado_hoy_texto = $marcacion_hoy ? "REGISTRADO" : "NO REGISTRADO";
    $estado_hoy_color = $marcacion_hoy ? "text-success" : "text-danger";
    $hora_entrada_hoy = $marcacion_hoy ? date('h:i:s A', strtotime($marcacion_hoy['hora_entrada'])) : "--:--:--";
    $novedad_hoy = $marcacion_hoy ? $marcacion_hoy['estado'] : "Sin registros hoy";

    // 5. Consultar Excusas Médicas
    // Se usa COUNT(DISTINCT) por si una misma excusa cubre varios días en la tabla asistencia
    $sql_excusas = "SELECT 
                        COUNT(DISTINCT e.id_excusa) AS total_excusas,
                        COUNT(DISTINCT CASE WHEN e.estado = 'Aprobada' THEN e.id_excusa END) AS excusas_aprobadas,
                        COUNT(DISTINCT CASE WHEN e.estado = 'Pendiente' THEN e.id_excusa END) AS excusas_pendientes
                    FROM asistencia a
                    INNER JOIN excusas_medicas e ON a.excusas_medicas_id_excusa = e.id_excusa
                    WHERE a.aprendices_id_aprendiz = :id_aprendiz";
    
    $stmt_excusas = $pdo->prepare($sql_excusas);
    $stmt_excusas->execute([':id_aprendiz' => $id_aprendiz]);
    $excusas = $stmt_excusas->fetch(PDO::FETCH_ASSOC);

    $total_excusas = $excusas['total_excusas'] ?? 0;
    $excusas_aprobadas = $excusas['excusas_aprobadas'] ?? 0;
    $excusas_pendientes = $excusas['excusas_pendientes'] ?? 0;

} catch (PDOException $e) {
    // Si hay error en las consultas, detener y mostrar
    die("Error al cargar los datos del dashboard: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Dashboard Aprendiz | Control de Asistencia SENA - Nalika</title>
    <meta name="description" content="Panel del Aprendiz - Sistema de Control de Ingreso RFID SENA">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.8 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons 1.13.1 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Nalika Icon CSS -->
    <link rel="stylesheet" href="css/nalika-icon.css">
    <!-- Swiper 12 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
    <!-- SimpleBar 6.2.7 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.css">
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="../../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link rel="stylesheet" href="../../../public/assets/css/responsive.css">
    <!-- Aprendiz SENA Custom UX/UI CSS -->
    <link rel="stylesheet" href="../../../public/assets/css/aprendiz-custom.css">
</head>

<body>
    <!-- Barra de Progreso de Scroll Superior -->
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <!-- Left Sidebar -->
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="aprendiz-dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <img class="main-logo" src="img/logo/logo.png" alt="SENA" style="max-height: 38px;" />
                    <span class="text-theme-primary fw-bold d-none d-sm-inline" style="font-size:13px; letter-spacing:0.5px;">ADSO SENA</span>
                </a>
                <!-- Mobile Close Button -->
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Aprendiz Profile Dtl -->
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="aprendiz-perfil.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ver Mi Perfil Completo">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Foto Aprendiz" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 10px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($_SESSION['nombre_completo']) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Aprendiz ADSO - Ficha <?= htmlspecialchars($numero_ficha) ?></small>
                </div>
                <div class="profile-social-dtl mt-1">
                    <span class="sena-badge-tag"><i class="bi bi-upc-scan"></i> RFID: <?= htmlspecialchars($rfid) ?></span>
                </div>
            </div>

            <!-- Navigation Menu (Sin Scroll) -->
            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li class="active">
                            <a href="aprendiz-dashboard.php">
                                <i class="bi bi-speedometer2 icon-wrap"></i>
                                <span class="mini-click-non">Dashboard Principal</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-horario.php">
                                <i class="bi bi-calendar3 icon-wrap"></i>
                                <span class="mini-click-non">Mi Horario & Calendario</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-historial.php">
                                <i class="bi bi-clock-history icon-wrap"></i>
                                <span class="mini-click-non">Historial de Asistencia</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-excusas.php">
                                <i class="bi bi-file-earmark-medical icon-wrap"></i>
                                <span class="mini-click-non">Mis Excusas Médicas</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-perfil.php">
                                <i class="bi bi-person-badge icon-wrap"></i>
                                <span class="mini-click-non">Mi Perfil & Ficha</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </nav>
    </div>

    <!-- Main Content Wrapper -->
    <div class="all-content-wrapper">
        <!-- Header Bar -->
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="nk-header-bar d-flex align-items-center justify-content-between">
                        
                        <div class="d-flex align-items-center">
                            <!-- Botón Toggle Menú Siempre Visible y Desplazado -->
                            <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" onclick="toggleSidebarGlobal(event)" title="Alternar Menú Lateral">
                                <i class="bi bi-list"></i>
                            </button>
                            
                            <div class="d-flex align-items-center gap-2 ms-2">
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - ADSO</span>
                                <span class="text-secondary ms-1 d-none d-md-inline" style="font-size:13px;">Sistema de Control de Ingreso RFID</span>
                            </div>
                        </div>

                        <!-- Live Clock Widget & Quick Controls -->
                        <div class="d-flex align-items-center gap-3">
                            <span id="relojLive" class="nk-live-clock d-none d-sm-inline-flex" data-bs-toggle="tooltip" title="Hora del Sistema">
                                <i class="bi bi-clock-history"></i> --:--:-- --
                            </span>

                            <!-- Simulación RFID Quick Button -->
                            <button class="btn btn-sm btn-sena-green d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalSimuladorRfid">
                                <i class="bi bi-broadcast"></i>
                                <span class="d-none d-sm-inline">Simular Marcación RFID</span>
                            </button>

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
                                    <span class="nk-user-name d-none d-md-inline"><?= htmlspecialchars($_SESSION['nombre_completo']) ?></span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-header text-muted">Aprendiz - Ficha <?= htmlspecialchars($numero_ficha) ?></a></li>
                                    <li><a class="dropdown-item" href="aprendiz-perfil.php"><i class="bi bi-person me-2 text-success"></i>Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="aprendiz-horario.php"><i class="bi bi-calendar me-2 text-info"></i>Ver Horario</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation Bar -->
            <div class="sena-breadcrumb-bar d-flex align-items-center justify-content-between">
                <div>
                    <a href="aprendiz-dashboard.php"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-theme-primary fw-semibold">Dashboard Principal</span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Jornada Activa: <strong><?= htmlspecialchars($horario_str) ?></strong> | Lector RFID: <span class="text-success">CONECTADO</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Main Content -->
        <div class="section-admin container-fluid mt-4">
            
            <!-- Sena Welcome Banner -->
            <div class="sena-welcome-banner d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="text-theme-primary fw-bold mb-0">¡Bienvenido de nuevo, <?= htmlspecialchars($_SESSION['nombres']) ?>! </h3>
                        <span class="badge bg-success" style="font-size:11px;">Aprendiz Activo</span>
                    </div>
                    <p class="text-secondary mb-0 mt-1" style="font-size:14px;">
                        Ficha <strong><?= htmlspecialchars($numero_ficha) ?> - <?= htmlspecialchars($programa) ?></strong> | Instructor Líder: <strong><?= htmlspecialchars($instructor_nombre) ?></strong>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="aprendiz-horario.php" class="btn btn-sm" style="background:var(--nk-border-color); color:#fff; border:1px solid #314a73;">
                        <i class="bi bi-calendar-event me-1 text-info"></i> Mi Horario
                    </a>
                    <a href="aprendiz-excusas.php" class="btn btn-sm btn-sena-green">
                        <i class="bi bi-plus-circle me-1"></i> Nueva Excusa
                    </a>
                </div>
            </div>

            <!-- RFID Today Live Status -->
            <div class="row g-3 mb-4">
                <div class="col-lg-8 col-md-7 col-12">
                    <div class="rfid-status-card h-100 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rfid-icon-circle active">
                                <i class="bi bi-broadcast-pin"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="text-theme-primary mb-0 fw-bold">Estado RFID de Hoy</h5>
                                    <span class="rfid-pulse"></span>
                                    <span class="<?= $estado_hoy_color ?> fw-bold" style="font-size:12px;"><?= $estado_hoy_texto ?></span>
                                </div>
                                <p class="text-secondary mb-0 mt-1" style="font-size:13px;">
                                    Llavero RFID Vinculado: <code style="color:#24b5ca;"><?= htmlspecialchars($rfid) ?></code> | Horario Ficha: <strong><?= htmlspecialchars($horario_str) ?></strong>
                                </p>
                            </div>
                        </div>
                        <div class="text-sm-end text-center p-3 rounded border border-secondary border-opacity-25" style="background:var(--nk-card-header); min-width:190px;">
                            <small class="text-secondary d-block">Último Ingreso Marcado</small>
                            <span class="text-theme-primary fw-bold fs-5"><?= $hora_entrada_hoy ?></span>
                            <div class="mt-1">
                                <?php if ($marcacion_hoy && $novedad_hoy == 'Retardo'): ?>
                                    <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                                <?php elseif ($marcacion_hoy): ?>
                                    <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Sin Marcación</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-5 col-12">
                    <div class="card border-0 h-100 card-stat-ux" style="background:var(--nk-card-bg);">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary" style="font-size:13px;">Resumen Asistencia Mes</span>
                                <span class="badge bg-success" style="font-size:11px;">Meta SENA >= 85%</span>
                            </div>
                            <h2 class="text-theme-primary fw-bold mb-1"><?= $porcentaje_asistencia_mes ?>%</h2>
                            <div class="progress mb-2" style="height: 9px; background:var(--nk-bg-main); border-radius:10px;">
                                <div class="progress-bar <?= ($porcentaje_asistencia_mes >= 85) ? 'bg-success' : 'bg-warning' ?>" role="progressbar" style="width: <?= $porcentaje_asistencia_mes ?>%; border-radius:10px;" aria-valuenow="<?= $porcentaje_asistencia_mes ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-secondary" style="font-size:12px;">Has asistido a <?= $dias_asistidos_mes ?> de <?= $dias_registrados_mes ?> jornadas este mes.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #24caa1 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">ASISTENCIAS A TIEMPO</small>
                                <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $total_a_tiempo ?> Días</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(36, 202, 161, 0.15); color:#24caa1;">
                                <i class="bi bi-check2-circle fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #f39c12 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">RETARDOS ACUMULADOS</small>
                                <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $total_retardos ?> Novedades</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(243, 156, 18, 0.15); color:#f39c12;">
                                <i class="bi bi-alarm fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><i class="bi bi-clock-history text-warning me-1"></i> Total: <?= $total_minutos_retardo ?> minutos tarde</small>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #eb4b4b !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">INASISTENCIAS</small>
                                <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $total_inasistencias ?> Días</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(235, 75, 75, 0.15); color:#eb4b4b;">
                                <i class="bi bi-x-circle fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #9b59b6 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">EXCUSAS RADICADAS</small>
                                <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $total_excusas ?> Solicitudes</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(155, 89, 182, 0.15); color:#a569bd;">
                                <i class="bi bi-file-earmark-text fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><span class="text-success"><?= $excusas_aprobadas ?> Aprobada(s)</span> | <span class="text-warning"><?= $excusas_pendientes ?> Pendiente(s)</span></small>
                    </div>
                </div>
            </div>

            <!-- ApexChart & Instructor Quick Card -->
            <div class="row g-3 mb-4">
                <!-- ApexChart Section -->
                <div class="col-lg-8 col-12">
                    <div class="card border-0 p-3 card-stat-ux">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="text-theme-primary mb-0 fw-bold"><i class="bi bi-bar-chart-line text-success me-2"></i>Comportamiento Mensual de Asistencia</h5>
                                <small class="text-secondary">Hora de llegada registrada vs hora límite de tolerancia</small>
                            </div>
                            <span class="badge" style="background:var(--nk-border-color); color:#8a9bb5;"><?= date('F Y') ?></span>
                        </div>
                        <div id="chartAsistenciaAprendiz" style="min-height: 300px;"></div>
                    </div>
                </div>

                <!-- Instructor Info Card -->
                <div class="col-lg-4 col-12">
                    <div class="card border-0 p-3 h-100 card-stat-ux">
                        <h5 class="text-theme-primary fw-bold mb-3"><i class="bi bi-info-circle text-info me-2"></i>Información de Ficha</h5>
                        
                        <div class="p-3 rounded mb-3" style="background:var(--nk-card-header); border:1px solid var(--nk-border-color);">
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=120&q=80" class="rounded-circle" style="width:52px; height:52px; object-fit:cover; border:2px solid #24b5ca;" alt="Instructor">
                                <div>
                                    <small class="text-secondary d-block">Instructor a Cargo</small>
                                    <strong class="text-theme-primary"><?= htmlspecialchars($instructor_nombre) ?></strong>
                                    <small class="text-success d-block"><i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($instructor_correo) ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-secondary d-block mb-1">Horario Asignado Ficha <?= htmlspecialchars($numero_ficha) ?>:</small>
                            <div class="d-flex justify-content-between p-2.5 rounded text-theme-primary" style="background:var(--nk-bg-main); font-size:13px; border:1px solid var(--nk-border-color);">
                                <span><i class="bi bi-clock me-1 text-warning"></i> Lunes a Viernes</span>
                                <strong class="text-success"><?= htmlspecialchars($horario_str) ?></strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-auto">
                            <a href="aprendiz-horario.php" class="btn btn-outline-info text-start d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-calendar-range me-2"></i>Ver Calendario Completo</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="aprendiz-excusas.php" class="btn btn-outline-success text-start d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-file-earmark-arrow-up me-2"></i>Radicar Excusa Médica</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance Table -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card border-0 card-stat-ux">
                        <div class="card-header d-flex justify-content-between align-items-center py-3" style="background:var(--nk-card-header); border-bottom:1px solid var(--nk-border-color); margin:-20px -20px 0 -20px; border-radius:10px 10px 0 0;">
                            <h5 class="text-theme-primary mb-0 fw-bold ps-3">
                                <i class="bi bi-list-check me-2 text-success"></i>Últimos Registros de Ingreso por RFID
                            </h5>
                            <a href="aprendiz-historial.php" class="btn btn-sm pe-3 text-theme-primary" style="background:var(--nk-border-color); border:1px solid #314a73;">
                                Ver Historial Completo <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-nalika-dark mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Horario Ficha</th>
                                            <th>Marcación Entrada</th>
                                            <th>Marcación Salida</th>
                                            <th>Retardo (min)</th>
                                            <th>Estado</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Estructura de ejemplo visual. Más adelante puedes hacer un query que llene dinámicamente este tbody -->
                                        <tr>
                                            <td><strong>Hoy (<?= date('d M Y') ?>)</strong></td>
                                            <td><?= htmlspecialchars($horario_str) ?></td>
                                            <td><span class="text-success fw-bold"><?= $hora_entrada_hoy ?></span></td>
                                            <td><span class="text-secondary">En curso</span></td>
                                            <td><?= ($marcacion_hoy['minutos_retardo'] ?? 0) ?> min</td>
                                            <td>
                                                <?php if ($marcacion_hoy && $novedad_hoy == 'Retardo'): ?>
                                                    <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                                                <?php elseif ($marcacion_hoy): ?>
                                                    <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Sin Registro</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><button class="btn btn-sm btn-outline-secondary" disabled>Sin novedad</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="footer-copyright-area mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="footer-copy-right text-center py-3 border-top border-secondary border-opacity-25">
                            <p class="mb-0 text-secondary" style="font-size:13px;">
                                Sistema de Control de Ingreso de Aprendices SENA © <?= date('Y') ?> | Desarrollado para ADSO con plantilla Nalika.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Flotante Volver Arriba -->
    <button type="button" id="btnScrollTop" class="btn-scroll-top" title="Volver al Inicio">
        <i class="bi bi-arrow-up-short"></i>
    </button>

    <!-- Modal Simulador RFID -->
    <div class="modal fade" id="modalSimuladorRfid" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-nalika">
                <div class="modal-header modal-header-nalika">
                    <h5 class="modal-title text-theme-primary fw-bold"><i class="bi bi-broadcast text-success me-2"></i>Simulador de Lector RFID SENA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="p-3 mb-3 rounded" style="background:var(--nk-bg-main); border:1px solid var(--nk-border-color);">
                        <i class="bi bi-upc-scan fs-1 text-success d-block mb-2"></i>
                        <h6 class="text-theme-primary mb-1">Acerque su Llavero RFID al Lector</h6>
                        <small class="text-secondary">Código Llavero Aprendiz: <strong><?= htmlspecialchars($rfid) ?></strong></small>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-success w-100 py-2" onclick="simularMarcacion('Entrada (06:55 AM) - A TIEMPO', 'success')">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Marcar Entrada (A Tiempo)
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" class="btn btn-warning w-100 py-2 text-dark fw-bold" onclick="simularMarcacion('Entrada (07:18 AM) - RETARDO', 'warning')">
                                <i class="bi bi-clock-history me-1"></i> Marcar Entrada (Retardo)
                            </button>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="button" class="btn btn-info w-100 py-2" onclick="simularMarcacion('Salida (01:02 PM) - REGISTRADA', 'info')">
                                <i class="bi bi-box-arrow-right me-1"></i> Marcar Salida de Jornada
                            </button>
                        </div>
                    </div>

                    <div id="rfidResultAlert" class="mt-3 alert alert-success d-none" role="alert"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
    <script src="../../../public/assets/js/main.js"></script>
    <script src="../../../public/assets/js/aprendiz-custom.js"></script>

    <!-- UI/UX Clock & Scroll Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Live Clock Script
            function actualizarReloj() {
                var ahora = new Date();
                var horas = ahora.getHours();
                var minutos = ahora.getMinutes();
                var segundos = ahora.getSeconds();
                var ampm = horas >= 12 ? 'PM' : 'AM';
                horas = horas % 12;
                horas = horas ? horas : 12;
                minutos = minutos < 10 ? '0' + minutos : minutos;
                segundos = segundos < 10 ? '0' + segundos : segundos;
                var strTiempo = horas + ':' + minutos + ':' + segundos + ' ' + ampm;
                var relojEl = document.getElementById('relojLive');
                if (relojEl) relojEl.innerHTML = '<i class="bi bi-clock-history me-1"></i> ' + strTiempo;
            }
            setInterval(actualizarReloj, 1000);
            actualizarReloj();

            // Mobile Sidebar Close Button Listener
            var closeBtnMobile = document.getElementById('sidebarCloseMobile');
            if (closeBtnMobile) {
                closeBtnMobile.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }

            // Scroll Progress Bar & Scroll To Top
            window.addEventListener('scroll', function() {
                var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                var scrolled = (winScroll / height) * 100;
                var progressBar = document.getElementById('scrollProgressBar');
                if (progressBar) progressBar.style.width = scrolled + '%';

                var btnTop = document.getElementById('btnScrollTop');
                if (btnTop) {
                    if (winScroll > 200) {
                        btnTop.classList.add('show');
                    } else {
                        btnTop.classList.remove('show');
                    }
                }
            });

            var btnTop = document.getElementById('btnScrollTop');
            if (btnTop) {
                btnTop.addEventListener('click', function() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            // Tooltips Init
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // ApexCharts Initialization for Aprendiz Attendance
            var options = {
                series: [{
                    name: 'Hora de Ingreso (Minutos transcurridos desde hora límite)',
                    data: [-6, -2, 14, 0, -9, -4, -1, -5, 12, -3]
                }],
                chart: {
                    type: 'area',
                    height: 290,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                colors: ['#24caa1'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: ['28 Jul', '29 Jul', '30 Jul', '31 Jul', '03 Ago', '04 Ago', '05 Ago', '06 Ago', '07 Ago', '10 Ago'],
                    labels: { style: { colors: '#8a9bb5' } },
                    axisBorder: { color: 'var(--nk-border-color)' }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#8a9bb5' },
                        formatter: function (val) {
                            return val > 0 ? '+' + val + ' min (Retardo)' : val + ' min (A tiempo)';
                        }
                    }
                },
                grid: { borderColor: 'var(--nk-border-color)' },
                theme: { mode: 'dark' },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return val > 0 ? 'Retardo de ' + val + ' minutos' : 'Llegada ' + Math.abs(val) + ' min antes';
                        }
                    }
                }
            };

            var chart = new ApexCharts(document.querySelector("#chartAsistenciaAprendiz"), options);
            chart.render();
        });

        function simularMarcacion(texto, tipo) {
            var alertEl = document.getElementById('rfidResultAlert');
            alertEl.className = 'mt-3 alert alert-' + tipo;
            alertEl.innerHTML = '<strong><i class="bi bi-check-circle-fill me-1"></i> ¡Marcación RFID Registrada!</strong><br>' + texto;
            alertEl.classList.remove('d-none');
        }
    </script>
</body>
</html>

