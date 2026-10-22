<?php
require_once dirname(__DIR__, 2) . '/config/init.php';

if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'instructor') === false) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit();
}

$tituloPagina  = $tituloPagina ?? 'Panel';
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Instructor';
$currentPage   = $_GET['page'] ?? '';

$temaActual = 'nalika';
$logoSena   = BASE_URL . '/public/assets/img/logo/logo_sena_verde.svg';
?>
<!doctype html>
<html lang="es" data-theme="nalika">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#39a900">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SENA Asistencias">
    <link rel="manifest" href="<?= BASE_URL ?>/public/manifest.json">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/public/assets/img/logo/logo.png">
    <title><?= htmlspecialchars($tituloPagina) ?> | Instructor SENA</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.8 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons 1.13.1 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Nalika Icon CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/nalika-icon.css">
    <!-- Swiper 12 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css">
    <!-- SimpleBar 6.2.7 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.css">
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/responsive.css?v=<?= time() ?>">
    <!-- Aprendiz SENA Custom UX/UI CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/aprendiz-custom.css?v=<?= time() ?>">
    <!-- Sistema de Temas Pastel Accesible e Inclusivo -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/theme.css?v=<?= time() ?>">
</head>

<body>
    <!-- Barra de Progreso de Scroll Superior -->
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <!-- Left Sidebar -->
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="<?= BASE_URL ?>/index.php?page=instructor_dashboard" class="d-flex align-items-center justify-content-center w-100 text-decoration-none">
                    <img class="main-logo" src="<?= $logoSena ?>" alt="SENA" style="height: 32px; width: auto; max-height: 32px; object-fit: contain;" />
                </a>
                <!-- Mobile Close Button -->
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Instructor Profile Dtl -->
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Instructor">
                        <img src="https://ui-avatars.com/api/?background=dcfce7&color=166534&bold=true&name=<?= urlencode($nombreUsuario) ?>" alt="Foto Instructor" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #86efac; box-shadow:0 2px 8px rgba(22, 163, 74, 0.15);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Instructor</small>
                </div>
            </div>

            <!-- Navigation Menu (Sin Scroll) -->
            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li class="<?= ($currentPage === 'instructor_dashboard' || strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_dashboard">
                                <i class="bi bi-journal-bookmark-fill icon-wrap"></i>
                                <span class="mini-click-non">Mis Fichas</span>
                            </a>
                        </li>
                        <li class="<?= ($currentPage === 'instructor_horario' || strpos($_SERVER['PHP_SELF'], 'horario.php') !== false) ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_horario">
                                <i class="bi bi-calendar-check icon-wrap"></i>
                                <span class="mini-click-non">Mi Horario</span>
                            </a>
                        </li>
                        <li class="<?= ($currentPage === 'instructor_aprendices' || strpos($_SERVER['PHP_SELF'], 'aprendices.php') !== false) ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_aprendices">
                                <i class="bi bi-people icon-wrap"></i>
                                <span class="mini-click-non">Mis Aprendices</span>
                            </a>
                        </li>
                        <li class="<?= ($currentPage === 'instructor_excusas' || strpos($_SERVER['PHP_SELF'], 'excusas.php') !== false) ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_excusas">
                                <i class="bi bi-file-earmark-medical icon-wrap"></i>
                                <span class="mini-click-non">Excusas Médicas</span>
                            </a>
                        </li>
                        <li class="<?= ($currentPage === 'instructor_ingresos' || strpos($_SERVER['PHP_SELF'], 'ingresos.php') !== false) ? 'active' : '' ?>">
                            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos">
                                <i class="bi bi-door-open icon-wrap"></i>
                                <span class="mini-click-non">Control de Ingresos</span>
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
                                <span class="badge badge-sena badge-on-time" style="font-size:12px; padding:6px 12px; font-weight:600;">SENA - Instructor</span>
                                <span class="text-secondary ms-1 d-none d-md-inline" style="font-size:13px;">Control de Asistencia</span>
                            </div>
                        </div>

                        <!-- User Profile Controls -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline"><?= htmlspecialchars($nombreUsuario) ?></span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/index.php?page=logout"><i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation Bar -->
            <div class="sena-breadcrumb-bar d-flex align-items-center justify-content-between">
                <div>
                    <a href="<?= BASE_URL ?>/index.php?page=instructor_dashboard"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-theme-primary fw-semibold"><?= htmlspecialchars($tituloPagina) ?></span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Rol: <strong>Instructor</strong>
                </div>
            </div>
        </div>

        <div class="container-fluid py-3 mt-4">
