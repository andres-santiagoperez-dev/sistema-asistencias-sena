<?php
/**
 * Vista de inicio de sesión rediseñada con fondo de la foto del SENA y marco flotante
 * @var string|null $error Mensaje de error de autenticación
 * @var string|null $mensaje Mensaje informativo
 */
$error = $error ?? ($_GET['error'] ?? null);
$mensaje = $mensaje ?? ($_GET['mensaje'] ?? null);

$baseUrl = defined('BASE_URL') ? BASE_URL : rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

// Tema predeterminado: Pastel Accesible (SENA)
$app_theme = 'nalika';
$logoSena  = $baseUrl . '/public/assets/img/logo/logo_sena_verde.svg';
$senaBg    = $baseUrl . '/public/assets/img/sena_bg.jpg';
?>
<!doctype html>
<html lang="es" data-theme="nalika">
<head>
    <meta charset="utf-8">
    <title>Iniciar Sesión · Sistema de Control de Ingreso SENA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $baseUrl ?>/public/assets/img/favicon.ico?v=<?= time() ?>">
    <link rel="icon" type="image/x-icon" href="<?= $baseUrl ?>/public/assets/img/favicon.ico?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        :root, [data-theme="nalika"] {
            /* Tema Pastel Accesible (Suave, relajante, sin sobrecarga) */
            --bg-overlay: rgba(244, 246, 251, 0.68);
            --card-bg: #ffffff;
            --card-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            --header-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --header-text: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --input-focus-border: #2e7d32;
            --input-text: #0f172a;
            --icon-color: #64748b;
            --btn-primary-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --btn-primary-hover: #1b5e20;
            --btn-text: #ffffff;
        }

        [data-theme="light"] {
            --bg-overlay: rgba(248, 250, 252, 0.68);
            --card-bg: #ffffff;
            --card-shadow: 0 8px 25px rgba(15, 23, 42, 0.06);
            --header-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --header-text: #ffffff;
            --text-main: #0f172a;
            --text-muted: #475569;
            --input-bg: #f8fafc;
            --input-border: #cbd5e1;
            --input-focus-border: #2e7d32;
            --input-text: #0f172a;
            --icon-color: #64748b;
            --btn-primary-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --btn-primary-hover: #1b5e20;
            --btn-text: #ffffff;
        }

        [data-theme="dark"] {
            --bg-overlay: rgba(20, 27, 41, 0.92);
            --card-bg: #1e293b;
            --card-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
            --header-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --header-text: #ffffff;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: #0f172a;
            --input-border: #334155;
            --input-focus-border: #388e3c;
            --input-text: #f8fafc;
            --icon-color: #94a3b8;
            --btn-primary-bg: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            --btn-primary-hover: #1b5e20;
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
            backdrop-filter: blur(1px);
            -webkit-backdrop-filter: blur(1px);
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
        .recuperar-link {
            color: #1b5e20;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s ease;
        }
        .recuperar-link:hover {
            color: #2e7d32;
            text-decoration: underline;
        }

        /* Modal de Recuperación */
        .modal-recuperar .modal-content {
            border: none;
            border-radius: 16px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.15);
            background-color: #ffffff;
            overflow: hidden;
        }
        .modal-recuperar .modal-header {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: #ffffff;
            border-bottom: none;
            padding: 18px 24px;
        }
        .modal-recuperar .modal-title {
            font-size: 17px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }
        .modal-recuperar .btn-close-white {
            filter: brightness(0) invert(1);
        }
        .modal-recuperar .modal-body {
            padding: 24px 26px;
            color: #1e293b;
        }
        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #166534;
            background-color: #dcfce7;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        .otp-code-input {
            letter-spacing: 8px !important;
            font-family: 'Consolas', 'Courier New', monospace !important;
            font-size: 24px !important;
            font-weight: 700 !important;
            text-align: center !important;
            color: #166534 !important;
            background-color: #f0fdf4 !important;
            border: 2px dashed #86efac !important;
        }
        .otp-code-input:focus {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2) !important;
        }
        .modal-alert {
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            display: none;
        }
        .modal-alert.show {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .modal-alert-success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        .modal-alert-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
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

                <div id="loginFeedback"></div>

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
                        <a href="javascript:void(0)" id="btnAbrirRecuperarModal" class="recuperar-link">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn-submit">
                        INICIAR SESIÓN
                    </button>
                </form>
            </div>
        </div>

        <div class="footer-credits">
            <div>&copy; <?= date('Y') ?> SENA · Centro de Comercio y Servicios</div>
            <div class="mt-2" style="font-size: 11px; opacity: 0.85; max-width: 440px; margin: 6px auto 0; line-height: 1.4;">
                <i class="bi bi-shield-check text-success me-1"></i> Este aplicativo utiliza almacenamiento local técnico indispensable para la autenticación y la operación offline conforme a la política de tratamiento de datos personales del SENA (Ley 1581 de 2012).
            </div>
        </div>
    </div>

    <!-- Modal Recuperar Contraseña -->
    <div class="modal fade modal-recuperar" id="modalRecuperarContrasena" tabindex="-1" aria-labelledby="modalRecuperarLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRecuperarLabel">
                        <i class="bi bi-shield-lock-fill"></i>
                        Recuperación de Contraseña
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="modalAlert" class="modal-alert"></div>

                    <!-- PASO 1: Ingreso de correo electrónico -->
                    <div id="paso1Container">
                        <div class="step-badge">
                            <i class="bi bi-envelope-check"></i> Paso 1 de 2: Correo Registrado
                        </div>
                        <p class="text-secondary small mb-3">
                            Ingresa el correo electrónico asociado a tu cuenta SENA. Te enviaremos un código de seguridad de 6 dígitos.
                        </p>
                        <form id="formPaso1" onsubmit="return false;">
                            <div class="input-group-custom mb-3">
                                <label for="recupCorreo">Correo Electrónico</label>
                                <div class="input-field-wrapper">
                                    <input type="email" class="form-control-custom" id="recupCorreo" placeholder="ejemplo@correo.com" required autocomplete="email">
                                    <i class="bi bi-envelope-at-fill field-icon"></i>
                                </div>
                            </div>
                            <button type="button" class="btn-submit" id="btnEnviarCodigo">
                                <i class="bi bi-send-fill me-1"></i> ENVIAR CÓDIGO
                            </button>
                        </form>
                    </div>

                    <!-- PASO 2: Ingreso de código OTP y nueva contraseña -->
                    <div id="paso2Container" style="display: none;">
                        <div class="step-badge">
                            <i class="bi bi-key-fill"></i> Paso 2 de 2: Verificación y Nueva Contraseña
                        </div>
                        <p class="text-secondary small mb-3">
                            Hemos enviado un código de 6 dígitos a <strong id="txtCorreoDestino" class="text-success"></strong>. Ingrésalo a continuación junto con tu nueva contraseña.
                        </p>
                        <form id="formPaso2" onsubmit="return false;">
                            <div class="input-group-custom mb-3">
                                <label for="recupCodigo" class="text-center d-block">Código de Verificación (6 dígitos)</label>
                                <input type="text" class="form-control-custom otp-code-input" id="recupCodigo" maxlength="6" placeholder="000000" required autocomplete="off" inputmode="numeric">
                                <div class="text-center mt-1">
                                    <small class="text-muted" style="font-size: 11.5px;">Válido durante 15 minutos</small>
                                </div>
                            </div>

                            <div class="input-group-custom mb-3">
                                <label for="recupPassword">Nueva Contraseña</label>
                                <div class="input-field-wrapper">
                                    <input type="password" class="form-control-custom" id="recupPassword" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
                                    <i class="bi bi-eye-fill field-icon toggle-password" id="toggleRecupPassBtn" title="Mostrar/Ocultar"></i>
                                </div>
                            </div>

                            <div class="input-group-custom mb-4">
                                <label for="recupPasswordConfirm">Confirmar Nueva Contraseña</label>
                                <div class="input-field-wrapper">
                                    <input type="password" class="form-control-custom" id="recupPasswordConfirm" placeholder="Repite la contraseña" required autocomplete="new-password">
                                    <i class="bi bi-eye-fill field-icon toggle-password" id="toggleRecupPassConfBtn" title="Mostrar/Ocultar"></i>
                                </div>
                            </div>

                            <button type="button" class="btn-submit mb-3" id="btnRestablecerPass">
                                <i class="bi bi-check-circle-fill me-1"></i> ACTUALIZAR CONTRASEÑA
                            </button>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted p-0" id="btnVolverPaso1">
                                    <i class="bi bi-arrow-left"></i> Cambiar correo
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-decoration-none text-success p-0 fw-semibold" id="btnReenviarCodigo">
                                    <i class="bi bi-arrow-clockwise"></i> Reenviar código
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Ocultar / Mostrar contraseña del login
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

        // Toggles para contraseñas dentro del modal de recuperación
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const isPass = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPass ? 'text' : 'password');
                    this.classList.toggle('bi-eye-fill', !isPass);
                    this.classList.toggle('bi-eye-slash-fill', isPass);
                });
            }
        }
        setupPasswordToggle('toggleRecupPassBtn', 'recupPassword');
        setupPasswordToggle('toggleRecupPassConfBtn', 'recupPasswordConfirm');

        // Modal y flujo de recuperación de contraseña
        const modalElement = document.getElementById('modalRecuperarContrasena');
        const recoveryModal = new bootstrap.Modal(modalElement);
        const btnAbrirRecuperar = document.getElementById('btnAbrirRecuperarModal');

        const paso1Container = document.getElementById('paso1Container');
        const paso2Container = document.getElementById('paso2Container');
        const modalAlert = document.getElementById('modalAlert');
        const loginFeedback = document.getElementById('loginFeedback');

        const recupCorreo = document.getElementById('recupCorreo');
        const recupCodigo = document.getElementById('recupCodigo');
        const recupPassword = document.getElementById('recupPassword');
        const recupPasswordConfirm = document.getElementById('recupPasswordConfirm');
        const txtCorreoDestino = document.getElementById('txtCorreoDestino');

        const btnEnviarCodigo = document.getElementById('btnEnviarCodigo');
        const btnRestablecerPass = document.getElementById('btnRestablecerPass');
        const btnVolverPaso1 = document.getElementById('btnVolverPaso1');
        const btnReenviarCodigo = document.getElementById('btnReenviarCodigo');

        let cooldownTimer = null;
        let cooldownSeconds = 0;

        function mostrarAlertaModal(tipo, mensaje) {
            modalAlert.className = `modal-alert show modal-alert-${tipo}`;
            const icono = tipo === 'danger' ? 'exclamation-triangle-fill' : (tipo === 'success' ? 'check-circle-fill' : 'info-circle-fill');
            modalAlert.innerHTML = `<i class="bi bi-${icono}"></i> <span>${mensaje}</span>`;
        }

        function ocultarAlertaModal() {
            modalAlert.className = 'modal-alert';
            modalAlert.innerHTML = '';
        }

        function reiniciarModal() {
            ocultarAlertaModal();
            paso1Container.style.display = 'block';
            paso2Container.style.display = 'none';
            recupCodigo.value = '';
            recupPassword.value = '';
            recupPasswordConfirm.value = '';
            btnEnviarCodigo.disabled = false;
            btnEnviarCodigo.innerHTML = '<i class="bi bi-send-fill me-1"></i> ENVIAR CÓDIGO';
            btnRestablecerPass.disabled = false;
            btnRestablecerPass.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ACTUALIZAR CONTRASEÑA';
        }

        if (btnAbrirRecuperar) {
            btnAbrirRecuperar.addEventListener('click', function() {
                reiniciarModal();
                // Si ya había ingresado algo en el login, sugerirlo como correo
                const valorLogin = document.getElementById('documento').value.trim();
                if (valorLogin.includes('@')) {
                    recupCorreo.value = valorLogin;
                }
                recoveryModal.show();
                setTimeout(() => recupCorreo.focus(), 400);
            });
        }

        // Paso 1: Enviar Código
        async function solicitarCodigo() {
            const correo = recupCorreo.value.trim();
            if (!correo || !correo.includes('@')) {
                mostrarAlertaModal('danger', 'Ingresa un correo electrónico válido.');
                recupCorreo.focus();
                return;
            }

            ocultarAlertaModal();
            btnEnviarCodigo.disabled = true;
            btnEnviarCodigo.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando código...';

            try {
                const formData = new FormData();
                formData.append('correo', correo);

                const response = await fetch('<?= $baseUrl ?>/index.php?page=recuperar_password&action=solicitar_codigo', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    txtCorreoDestino.textContent = correo;
                    paso1Container.style.display = 'none';
                    paso2Container.style.display = 'block';
                    mostrarAlertaModal('success', data.message || 'Código enviado con éxito.');
                    iniciarCooldownReenvio(45);
                    setTimeout(() => recupCodigo.focus(), 300);
                } else {
                    mostrarAlertaModal('danger', data.message || 'Error al enviar el código.');
                }
            } catch (err) {
                mostrarAlertaModal('danger', 'Error de conexión con el servidor. Verifica tu conexión a internet.');
            } finally {
                btnEnviarCodigo.disabled = false;
                btnEnviarCodigo.innerHTML = '<i class="bi bi-send-fill me-1"></i> ENVIAR CÓDIGO';
            }
        }

        btnEnviarCodigo.addEventListener('click', solicitarCodigo);
        recupCorreo.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                solicitarCodigo();
            }
        });

        // Paso 2: Volver al Paso 1
        btnVolverPaso1.addEventListener('click', function() {
            ocultarAlertaModal();
            paso2Container.style.display = 'none';
            paso1Container.style.display = 'block';
            recupCorreo.focus();
        });

        // Paso 2: Reenviar Código
        function iniciarCooldownReenvio(segundos) {
            cooldownSeconds = segundos;
            btnReenviarCodigo.disabled = true;
            btnReenviarCodigo.classList.add('text-muted');
            btnReenviarCodigo.classList.remove('text-success');

            clearInterval(cooldownTimer);
            cooldownTimer = setInterval(() => {
                if (cooldownSeconds <= 0) {
                    clearInterval(cooldownTimer);
                    btnReenviarCodigo.disabled = false;
                    btnReenviarCodigo.classList.remove('text-muted');
                    btnReenviarCodigo.classList.add('text-success');
                    btnReenviarCodigo.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Reenviar código';
                } else {
                    btnReenviarCodigo.innerHTML = `<i class="bi bi-clock"></i> Reenviar en ${cooldownSeconds}s`;
                    cooldownSeconds--;
                }
            }, 1000);
        }

        btnReenviarCodigo.addEventListener('click', function() {
            if (btnReenviarCodigo.disabled) return;
            solicitarCodigo();
        });

        // Paso 2: Validar y Restablecer Contraseña
        async function restablecerPassword() {
            const correo = recupCorreo.value.trim();
            const codigo = recupCodigo.value.trim();
            const password = recupPassword.value;
            const passwordConfirm = recupPasswordConfirm.value;

            if (codigo.length !== 6 || !/^\d+$/.test(codigo)) {
                mostrarAlertaModal('danger', 'El código de verificación debe contener 6 números.');
                recupCodigo.focus();
                return;
            }

            if (password.length < 6) {
                mostrarAlertaModal('danger', 'La contraseña debe tener al menos 6 caracteres.');
                recupPassword.focus();
                return;
            }

            if (password !== passwordConfirm) {
                mostrarAlertaModal('danger', 'Las contraseñas no coinciden. Verifícalas bien.');
                recupPasswordConfirm.focus();
                return;
            }

            ocultarAlertaModal();
            btnRestablecerPass.disabled = true;
            btnRestablecerPass.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Actualizando...';

            try {
                const formData = new FormData();
                formData.append('correo', correo);
                formData.append('codigo', codigo);
                formData.append('password', password);
                formData.append('password_confirm', passwordConfirm);

                const response = await fetch('<?= $baseUrl ?>/index.php?page=recuperar_password&action=restablecer', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Cerrar modal
                    recoveryModal.hide();

                    // Mostrar mensaje de éxito en la pantalla de inicio de sesión
                    loginFeedback.innerHTML = `
                        <div class="alert-custom" style="background-color: #f0fdf4; border: 1px solid #86efac; color: #166534;">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>${data.message || 'Contraseña actualizada con éxito.'}</div>
                        </div>
                    `;

                    // Pre-llenar campo de usuario y enfocar contraseña
                    document.getElementById('documento').value = correo;
                    document.getElementById('password').value = '';
                    document.getElementById('password').focus();
                } else {
                    mostrarAlertaModal('danger', data.message || 'Error al restablecer la contraseña.');
                }
            } catch (err) {
                mostrarAlertaModal('danger', 'Error de conexión al intentar actualizar la contraseña.');
            } finally {
                btnRestablecerPass.disabled = false;
                btnRestablecerPass.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> ACTUALIZAR CONTRASEÑA';
            }
        }

        btnRestablecerPass.addEventListener('click', restablecerPassword);
        recupPasswordConfirm.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                restablecerPassword();
            }
        });
    </script>
</body>
</html>
