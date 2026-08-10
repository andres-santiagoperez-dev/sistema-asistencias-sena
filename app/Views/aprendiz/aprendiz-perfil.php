<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Mi Perfil y Ficha | Aprendiz SENA - Nalika</title>
    <meta name="description" content="Perfil de Usuario y Datos de Ficha para Aprendiz SENA">
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
    <!-- Main & Theme CSS -->
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <!-- Aprendiz Custom CSS -->
    <link rel="stylesheet" href="css/aprendiz-custom.css">
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
                <button type="button" class="sidebar-close-btn-mobile" id="sidebarCloseMobile" title="Cerrar Menú">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <!-- Aprendiz Profile Dtl -->
            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <a href="aprendiz-perfil.html">
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
                        <li>
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
                        <li class="active">
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
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px;">SENA - ADSO</span>
                                <span class="text-secondary ms-1 d-none d-md-inline" style="font-size:13px;">Perfil del Aprendiz e Información Académica</span>
                            </div>
                        </div>

                        <!-- Controls -->
                        <div class="d-flex align-items-center gap-3">
                            <span id="relojLive" class="nk-live-clock d-none d-sm-inline-flex">
                                <i class="bi bi-clock-history"></i> --:--:-- --
                            </span>

                            <a href="aprendiz-dashboard.html" class="btn btn-sm btn-outline-light d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-left"></i>
                                <span>Volver al Dashboard</span>
                            </a>

                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <a href="#" class="nk-header-icon nk-header-user d-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown">
                                    <i class="icon nalika-user"></i>
                                    <span class="nk-user-name d-none d-md-inline">Carlos Mendoza</span>
                                    <i class="bi bi-chevron-down" style="font-size:12px;"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                                    <li><a class="dropdown-header text-muted">Aprendiz - Ficha 2670123</a></li>
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
                    <span class="text-white fw-semibold">Mi Perfil & Ficha</span>
                </div>
                <div class="d-none d-md-block text-secondary" style="font-size:12px;">
                    Configuración de Cuenta & Dispositivo RFID
                </div>
            </div>
        </div>

        <!-- Content Container -->
        <div class="container-fluid mt-4">
            
            <div class="row g-3">
                
                <!-- Left Profile Summary Card -->
                <div class="col-lg-4 col-12">
                    <div class="card border-0 text-center p-4 card-stat-ux">
                        <div class="position-relative d-inline-block mx-auto mb-3">
                            <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80" class="rounded-circle" style="width:110px; height:110px; object-fit:cover; border:3px solid #39a900; box-shadow:0 4px 15px rgba(0,0,0,0.4);" alt="Carlos Mendoza">
                            <span class="position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border border-2 border-dark" title="Cuenta Activa"></span>
                        </div>
                        <h4 class="text-white fw-bold mb-1">Carlos Eduardo Mendoza</h4>
                        <p class="text-secondary mb-2" style="font-size:14px;">Aprendiz de Formación Tecnológica</p>
                        <span class="badge mb-3" style="background:#007934; font-size:12px;">Estado: En Formación (Activo)</span>

                        <!-- Card Dispositivo RFID -->
                        <div class="p-3 rounded mb-3 text-start" style="background:#162544; border:1px solid #253a5c;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rfid-icon-circle active">
                                    <i class="bi bi-upc-scan"></i>
                                </div>
                                <div>
                                    <small class="text-secondary d-block">Llavero RFID Vinculado:</small>
                                    <strong class="text-info fs-6">#RFID-883921</strong>
                                    <small class="text-success d-block"><i class="bi bi-check-circle-fill me-1"></i>Registrado en BD</small>
                                </div>
                            </div>
                        </div>

                        <!-- Info Académica Rápida -->
                        <div class="text-start p-3 rounded" style="background:#152036; font-size:13px; border:1px solid #253a5c;">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Programa:</span>
                                <strong class="text-white">ADSO</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Ficha:</span>
                                <strong class="text-white">2670123</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Jornada:</span>
                                <strong class="text-white">Mañana</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary">Instructor:</span>
                                <strong class="text-info">Juan C. Vanegas G.</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Tabs Section -->
                <div class="col-lg-8 col-12">
                    <div class="card border-0 card-stat-ux">
                        <div class="card-header p-0" style="background:#162544; border-bottom:1px solid #253a5c; margin:-20px -20px 0 -20px; border-radius:10px 10px 0 0;">
                            <ul class="nav nav-tabs border-0" id="perfilTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-item nav-link active text-white fw-bold px-4 py-3 border-0 rounded-0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-pane" type="button" role="tab">
                                        <i class="bi bi-person-lines-fill me-2 text-success"></i>Información General
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-item nav-link text-white fw-bold px-4 py-3 border-0 rounded-0" id="horario-tab" data-bs-toggle="tab" data-bs-target="#horario-pane" type="button" role="tab">
                                        <i class="bi bi-clock-history me-2 text-info"></i>Horario de Ficha
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-item nav-link text-white fw-bold px-4 py-3 border-0 rounded-0" id="seguridad-tab" data-bs-toggle="tab" data-bs-target="#seguridad-pane" type="button" role="tab">
                                        <i class="bi bi-shield-lock me-2 text-warning"></i>Seguridad y Clave
                                    </button>
                                </li>
                            </ul>
                        </div>

                        <div class="card-body p-4 mt-2">
                            <div class="tab-content" id="perfilTabsContent">
                                
                                <!-- Tab 1: Info General -->
                                <div class="tab-pane fade show active" id="info-pane" role="tabpanel">
                                    <h5 class="text-white fw-bold mb-3">Datos Personales y de Contacto</h5>
                                    <form onsubmit="actualizarPerfil(event)">
                                        <div class="row g-3">
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Tipo de Documento:</label>
                                                <input type="text" class="form-control form-control-nalika" value="Cédula de Ciudadanía (CC)" readonly>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Número de Documento:</label>
                                                <input type="text" class="form-control form-control-nalika" value="1.098.765.432" readonly>
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Nombres Completos:</label>
                                                <input type="text" class="form-control form-control-nalika" value="Carlos Eduardo">
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Apellidos Completos:</label>
                                                <input type="text" class="form-control form-control-nalika" value="Mendoza Rodríguez">
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Correo Institucional SENA (*):</label>
                                                <input type="email" class="form-control form-control-nalika" value="cemendoza@misena.edu.co">
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Teléfono / Celular de Contacto (*):</label>
                                                <input type="text" class="form-control form-control-nalika" value="315 876 5432">
                                            </div>
                                            <div class="col-12 mt-4 text-end">
                                                <button type="submit" class="btn btn-sena-green fw-bold">
                                                    <i class="bi bi-save me-1"></i> Guardar Cambios
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <!-- Tab 2: Horario Ficha -->
                                <div class="tab-pane fade" id="horario-pane" role="tabpanel">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="text-white fw-bold mb-0">Horario de Ingreso y Salida Ficha 2670123</h5>
                                        <a href="aprendiz-horario.html" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-calendar3 me-1"></i> Ir al Calendario PDF
                                        </a>
                                    </div>

                                    <div class="table-responsive mb-3">
                                        <table class="table table-nalika-dark align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Día</th>
                                                    <th>Hora Entrada</th>
                                                    <th>Tolerancia Máx</th>
                                                    <th>Hora Salida</th>
                                                    <th>Ambiente</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Lunes</strong></td>
                                                    <td><span class="text-success fw-bold">07:00 AM</span></td>
                                                    <td>07:10 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Ambiente ADSO 402</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Martes</strong></td>
                                                    <td><span class="text-success fw-bold">07:00 AM</span></td>
                                                    <td>07:10 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Lab Software 105</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Miércoles</strong></td>
                                                    <td><span class="text-success fw-bold">07:00 AM</span></td>
                                                    <td>07:10 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Lab Software 105</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Jueves</strong></td>
                                                    <td><span class="text-success fw-bold">07:00 AM</span></td>
                                                    <td>07:10 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Ambiente ADSO 402</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Viernes</strong></td>
                                                    <td><span class="text-success fw-bold">07:00 AM</span></td>
                                                    <td>07:10 AM</td>
                                                    <td>01:00 PM</td>
                                                    <td>Lab Hardware / RFID</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-dark border border-secondary text-secondary mb-0" style="font-size:12px;">
                                        <i class="bi bi-info-circle text-warning me-1"></i> Nota: Las llegadas después de las 07:10 AM quedan automáticamente registradas como <strong>Retardo</strong> en la tabla de ingresos.
                                    </div>
                                </div>

                                <!-- Tab 3: Seguridad -->
                                <div class="tab-pane fade" id="seguridad-pane" role="tabpanel">
                                    <h5 class="text-white fw-bold mb-3">Cambio de Contraseña de Acceso</h5>
                                    <form onsubmit="cambiarPassword(event)">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label-nalika">Contraseña Actual (*):</label>
                                                <input type="password" class="form-control form-control-nalika" required placeholder="••••••••">
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Nueva Contraseña (*):</label>
                                                <input type="password" class="form-control form-control-nalika" required placeholder="Mínimo 8 caracteres">
                                            </div>
                                            <div class="col-md-6 col-12">
                                                <label class="form-label-nalika">Confirmar Nueva Contraseña (*):</label>
                                                <input type="password" class="form-control form-control-nalika" required placeholder="Repita la nueva contraseña">
                                            </div>
                                            <div class="col-12 mt-4 text-end">
                                                <button type="submit" class="btn btn-warning fw-bold text-dark">
                                                    <i class="bi bi-key-fill me-1"></i> Actualizar Contraseña
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

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
                                Sistema de Control de Ingreso de Aprendices SENA © 2026 | Perfil del Aprendiz.
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

    <!-- Scripts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        });

        function actualizarPerfil(e) {
            e.preventDefault();
            alert('¡Datos de perfil actualizados exitosamente!');
        }

        function cambiarPassword(e) {
            e.preventDefault();
            alert('¡Contraseña actualizada de manera segura!');
        }
    </script>
</body>

</html>
