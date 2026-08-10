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
                <a href="aprendiz-dashboard.html" class="d-flex align-items-center gap-2 text-decoration-none">
                    <img class="main-logo" src="img/logo/logo.png" alt="SENA" style="max-height: 38px;" />
                    <span class="text-white fw-bold d-none d-sm-inline" style="font-size:13px; letter-spacing:0.5px;">ADSO SENA</span>
                </a>
                <!-- Mobile Close Button -->
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Aprendiz Profile Dtl -->
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="aprendiz-perfil.html" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ver Mi Perfil Completo">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Foto Aprendiz" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 10px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-white mb-0" style="font-size:14px; font-weight:600;">Carlos Mendoza</h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Aprendiz ADSO - Ficha 2670123</small>
                </div>
                <div class="profile-social-dtl mt-1">
                    <span class="sena-badge-tag"><i class="bi bi-upc-scan"></i> RFID: #883921</span>
                </div>
            </div>

            <!-- Navigation Menu (Sin Scroll) -->
            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li class="active">
                            <a href="aprendiz-dashboard.html">
                                <i class="bi bi-speedometer2 icon-wrap"></i>
                                <span class="mini-click-non">Dashboard Principal</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-horario.html">
                                <i class="bi bi-calendar3 icon-wrap"></i>
                                <span class="mini-click-non">Mi Horario & Calendario</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-historial.html">
                                <i class="bi bi-clock-history icon-wrap"></i>
                                <span class="mini-click-non">Historial de Asistencia</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-excusas.html">
                                <i class="bi bi-file-earmark-medical icon-wrap"></i>
                                <span class="mini-click-non">Mis Excusas Médicas</span>
                            </a>
                        </li>
                        <li>
                            <a href="aprendiz-perfil.html">
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

                            <!-- User Profile Dropdown -->
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline">Carlos Mendoza</span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-header text-muted">Aprendiz - Ficha 2670123</a></li>
                                    <li><a class="dropdown-item" href="aprendiz-perfil.html"><i class="bi bi-person me-2 text-success"></i>Mi Perfil</a></li>
                                    <li><a class="dropdown-item" href="aprendiz-horario.html"><i class="bi bi-calendar me-2 text-info"></i>Ver Horario</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="login.html"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation Bar -->
            <div class="sena-breadcrumb-bar d-flex align-items-center justify-content-between">
                <div>
                    <a href="aprendiz-dashboard.html"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-white fw-semibold">Dashboard Principal</span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Jornada Activa: <strong>07:00 AM - 01:00 PM</strong> | Lector RFID: <span class="text-success">CONECTADO</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Main Content -->
        <div class="section-admin container-fluid mt-4">
            
            <!-- Sena Welcome Banner -->
            <div class="sena-welcome-banner d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="text-white fw-bold mb-0">¡Bienvenido de nuevo, Carlos! 👋</h3>
                        <span class="badge bg-success" style="font-size:11px;">Aprendiz Activo</span>
                    </div>
                    <p class="text-secondary mb-0 mt-1" style="font-size:14px;">
                        Ficha <strong>2670123 - Análisis y Desarrollo de Software (ADSO)</strong> | Instructor Líder: <strong>Juan Camilo Vanegas González</strong>
                    </p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="aprendiz-horario.html" class="btn btn-sm" style="background:#253a5c; color:#fff; border:1px solid #314a73;">
                        <i class="bi bi-calendar-event me-1 text-info"></i> Mi Horario
                    </a>
                    <a href="aprendiz-excusas.html" class="btn btn-sm btn-sena-green">
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
                                    <h5 class="text-white mb-0 fw-bold">Estado RFID de Hoy</h5>
                                    <span class="rfid-pulse"></span>
                                    <span class="text-success fw-bold" style="font-size:12px;">REGISTRADO</span>
                                </div>
                                <p class="text-secondary mb-0 mt-1" style="font-size:13px;">
                                    Llavero RFID Vinculado: <code style="color:#24b5ca;">#RFID-883921</code> | Horario Ficha: <strong>07:00 AM - 01:00 PM</strong>
                                </p>
                            </div>
                        </div>
                        <div class="text-sm-end text-center p-3 rounded border border-secondary border-opacity-25" style="background:#162544; min-width:190px;">
                            <small class="text-secondary d-block">Último Ingreso Marcado</small>
                            <span class="text-white fw-bold fs-5">06:54:12 AM</span>
                            <div class="mt-1"><span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-5 col-12">
                    <div class="card border-0 h-100 card-stat-ux" style="background:#1b2a47;">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary" style="font-size:13px;">Resumen Asistencia Mes</span>
                                <span class="badge bg-success" style="font-size:11px;">Meta SENA >= 85%</span>
                            </div>
                            <h2 class="text-white fw-bold mb-1">95.8%</h2>
                            <div class="progress mb-2" style="height: 9px; background:#152036; border-radius:10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 95.8%; border-radius:10px;" aria-valuenow="95.8" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-secondary" style="font-size:12px;">Has asistido a 23 de 24 jornadas este mes.</small>
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
                                <h3 class="text-white fw-bold mb-0 mt-1">22 Días</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(36, 202, 161, 0.15); color:#24caa1;">
                                <i class="bi bi-check2-circle fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><i class="bi bi-graph-up-arrow text-success me-1"></i> 91.6% del total registrado</small>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #f39c12 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">RETARDOS ACUMULADOS</small>
                                <h3 class="text-white fw-bold mb-0 mt-1">2 Novedades</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(243, 156, 18, 0.15); color:#f39c12;">
                                <i class="bi bi-alarm fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><i class="bi bi-clock-history text-warning me-1"></i> Total: 18 minutos tarde</small>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #eb4b4b !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">INASISTENCIAS</small>
                                <h3 class="text-white fw-bold mb-0 mt-1">1 Día</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(235, 75, 75, 0.15); color:#eb4b4b;">
                                <i class="bi bi-x-circle fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><i class="bi bi-file-earmark-check text-info me-1"></i> Justificada con excusa médica</small>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="card border-0 p-3 card-stat-ux" style="border-left:4px solid #9b59b6 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-secondary fw-bold">EXCUSAS RADICADAS</small>
                                <h3 class="text-white fw-bold mb-0 mt-1">2 Solicitudes</h3>
                            </div>
                            <div class="rounded-circle p-3" style="background:rgba(155, 89, 182, 0.15); color:#a569bd;">
                                <i class="bi bi-file-earmark-text fs-3"></i>
                            </div>
                        </div>
                        <small class="text-secondary mt-2 d-block" style="font-size:12px;"><span class="text-success">1 Aprobada</span> | <span class="text-warning">1 Pendiente</span></small>
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
                                <h5 class="text-white mb-0 fw-bold"><i class="bi bi-bar-chart-line text-success me-2"></i>Comportamiento Mensual de Asistencia</h5>
                                <small class="text-secondary">Hora de llegada registrada vs hora límite de tolerancia (07:10 AM)</small>
                            </div>
                            <span class="badge" style="background:#253a5c; color:#8a9bb5;">Agosto 2026</span>
                        </div>
                        <div id="chartAsistenciaAprendiz" style="min-height: 300px;"></div>
                    </div>
                </div>

                <!-- Instructor Info Card -->
                <div class="col-lg-4 col-12">
                    <div class="card border-0 p-3 h-100 card-stat-ux">
                        <h5 class="text-white fw-bold mb-3"><i class="bi bi-info-circle text-info me-2"></i>Información de Ficha</h5>
                        
                        <div class="p-3 rounded mb-3" style="background:#162544; border:1px solid #253a5c;">
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=120&q=80" class="rounded-circle" style="width:52px; height:52px; object-fit:cover; border:2px solid #24b5ca;" alt="Instructor">
                                <div>
                                    <small class="text-secondary d-block">Instructor a Cargo</small>
                                    <strong class="text-white">Juan Camilo Vanegas G.</strong>
                                    <small class="text-success d-block"><i class="bi bi-envelope-fill me-1"></i>jvanegas@sena.edu.co</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-secondary d-block mb-1">Horario Asignado Ficha 2670123:</small>
                            <div class="d-flex justify-content-between p-2.5 rounded text-white" style="background:#152036; font-size:13px; border:1px solid #253a5c;">
                                <span><i class="bi bi-clock me-1 text-warning"></i> Lunes a Viernes</span>
                                <strong class="text-success">07:00 AM - 01:00 PM</strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-auto">
                            <a href="aprendiz-horario.html" class="btn btn-outline-info text-start d-flex align-items-center justify-content-between">
                                <span><i class="bi bi-calendar-range me-2"></i>Ver Calendario Completo</span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                            <a href="aprendiz-excusas.html" class="btn btn-outline-success text-start d-flex align-items-center justify-content-between">
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
                        <div class="card-header d-flex justify-content-between align-items-center py-3" style="background:#162544; border-bottom:1px solid #253a5c; margin:-20px -20px 0 -20px; border-radius:10px 10px 0 0;">
                            <h5 class="text-white mb-0 fw-bold ps-3">
                                <i class="bi bi-list-check me-2 text-success"></i>Últimos Registros de Ingreso por RFID
                            </h5>
                            <a href="aprendiz-historial.html" class="btn btn-sm pe-3 text-white" style="background:#253a5c; border:1px solid #314a73;">
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
                                        <tr>
                                            <td><strong>Hoy (10 Ago 2026)</strong></td>
                                            <td>07:00 AM - 01:00 PM</td>
                                            <td><span class="text-success fw-bold">06:54:12 AM</span></td>
                                            <td><span class="text-secondary">En curso</span></td>
                                            <td>0 min</td>
                                            <td><span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span></td>
                                            <td><button class="btn btn-sm btn-outline-secondary" disabled>Sin novedad</button></td>
                                        </tr>
                                        <tr>
                                            <td>07 Ago 2026</td>
                                            <td>07:00 AM - 01:00 PM</td>
                                            <td><span class="text-success fw-bold">06:58:30 AM</span></td>
                                            <td><span class="text-white">01:02:15 PM</span></td>
                                            <td>0 min</td>
                                            <td><span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> A Tiempo</span></td>
                                            <td><button class="btn btn-sm btn-outline-secondary" disabled>Sin novedad</button></td>
                                        </tr>
                                        <tr>
                                            <td>06 Ago 2026</td>
                                            <td>07:00 AM - 01:00 PM</td>
                                            <td><span class="text-warning fw-bold">07:14:05 AM</span></td>
                                            <td><span class="text-white">01:00:40 PM</span></td>
                                            <td>14 min</td>
                                            <td><span class="badge-sena badge-late"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span></td>
                                            <td><a href="aprendiz-excusas.html" class="btn btn-sm btn-outline-warning"><i class="bi bi-plus"></i> Excusa</a></td>
                                        </tr>
                                        <tr>
                                            <td>05 Ago 2026</td>
                                            <td>07:00 AM - 01:00 PM</td>
                                            <td><span class="text-danger fw-bold">No Registra</span></td>
                                            <td><span class="text-danger fw-bold">No Registra</span></td>
                                            <td>-</td>
                                            <td><span class="badge-sena badge-excuse"><i class="bi bi-file-medical-fill"></i> Excusa Aprobada</span></td>
                                            <td><span class="badge bg-secondary" style="font-size:11px;">Radicado #EX-402</span></td>
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
                                Sistema de Control de Ingreso de Aprendices SENA © 2026 | Desarrollado para ADSO con plantilla Nalika.
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
                    <h5 class="modal-title text-white fw-bold"><i class="bi bi-broadcast text-success me-2"></i>Simulador de Lector RFID SENA</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="p-3 mb-3 rounded" style="background:#152036; border:1px solid #253a5c;">
                        <i class="bi bi-upc-scan fs-1 text-success d-block mb-2"></i>
                        <h6 class="text-white mb-1">Acerque su Llavero RFID al Lector</h6>
                        <small class="text-secondary">Código Llavero Aprendiz: <strong>#RFID-883921</strong></small>
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
                    name: 'Hora de Ingreso (Minutos transcurridos desde 07:00 AM)',
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
                    axisBorder: { color: '#253a5c' }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#8a9bb5' },
                        formatter: function (val) {
                            return val > 0 ? '+' + val + ' min (Retardo)' : val + ' min (A tiempo)';
                        }
                    }
                },
                grid: { borderColor: '#253a5c' },
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
