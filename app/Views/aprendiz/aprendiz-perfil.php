<?php 
session_start(); 

require_once '../../config/database.php'; 
$pdo = Database::conectar(); 

$id_aprendiz = $_SESSION['id_aprendiz'];

try {
    // Consultar Información Completa del Aprendiz, Ficha e Instructor
    $sql_perfil = "SELECT a.*, f.numero_ficha, f.programa, h.hora_entrada, h.hora_salida,
                          i.id_instructor, u_inst.nombres AS inst_nombres, u_inst.apellidos AS inst_apellidos, u_inst.correo AS inst_correo
                   FROM aprendices a
                   LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                   LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                   LEFT JOIN instructores i ON f.Instructores_id_instructor1 = i.id_instructor
                   LEFT JOIN usuarios u_inst ON i.usuarios_id_usuario = u_inst.id_usuario
                   WHERE a.id_aprendiz = :id_aprendiz";
                   
    $stmt_perfil = $pdo->prepare($sql_perfil);
    $stmt_perfil->execute([':id_aprendiz' => $id_aprendiz]);
    $perfil = $stmt_perfil->fetch(PDO::FETCH_ASSOC);

    $rfid = $perfil['codigo_rfid'] ?? 'Sin Asignar';
    $numero_ficha = $perfil['numero_ficha'] ?? 'Sin Ficha';
    $programa = $perfil['programa'] ?? 'Sin Programa';
    $horario_str = ($perfil['hora_entrada'] ?? '00:00') . ' - ' . ($perfil['hora_salida'] ?? '00:00');
    $instructor_nombre = trim(($perfil['inst_nombres'] ?? 'Sin Asignar') . ' ' . ($perfil['inst_apellidos'] ?? ''));
    $instructor_correo = $perfil['inst_correo'] ?? 'No disponible';

} catch (PDOException $e) {
    die("Error al cargar datos del perfil: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mi Perfil & Ficha | Control de Ingreso SENA</title>
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
        .profile-hero {
            background: linear-gradient(135deg, var(--nk-card-header) 0%, var(--nk-card-bg) 100%);
            border-radius: 15px;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--nk-border-color);
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(36, 181, 202, 0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #39a900;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            margin-bottom: 15px;
        }
        .info-group {
            padding: 15px;
            background: var(--nk-bg-main);
            border: 1px solid var(--nk-border-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        .info-group:hover {
            border-color: #24b5ca;
            background: var(--nk-card-bg);
        }
        .info-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(255,255,255,0.05);
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
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&q=80" alt="Foto" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900;" />
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
                        <li><a href="aprendiz-excusas.php"><i class="bi bi-file-earmark-medical icon-wrap"></i><span class="mini-click-non">Mis Excusas Médicas</span></a></li>
                        <li class="active"><a href="aprendiz-perfil.php"><i class="bi bi-person-badge icon-wrap"></i><span class="mini-click-non">Mi Perfil & Ficha</span></a></li>
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
                    <span class="text-theme-primary fw-semibold">Mi Perfil & Ficha</span>
                </div>
            </div>
        </div>

        <div class="section-admin container-fluid mt-4">
            
            <!-- Hero Section -->
            <div class="profile-hero text-center mb-4">
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80" alt="Foto Aprendiz" class="profile-avatar">
                <h2 class="text-theme-primary fw-bold mb-1"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Nombre del Aprendiz') ?></h2>
                <h6 class="text-info mb-3">Aprendiz Activo SENA</h6>
                <div class="d-flex justify-content-center gap-3">
                    <span class="badge bg-success px-3 py-2" style="font-size:13px;"><i class="bi bi-upc-scan me-1"></i>RFID: <?= htmlspecialchars($rfid) ?></span>
                    <span class="badge" style="background:var(--nk-border-color); border:1px solid #314a73; font-size:13px;"><i class="bi bi-bookmark-star me-1"></i>Ficha: <?= htmlspecialchars($numero_ficha) ?></span>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <!-- Información Personal -->
                <div class="col-lg-6">
                    <div class="card border-0 card-stat-ux h-100">
                        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3">
                            <h5 class="text-theme-primary mb-0"><i class="bi bi-person-lines-fill me-2 text-success"></i>Información Personal</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-success"><i class="bi bi-card-text"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Documento de Identidad</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($perfil['identificacion'] ?? 'Cargando...') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-info"><i class="bi bi-envelope-at"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Correo Electrónico</small>
                                            <strong class="text-theme-primary" style="word-break: break-all;"><?= htmlspecialchars($perfil['correo'] ?? 'Cargando...') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-warning"><i class="bi bi-telephone"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Teléfono / Celular</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($perfil['telefono'] ?? 'No registrado') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-danger"><i class="bi bi-droplet-half"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">RH / Tipo de Sangre</small>
                                            <strong class="text-theme-primary">O+ (Ejemplo)</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Formación -->
                <div class="col-lg-6">
                    <div class="card border-0 card-stat-ux h-100">
                        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3">
                            <h5 class="text-theme-primary mb-0"><i class="bi bi-mortarboard me-2 text-info"></i>Detalles de Formación</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="p-3 mb-4 rounded" style="background: rgba(36, 181, 202, 0.1); border: 1px solid #24b5ca;">
                                <h6 class="text-info fw-bold mb-1">Programa de Formación</h6>
                                <h5 class="text-theme-primary mb-0"><?= htmlspecialchars($programa) ?></h5>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-primary"><i class="bi bi-clock-history"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Horario Habitual</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($horario_str) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="info-group">
                                        <div class="info-icon text-success"><i class="bi bi-person-video3"></i></div>
                                        <div class="w-100">
                                            <small class="text-secondary d-block">Instructor Líder Asignado</small>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong class="text-theme-primary"><?= htmlspecialchars($instructor_nombre) ?></strong>
                                                <a href="mailto:<?= htmlspecialchars($instructor_correo) ?>" class="btn btn-sm btn-outline-success border-0"><i class="bi bi-envelope"></i> Contactar</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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

