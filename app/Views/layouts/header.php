<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'admin') === false) {
    header('Location: ../auth/login.php');
    exit();
}

$tituloPagina  = $tituloPagina ?? 'Panel';
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$temaActual = $_COOKIE['app_theme'] ?? $_COOKIE['sena_theme'] ?? 'nalika';
$logoSena   = ($temaActual === 'light') ? '../../../public/assets/img/logo/logo_sena_negro.svg' : '../../../public/assets/img/logo/logo_sena_verde.svg';

?>
<!doctype html>
<html lang="es" data-theme="<?= htmlspecialchars($temaActual) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina) ?> | Administrador SENA</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.8 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons 1.13.1 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <!-- Nalika Icon CSS -->
    <link rel="stylesheet" href="../../../public/assets/css/nalika-icon.css">
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
                <a href="dashboard.php" class="d-flex align-items-center justify-content-center w-100 text-decoration-none">
                    <img class="main-logo" src="<?= $logoSena ?>" alt="SENA" style="height: 32px; width: auto; max-height: 32px; object-fit: contain;" />
                </a>
                <!-- Mobile Close Button -->
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Admin Profile Dtl -->
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Administrador del Sistema">
                        <img src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($nombreUsuario) ?>" alt="Foto Administrador" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 10px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Administrador del Sistema</small>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'active' : '' ?>">
                            <a href="dashboard.php">
                                <i class="bi bi-speedometer2 icon-wrap"></i>
                                <span class="mini-click-non">Dashboard</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'aprendices.php') !== false) ? 'active' : '' ?>">
                            <a href="aprendices.php">
                                <i class="bi bi-people icon-wrap"></i>
                                <span class="mini-click-non">Aprendices</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'excusas.php') !== false) ? 'active' : '' ?>">
                            <a href="excusas.php">
                                <i class="bi bi-file-earmark-medical icon-wrap"></i>
                                <span class="mini-click-non">Excusas Médicas</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'instructores.php') !== false) ? 'active' : '' ?>">
                            <a href="instructores.php">
                                <i class="bi bi-person-badge icon-wrap"></i>
                                <span class="mini-click-non">Instructores</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'fichas.php') !== false) ? 'active' : '' ?>">
                            <a href="fichas.php">
                                <i class="bi bi-journal-bookmark-fill icon-wrap"></i>
                                <span class="mini-click-non">Fichas y Horarios</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'ingresos.php') !== false) ? 'active' : '' ?>">
                            <a href="ingresos.php">
                                <i class="bi bi-door-open icon-wrap"></i>
                                <span class="mini-click-non">Control de Ingresos</span>
                            </a>
                        </li>
                        <li class="<?= (strpos($_SERVER['PHP_SELF'], 'reportes.php') !== false) ? 'active' : '' ?>">
                            <a href="reportes.php">
                                <i class="bi bi-bar-chart-line-fill icon-wrap"></i>
                                <span class="mini-click-non">Reportes</span>
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
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - Admin</span>
                                <span class="text-secondary ms-1 d-none d-md-inline" style="font-size:13px;">Sistema de Control de Asistencia</span>
                            </div>
                        </div>

                        <!-- Right Side Dropdowns -->
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
                            
                            <!-- User Profile -->
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline"><?= htmlspecialchars($nombreUsuario) ?></span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid py-3 mt-4">
