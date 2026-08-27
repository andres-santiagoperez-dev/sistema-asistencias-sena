<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario']) || stripos($_SESSION['nombre_rol'] ?? '', 'instructor') === false) {
    header('Location: ../auth/login.php');
    exit();
}

$tituloPagina  = $tituloPagina ?? 'Panel';
$nombreUsuario = $_SESSION['nombre_completo'] ?? 'Instructor';
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($tituloPagina) ?> | Control de Asistencia SENA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../../public/assets/css/nalika-icon.css">
    <link rel="stylesheet" href="../../../public/assets/css/main.css">
    <link rel="stylesheet" href="../../../public/assets/css/style.css">
    <link rel="stylesheet" href="../../../public/assets/css/aprendiz-custom.css">
    <link rel="stylesheet" href="../../../public/assets/css/responsive.css">
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <span class="text-white fw-bold" style="font-size:13px; letter-spacing:0.5px;">INSTRUCTOR SENA</span>
                </a>
            </div>

            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <img src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($nombreUsuario) ?>" alt="" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900;" />
                    <h2 class="mt-1 text-white mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Instructor</small>
                </div>
            </div>

            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li><a href="dashboard.php"><i class="bi bi-speedometer2 icon-wrap"></i><span class="mini-click-non">Dashboard</span></a></li>
                        <li><a href="aprendices.php"><i class="bi bi-people icon-wrap"></i><span class="mini-click-non">Aprendices</span></a></li>
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
                            <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - Instructor</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="nk-user-name"><?= htmlspecialchars($nombreUsuario) ?></span>
                            <a href="../auth/login.php" class="btn btn-sm btn-outline-danger" title="Cerrar sesión">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid py-3">