<?php
/**
 * Vista de inicio de sesión con selector de rol.
 * @var string|null $error Mensaje de error de autenticación (opcional, lo llenará el controlador)
 */
$error = $error ?? ($_GET['error'] ?? null);

// Calcula automáticamente la carpeta base (funciona tanto en la raíz de
// htdocs como dentro de un subdirectorio, p. ej. /sena-control-ingreso/public/).
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$baseUrl   = ($scriptDir !== '' ? $scriptDir : '') . '/';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Iniciar sesión · Sistema de Control de Ingreso de Aprendices</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $baseUrl ?>assets/img/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        body{
            background: linear-gradient(160deg, #0f3d1a 0%, #152036 55%, #10192b 100%);
            font-family:'Roboto', sans-serif;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0;
            padding:24px;
        }
        .nk-auth-card{
            background:#1b2a47;
            border:1px solid rgba(255,255,255,.06);
            border-radius:14px;
            width:100%;
            max-width:480px;
            padding:38px 40px;
            box-shadow:0 20px 60px rgba(0,0,0,.35);
        }
        .nk-auth-logo{ display:flex; align-items:center; gap:12px; margin-bottom:6px; }
        .nk-auth-logo img{ height:42px; }
        .nk-auth-logo span{ font-size:20px; font-weight:700; color:#fff; line-height:1.2; }
        .nk-auth-logo small{ display:block; font-size:12px; color:#8a9bb5; font-weight:400; }
        .nk-auth-sub{ color:#8a9bb5; font-size:14px; margin-bottom:22px; }

        /* Selector de rol */
        .role-switch{ display:flex; gap:8px; background:#152036; border-radius:10px; padding:5px; margin-bottom:16px; }
        .role-btn{
            flex:1; border:none; background:transparent; color:#8a9bb5;
            font-size:13px; font-weight:600; padding:9px 6px; border-radius:7px;
            cursor:pointer; transition:.15s; text-align:center;
        }
        .role-btn i{ display:block; font-size:16px; margin-bottom:3px; }
        .role-btn.active{ background:#39A900; color:#fff; }

        .role-info{
            background:#152036; border:1px solid #253a5c; border-radius:10px;
            padding:14px 16px; margin-bottom:22px;
        }
        .role-info h6{ color:#fff; font-size:13px; font-weight:700; margin:0 0 8px 0; display:flex; align-items:center; gap:6px; }
        .role-info ul{ margin:0; padding-left:18px; }
        .role-info li{ color:#8a9bb5; font-size:12.5px; line-height:1.6; }

        .nk-auth-card .form-label{ color:#8a9bb5; font-size:13px; font-weight:500; }
        .nk-auth-card .form-control{
            background:#152036; border:1px solid #253a5c; color:#fff;
            border-radius:8px; padding:10px 14px; font-size:14px;
        }
        .nk-auth-card .form-control:focus{
            border-color:#39A900; box-shadow:0 0 0 2px rgba(57,169,0,.18);
            background:#152036; color:#fff;
        }
        .nk-auth-card .form-control::placeholder{ color:#5a6a7f; }
        .nk-auth-card .form-check-label{ color:#8a9bb5; font-size:13px; }
        .input-icon-wrap{ position:relative; }
        .input-icon-wrap i{ position:absolute; top:50%; left:14px; transform:translateY(-50%); color:#5a6a7f; }
        .input-icon-wrap .form-control{ padding-left:38px; }
        .btn-sena{
            background:#39A900; border:none; color:#fff; border-radius:8px;
            padding:11px; font-weight:600; font-size:15px; width:100%;
        }
        .btn-sena:hover{ background:#2E7D32; color:#fff; }
        .nk-auth-footer{ text-align:center; margin-top:22px; font-size:13px; color:#5a6a7f; }
        .nk-auth-footer a{ color:#39A900; text-decoration:none; }
        .role-hint{ font-size:12px; color:#5a6a7f; margin-top:6px; }
    </style>
</head>
<body>
    <div class="nk-auth-card">
        <div class="nk-auth-logo">
            <img src="<?= $baseUrl ?>assets/img/logo/logosn.png" alt="SENA">
            <span>Control de Ingreso <small>Sistema de Aprendices &middot; SENA</small></span>
        </div>
        <p class="nk-auth-sub">Elige tu rol e ingresa con tu usuario institucional.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3" style="font-size:13px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Selector de rol: define qué panel se muestra tras iniciar sesión -->
        <div class="role-switch" role="tablist">
            <button type="button" class="role-btn active" data-rol="aprendiz">
                <i class="bi bi-mortarboard"></i> Usuario
            </button>
        </div>
        <!-- TODO: al recibir POST, validar documento/contraseña contra la BD y
            confirmar que el rol coincida antes de redirigir (ver AuthController). -->
        <form method="post" action="../../controllers/loginAuth.php">
            <input type="hidden" name="rol" id="rolSeleccionado" value="aprendiz">

            <div class="mb-3">
                <label class="form-label" for="documento">Usuario o número de documento</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" class="form-control" id="documento" name="documento" placeholder="Ej. 1019456782" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordar" name="recordar">
                    <label class="form-check-label" for="recordar">Recordarme</label>
                </div>
                <a href="#" class="role-hint">¿Olvidó su contraseña?</a>
            </div>
            <button type="submit" class="btn btn-sena">Ingresar</button>
        </form>

        <div class="nk-auth-footer">
            Instructor Juan Camilo Vanegas González &middot; Proyecto ADSO
        </div>
    </div>

    <script>
        // Alterna entre las 3 tarjetas de rol; el valor queda en el input
        // oculto "rol" que se envía junto con el formulario de login.
        document.querySelectorAll('.role-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.role-btn').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.role-info').forEach(function (info) { info.classList.add('d-none'); });

                btn.classList.add('active');
                var rol = btn.getAttribute('data-rol');
                document.getElementById('rolSeleccionado').value = rol;

                var idPorRol = { aprendiz: 'infoAprendiz', instructor: 'infoInstructor', admin: 'infoAdmin' };
                document.getElementById(idPorRol[rol]).classList.remove('d-none');
            });
        });
    </script>
</body>
</html>
