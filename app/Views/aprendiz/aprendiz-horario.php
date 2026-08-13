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

} catch (PDOException $e) {
    die("Error al cargar datos básicos: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Mi Horario & Calendario | Control de Asistencia SENA</title>
    <meta name="description" content="Calendario interactivo del Aprendiz SENA">
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
    <!-- SimpleBar 6.2.7 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.css">
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="../../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link rel="stylesheet" href="../../../public/assets/css/responsive.css">
    <!-- Aprendiz SENA Custom UX/UI CSS -->
    <link rel="stylesheet" href="../../../public/assets/css/aprendiz-custom.css">
    
    <!-- FullCalendar CSS Customization for Dark Theme -->
    <style>
        /* Adaptación de FullCalendar al Dark Mode de Nalika */
        #calendar {
            background-color: var(--nk-card-header);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--nk-border-color);
            color: #fff;
        }
        
        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid var(--nk-border-color);
        }
        
        .fc-theme-standard td, .fc-theme-standard th {
            border: 1px solid var(--nk-border-color);
        }
        
        .fc-col-header-cell {
            background-color: var(--nk-card-bg);
            padding: 10px 0;
            color: #24b5ca;
        }
        
        .fc-day-today {
            background-color: rgba(36, 181, 202, 0.1) !important;
        }
        
        .fc-daygrid-day-number {
            color: #8a9bb5;
            font-weight: 500;
            padding: 8px !important;
        }
        
        .fc-day-today .fc-daygrid-day-number {
            color: #24caa1;
            font-weight: bold;
        }
        
        /* Botones del Toolbar */
        .fc .fc-button-primary {
            background-color: var(--nk-border-color);
            border-color: #314a73;
            color: #fff;
        }
        
        .fc .fc-button-primary:hover {
            background-color: #314a73;
            border-color: #24b5ca;
        }
        
        .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #24caa1;
            border-color: #24caa1;
            color: #fff;
        }
        
        .fc .fc-toolbar-title {
            font-size: 1.5em;
            color: #fff;
            font-weight: 700;
        }

        /* Estilos de Eventos */
        .fc-event {
            border-radius: 4px;
            border: none;
            padding: 2px 5px;
            font-size: 0.85em;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .fc-event:hover {
            transform: scale(1.02);
            filter: brightness(1.2);
        }
        
        .event-clase { background-color: var(--nk-border-color); color: #fff; border-left: 3px solid #24b5ca; }
        .event-novedad { background-color: rgba(243, 156, 18, 0.2); color: #f39c12; border-left: 3px solid #f39c12; }
    </style>
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
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Aprendiz') ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Aprendiz ADSO - Ficha <?= htmlspecialchars($numero_ficha) ?></small>
                </div>
                <div class="profile-social-dtl mt-1">
                    <span class="sena-badge-tag"><i class="bi bi-upc-scan"></i> RFID: <?= htmlspecialchars($rfid) ?></span>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li>
                            <a href="aprendiz-dashboard.php">
                                <i class="bi bi-speedometer2 icon-wrap"></i>
                                <span class="mini-click-non">Dashboard Principal</span>
                            </a>
                        </li>
                        <li class="active">
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
                            <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" onclick="toggleSidebarGlobal(event)" title="Alternar Menú Lateral">
                                <i class="bi bi-list"></i>
                            </button>
                            <div class="d-flex align-items-center gap-2 ms-2">
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - ADSO</span>
                            </div>
                        </div>

                        <!-- Live Clock Widget & Quick Controls -->
                        <div class="d-flex align-items-center gap-3">
                            <span id="relojLive" class="nk-live-clock d-none d-sm-inline-flex">
                                <i class="bi bi-clock-history"></i> --:--:-- --
                            </span>
                            
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
                                    <li><a class="dropdown-header text-muted">Ficha <?= htmlspecialchars($numero_ficha) ?></a></li>
                                    <li><a class="dropdown-item" href="aprendiz-perfil.php"><i class="bi bi-person me-2 text-success"></i>Mi Perfil</a></li>
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
                    <span class="text-theme-primary fw-semibold">Mi Horario & Calendario</span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Jornada Activa: <strong><?= htmlspecialchars($horario_str) ?></strong>
                </div>
            </div>
        </div>

        <!-- Dashboard Main Content -->
        <div class="section-admin container-fluid mt-4">
            
            <!-- Welcome Header -->
            <div class="sena-welcome-banner mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                        <i class="bi bi-calendar-check fs-2"></i>
                    </div>
                    <div>
                        <h3 class="text-theme-primary fw-bold mb-1">Mi Horario de Formación</h3>
                        <p class="text-secondary mb-0" style="font-size:14px;">Consulta tus horarios asignados, novedades y eventos académicos.</p>
                    </div>
                </div>
            </div>

            <!-- Resumen Horario Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4 col-sm-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24b5ca !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-secondary fw-bold">FICHA & PROGRAMA</small>
                                <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= htmlspecialchars($numero_ficha) ?></h5>
                                <small class="text-info"><?= htmlspecialchars($programa) ?></small>
                            </div>
                            <i class="bi bi-mortarboard text-info fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24caa1 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-secondary fw-bold">JORNADA ASIGNADA</small>
                                <h5 class="text-theme-primary fw-bold mb-0 mt-1">Lunes a Viernes</h5>
                                <small class="text-success"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($horario_str) ?></small>
                            </div>
                            <i class="bi bi-sun text-success fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-secondary fw-bold">LÍMITE DE INGRESO</small>
                                <h5 class="text-theme-primary fw-bold mb-0 mt-1">Margen de Tolerancia</h5>
                                <small class="text-warning">15 Minutos después de entrada</small>
                            </div>
                            <i class="bi bi-hourglass-split text-warning fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendario FullCalendar -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 card-stat-ux">
                        <div class="card-body">
                            <div id="calendar"></div>
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

    <!-- Modal Detalle Evento -->
    <div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-nalika">
                <div class="modal-header modal-header-nalika">
                    <h5 class="modal-title text-theme-primary fw-bold" id="eventoTitulo"><i class="bi bi-info-circle text-info me-2"></i>Detalle de Clase</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                        <small class="text-secondary d-block">Fecha y Hora</small>
                        <strong class="text-theme-primary fs-5" id="eventoFecha"></strong>
                    </div>
                    <div>
                        <small class="text-secondary d-block">Descripción</small>
                        <p class="text-theme-primary mb-0" id="eventoDesc"></p>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <!-- FullCalendar v6 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    
    <script src="../../../public/assets/js/main.js"></script>
    <script src="../../../public/assets/js/aprendiz-custom.js"></script>

    <!-- Init Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Reloj en vivo
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

            // Mobile sidebar toggle
            var closeBtnMobile = document.getElementById('sidebarCloseMobile');
            if (closeBtnMobile) {
                closeBtnMobile.addEventListener('click', function () {
                    document.body.classList.remove('sidebar-mobile-open');
                });
            }

            // Init FullCalendar
            var calendarEl = document.getElementById('calendar');
            
            // Generar eventos simulados recurrentes basados en el horario
            var horaEntrada = "<?= htmlspecialchars($hora_entrada_raw) ?>";
            var horaSalida = "<?= htmlspecialchars($hora_salida_raw) ?>";
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Agenda'
                },
                firstDay: 1, // Lunes
                height: 700,
                events: function(fetchInfo, successCallback, failureCallback) {
                    // Aquí se conectarían en el futuro los eventos reales vía AJAX.
                    // Por ahora, simularemos clases recurrentes de Lunes a Viernes.
                    var events = [];
                    var startDate = fetchInfo.start;
                    var endDate = fetchInfo.end;
                    
                    var currentDate = new Date(startDate);
                    
                    while(currentDate <= endDate) {
                        var day = currentDate.getDay();
                        // 1: Lunes a 5: Viernes
                        if(day >= 1 && day <= 5) {
                            var dateStr = currentDate.toISOString().split('T')[0];
                            events.push({
                                title: 'Formación ADSO',
                                start: dateStr + 'T' + horaEntrada,
                                end: dateStr + 'T' + horaSalida,
                                className: 'event-clase',
                                extendedProps: {
                                    description: 'Jornada regular de formación para la ficha <?= htmlspecialchars($numero_ficha) ?>.'
                                }
                            });
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }
                    
                    // Agregar un evento simulado de Novedad/Retardo (para UI demostrativa)
                    var today = new Date();
                    var lastWeek = new Date(today);
                    lastWeek.setDate(today.getDate() - 3);
                    if(lastWeek.getDay() === 0 || lastWeek.getDay() === 6) lastWeek.setDate(lastWeek.getDate() - 2); // Evitar fin de semana
                    
                    events.push({
                        title: 'Novedad: Retardo',
                        start: lastWeek.toISOString().split('T')[0] + 'T' + horaEntrada,
                        allDay: true,
                        className: 'event-novedad',
                        extendedProps: {
                            description: 'Se registró una llegada tarde. Se debe radicar excusa si aplica.'
                        }
                    });

                    successCallback(events);
                },
                eventClick: function(info) {
                    // Mostrar modal con detalles del evento
                    document.getElementById('eventoTitulo').innerHTML = '<i class="bi bi-info-circle text-info me-2"></i>' + info.event.title;
                    
                    var startFormat = info.event.start.toLocaleString('es-CO', { 
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                    
                    var endFormat = info.event.end ? ' - ' + info.event.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
                    
                    document.getElementById('eventoFecha').innerText = startFormat + endFormat;
                    document.getElementById('eventoDesc').innerText = info.event.extendedProps.description || 'Sin detalles adicionales.';
                    
                    var myModal = new bootstrap.Modal(document.getElementById('modalEvento'));
                    myModal.show();
                }
            });
            
            calendar.render();
        });
    </script>
</body>
</html>

