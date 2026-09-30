<?php
/**
 * Vista de inicio de sesión rediseñada con fondo de la foto del SENA y marco flotante
 * @var string|null $error Mensaje de error de autenticación
 * @var string|null $mensaje Mensaje informativo
 */
$error = $error ?? ($_GET['error'] ?? null);
$mensaje = $mensaje ?? ($_GET['mensaje'] ?? null);

$baseUrl = defined('BASE_URL') ? BASE_URL : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Leer el tema actual de la cookie o por defecto nalika
$app_theme = $_COOKIE['app_theme'] ?? $_COOKIE['sena_theme'] ?? 'nalika';
$logoSena  = $baseUrl . '/public/assets/img/logo/logo_sena_verde.svg';
$senaBg    = $baseUrl . '/public/assets/img/sena_bg.jpg';
?>
<!doctype html>
<html lang="es" data-theme="<?= htmlspecialchars($app_theme) ?>">
<head>
    <meta charset="utf-8">
    <title>Iniciar Sesión · Sistema de Control de Ingreso SENA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $baseUrl ?>/public/assets/img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        :root {
            /* Tema Claro */
            --bg-overlay: rgba(245, 247, 250, 0.75);
            --card-bg: #ffffff;
            --card-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
            --header-bg: linear-gradient(135deg, #39A900 0%, #2E7D32 100%);
            --header-text: #ffffff;
            --text-main: #2b2b2b;
            --text-muted: #6c757d;
            --input-bg: #f8f9fa;
            --input-border: #e2e8f0;
            --input-focus-border: #39A900;
            --input-text: #2b2b2b;
            --icon-color: #6c757d;
            --btn-primary-bg: linear-gradient(135deg, #39A900 0%, #2E7D32 100%);
            --btn-primary-hover: #2E7D32;
            --btn-text: #ffffff;
        }

        [data-theme="dark"] {
            --bg-overlay: rgba(15, 20, 28, 0.85);
            --card-bg: #1e293b;
            --card-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            --header-bg: linear-gradient(135deg, #39A900 0%, #1e5c22 100%);
            --header-text: #ffffff;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-focus-border: #39A900;
            --input-text: #f8fafc;
            --icon-color: #94a3b8;
            --btn-primary-bg: linear-gradient(135deg, #39A900 0%, #2E7D32 100%);
            --btn-primary-hover: #2E7D32;
            --btn-text: #ffffff;
        }

        [data-theme="nalika"] {
            --bg-overlay: rgba(15, 26, 48, 0.82);
            --card-bg: #152036;
            --card-shadow: 0 20px 45px rgba(0, 0, 0, 0.7);
            --header-bg: linear-gradient(135deg, #39A900 0%, #2E7D32 100%);
            --header-text: #ffffff;
            --text-main: #ffffff;
            --text-muted: #8a9bb5;
            --input-bg: #0f192a;
            --input-border: #253a5c;
            --input-focus-border: #39A900;
            --input-text: #ffffff;
            --icon-color: #8a9bb5;
            --btn-primary-bg: linear-gradient(135deg, #39A900 0%, #2E7D32 100%);
            --btn-primary-hover: #2E7D32;
            --btn-text: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('<?= $senaBg ?>') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            overflow-x: hidden;
        }

        /* Overlay semi-transparente sobre la imagen de fondo */
        .bg-overlay-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--bg-overlay);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 1;
        }

        /* Selector de tema */
        .theme-switcher {
            position: fixed;
            top: 24px;
            right: 28px;
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .theme-btn {
            background: var(--card-bg);
            border: 1px solid var(--input-border);
            color: var(--text-muted);
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .theme-btn:hover, .theme-btn.active {
            background: #39A900;
            color: #ffffff;
            border-color: #39A900;
            transform: translateY(-2px);
        }

        /* Contenedor Principal Centrado */
        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            padding: 40px 20px 20px;
        }

        /* Marco del Login (Tarjeta Principal estilo Ejemplo 2) */
        .login-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            padding: 0 35px 40px;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        /* Cabecera Flotante/Sobresaliente del Marco */
        .login-card-header {
            background: var(--header-bg);
            border-radius: 12px;
            padding: 24px 20px;
            text-align: center;
            margin-top: -35px;
            margin-bottom: 28px;
            box-shadow: 0 8px 25px rgba(57, 169, 0, 0.35);
            color: var(--header-text);
            position: relative;
        }
        .login-card-header .brand-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .login-card-header .brand-title img {
            height: 36px;
            width: auto;
            filter: brightness(0) invert(1);
        }
        .login-card-header .brand-subtitle {
            font-size: 13px;
            margin-top: 6px;
            opacity: 0.92;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        .header-icons {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 12px;
            font-size: 16px;
            opacity: 0.85;
        }

        .login-body {
            display: flex;
            flex-direction: column;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 22px;
        }
        .input-group-custom label {
            display: block;
            font-size: 13.5px;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .input-field-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .form-control-custom {
            width: 100%;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            padding: 12px 42px 12px 16px;
            font-size: 14.5px;
            color: var(--input-text);
            transition: all 0.25s ease;
            outline: none;
        }
        .form-control-custom:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 4px rgba(57, 169, 0, 0.15);
        }
        .field-icon {
            position: absolute;
            right: 14px;
            color: var(--icon-color);
            font-size: 17px;
            pointer-events: none;
            transition: color 0.2s ease;
        }
        .toggle-password {
            pointer-events: auto !important;
            cursor: pointer;
        }
        .toggle-password:hover {
            color: var(--input-focus-border);
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 13.5px;
        }
        .form-check-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-muted);
            margin: 0;
            user-select: none;
        }
        .form-check-custom input {
            width: 17px;
            height: 17px;
            accent-color: #39A900;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-submit {
            background: var(--btn-primary-bg);
            color: var(--btn-text);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(57, 169, 0, 0.3);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(57, 169, 0, 0.45);
            background-color: var(--btn-primary-hover);
        }
        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-custom-danger {
            background-color: rgba(220, 38, 38, 0.12);
            border: 1px solid rgba(220, 38, 38, 0.3);
            color: #ef4444;
        }
        .alert-custom-info {
            background-color: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }

        .footer-credits {
            text-align: center;
            margin-top: 24px;
            font-size: 12.5px;
            color: rgba(255, 255, 255, 0.7);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 0 22px 30px;
            }
            .theme-switcher {
                top: 15px;
                right: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-overlay-layer"></div>

    <div class="theme-switcher">
        <button type="button" class="theme-btn <?= $app_theme === 'light' ? 'active' : '' ?>" data-set-theme="light" title="Tema Claro">
            <i class="bi bi-sun"></i>
        </button>
        <button type="button" class="theme-btn <?= $app_theme === 'dark' ? 'active' : '' ?>" data-set-theme="dark" title="Tema Oscuro">
            <i class="bi bi-moon"></i>
        </button>
        <button type="button" class="theme-btn <?= $app_theme === 'nalika' ? 'active' : '' ?>" data-set-theme="nalika" title="Tema Nalika">
            <i class="bi bi-stars"></i>
        </button>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <!-- Cabecera del Marco (Header flotante del login) -->
            <div class="login-card-header">
                <h1 class="brand-title">
                    <img src="<?= $logoSena ?>" alt="SENA">
                    <span>SENA</span>
                </h1>
                <div class="brand-subtitle">Control de Ingreso y Asistencia</div>
                <div class="header-icons">
                    <i class="bi bi-shield-check" title="Seguridad"></i>
                    <i class="bi bi-person-badge" title="Asistencias"></i>
                    <i class="bi bi-clock-history" title="Horarios"></i>
                </div>
            </div>

            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert-custom alert-custom-danger">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= htmlspecialchars($error) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje): ?>
                    <div class="alert-custom alert-custom-info">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div><?= htmlspecialchars($mensaje) ?></div>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $baseUrl ?>/index.php?page=login">
                    <input type="hidden" name="rol" id="rolSeleccionado" value="aprendiz">

                    <div class="input-group-custom">
                        <label for="documento">Usuario o Documento</label>
                        <div class="input-field-wrapper">
                            <input type="text" class="form-control-custom" id="documento" name="documento" placeholder="Correo electrónico / documento..." required autocomplete="username">
                            <i class="bi bi-envelope-fill field-icon"></i>
                        </div>
                    </div>

                    <div class="input-group-custom">
                        <label for="password">Contraseña</label>
                        <div class="input-field-wrapper">
                            <input type="password" class="form-control-custom" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
                            <i class="bi bi-lock-fill field-icon toggle-password" id="togglePasswordBtn" title="Mostrar/Ocultar contraseña"></i>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="form-check-custom">
                            <input type="checkbox" id="recordar" name="recordar">
                            <span>Recordarme</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">
                        INICIAR SESIÓN
                    </button>
                </form>
            </div>
        </div>

        <div class="footer-credits">
            &copy; <?= date('Y') ?> SENA · Centro de Comercio y Servicios
        </div>
    </div>

    <script>
        // Ocultar / Mostrar contraseña
        const togglePasswordBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');

        if (togglePasswordBtn && passwordInput) {
            togglePasswordBtn.addEventListener('click', function() {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                this.classList.toggle('bi-lock-fill', !isPassword);
                this.classList.toggle('bi-unlock-fill', isPassword);
            });
        }

        // Cambio de tema dinámico
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.getAttribute('data-set-theme');
                document.documentElement.setAttribute('data-theme', theme);
                
                document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                document.cookie = `app_theme=${theme}; path=/; max-age=${60*60*24*30}`;
                document.cookie = `sena_theme=${theme}; path=/; max-age=${60*60*24*30}`;
                localStorage.setItem('app_theme', theme);
                localStorage.setItem('sena_theme', theme);
            });
        });
    </script>
</body>
</html>
