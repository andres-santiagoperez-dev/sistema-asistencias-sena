<?php
/**
 * Layout: Header Unificado del Portal del Aprendiz
 * Sistema de Control de Asistencia SENA
 */

require_once dirname(__DIR__, 2) . '/config/init.php';

// Verificación de autenticación y rol de Aprendiz
if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'aprendiz') === false) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit();
}

$tituloPagina  = $tituloPagina ?? 'Portal del Aprendiz';
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Aprendiz SENA';
$numeroFicha   = $infoAprendiz['numero_ficha'] ?? $_SESSION['numero_ficha'] ?? 'Sin Ficha';
$programa      = $infoAprendiz['programa'] ?? 'ADSO';
$codigoRfid    = $infoAprendiz['codigo_rfid'] ?? $_SESSION['codigo_rfid'] ?? 'Sin Asignar';
$horarioStr    = $horarioStr ?? (($infoAprendiz['hora_entrada'] ?? '06:00') . ' - ' . ($infoAprendiz['hora_salida'] ?? '12:00'));

$temaActual = 'nalika';
$logoSena   = BASE_URL . '/public/assets/img/logo/logo_sena_verde.svg';
$currentPage = $_GET['page'] ?? 'aprendiz_horario';
?>
<!doctype html>
<html lang="es" data-theme="nalika">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina) ?> | Portal Aprendiz SENA</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?= BASE_URL ?>/public/assets/img/favicon.ico">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3.8 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    
    <!-- Bootstrap Icons 1.13.1 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    
    <!-- Nalika Icon CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/nalika-icon.css">
    
    <!-- SimpleBar 6.2.7 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.css">
    
    <!-- DataTables CSS for Dark Mode (si se requiere) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/responsive.css?v=<?= time() ?>">
    
    <!-- Aprendiz Custom UX/UI CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/aprendiz-custom.css?v=<?= time() ?>">
    <!-- Sistema de Temas Pastel Accesible e Inclusivo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/theme.css?v=<?= time() ?>">
    
    <style id="sena-sidebar-scrollbar-css">
        :root, [data-theme="nalika"] {
            --sidebar-scroll-track-bg: #f8fafc;
            --sidebar-scroll-border: #e2e8f0;
            --sidebar-scroll-thumb: #cbd5e1;
            --sidebar-scroll-thumb-hover: #94a3b8;
            --sidebar-scroll-thumb-active: #16a34a;
            --sidebar-scroll-arrow: #64748b;
            --sidebar-scroll-arrow-hover: #16a34a;
            --sidebar-scroll-arrow-bg-hover: rgba(22, 163, 74, 0.15);
        }

        [data-theme="dark"] {
            --sidebar-scroll-track-bg: #111827;
            --sidebar-scroll-border: #1f2937;
            --sidebar-scroll-thumb: #475569;
            --sidebar-scroll-thumb-hover: #64748b;
            --sidebar-scroll-thumb-active: #388e3c;
            --sidebar-scroll-arrow: #94a3b8;
            --sidebar-scroll-arrow-hover: #f8fafc;
            --sidebar-scroll-arrow-bg-hover: rgba(56, 142, 60, 0.25);
        }

        [data-theme="light"] {
            --sidebar-scroll-track-bg: #f1f5f9;
            --sidebar-scroll-border: #e2e8f0;
            --sidebar-scroll-thumb: #cbd5e1;
            --sidebar-scroll-thumb-hover: #94a3b8;
            --sidebar-scroll-thumb-active: #2e7d32;
            --sidebar-scroll-arrow: #64748b;
            --sidebar-scroll-arrow-hover: #2e7d32;
            --sidebar-scroll-arrow-bg-hover: rgba(46, 125, 50, 0.15);
        }

        .sidebar-scroll-container {
            position: relative !important;
            width: 100% !important;
            height: calc(100vh - 165px) !important;
            display: flex !important;
            flex-direction: row !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
        }

        #sidebarMenuWrap,
        .left-custom-menu-adp-wrap {
            width: calc(100% - 18px) !important;
            height: 100% !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
            box-sizing: border-box !important;
        }

        #sidebarMenuWrap::-webkit-scrollbar,
        .left-custom-menu-adp-wrap::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
        }
    </style>
</head>

<body>
    <!-- Barra de Progreso de Scroll Superior -->
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <!-- Menú Lateral Izquierdo -->
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="<?= BASE_URL ?>/index.php?page=aprendiz_horario" class="d-flex align-items-center justify-content-center w-100 text-decoration-none">
                    <img class="main-logo" src="<?= $logoSena ?>" alt="SENA" style="height: 32px; width: auto; max-height: 32px; object-fit: contain;" />
                </a>
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Perfil del Aprendiz en Sidebar -->
            <div class="nalika-profile text-center py-3 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="<?= BASE_URL ?>/index.php?page=aprendiz_perfil" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Ver Mi Perfil">
                        <img src="https://ui-avatars.com/api/?background=24caa1&color=fff&name=<?= urlencode($nombreUsuario) ?>" alt="Foto Aprendiz" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2.5px solid #24caa1; box-shadow:0 3px 8px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-2 text-theme-primary mb-0" style="font-size:13px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block mt-1" style="font-size:11px;">Ficha: <strong><?= htmlspecialchars($numeroFicha) ?></strong></small>
                </div>
            </div>

            <!-- Navegación del Aprendiz -->
            <div class="sidebar-scroll-container">
                <div class="left-custom-menu-adp-wrap" id="sidebarMenuWrap">
                    <nav class="sidebar-nav left-sidebar-menu-pro">
                        <ul class="metismenu" id="menu1">
                            <li class="<?= ($currentPage === 'aprendiz_horario') ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=aprendiz_horario" title="Mi Horario & Calendario">
                                    <i class="bi bi-calendar-check icon-wrap"></i>
                                    <span class="mini-click-non">Mi Horario</span>
                                </a>
                            </li>
                            <li class="<?= ($currentPage === 'aprendiz_historial') ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=aprendiz_historial" title="Historial de Asistencia">
                                    <i class="bi bi-clock-history icon-wrap"></i>
                                    <span class="mini-click-non">Mis Asistencias</span>
                                </a>
                            </li>
                            <li class="<?= ($currentPage === 'aprendiz_excusas') ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=aprendiz_excusas" title="Radicación y Estado de Excusas Médicas">
                                    <i class="bi bi-file-earmark-medical icon-wrap"></i>
                                    <span class="mini-click-non">Mis Excusas</span>
                                </a>
                            </li>
                            <li class="<?= ($currentPage === 'aprendiz_perfil') ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=aprendiz_perfil" title="Mi Perfil & Ficha">
                                    <i class="bi bi-person-badge icon-wrap"></i>
                                    <span class="mini-click-non">Mi Perfil</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </nav>
    </div>

    <!-- Contenedor Principal -->
    <div class="all-content-wrapper">
        <div class="header-advance-area">
            <div class="header-top-area">
                <div class="container-fluid">
                    <div class="nk-header-bar d-flex align-items-center justify-content-between">
                        
                        <!-- Lado Izquierdo: Botón Toggle e Indicador RFID en Vivo -->
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="sidebarCollapse" class="btn-sidebar-toggle" onclick="toggleSidebarGlobal(event)" title="Alternar Menú">
                                <i class="bi bi-list"></i>
                            </button>
                            
                            <div class="d-flex align-items-center gap-2 ms-1">
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA Aprendiz</span>
                                
                                <!-- Indicador Dinámico de Marcación RFID de Hoy -->
                                <?php if (!empty($marcacionHoy) && !empty($marcacionHoy['hora_entrada'])): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size:11.5px;">
                                        <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width:7px; height:7px;"></span>
                                        <i class="bi bi-upc-scan"></i> Entrada: <?= date('h:i A', strtotime($marcacionHoy['hora_entrada'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size:11.5px;">
                                        <span class="spinner-grow spinner-grow-sm text-warning" role="status" style="width:7px; height:7px;"></span>
                                        <i class="bi bi-exclamation-circle"></i> Sin registrar hoy
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Lado Derecho: Menú de Usuario -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline"><?= htmlspecialchars($nombreUsuario) ?></span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><span class="dropdown-header text-muted">Ficha <?= htmlspecialchars($numeroFicha) ?></span></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php?page=aprendiz_perfil"><i class="bi bi-person me-2 text-success"></i>Mi Perfil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/index.php?page=logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Barra de Migas de Pan (Breadcrumbs) -->
            <div class="sena-breadcrumb-bar d-flex align-items-center justify-content-between">
                <div>
                    <a href="<?= BASE_URL ?>/index.php?page=aprendiz_horario"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-theme-primary fw-semibold"><?= htmlspecialchars($tituloPagina) ?></span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Jornada Activa: <strong><?= htmlspecialchars($horarioStr) ?></strong>
                </div>
            </div>
        </div>

        <!-- Contenido Dinámico de la Vista -->
        <div class="section-admin container-fluid mt-4">
