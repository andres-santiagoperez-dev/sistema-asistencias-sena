<?php
/**
 * Vista de inicio de sesión rediseñada con temas
 * @var string|null $error Mensaje de error de autenticación
 */
$error = $error ?? ($_GET['error'] ?? null);

$baseUrl = defined('BASE_URL') ? BASE_URL : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Leer el tema actual de la cookie o por defecto nalika
$app_theme = $_COOKIE['app_theme'] ?? $_COOKIE['sena_theme'] ?? 'nalika';
$logoSena  = ($app_theme === 'light') ? $baseUrl . '/public/assets/img/logo/logo_sena_negro.svg' : $baseUrl . '/public/assets/img/logo/logo_sena_verde.svg';
?>
<!doctype html>
<html lang="es" data-theme="<?= htmlspecialchars($app_theme) ?>">
<head>
    <meta charset="utf-8">
    <title>Iniciar sesión · Sistema de Control de Ingreso</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $baseUrl ?>/public/assets/img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        :root {
            /* Tema Claro por defecto */
            --bg-color: #ffffff;
            --bg-image: none;
            --text-main: #111111;
            --text-muted: #666666;
            --input-bg: #ffffff;
            --input-border: #d1d5db;
            --input-text: #111111;
            --btn-social-bg: #ffffff;
            --btn-social-border: #d1d5db;
            --btn-social-hover: #f9fafb;
            --btn-social-text: #333333;
            --btn-primary-bg: #6952cc;
            --btn-primary-hover: #5844aa;
            --apple-icon: #000;
        }

        [data-theme="dark"] {
            --bg-color: #121212;
            --bg-image: none;
            --text-main: #ffffff;
            --text-muted: #a1a1aa;
            --input-bg: #1e1e1e;
            --input-border: #333333;
            --input-text: #ffffff;
            --btn-social-bg: #1e1e1e;
            --btn-social-border: #333333;
            --btn-social-hover: #2a2a2a;
            --btn-social-text: #ffffff;
            --btn-primary-bg: #6952cc;
            --btn-primary-hover: #5844aa;
            --apple-icon: #fff;
        }

        [data-theme="nalika"] {
            --bg-color: #152036;
            --bg-image: linear-gradient(160deg, #0f3d1a 0%, #152036 55%, #10192b 100%);
            --text-main: #ffffff;
            --text-muted: #8a9bb5;
            --input-bg: #152036;
            --input-border: #253a5c;
            --input-text: #ffffff;
            --btn-social-bg: transparent;
            --btn-social-border: #5a6a7f;
            --btn-social-hover: rgba(255, 255, 255, 0.05);
            --btn-social-text: #ffffff;
            --btn-primary-bg: #39A900;
            --btn-primary-hover: #2E7D32;
            --apple-icon: #fff;
        }

        body {
            background-color: var(--bg-color);
            background-image: var(--bg-image);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 24px;
            color: var(--text-main);
            box-sizing: border-box;
            transition: background 0.3s ease;
        }

        /* Selector de tema */
        .theme-switcher {
            position: absolute;
            top: 24px;
            right: 32px;
            display: flex;
            gap: 8px;
            z-index: 10;
        }
        .theme-btn {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-muted);
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .theme-btn:hover, .theme-btn.active {
            background: var(--input-border);
            color: var(--text-main);
        }

        .top-logo {
            position: absolute;
            top: 24px;
            left: 32px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .top-logo img {
            height: 32px;
            width: auto;
        }
        .top-logo span {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .login-container {
            width: 100%;
            max-width: 360px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        .login-header p {
            font-size: 14px;
            color: var(--text-muted);
            margin: 0;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .form-control {
            border: 1px solid var(--input-border);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            background-color: var(--input-bg);
            color: var(--input-text);
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            margin-bottom: 16px;
        }
        .form-control:focus {
            border-color: var(--btn-primary-bg);
            box-shadow: 0 0 0 3px rgba(105, 82, 204, 0.15);
            outline: none;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 0;
            margin-bottom: 20px;
        }
        .form-check-input {
            margin-top: 0;
            margin-left: 0;
            width: 16px;
            height: 16px;
            border-color: var(--input-border);
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--btn-primary-bg);
            border-color: var(--btn-primary-bg);
        }
        .form-check-label {
            font-size: 13.5px;
            color: var(--text-main);
            cursor: pointer;
        }

        .btn-primary-custom {
            background-color: var(--btn-primary-bg);
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            font-size: 14.5px;
            width: 100%;
            transition: background-color 0.2s;
            cursor: pointer;
        }
        .btn-primary-custom:hover {
            background-color: var(--btn-primary-hover);
        }

        .alert-error {
            background-color: rgba(153, 27, 27, 0.1);
            border: 1px solid rgba(153, 27, 27, 0.2);
            color: #ef4444;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
        }

        @media (max-width: 576px) {
            .top-logo {
                top: 20px;
                left: 20px;
            }
            .theme-switcher {
                top: 20px;
                right: 20px;
            }
        }
    </style>
</head>
<body>
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

    <div class="top-logo">
        <img class="main-logo" src="<?= $logoSena ?>" alt="SENA">
        <span>Control Ingreso</span>
    </div>

    <div class="login-container">
        <div class="login-header">
            <h1>¡Bienvenido de nuevo!</h1>
            <p>Por favor, ingrese sus datos</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $baseUrl ?>/index.php?page=login">
            <input type="hidden" name="rol" id="rolSeleccionado" value="aprendiz">

            <div>
                <label class="form-label" for="documento">Usuario o documento</label>
                <input type="text" class="form-control" id="documento" name="documento" required>
            </div>
            
            <div>
                <label class="form-label" for="password">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="recordar" name="recordar">
                <label class="form-check-label" for="recordar">Recordarme por 30 días</label>
            </div>
            
            <button type="submit" class="btn-primary-custom">Ingresar</button>
        </form>
    </div>

    <script>
        function updateThemeLogos(themeName) {
            const isLight = (themeName === 'light');
            const logoFile = isLight ? 'logo_sena_negro.svg' : 'logo_sena_verde.svg';
            document.querySelectorAll('.top-logo img, .main-logo').forEach(img => {
                const src = img.getAttribute('src') || '';
                const idx = src.lastIndexOf('/');
                if (idx !== -1) {
                    img.src = src.substring(0, idx + 1) + logoFile;
                } else {
                    img.src = logoFile;
                }
            });
        }

        // Lógica de cambio de tema en la misma vista de login
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const theme = this.getAttribute('data-set-theme');
                document.documentElement.setAttribute('data-theme', theme);
                
                // Actualizar estado activo en botones
                document.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                // Guardar preferencia en cookie y localStorage
                document.cookie = `app_theme=${theme}; path=/; max-age=${60*60*24*30}`;
                document.cookie = `sena_theme=${theme}; path=/; max-age=${60*60*24*30}`;
                localStorage.setItem('app_theme', theme);
                localStorage.setItem('sena_theme', theme);

                updateThemeLogos(theme);
            });
        });
    </script>
</body>
</html>
