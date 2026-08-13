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
<<<<<<< HEAD

// Lógica de Temas
$tema_actual = 'nalika'; // Tema por defecto
$temas_permitidos = ['nalika', 'dark', 'light'];

if (isset($_COOKIE['app_theme']) && in_array($_COOKIE['app_theme'], $temas_permitidos)) {
    $tema_actual = $_COOKIE['app_theme'];
}
?>
<!doctype html>
<html lang="es" data-theme="<?= htmlspecialchars($tema_actual) ?>">
=======
?>
<!doctype html>
<html lang="es">
>>>>>>> 365d121a63cfbbeee0bd0b8edaa4b0474f86cf9b

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
<<<<<<< HEAD
    <link rel="stylesheet" href="../../../public/assets/css/theme.css">
=======
>>>>>>> 365d121a63cfbbeee0bd0b8edaa4b0474f86cf9b
</head>

<body>
    <div class="left-sidebar-pro">
        <nav id="sidebar">
            <div class="sidebar-header">
                <a href="aprendices.php" class="d-flex align-items-center gap-2 text-decoration-none">
                    <span class="text-white fw-bold" style="font-size:13px; letter-spacing:0.5px;">ADMIN SENA</span>
                </a>
            </div>

            <div class="nalika-profile text-center py-2.5 px-2 border-bottom border-secondary border-opacity-25">
                <div class="profile-dtl">
                    <img src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($nombreUsuario) ?>" alt="" style="width:64px; height:64px; border-radius:50%; object-fit:cover; border:3px solid #39a900;" />
                    <h2 class="mt-1 text-white mb-0" style="font-size:14px; font-weight:600;"><?= htmlspecialchars($nombreUsuario) ?></h2>
                    <small class="text-secondary d-block" style="font-size:11px;">Administrador del Sistema</small>
                </div>
            </div>

            <div class="left-custom-menu-adp-wrap">
                <nav class="sidebar-nav left-sidebar-menu-pro">
                    <ul class="metismenu" id="menu1">
                        <li><a href="#"><i class="bi bi-speedometer2 icon-wrap"></i><span class="mini-click-non">Dashboard</span></a></li>
                        <li class="active"><a href="aprendices.php"><i class="bi bi-people icon-wrap"></i><span class="mini-click-non">Aprendices</span></a></li>
                        <li><a href="#"><i class="bi bi-file-earmark-medical icon-wrap"></i><span class="mini-click-non">Excusas Médicas</span></a></li>
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
                            <span class="badge" style="background:#007934; font-size:12px; padding:6px 12px; font-weight:600;">SENA - Admin</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
<<<<<<< HEAD
                            <select id="theme-selector" class="form-select form-select-sm" style="width: auto; background-color: var(--btn-bg, #1f2e4d); color: var(--btn-text, #fff); border: 1px solid rgba(255,255,255,0.2);">
                                <option value="nalika">Tema: Nalika</option>
                                <option value="dark">Tema: Oscuro</option>
                                <option value="light">Tema: Claro</option>
                            </select>
=======
>>>>>>> 365d121a63cfbbeee0bd0b8edaa4b0474f86cf9b
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