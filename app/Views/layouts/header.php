<?php
require_once dirname(__DIR__, 2) . '/config/init.php';

if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'admin') === false) {
    header('Location: ' . BASE_URL . '/index.php?page=login');
    exit();
}

$tituloPagina  = $tituloPagina ?? 'Panel';
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Administrador';

$temaActual = 'nalika';
$logoSena   = BASE_URL . '/public/assets/img/logo/logo_sena_verde.svg';

?>
<!doctype html>
<html lang="es" data-theme="nalika">
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
    <style id="sena-sidebar-scrollbar-css">
        /* ── Variables Dinámicas del Scrollbar según el Tema Activo ── */
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

        /* ── Barra Deslizadora en el Navbar Lateral (Lado Derecho del Menú) ── */
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
            height: 0 !important;
        }

        .sidebar-custom-scrollbar {
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 18px !important;
            height: 100% !important;
            background-color: var(--sidebar-scroll-track-bg) !important;
            border-left: 1px solid var(--sidebar-scroll-border) !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: space-between !important;
            padding: 6px 0 !important;
            z-index: 100 !important;
            box-sizing: border-box !important;
            user-select: none !important;
            transition: background-color 0.3s ease, border-color 0.3s ease !important;
        }

        .sidebar-custom-scrollbar .scroll-arrow {
            width: 14px !important;
            height: 16px !important;
            padding: 0 !important;
            margin: 0 !important;
            background: transparent !important;
            border: none !important;
            color: var(--sidebar-scroll-arrow) !important;
            font-size: 10px !important;
            line-height: 1 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            border-radius: 2px !important;
            flex-shrink: 0 !important;
            transition: all 0.2s ease !important;
        }

        .sidebar-custom-scrollbar .scroll-arrow:hover {
            color: var(--sidebar-scroll-arrow-hover) !important;
            background-color: var(--sidebar-scroll-arrow-bg-hover) !important;
        }

        .sidebar-custom-scrollbar .scroll-arrow:active {
            color: #fff !important;
            background-color: var(--sidebar-scroll-arrow-hover) !important;
            transform: scale(0.9) !important;
        }

        .sidebar-custom-scrollbar .scroll-track {
            position: relative !important;
            flex: 1 1 auto !important;
            width: 100% !important;
            height: 100% !important;
            margin: 4px 0 !important;
            cursor: pointer !important;
        }

        .sidebar-custom-scrollbar .scroll-thumb {
            position: absolute !important;
            left: 3px !important;
            top: 0px !important;
            width: 11px !important;
            min-height: 40px !important;
            height: 55px !important;
            background-color: var(--sidebar-scroll-thumb) !important;
            border-radius: 8px !important;
            cursor: grab !important;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid var(--sidebar-scroll-border) !important;
            transition: background-color 0.25s ease, border-color 0.25s ease !important;
        }

        .sidebar-custom-scrollbar .scroll-thumb:hover {
            background-color: var(--sidebar-scroll-thumb-hover) !important;
        }

        .sidebar-custom-scrollbar .scroll-thumb.is-dragging {
            cursor: grabbing !important;
            background-color: var(--sidebar-scroll-thumb-active) !important;
        }

        body.mini-navbar #sidebar .sidebar-custom-scrollbar,
        #sidebar.active .sidebar-custom-scrollbar {
            display: none !important;
        }
    </style>
</head>

<body>
    <!-- Barra de Progreso de Scroll Superior -->
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <!-- Left Sidebar -->
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="d-flex align-items-center justify-content-center w-100 text-decoration-none">
                    <img class="main-logo" src="<?= $logoSena ?>" alt="SENA" style="height: 32px; width: auto; max-height: 32px; object-fit: contain;" />
                </a>
                <!-- Mobile Close Button -->
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <!-- Admin Profile Dtl -->
            <div class="nalika-profile text-center py-2 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="#" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Administrador del Sistema">
                        <img src="https://ui-avatars.com/api/?background=007934&color=fff&name=<?= urlencode($nombreUsuario) ?>" alt="Foto Administrador" style="width:48px; height:48px; border-radius:50%; object-fit:cover; border:2.5px solid #39a900; box-shadow:0 3px 8px rgba(0,0,0,0.3);" />
                    </a>
                    <h2 class="mt-1 text-theme-primary mb-0" style="font-size:13px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Administrador del Sistema</small>
                </div>
            </div>

            <!-- Navigation Menu with Custom Scrollbar Complement -->
            <div class="sidebar-scroll-container">
                <div class="left-custom-menu-adp-wrap" id="sidebarMenuWrap">
                    <nav class="sidebar-nav left-sidebar-menu-pro">
                        <ul class="metismenu" id="menu1">
                            <li class="<?= (($_GET['page'] ?? '') === 'dashboard' || strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=dashboard" title="Dashboard">
                                    <i class="bi bi-speedometer2 icon-wrap"></i>
                                    <span class="mini-click-non">Dashboard</span>
                                </a>
                            </li>
                            <li class="<?= (($_GET['page'] ?? '') === 'aprendices' || strpos($_SERVER['PHP_SELF'], 'aprendices.php') !== false) ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=aprendices" title="Aprendices">
                                    <i class="bi bi-people icon-wrap"></i>
                                    <span class="mini-click-non">Aprendices</span>
                                </a>
                            </li>
                            <li class="<?= (($_GET['page'] ?? '') === 'instructores' || strpos($_SERVER['PHP_SELF'], 'instructores.php') !== false) ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=instructores" title="Instructores">
                                    <i class="bi bi-person-badge icon-wrap"></i>
                                    <span class="mini-click-non">Instructores</span>
                                </a>
                            </li>
                            <li class="<?= (($_GET['page'] ?? '') === 'fichas' || strpos($_SERVER['PHP_SELF'], 'fichas.php') !== false) ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=fichas" title="Fichas y Horarios">
                                    <i class="bi bi-journal-bookmark-fill icon-wrap"></i>
                                    <span class="mini-click-non">Fichas y Horarios</span>
                                </a>
                            </li>
                            <li class="<?= (($_GET['page'] ?? '') === 'reportes' || strpos($_SERVER['PHP_SELF'], 'reportes.php') !== false) ? 'active' : '' ?>">
                                <a href="<?= BASE_URL ?>/index.php?page=reportes" title="Reportes">
                                    <i class="bi bi-bar-chart-line-fill icon-wrap"></i>
                                    <span class="mini-click-non">Reportes</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <!-- Custom Right Scrollbar Complement (Para scrollear sin ratón) -->
                <div class="sidebar-custom-scrollbar" id="sidebarCustomScrollbar" title="Desplazamiento manual sin ratón">
                    <button type="button" class="scroll-arrow scroll-arrow-up" id="scrollArrowUp" title="Desplazar hacia arriba">
                        <i class="bi bi-caret-up-fill"></i>
                    </button>
                    <div class="scroll-track" id="scrollTrack">
                        <div class="scroll-thumb" id="scrollThumb" title="Arrastrar para desplazar"></div>
                    </div>
                    <button type="button" class="scroll-arrow scroll-arrow-down" id="scrollArrowDown" title="Desplazar hacia abajo">
                        <i class="bi bi-caret-down-fill"></i>
                    </button>
                </div>
            </div>
        </nav>
    </div>

    <!-- Script de Inicialización Inmediata del Scrollbar Lateral (Garantiza ejecución sin depender de cache) -->
    <script>
    (function() {
        function setupSidebarScrollbar() {
            var container = document.getElementById('sidebarMenuWrap');
            var scrollbar = document.getElementById('sidebarCustomScrollbar');
            var track = document.getElementById('scrollTrack');
            var thumb = document.getElementById('scrollThumb');
            var btnUp = document.getElementById('scrollArrowUp');
            var btnDown = document.getElementById('scrollArrowDown');

            if (!container || !scrollbar || !track || !thumb) return;

            var isDragging = false;
            var startY = 0;
            var startScrollTop = 0;
            var autoScrollInterval = null;

            function updateThumbPosition() {
                var scrollHeight = container.scrollHeight;
                var clientHeight = container.clientHeight;
                var trackHeight = track.clientHeight;

                if (trackHeight <= 0) return;

                if (scrollHeight <= clientHeight) {
                    thumb.style.height = '48px';
                    thumb.style.top = '0px';
                    thumb.style.opacity = '0.55';
                    return;
                }

                thumb.style.opacity = '1';
                var thumbHeight = Math.max(28, Math.round((clientHeight / scrollHeight) * trackHeight));
                thumb.style.height = thumbHeight + 'px';

                var maxScroll = scrollHeight - clientHeight;
                var maxTop = trackHeight - thumbHeight;
                var currentTop = Math.round((container.scrollTop / maxScroll) * maxTop);
                thumb.style.top = Math.max(0, Math.min(maxTop, currentTop)) + 'px';
            }

            container.addEventListener('scroll', updateThumbPosition, { passive: true });
            window.addEventListener('resize', updateThumbPosition);

            // Arrastre con ratón
            thumb.addEventListener('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                isDragging = true;
                thumb.classList.add('is-dragging');
                startY = e.clientY;
                startScrollTop = container.scrollTop;
                document.body.style.userSelect = 'none';
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                var deltaY = e.clientY - startY;
                var trackHeight = track.clientHeight;
                var thumbHeight = thumb.clientHeight;
                var maxTop = trackHeight - thumbHeight;
                var maxScroll = container.scrollHeight - container.clientHeight;

                if (maxTop > 0 && maxScroll > 0) {
                    var scrollRatio = deltaY / maxTop;
                    container.scrollTop = startScrollTop + (scrollRatio * maxScroll);
                }
            });

            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    thumb.classList.remove('is-dragging');
                    document.body.style.userSelect = '';
                }
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            });

            // Soporte Touch para pantallas táctiles (sin ratón)
            thumb.addEventListener('touchstart', function(e) {
                if (!e.touches || !e.touches[0]) return;
                isDragging = true;
                thumb.classList.add('is-dragging');
                startY = e.touches[0].clientY;
                startScrollTop = container.scrollTop;
            }, { passive: true });

            document.addEventListener('touchmove', function(e) {
                if (!isDragging || !e.touches || !e.touches[0]) return;
                var deltaY = e.touches[0].clientY - startY;
                var trackHeight = track.clientHeight;
                var thumbHeight = thumb.clientHeight;
                var maxTop = trackHeight - thumbHeight;
                var maxScroll = container.scrollHeight - container.clientHeight;

                if (maxTop > 0 && maxScroll > 0) {
                    var scrollRatio = deltaY / maxTop;
                    container.scrollTop = startScrollTop + (scrollRatio * maxScroll);
                }
            }, { passive: true });

            document.addEventListener('touchend', function() {
                if (isDragging) {
                    isDragging = false;
                    thumb.classList.remove('is-dragging');
                }
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            });

            // Clic en la pista
            track.addEventListener('click', function(e) {
                if (e.target === thumb) return;
                var rect = track.getBoundingClientRect();
                var clickY = e.clientY - rect.top;
                var trackHeight = track.clientHeight;
                var thumbHeight = thumb.clientHeight;
                var maxScroll = container.scrollHeight - container.clientHeight;

                if (maxScroll > 0 && trackHeight > thumbHeight) {
                    var targetPercent = Math.max(0, Math.min(1, (clickY - thumbHeight / 2) / (trackHeight - thumbHeight)));
                    container.scrollTo({
                        top: targetPercent * maxScroll,
                        behavior: 'smooth'
                    });
                }
            });

            // Flecha abajo
            if (btnDown) {
                btnDown.addEventListener('click', function(e) {
                    e.preventDefault();
                    container.scrollBy({ top: 60, behavior: 'smooth' });
                });

                btnDown.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    if (autoScrollInterval) clearInterval(autoScrollInterval);
                    autoScrollInterval = setInterval(function() {
                        container.scrollTop += 14;
                    }, 35);
                });

                btnDown.addEventListener('mouseleave', function() {
                    if (autoScrollInterval) {
                        clearInterval(autoScrollInterval);
                        autoScrollInterval = null;
                    }
                });
            }

            // Flecha arriba
            if (btnUp) {
                btnUp.addEventListener('click', function(e) {
                    e.preventDefault();
                    container.scrollBy({ top: -60, behavior: 'smooth' });
                });

                btnUp.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    if (autoScrollInterval) clearInterval(autoScrollInterval);
                    autoScrollInterval = setInterval(function() {
                        container.scrollTop -= 14;
                    }, 35);
                });

                btnUp.addEventListener('mouseleave', function() {
                    if (autoScrollInterval) {
                        clearInterval(autoScrollInterval);
                        autoScrollInterval = null;
                    }
                });
            }

            setTimeout(updateThumbPosition, 100);
            setTimeout(updateThumbPosition, 500);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupSidebarScrollbar);
        } else {
            setupSidebarScrollbar();
        }
    })();
    </script>

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
                            <!-- User Profile -->
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
                    <a href="<?= BASE_URL ?>/index.php?page=dashboard"><i class="bi bi-house-door me-1"></i>Inicio</a>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="text-theme-primary fw-semibold"><?= htmlspecialchars($tituloPagina) ?></span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Rol: <strong>Administrador</strong>
                </div>
            </div>
        </div>

        <div class="container-fluid py-3 mt-4">
