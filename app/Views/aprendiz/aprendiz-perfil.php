<?php 
session_start(); 

require_once '../../config/database.php'; 
$pdo = Database::conectar(); 

$id_aprendiz = $_SESSION['id_aprendiz'];
$mensaje_exito = "";
$mensaje_error = "";

// 1. Procesar Actualización de Perfil (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_perfil'])) {
    $nuevo_correo = trim($_POST['correo'] ?? '');
    $nuevo_telefono = trim($_POST['telefono'] ?? '');
    $id_usuario_actual = $_POST['id_usuario'] ?? '';

    if (!empty($nuevo_correo) && !empty($id_usuario_actual)) {
        try {
            $sql_update = "UPDATE usuarios SET correo = :correo, telefono = :telefono WHERE id_usuario = :id_usuario";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                ':correo' => $nuevo_correo,
                ':telefono' => $nuevo_telefono,
                ':id_usuario' => $id_usuario_actual
            ]);
            $mensaje_exito = "¡Información personal actualizada exitosamente!";
            
            // Actualizar sesión si es necesario
            if(isset($_SESSION['correo'])) {
                $_SESSION['correo'] = $nuevo_correo;
            }
        } catch (PDOException $e) {
            $mensaje_error = "Error al actualizar la información: " . $e->getMessage();
        }
    } else {
        $mensaje_error = "El correo electrónico es obligatorio.";
    }
}

try {
    // 2. Consultar Información Completa del Aprendiz, Usuario, Ficha e Instructor
    $sql_perfil = "SELECT a.*, f.numero_ficha, f.programa, h.hora_entrada, h.hora_salida,
                          i.id_instructor, u_inst.nombres AS inst_nombres, u_inst.apellidos AS inst_apellidos, u_inst.correo AS inst_correo,
                          u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres, u.apellidos, u.correo, u.telefono
                   FROM aprendices a
                   INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
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

    // Consultar Estado RFID / Marcación de Hoy
    $sql_hoy = "SELECT hora_entrada, hora_salida, estado 
                FROM asistencia 
                WHERE aprendices_id_aprendiz = :id_aprendiz AND fecha = CURRENT_DATE() LIMIT 1";
    $stmt_hoy = $pdo->prepare($sql_hoy);
    $stmt_hoy->execute([':id_aprendiz' => $id_aprendiz]);
    $marcacion_hoy = $stmt_hoy->fetch(PDO::FETCH_ASSOC);

    $rfid_registrado = !empty($marcacion_hoy);
    $hora_entrada_hoy = ($rfid_registrado && !empty($marcacion_hoy['hora_entrada'])) ? date('h:i A', strtotime($marcacion_hoy['hora_entrada'])) : '';
    $instructor_nombre = trim(($perfil['inst_nombres'] ?? 'Sin Asignar') . ' ' . ($perfil['inst_apellidos'] ?? ''));
    $instructor_correo = $perfil['inst_correo'] ?? 'No disponible';

    $temaActual = $_COOKIE['app_theme'] ?? $_COOKIE['sena_theme'] ?? 'nalika';
    $logoSena   = ($temaActual === 'light') ? '../../../public/assets/img/logo/logo_sena_negro.svg' : '../../../public/assets/img/logo/logo_sena_verde.svg';

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
        
        .form-control-sena {
            background-color: var(--nk-bg-main);
            border: 1px solid var(--nk-border-color);
            color: var(--text-main);
        }
        .form-control-sena:focus {
            background-color: var(--nk-card-bg);
            border-color: #24b5ca;
            color: var(--text-main);
            box-shadow: none;
        }
    </style>
</head>

<body>
    <div id="scrollProgressBar" class="sena-scroll-progress-bar"></div>

    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="aprendiz-horario.php" class="d-flex align-items-center justify-content-center w-100 text-decoration-none">
                    <img class="main-logo" src="<?= $logoSena ?>" alt="SENA" />
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
                            <div class="d-flex align-items-center gap-2 ms-2">
                                <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - ADSO</span>
                            </div>
                        </div>

                        <!-- Estado RFID Barra Superior -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-none d-md-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill" style="background:var(--nk-card-bg); border:1px solid var(--nk-border-color); font-size:12px;" title="Llavero RFID Vinculado">
                                    <i class="bi bi-upc-scan text-info"></i>
                                    <span class="text-secondary">RFID:</span>
                                    <strong class="text-theme-primary"><?= htmlspecialchars($rfid) ?></strong>
                                </span>
                                <span class="badge <?= $rfid_registrado ? 'bg-success' : 'bg-danger' ?> d-inline-flex align-items-center gap-1.5 px-2.5 py-1.5 rounded-pill" style="font-size:12px; font-weight:600;" title="<?= $rfid_registrado ? 'Marcación RFID registrada hoy a las ' . $hora_entrada_hoy : 'No se ha registrado marcación RFID hoy' ?>">
                                    <i class="bi <?= $rfid_registrado ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i>
                                    <span><?= $rfid_registrado ? 'RFID Registrado' : 'RFID Sin Registrar' ?></span>
                                    <?php if ($rfid_registrado && !empty($hora_entrada_hoy)): ?>
                                        <span class="opacity-75 ms-1">(<?= $hora_entrada_hoy ?>)</span>
                                    <?php endif; ?>
                                </span>
                            </div>

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
                    <a href="aprendiz-horario.php"><i class="bi bi-house-door me-1"></i>Inicio</a>
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
                        <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
                            <h5 class="text-theme-primary mb-0"><i class="bi bi-person-lines-fill me-2 text-success"></i>Información Personal</h5>
                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="bi bi-pencil-square me-1"></i>Editar
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <?php if(!empty($mensaje_exito)): ?>
                                <div class="alert alert-success py-2"><i class="bi bi-check-circle me-2"></i><?= $mensaje_exito ?></div>
                            <?php endif; ?>
                            <?php if(!empty($mensaje_error)): ?>
                                <div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-2"></i><?= $mensaje_error ?></div>
                            <?php endif; ?>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-success"><i class="bi bi-card-text"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Documento de Identidad</small>
                                            <strong class="text-theme-primary">
                                                <?= htmlspecialchars($perfil['tipo_documento'] ?? '') ?> <?= htmlspecialchars($perfil['numero_documento'] ?? 'Cargando...') ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-primary"><i class="bi bi-person"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Nombres</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($perfil['nombres'] ?? '') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-primary"><i class="bi bi-person-badge"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Apellidos</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($perfil['apellidos'] ?? '') ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="info-group">
                                        <div class="info-icon text-warning"><i class="bi bi-telephone"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Teléfono / Celular</small>
                                            <strong class="text-theme-primary"><?= !empty($perfil['telefono']) ? htmlspecialchars($perfil['telefono']) : 'Teléfono no registrado' ?></strong>
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
                                        <div class="info-icon text-danger"><i class="bi bi-upc-scan"></i></div>
                                        <div>
                                            <small class="text-secondary d-block">Código RFID</small>
                                            <strong class="text-theme-primary"><?= htmlspecialchars($rfid) ?></strong>
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

    <!-- Modal Editar Perfil -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title text-theme-primary" id="editProfileModalLabel"><i class="bi bi-pencil-square me-2 text-success"></i>Editar Información Personal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="aprendiz-perfil.php" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="editar_perfil" value="1">
                        <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($perfil['id_usuario'] ?? '') ?>">
                        
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>Solo puedes modificar tu teléfono y correo. Para otros cambios, contacta a coordinación.
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Teléfono / Celular</label>
                            <input type="text" name="telefono" class="form-control form-control-sena" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" placeholder="Ej. 3001234567">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" name="correo" class="form-control form-control-sena" value="<?= htmlspecialchars($perfil['correo'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-secondary border-opacity-25">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sena-green"><i class="bi bi-save me-1"></i>Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../public/assets/js/main.js"></script>
    <script src="../../../public/assets/js/aprendiz-custom.js"></script>
</body>
</html>

