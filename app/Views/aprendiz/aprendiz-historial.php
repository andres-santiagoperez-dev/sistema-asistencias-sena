<?php 
session_start(); 

// 1. Conexión a la base de datos
require_once '../../config/database.php'; 
$pdo = Database::conectar(); 

$id_aprendiz = $_SESSION['id_aprendiz'];

try {
    // 2. Consultar Información Básica (Ficha, Horario, RFID) para Sidebar y Topbar
    $sql_info = "SELECT a.codigo_rfid, f.numero_ficha, f.programa, h.hora_entrada, h.hora_salida
                 FROM aprendices a
                 LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                 LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                 WHERE a.id_aprendiz = :id_aprendiz";
    
    $stmt_info = $pdo->prepare($sql_info);
    $stmt_info->execute([':id_aprendiz' => $id_aprendiz]);
    $info_aprendiz = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $rfid = $info_aprendiz['codigo_rfid'] ?? 'Sin Asignar';
    $numero_ficha = $info_aprendiz['numero_ficha'] ?? 'Sin Ficha';
    $programa = $info_aprendiz['programa'] ?? 'Sin Programa';
    
    $hora_entrada_raw = $info_aprendiz['hora_entrada'] ?? '00:00';
    $hora_salida_raw = $info_aprendiz['hora_salida'] ?? '00:00';
    $horario_str = $hora_entrada_raw . ' - ' . $hora_salida_raw;

    // 3. Obtener filtros de fecha si existen
    $fecha_inicio = $_GET['fecha_inicio'] ?? '';
    $fecha_fin = $_GET['fecha_fin'] ?? '';
    
    $where_clause = "WHERE aprendices_id_aprendiz = :id_aprendiz";
    $params = [':id_aprendiz' => $id_aprendiz];
    
    if (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $where_clause .= " AND fecha BETWEEN :fecha_inicio AND :fecha_fin";
        $params[':fecha_inicio'] = $fecha_inicio;
        $params[':fecha_fin'] = $fecha_fin;
    }

    // 4. Consultar Historial de Asistencia
    $sql_historial = "SELECT fecha, hora_entrada, hora_salida, estado, minutos_retardo 
                      FROM asistencia 
                      $where_clause
                      ORDER BY fecha DESC";
    $stmt_historial = $pdo->prepare($sql_historial);
    $stmt_historial->execute($params);
    $historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

    // 5. Estadísticas Rápidas
    $sql_stats = "SELECT 
                    SUM(CASE WHEN estado = 'Asistio' THEN 1 ELSE 0 END) AS a_tiempo,
                    SUM(CASE WHEN estado = 'Retardo' THEN 1 ELSE 0 END) AS retardos,
                    SUM(CASE WHEN estado = 'Inasistencia' THEN 1 ELSE 0 END) AS inasistencias
                  FROM asistencia $where_clause";
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar datos básicos: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Historial de Asistencia | Control de Ingreso SENA</title>
    <meta name="description" content="Historial de marcaciones del Aprendiz SENA">
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
    
    <!-- DataTables CSS for Dark Mode -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        .table-nalika-dark th, .table-nalika-dark td {
            background-color: transparent !important;
            color: #fff;
            border-bottom: 1px solid var(--nk-border-color);
        }
        .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_processing,
        .dataTables_wrapper .dataTables_paginate {
            color: #8a9bb5;
            margin-bottom: 15px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #24caa1 !important;
            border-color: #24caa1 !important;
            color: white !important;
        }
        .dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
            background-color: var(--nk-bg-main);
            border: 1px solid var(--nk-border-color);
            color: #fff;
        }
    </style>
</head>

<body>
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <!-- Left Sidebar -->
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="aprendiz-dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <img class="main-logo" src="img/logo/logo.png" alt="SENA" style="max-height: 38px;" />
                    <span class="text-theme-primary fw-bold d-none d-sm-inline" style="font-size:13px; letter-spacing:0.5px;">ADSO SENA</span>
                </a>
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="aprendiz-perfil.php" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ver Mi Perfil Completo">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Foto Aprendiz" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 10px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Aprendiz') ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Aprendiz ADSO - Ficha <?= htmlspecialchars($numero_ficha) ?></small>
                </div>
                <div class="profile-social-dtl mt-1">
                    <span class="sena-badge-tag"><i class="bi bi-upc-scan"></i> RFID: <?= htmlspecialchars($rfid) ?></span>
                </div>
            </div>

            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li>
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
                        <li class="active">
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
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="nk-header-bar d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" onclick="toggleSidebarGlobal(event)">
                                <i class="bi bi-list"></i>
                            </button>
                            <div class="d-flex align-items-center gap-2 ms-2">
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px;">SENA - ADSO</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
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
                                    <li><a class="dropdown-item" href="aprendiz-horario.php"><i class="bi bi-calendar me-2 text-info"></i>Ver Horario</a></li>
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
                    <span class="text-theme-primary fw-semibold">Historial de Asistencia</span>
                </div>
            </div>
        </div>

        <div class="section-admin container-fluid mt-4">
            
            <div class="sena-welcome-banner mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                    <div>
                        <h3 class="text-theme-primary fw-bold mb-1">Historial de Marcaciones</h3>
                        <p class="text-secondary mb-0" style="font-size:14px;">Consulta todas tus entradas y salidas registradas con tu llavero RFID.</p>
                    </div>
                </div>
            </div>

            <!-- Filtros de Fecha -->
            <div class="card border-0 card-stat-ux mb-4 p-3">
                <form method="GET" action="aprendiz-historial.php" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-secondary fw-bold" style="font-size:12px;">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($fecha_inicio) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-secondary fw-bold" style="font-size:12px;">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars($fecha_fin) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-sena-green me-2"><i class="bi bi-filter"></i> Filtrar</button>
                        <a href="aprendiz-historial.php" class="btn" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border: 1px solid var(--nk-border-color);"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</a>
                    </div>
                </form>
            </div>

            <!-- Resumen Estadístico KPI -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24caa1 !important;">
                        <small class="text-secondary fw-bold">TOTAL ASISTENCIAS</small>
                        <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $stats['a_tiempo'] ?? 0 ?></h3>
                        <small class="text-success"><i class="bi bi-check-circle me-1"></i>A tiempo</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
                        <small class="text-secondary fw-bold">RETARDOS</small>
                        <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $stats['retardos'] ?? 0 ?></h3>
                        <small class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Acumulados</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #eb4b4b !important;">
                        <small class="text-secondary fw-bold">INASISTENCIAS</small>
                        <h3 class="text-theme-primary fw-bold mb-0 mt-1"><?= $stats['inasistencias'] ?? 0 ?></h3>
                        <small class="text-danger"><i class="bi bi-x-circle me-1"></i>Faltas totales</small>
                    </div>
                </div>
            </div>

            <!-- Tabla de Historial -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 card-stat-ux">
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table id="tablaHistorial" class="table table-nalika-dark table-hover w-100 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Hora de Entrada</th>
                                            <th>Hora de Salida</th>
                                            <th>Min. Retardo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($historial)): ?>
                                            <!-- Si no hay historial real, mostramos datos simulados para la maqueta -->
                                            <tr>
                                                <td>2026-08-11</td>
                                                <td><span class="text-success fw-bold">05:50 AM</span></td>
                                                <td><span class="text-info fw-bold">12:05 PM</span></td>
                                                <td>0 min</td>
                                                <td><span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span></td>
                                            </tr>
                                            <tr>
                                                <td>2026-08-10</td>
                                                <td><span class="text-success fw-bold">06:15 AM</span></td>
                                                <td><span class="text-info fw-bold">11:50 AM</span></td>
                                                <td>15 min</td>
                                                <td><span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span></td>
                                            </tr>
                                            <tr>
                                                <td>2026-08-09</td>
                                                <td>--</td>
                                                <td>--</td>
                                                <td>--</td>
                                                <td><span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> Inasistencia</span></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach($historial as $row): ?>
                                                <tr>
                                                    <td><?= date('d M Y', strtotime($row['fecha'])) ?></td>
                                                    <td>
                                                        <?php if($row['hora_entrada']): ?>
                                                            <span class="text-success fw-bold"><?= date('h:i A', strtotime($row['hora_entrada'])) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-secondary">--:--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if($row['hora_salida']): ?>
                                                            <span class="text-info fw-bold"><?= date('h:i A', strtotime($row['hora_salida'])) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-secondary">--:--</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= $row['minutos_retardo'] ?> min</td>
                                                    <td>
                                                        <?php if($row['estado'] == 'Asistio'): ?>
                                                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                                                        <?php elseif($row['estado'] == 'Retardo'): ?>
                                                            <span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger"><i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($row['estado']) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

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

    <button type="button" id="btnScrollTop" class="btn-scroll-top" title="Volver al Inicio">
        <i class="bi bi-arrow-up-short"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    
    <script src="../../../public/assets/js/main.js"></script>
    <script src="../../../public/assets/js/aprendiz-custom.js"></script>

    <script>
        $(document).ready(function() {
            $('#tablaHistorial').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "order": [[ 0, "desc" ]],
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Todos"]]
            });
            
            // Reloj en vivo
            function actualizarReloj() {
                var ahora = new Date();
                var strTiempo = ahora.toLocaleTimeString('es-CO');
                var relojEl = document.getElementById('relojLive');
                if (relojEl) relojEl.innerHTML = '<i class="bi bi-clock-history me-1"></i> ' + strTiempo;
            }
            setInterval(actualizarReloj, 1000);
            actualizarReloj();
        });
    </script>
</body>
</html>

