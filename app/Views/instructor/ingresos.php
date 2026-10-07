<?php
/**
 * Vista de Control de Ingresos para Instructor
 * Sistema de Control de Asistencia SENA
 */

$tituloPagina = $tituloPagina ?? 'Control de Ingresos';
require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-upc-scan fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Control de Ingresos y Asistencia</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Escanée con el lector de código de barras o RFID para registrar marcaciones en tiempo real.</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge px-3 py-2 text-theme-primary" style="font-size:13px; background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                <i class="bi bi-calendar3 text-info me-1"></i> Fecha: <strong><?= date('d/m/Y', strtotime($fecha)) ?></strong>
            </span>
            <span class="badge-sena badge-on-time" id="contadorIngresos">
                <i class="bi bi-person-check-fill me-1"></i> <?= count($ingresos) ?> Registros
            </span>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. SECCIÓN SUPERIOR: FICHAS DEL INSTRUCTOR -->
<!-- ========================================== -->
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="text-theme-primary fw-bold mb-0">
            <i class="bi bi-journal-bookmark-fill me-2 text-success"></i>Mis Fichas Formativas Asignadas
        </h5>
        <small class="text-secondary">Haga clic en una ficha para filtrar las marcaciones</small>
    </div>

    <div class="row g-3">
        <?php if (empty($misFichas)): ?>
            <div class="col-12">
                <div class="alert alert-warning border-0 p-3" style="border-radius:10px;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> No tiene fichas asignadas en este momento.
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($misFichas as $f): 
            $esSeleccionada = ($f['numero_ficha'] === $fichaSeleccionada || $f['ficha_completa'] === $fichaSeleccionada);
        ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <div class="card card-stat-ux h-100 p-3 ficha-card <?= $esSeleccionada ? 'border-success' : '' ?>" 
                     style="border-radius: 12px; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; <?= $esSeleccionada ? 'border: 2px solid #39a900 !important;' : '' ?>"
                     onclick="filtrarPorFicha('<?= htmlspecialchars($f['numero_ficha']) ?>')">
                    
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2.5 py-1 fw-bold" style="font-size: 13px;">
                            Ficha: <?= htmlspecialchars($f['numero_ficha']) ?>
                        </span>
                        <button type="button" 
                                class="btn btn-sm btn-warning text-dark font-size-11 py-0 px-2 fw-bold" 
                                style="font-size:11px;"
                                onclick="event.stopPropagation(); abrirModalTolerancia(<?= (int)$f['id_ficha'] ?>, '<?= htmlspecialchars($f['numero_ficha']) ?>', '<?= htmlspecialchars(addslashes($f['programa'])) ?>', '<?= htmlspecialchars($f['entrada'] ?? '07:00 AM') ?>', '<?= htmlspecialchars($f['salida'] ?? '01:00 PM') ?>', '<?= htmlspecialchars($f['raw_hora_entrada'] ?? '07:00:00') ?>', '<?= htmlspecialchars($f['raw_hora_salida'] ?? '13:00:00') ?>', <?= (int)($f['minutos_retardo'] ?? 15) ?>, <?= (int)($f['minutos_salida'] ?? 0) ?>)" 
                                title="Modificar Tolerancia de Horario">
                            <i class="bi bi-clock-history me-1"></i> Tolerancia
                        </button>
                    </div>

                    <h6 class="text-theme-primary fw-bold mb-2 text-truncate" title="<?= htmlspecialchars($f['programa']) ?>" style="font-size:14px;">
                        <?= htmlspecialchars($f['programa']) ?>
                    </h6>

                    <div class="d-flex align-items-center justify-content-between text-secondary" style="font-size: 12px;">
                        <span><i class="bi bi-clock me-1 text-info"></i><?= $f['entrada'] ?? '07:00 AM' ?> - <?= $f['salida'] ?? '01:00 PM' ?></span>
                        <span class="text-success"><i class="bi bi-people me-1"></i><?= $f['aprendices'] ?> apr.</span>
                    </div>
                    <div class="mt-2 pt-1.5 border-top border-secondary border-opacity-10 d-flex align-items-center justify-content-between" style="font-size: 11px;">
                        <span class="text-success fw-semibold" title="Ingreso a tiempo permitido">
                            <i class="bi bi-shield-check me-1"></i>Entrada hasta: <strong><?= htmlspecialchars($f['limite_entrada'] ?? ($f['entrada'] ?? '07:00 AM')) ?></strong> (<?= htmlspecialchars($f['texto_tolerancia_entrada'] ?? '0m') ?>)
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- =================================================================== -->
<div class="row g-4 mb-4">
    
    <!-- COLUMNA IZQUIERDA: INPUT DEL LECTOR DE CÓDIGOS -->
    <div class="col-12 col-lg-4 col-xl-4">
        <div class="card card-stat-ux p-4 h-100" style="border-radius: 12px;">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2" style="border-color: var(--nk-border-color) !important;">
                <h5 class="text-theme-primary fw-bold mb-0">
                    <i class="bi bi-qr-code-scan me-2 text-success"></i>Lector de Código
                </h5>
                <span class="badge bg-success border border-success text-white px-2 py-1" id="badgeStatusScanner" style="font-size: 11px; animation: pulse 2s infinite;">
                    <i class="bi bi-record-circle me-1"></i>Enfocado
                </span>
            </div>

            <p class="text-secondary mb-3" style="font-size: 13px;">
                Ubique el cursor en el recuadro y use el lector. El lector copiará el código y al presionar Enter registrará la marcación en la tabla.
            </p>

            <form id="formEscaner" method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_ingresos&action=registrar_escaneo" onsubmit="procesarEscaneoForm(event)">
                <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">
                <input type="hidden" name="ficha" value="<?= htmlspecialchars($fichaSeleccionada) ?>">

                <div class="mb-3">
                    <label for="inputCodigoScanner" class="form-label text-theme-primary fw-semibold" style="font-size:14px;">
                        <i class="bi bi-barcode text-info me-1"></i>Código RFID / Documento:
                    </label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text border-success" style="background:#007934; color:#fff;">
                            <i class="bi bi-upc-scan fs-4"></i>
                        </span>
                        <input type="text" 
                               id="inputCodigoScanner" 
                               name="codigo" 
                               class="form-control form-control-lg text-theme-primary fw-bold" 
                               placeholder="Escanee código..." 
                               style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: #39a900; letter-spacing: 1px;"
                               autofocus 
                               autocomplete="off"
                               required>
                    </div>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-sena-green py-2.5 fw-bold" id="btnEnviarScan">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Registrar Marcación (Enter)
                    </button>
                    <button type="button" class="btn btn-sm btn-sena-outline" onclick="enfocarLectorManualmente()">
                        <i class="bi bi-crosshair me-1"></i> Volver a Enfocar Casilla
                    </button>
                </div>
            </form>

            <!-- Notificación / Feedback en vivo del escaneo -->
            <div id="contenedorFeedbackScan" class="mt-2">
                <?php if (!empty($mensajeScan)): ?>
                    <div class="alert alert-<?= $mensajeScan['type'] ?? 'info' ?> border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill fs-5 mt-0.5"></i>
                            <div>
                                <strong class="d-block" style="font-size:14px;"><?= htmlspecialchars($mensajeScan['message']) ?></strong>
                                <?php if (!empty($mensajeScan['aprendiz'])): ?>
                                    <small class="text-secondary d-block mt-1">
                                        Aprendiz: <strong><?= htmlspecialchars($mensajeScan['aprendiz']) ?></strong> | 
                                        Ficha: <strong><?= htmlspecialchars($mensajeScan['ficha']) ?></strong>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="p-3 text-center mb-0" style="border-radius: 10px; background: var(--nk-bg-inset); border: 1px solid var(--nk-border-color);">
                        <i class="bi bi-broadcast fs-3 text-success d-block mb-1"></i>
                        <span class="text-secondary" style="font-size: 12px;">Esperando lectura del lector de tarjetas o códigos...</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- COLUMNA DERECHA: TABLA DE CÓDIGOS E INGRESOS INSERTADOS -->
    <div class="col-12 col-lg-8 col-xl-8">
        <div class="card card-stat-ux p-4 h-100" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 border-bottom pb-2" style="border-color: var(--nk-border-color) !important;">
                <div>
                    <h5 class="text-theme-primary fw-bold mb-1">
                        <i class="bi bi-table me-2 text-info"></i>Tabla de Ingresos Registrados
                    </h5>
                    <p class="text-secondary mb-0" style="font-size:13px;">Visualización de marcaciones y códigos procesados</p>
                </div>
                
                <!-- Filtros de fecha y ficha -->
                <form class="d-flex gap-2 flex-wrap align-items-center" method="GET" action="<?= BASE_URL ?>/index.php">
                    <input type="hidden" name="page" value="instructor_ingresos">
                    <input type="date" class="form-control form-control-sm" name="fecha" value="<?= htmlspecialchars($fecha) ?>" style="width: 150px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);" onchange="this.form.submit()">
                    
                    <select class="form-select form-select-sm" name="ficha" id="selectFichaFiltro" style="width: 200px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);" onchange="this.form.submit()">
                        <option value="" style="background-color: var(--nk-card-bg); color: var(--nk-text-primary);">Todas mis fichas</option>
                        <?php foreach ($misFichas as $f): ?>
                            <option value="<?= htmlspecialchars($f['numero_ficha']) ?>" <?= ($f['numero_ficha'] === $fichaSeleccionada || $f['ficha_completa'] === $fichaSeleccionada) ? 'selected' : '' ?> style="background-color: var(--nk-card-bg); color: var(--nk-text-primary);">
                                <?= htmlspecialchars($f['ficha_completa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!empty($fichaSeleccionada) || $fecha !== date('Y-m-d')): ?>
                        <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sm btn-sena-outline" title="Ver hoy"><i class="bi bi-arrow-counterclockwise"></i> Hoy</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de Ingresos -->
            <div class="w-100" style="overflow-x: hidden;">
                <table class="table table-nalika-dark table-hover w-100 align-middle mb-0" id="tablaIngresos" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 17%; font-size:12px;">Código / Doc</th>
                            <th style="width: 25%; font-size:12px;">Aprendiz</th>
                            <th style="width: 22%; font-size:12px;">Ficha</th>
                            <th style="width: 12%; font-size:12px;">Entrada</th>
                            <th style="width: 12%; font-size:12px;">Salida</th>
                            <th style="width: 12%; font-size:12px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyIngresos">
                    <?php if (empty($ingresos)): ?>
                        <tr id="trSinRegistros">
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-door-closed fs-1 text-muted mb-2 d-block"></i>
                                No hay marcaciones registradas para la fecha seleccionada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ingresos as $i): 
                            $estadoFormat = strtolower($i['estado']);
                            $codigoMostrar = !empty($i['codigo_rfid']) ? $i['codigo_rfid'] : ($i['documento'] ?? 'N/A');
                            $partesFicha = explode(' - ', $i['ficha'], 2);
                            $nFicha = $partesFicha[0] ?? $i['ficha'];
                            $pFicha = $partesFicha[1] ?? '';
                        ?>
                            <tr>
                                <td style="padding: 8px 6px;">
                                    <span class="badge font-monospace px-1.5 py-1 text-truncate d-inline-block mw-100" style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border: 1px solid var(--nk-border-color); font-size:11.5px;" title="<?= htmlspecialchars($codigoMostrar) ?>">
                                        <i class="bi bi-barcode text-success me-1"></i><?= htmlspecialchars($codigoMostrar) ?>
                                    </span>
                                </td>
                                <td style="padding: 8px 6px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <img class="avatar-sm flex-shrink-0" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($i['aprendiz']) ?>" alt="" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #39a900;">
                                        <strong class="text-theme-primary text-truncate d-block" style="font-size:12.5px;" title="<?= htmlspecialchars($i['aprendiz']) ?>"><?= htmlspecialchars($i['aprendiz']) ?></strong>
                                    </div>
                                </td>
                                <td style="padding: 8px 6px;">
                                    <div>
                                        <strong class="text-theme-primary d-block" style="font-size:12.5px;">Ficha <?= htmlspecialchars($nFicha) ?></strong>
                                        <?php if (!empty($pFicha)): ?>
                                            <small class="text-secondary d-block text-truncate" style="font-size:11px;" title="<?= htmlspecialchars($pFicha) ?>"><?= htmlspecialchars($pFicha) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($i['duracion_horas'])): ?>
                                            <span class="badge bg-dark bg-opacity-50 text-info border border-info border-opacity-25 mt-0.5" style="font-size: 10px;">
                                                <i class="bi bi-clock me-1"></i><?= ((int)$i['duracion_horas'] >= 6 || ($i['tipo_bloque'] ?? '') === '6H_CONTINUO') ? '6h Continua' : $i['duracion_horas'] . 'h' ?><?= !empty($i['ambiente']) ? ' · ' . htmlspecialchars($i['ambiente']) : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 8px 6px;">
                                    <?php if ($i['entrada']): ?>
                                        <span class="text-success fw-bold" style="font-size:12px;"><i class="bi bi-box-arrow-in-right me-1"></i><?= date('h:i A', strtotime($i['entrada'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">--:--</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 6px;">
                                    <?php if ($i['salida']): ?>
                                        <span class="text-info fw-bold" style="font-size:12px;"><i class="bi bi-box-arrow-left me-1"></i><?= date('h:i A', strtotime($i['salida'])) ?></span>
                                    <?php else: ?>
                                        <span class="text-secondary">--:--</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 6px;">
                                    <?php if ($estadoFormat === 'asistio'): ?>
                                        <span class="badge-sena badge-on-time py-0.5 px-2" style="font-size:11px;"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>
                                    <?php elseif ($estadoFormat === 'retardo'): ?>
                                        <span class="badge-sena badge-late py-0.5 px-2" style="font-size:11px;"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>
                                    <?php elseif ($estadoFormat === 'inasistencia'): ?>
                                        <span class="badge-sena badge-absent py-0.5 px-2" style="font-size:11px;"><i class="bi bi-x-circle-fill"></i> Inasist.</span>
                                    <?php elseif ($estadoFormat === 'salida temprana'): ?>
                                        <span class="badge-sena badge-late py-0.5 px-2" style="font-size:11px;"><i class="bi bi-arrow-left-circle-fill"></i> S. Temp.</span>
                                    <?php else: ?>
                                        <span class="badge-sena badge-pending py-0.5 px-2" style="font-size:11px;"><?= ucfirst($i['estado']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Estilos CSS Personalizados para la página de ingresos -->
<style>
.ficha-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(57, 169, 0, 0.2) !important;
}
@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.4; }
    100% { opacity: 1; }
}
#inputCodigoScanner:focus {
    box-shadow: 0 0 14px rgba(57, 169, 0, 0.6) !important;
    border-color: #39a900 !important;
}
.fila-nueva-animada {
    animation: highlightGreen 2.5s ease-out;
}
@keyframes highlightGreen {
    0% { background-color: rgba(57, 169, 0, 0.45); }
    100% { background-color: transparent; }
}
</style>

<!-- Scripts de Interacción del Escáner y Re-Enfoque Automático -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputScanner = document.getElementById('inputCodigoScanner');
    const badgeScanner = document.getElementById('badgeStatusScanner');

    if (inputScanner) {
        // Enfoque inicial
        inputScanner.focus();

        // Control visual de enfocado
        inputScanner.addEventListener('focus', function() {
            if (badgeScanner) {
                badgeScanner.className = 'badge bg-success border border-success text-white px-2 py-1';
                badgeScanner.innerHTML = '<i class="bi bi-record-circle me-1"></i>Enfocado';
            }
        });

        inputScanner.addEventListener('blur', function() {
            if (badgeScanner) {
                badgeScanner.className = 'badge bg-warning border border-warning text-dark px-2 py-1';
                badgeScanner.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Desenfocado (Haga clic aquí)';
            }
        });

        // Al hacer clic en cualquier parte vacía de la página, devolver el foco automáticamente
        document.addEventListener('click', function(e) {
            const esOtroInput = ['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'A'].includes(e.target.tagName);
            if (!esOtroInput && inputScanner) {
                inputScanner.focus();
            }
        });
    }
});

function enfocarLectorManualmente() {
    const inputScanner = document.getElementById('inputCodigoScanner');
    if (inputScanner) {
        inputScanner.focus();
        inputScanner.select();
    }
}

function filtrarPorFicha(numeroFicha) {
    const baseUrl = '<?= BASE_URL ?>/index.php?page=instructor_ingresos';
    const fechaActual = '<?= htmlspecialchars($fecha) ?>';
    window.location.href = baseUrl + '&fecha=' + encodeURIComponent(fechaActual) + '&ficha=' + encodeURIComponent(numeroFicha);
}

// Reproductor de Tonos Web Audio API para Feedback Sonoro Físico
function reproducirSonido(tipo) {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        if (tipo === 'success') {
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.15);
        } else if (tipo === 'warning') {
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.25);
        } else {
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(220, audioCtx.currentTime); // A3
            gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.3);
        }
    } catch(e) {
        // Audio deshabilitado por el navegador
    }
}

// Procesar Escaneo vía AJAX Ultrarrápido
function procesarEscaneoForm(event) {
    event.preventDefault();

    const form = document.getElementById('formEscaner');
    const inputScanner = document.getElementById('inputCodigoScanner');
    const contenedorFeedback = document.getElementById('contenedorFeedbackScan');
    const codigo = inputScanner.value.trim();

    if (!codigo) return;

    // Limpiar input y re-enfocar INMEDIATAMENTE para permitir escaneos continuos en milisegundos
    const codigoEnviado = codigo;
    inputScanner.value = '';
    inputScanner.focus();

    const formData = new FormData(form);
    formData.set('codigo', codigoEnviado);
    formData.append('ajax', '1');

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Reproducir Tono Sonoro
        reproducirSonido(data.success ? (data.type === 'warning' ? 'warning' : 'success') : 'error');

        // Mostrar Alerta de Feedback en la tarjeta del lector
        let alertClass = 'alert-' + (data.type || 'info');
        let iconClass = data.success ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

        contenedorFeedback.innerHTML = `
            <div class="alert ${alertClass} border-0 shadow-sm p-3 mb-0" style="border-radius: 10px;">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi ${iconClass} fs-5 mt-0.5"></i>
                    <div>
                        <strong class="d-block" style="font-size:14px;">${escapeHtml(data.message)}</strong>
                        ${data.aprendiz ? `
                            <small class="text-secondary d-block mt-1">
                                Aprendiz: <strong>${escapeHtml(data.aprendiz)}</strong> | Ficha: <strong>${escapeHtml(data.ficha)}</strong> | Hora: <strong>${escapeHtml(data.hora)}</strong>
                            </small>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;

        // 1. Inserción u actualización ULTRA-RÁPIDA e INMEDIATA en el DOM
        if (data.success && data.aprendiz) {
            insertarRegistroEnTabla(data);
        }

        // 2. Sincronizar tabla completa si el servidor devolvió lista actualizada
        if (data.ingresos && data.ingresos.length > 0) {
            actualizarTablaIngresos(data.ingresos);
        }

        // Mantener foco siempre listo para la siguiente lectura
        inputScanner.focus();
    })
    .catch(error => {
        console.error("Error al procesar el escaneo:", error);
        contenedorFeedback.innerHTML = `
            <div class="alert alert-danger border-0 p-3 mb-0" style="border-radius: 10px;">
                <i class="bi bi-x-circle-fill me-1"></i> Error de conexión al procesar el código.
            </div>
        `;
        inputScanner.focus();
    });
}

// Inserción / Actualización Inmediata en el DOM del navegador (< 1ms)
function insertarRegistroEnTabla(data) {
    const tbody = document.getElementById('tbodyIngresos');
    if (!tbody) return;

    // Quitar mensaje de "No hay marcaciones"
    const trSinReg = document.getElementById('trSinRegistros');
    if (trSinReg) trSinReg.remove();

    // Buscar si ya existe una fila para este aprendiz en la tabla
    const filas = tbody.querySelectorAll('tr');
    let filaExistente = null;

    filas.forEach(tr => {
        const texto = tr.textContent.toLowerCase();
        if (data.aprendiz && texto.includes(data.aprendiz.toLowerCase())) {
            filaExistente = tr;
        }
    });

    if (filaExistente && data.action === 'salida') {
        const celdas = filaExistente.querySelectorAll('td');
        if (celdas.length >= 5) {
            celdas[4].innerHTML = `<span class="text-info fw-bold" style="font-size:13px;"><i class="bi bi-box-arrow-left me-1"></i>${escapeHtml(data.hora)}</span>`;
        }
        filaExistente.classList.add('fila-nueva-animada');
        setTimeout(() => filaExistente.classList.remove('fila-nueva-animada'), 2500);
        return;
    }

    if (filaExistente && data.action === 'entrada') {
        return;
    }

    // Crear la nueva fila al principio de la tabla (Prepend instantáneo)
    let estadoLower = (data.estado || '').toLowerCase();
    let badgeHtml = '';
    if (estadoLower === 'asistio') {
        badgeHtml = '<span class="badge-sena badge-on-time py-0.5 px-2" style="font-size:11px;"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>';
    } else if (estadoLower === 'retardo') {
        badgeHtml = '<span class="badge-sena badge-late py-0.5 px-2" style="font-size:11px;"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>';
    } else if (estadoLower === 'inasistencia') {
        badgeHtml = '<span class="badge-sena badge-absent py-0.5 px-2" style="font-size:11px;"><i class="bi bi-x-circle-fill"></i> Inasist.</span>';
    } else {
        badgeHtml = `<span class="badge-sena badge-pending py-0.5 px-2" style="font-size:11px;">${escapeHtml(data.estado || 'Registrado')}</span>`;
    }

    let codigoMostrar = data.codigo || 'N/A';
    let horaEntrada = `<span class="text-success fw-bold" style="font-size:12px;"><i class="bi bi-box-arrow-in-right me-1"></i>${escapeHtml(data.hora)}</span>`;
    let horaSalida  = '<span class="text-secondary">--:--</span>';

    let partesFichaData = String(data.ficha || '').split(' - ');
    let numFichaData = partesFichaData[0] || data.ficha || 'N/A';
    let progFichaData = partesFichaData.length > 1 ? partesFichaData.slice(1).join(' - ') : '';
    let bloqueBadge = data.bloque ? `<span class="badge bg-dark bg-opacity-50 text-info border border-info border-opacity-25 mt-0.5" style="font-size: 10px;"><i class="bi bi-clock me-1"></i>${escapeHtml(data.bloque)}</span>` : '';
    let fichaHtmlData = `<div><strong class="text-theme-primary d-block" style="font-size:12.5px;">Ficha ${escapeHtml(numFichaData)}</strong>${progFichaData ? `<small class="text-secondary d-block text-truncate" style="font-size:11px;" title="${escapeHtml(progFichaData)}">${escapeHtml(progFichaData)}</small>` : ''}${bloqueBadge}</div>`;

    const tr = document.createElement('tr');
    tr.className = 'fila-nueva-animada';
    tr.innerHTML = `
        <td style="padding: 8px 6px;">
            <span class="badge font-monospace px-1.5 py-1 text-truncate d-inline-block mw-100" style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border: 1px solid var(--nk-border-color); font-size:11.5px;" title="${escapeHtml(codigoMostrar)}">
                <i class="bi bi-barcode text-success me-1"></i>${escapeHtml(codigoMostrar)}
            </span>
        </td>
        <td style="padding: 8px 6px;">
            <div class="d-flex align-items-center gap-2">
                <img class="avatar-sm flex-shrink-0" src="https://ui-avatars.com/api/?background=random&name=${encodeURIComponent(data.aprendiz)}" alt="" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #39a900;">
                <strong class="text-theme-primary text-truncate d-block" style="font-size:12.5px;" title="${escapeHtml(data.aprendiz)}">${escapeHtml(data.aprendiz)}</strong>
            </div>
        </td>
        <td style="padding: 8px 6px;">${fichaHtmlData}</td>
        <td style="padding: 8px 6px;">${horaEntrada}</td>
        <td style="padding: 8px 6px;">${horaSalida}</td>
        <td style="padding: 8px 6px;">${badgeHtml}</td>
    `;

    tbody.insertBefore(tr, tbody.firstChild);

    // Actualizar contador
    const contador = document.getElementById('contadorIngresos');
    if (contador) {
        const total = tbody.querySelectorAll('tr:not(#trSinRegistros)').length;
        contador.innerHTML = `<i class="bi bi-person-check-fill me-1"></i> ${total} Registros`;
    }

    setTimeout(() => tr.classList.remove('fila-nueva-animada'), 2500);
}

function actualizarTablaIngresos(ingresos) {
    const tbody = document.getElementById('tbodyIngresos');
    const contador = document.getElementById('contadorIngresos');

    if (contador) {
        contador.innerHTML = `<i class="bi bi-person-check-fill me-1"></i> ${ingresos.length} Registros`;
    }

    if (!ingresos || ingresos.length === 0) {
        if (!tbody.querySelector('tr:not(#trSinRegistros)')) {
            tbody.innerHTML = `
                <tr id="trSinRegistros">
                    <td colspan="6" class="text-center text-secondary py-5">
                        <i class="bi bi-door-closed fs-1 text-muted mb-2 d-block"></i>
                        No hay marcaciones registradas para la fecha seleccionada.
                    </td>
                </tr>
            `;
        }
        return;
    }

    let html = '';
    ingresos.forEach((i, index) => {
        let codigoMostrar = i.codigo_rfid ? i.codigo_rfid : (i.documento || 'N/A');
        let estadoLower = (i.estado || '').toLowerCase();
        let badgeHtml = '';

        if (estadoLower === 'asistio') {
            badgeHtml = '<span class="badge-sena badge-on-time py-0.5 px-2" style="font-size:11px;"><i class="bi bi-check-circle-fill"></i> A Tiempo</span>';
        } else if (estadoLower === 'retardo') {
            badgeHtml = '<span class="badge-sena badge-late py-0.5 px-2" style="font-size:11px;"><i class="bi bi-exclamation-triangle-fill"></i> Retardo</span>';
        } else if (estadoLower === 'inasistencia') {
            badgeHtml = '<span class="badge-sena badge-absent py-0.5 px-2" style="font-size:11px;"><i class="bi bi-x-circle-fill"></i> Inasist.</span>';
        } else if (estadoLower === 'salida temprana') {
            badgeHtml = '<span class="badge-sena badge-late py-0.5 px-2" style="font-size:11px;"><i class="bi bi-arrow-left-circle-fill"></i> S. Temp.</span>';
        } else {
            badgeHtml = `<span class="badge-sena badge-pending py-0.5 px-2" style="font-size:11px;">${escapeHtml(i.estado)}</span>`;
        }

        let horaEntrada = i.entrada ? `<span class="text-success fw-bold" style="font-size:12px;"><i class="bi bi-box-arrow-in-right me-1"></i>${formatearHora(i.entrada)}</span>` : '<span class="text-secondary">--:--</span>';
        let horaSalida  = i.salida  ? `<span class="text-info fw-bold" style="font-size:12px;"><i class="bi bi-box-arrow-left me-1"></i>${formatearHora(i.salida)}</span>`  : '<span class="text-secondary">--:--</span>';

        let partesFichaItem = String(i.ficha || '').split(' - ');
        let numFichaItem = partesFichaItem[0] || i.ficha || 'N/A';
        let progFichaItem = partesFichaItem.length > 1 ? partesFichaItem.slice(1).join(' - ') : '';
        let fichaHtmlItem = `<div><strong class="text-theme-primary d-block" style="font-size:12.5px;">Ficha ${escapeHtml(numFichaItem)}</strong>${progFichaItem ? `<small class="text-secondary d-block text-truncate" style="font-size:11px;" title="${escapeHtml(progFichaItem)}">${escapeHtml(progFichaItem)}</small>` : ''}</div>`;

        let esReciente = (index === 0) ? 'fila-nueva-animada' : '';

        html += `
            <tr class="${esReciente}">
                <td style="padding: 8px 6px;">
                    <span class="badge font-monospace px-1.5 py-1 text-truncate d-inline-block mw-100" style="background: var(--nk-bg-inset); color: var(--nk-text-primary); border: 1px solid var(--nk-border-color); font-size:11.5px;" title="${escapeHtml(codigoMostrar)}">
                        <i class="bi bi-barcode text-success me-1"></i>${escapeHtml(codigoMostrar)}
                    </span>
                </td>
                <td style="padding: 8px 6px;">
                    <div class="d-flex align-items-center gap-2">
                        <img class="avatar-sm flex-shrink-0" src="https://ui-avatars.com/api/?background=random&name=${encodeURIComponent(i.aprendiz)}" alt="" style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid #39a900;">
                        <strong class="text-theme-primary text-truncate d-block" style="font-size:12.5px;" title="${escapeHtml(i.aprendiz)}">${escapeHtml(i.aprendiz)}</strong>
                    </div>
                </td>
                <td style="padding: 8px 6px;">${fichaHtmlItem}</td>
                <td style="padding: 8px 6px;">${horaEntrada}</td>
                <td style="padding: 8px 6px;">${horaSalida}</td>
                <td style="padding: 8px 6px;">${badgeHtml}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function formatearHora(horaStr) {
    if (!horaStr) return '--:--';
    const partes = horaStr.split(':');
    if (partes.length < 2) return horaStr;
    let hh = parseInt(partes[0], 10);
    let mm = partes[1];
    let ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12;
    hh = hh ? hh : 12;
    return (hh < 10 ? '0' + hh : hh) + ':' + mm + ' ' + ampm;
}

function escapeHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
<!-- Modal para Ajustar Tolerancia de Horario (Entrada / Salida) -->
<div class="modal fade" id="modalToleranciaHorario" tabindex="-1" aria-labelledby="modalToleranciaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="background: var(--nk-bg-card, #1b2a47); color: var(--nk-text-primary, #fff); border-radius: 12px;">
            <div class="modal-header border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold" id="modalToleranciaLabel">
                    <i class="bi bi-clock-history text-warning me-2"></i>Ajustar Tolerancia y Horario de Ficha
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formToleranciaHorario" method="POST" action="<?= BASE_URL ?>/index.php?page=instructor_ingresos&action=guardar_tolerancia">
                <input type="hidden" name="id_ficha" id="modalIdFicha">
                <input type="hidden" name="minutos_retardo" id="modalMinutosRetardoCalculados" value="0">
                <input type="hidden" name="minutos_salida" id="modalMinutosSalidaCalculados" value="0">
                <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($_GET['page'] ?? 'instructor_ingresos') ?>">

                <div class="modal-body p-4">
                    <div class="alert alert-dark border border-secondary border-opacity-25 mb-3" style="background: rgba(0,0,0,0.25); border-radius: 10px;">
                        <span class="badge bg-success mb-1" id="modalBadgeFicha">Ficha: ---</span>
                        <h6 class="fw-bold mb-1 text-white" id="modalProgramaFicha">Programa de Formación</h6>
                        <small class="text-info d-block" id="modalHorarioFicha">
                            <i class="bi bi-clock me-1"></i> Horario oficial: --:-- AM - --:-- PM
                        </small>
                    </div>

                    <!-- 1. TOLERANCIA DE ENTRADA -->
                    <div class="mb-4">
                        <label class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-in-right text-success me-1"></i> Tolerancia de Entrada (Permiso de Retardo):
                        </label>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Horas de tolerancia:</label>
                                <div class="input-group">
                                    <input type="number" id="inputHorasRetardo" min="0" max="12" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutos()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">horas</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Minutos adicionales:</label>
                                <div class="input-group">
                                    <inpu1t type="number" id="inputMinutosRetardoInput" min="0" max="59" class="form-control text-white fw-bold bg-dark border-secondary" value="15" oninput="calcularDesdeHorasMinutos()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">min</span>
                                </div>
                            </div>
                        </div>

                        <!-- Selección directa de hora límite -->
                        <div class="mb-2">
                            <label class="form-label text-secondary small mb-1"><i class="bi bi-clock-fill text-warning me-1"></i> O seleccione la Hora Máxima de Entrada A Tiempo:</label>
                            <input type="time" id="inputHoraLimiteEntrada" class="form-control text-white fw-bold bg-dark border-secondary" onchange="calcularDesdeHoraLimiteEntrada()">
                        </div>

                        <div class="form-text text-secondary mt-1 p-2.5 rounded" id="previewEntradaText" style="background: rgba(57,169,0,0.12); border-left: 3px solid #39a900; font-size:12.5px;">
                            Cargando cálculo...
                        </div>
                    </div>

                    <!-- 2. TOLERANCIA DE SALIDA -->
                    <div class="mb-3">
                        <label class="form-label text-theme-primary fw-semibold" style="font-size: 14px;">
                            <i class="bi bi-box-arrow-left text-info me-1"></i> Tolerancia de Salida (Anticipación Permitida):
                        </label>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Horas de anticipación:</label>
                                <div class="input-group">
                                    <input type="number" id="inputHorasSalida" min="0" max="12" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutosSalida()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">horas</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-secondary small mb-1">Minutos de anticipación:</label>
                                <div class="input-group">
                                    <input type="number" id="inputMinutosSalidaInput" min="0" max="59" class="form-control text-white fw-bold bg-dark border-secondary" value="0" oninput="calcularDesdeHorasMinutosSalida()">
                                    <span class="input-group-text bg-dark border-secondary text-secondary">min</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-text text-secondary mt-1 p-2.5 rounded" id="previewSalidaText" style="background: rgba(36,181,202,0.12); border-left: 3px solid #24b5ca; font-size:12.5px;">
                            Cargando cálculo...
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green fw-bold">
                        <i class="bi bi-check-circle me-1"></i> Guardar Tolerancia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rawHoraEntradaGlobal = '07:00:00';
let rawHoraSalidaGlobal  = '13:00:00';

function abrirModalTolerancia(idFicha, numeroFicha, programa, entradaStr, salidaStr, rawEntradaTime, rawSalidaTime, totalMinutosRetardo, totalMinutosSalida) {
    document.getElementById('modalIdFicha').value = idFicha;
    document.getElementById('modalBadgeFicha').textContent = 'Ficha: ' + numeroFicha;
    document.getElementById('modalProgramaFicha').textContent = programa;
    document.getElementById('modalHorarioFicha').innerHTML = '<i class="bi bi-clock me-1"></i> Horario oficial: ' + entradaStr + ' - ' + salidaStr;
    
    rawHoraEntradaGlobal = rawEntradaTime || '07:00:00';
    rawHoraSalidaGlobal  = rawSalidaTime  || '13:00:00';

    // Desglosar minutos de entrada en Horas y Minutos
    const retMins = parseInt(totalMinutosRetardo, 10) || 0;
    const hRet = Math.floor(retMins / 60);
    const mRet = retMins % 60;

    document.getElementById('inputHorasRetardo').value = hRet;
    document.getElementById('inputMinutosRetardoInput').value = mRet;

    // Desglosar minutos de salida en Horas y Minutos
    const salMins = parseInt(totalMinutosSalida, 10) || 0;
    const hSal = Math.floor(salMins / 60);
    const mSal = salMins % 60;

    document.getElementById('inputHorasSalida').value = hSal;
    document.getElementById('inputMinutosSalidaInput').value = mSal;

    calcularDesdeHorasMinutos();
    calcularDesdeHorasMinutosSalida();

    const modal = new bootstrap.Modal(document.getElementById('modalToleranciaHorario'));
    modal.show();
}

function calcularDesdeHorasMinutos() {
    const h = parseInt(document.getElementById('inputHorasRetardo').value, 10) || 0;
    const m = parseInt(document.getElementById('inputMinutosRetardoInput').value, 10) || 0;
    const totalMinutes = (h * 60) + m;

    document.getElementById('modalMinutosRetardoCalculados').value = totalMinutes;

    // Calcular hora límite
    const [hh, mm] = rawHoraEntradaGlobal.split(':').map(n => parseInt(n, 10));
    const fechaRef = new Date();
    fechaRef.setHours(hh, mm, 0, 0);
    fechaRef.setMinutes(fechaRef.getMinutes() + totalMinutes);

    // Formatear HH:MM para el input time
    const inputTimeStr = String(fechaRef.getHours()).padStart(2, '0') + ':' + String(fechaRef.getMinutes()).padStart(2, '0');
    document.getElementById('inputHoraLimiteEntrada').value = inputTimeStr;

    // Formatear 12h AM/PM
    const hora12 = formatTime12(fechaRef.getHours(), fechaRef.getMinutes());

    const preview = document.getElementById('previewEntradaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Entrada Oficial: <strong>${formatTime12(hh, mm)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices que ingresen hasta las <strong>${hora12}</strong> contarán como <span class="badge bg-success">A Tiempo</span>. Después de las <strong>${hora12}</strong> se registrará <span class="badge bg-warning text-dark">Retardo</span>.`;
    }
}

function calcularDesdeHoraLimiteEntrada() {
    const timeVal = document.getElementById('inputHoraLimiteEntrada').value;
    if (!timeVal) return;

    const [hhLim, mmLim] = timeVal.split(':').map(n => parseInt(n, 10));
    const [hhOfi, mmOfi] = rawHoraEntradaGlobal.split(':').map(n => parseInt(n, 10));

    const dateOfi = new Date();
    dateOfi.setHours(hhOfi, mmOfi, 0, 0);

    const dateLim = new Date();
    dateLim.setHours(hhLim, mmLim, 0, 0);
    if (dateLim < dateOfi) {
        dateLim.setDate(dateLim.getDate() + 1);
    }

    const diffMs = dateLim - dateOfi;
    const diffMins = Math.max(0, Math.floor(diffMs / 60000));

    const h = Math.floor(diffMins / 60);
    const m = diffMins % 60;

    document.getElementById('inputHorasRetardo').value = h;
    document.getElementById('inputMinutosRetardoInput').value = m;
    document.getElementById('modalMinutosRetardoCalculados').value = diffMins;

    const hora12 = formatTime12(hhLim, mmLim);
    const preview = document.getElementById('previewEntradaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Entrada Oficial: <strong>${formatTime12(hhOfi, mmOfi)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices que ingresen hasta las <strong>${hora12}</strong> contarán como <span class="badge bg-success">A Tiempo</span>. Después de las <strong>${hora12}</strong> se registrará <span class="badge bg-warning text-dark">Retardo</span>.`;
    }
}

function calcularDesdeHorasMinutosSalida() {
    const h = parseInt(document.getElementById('inputHorasSalida').value, 10) || 0;
    const m = parseInt(document.getElementById('inputMinutosSalidaInput').value, 10) || 0;
    const totalMinutes = (h * 60) + m;

    document.getElementById('modalMinutosSalidaCalculados').value = totalMinutes;

    const [hh, mm] = rawHoraSalidaGlobal.split(':').map(n => parseInt(n, 10));
    const fechaRef = new Date();
    fechaRef.setHours(hh, mm, 0, 0);
    fechaRef.setMinutes(fechaRef.getMinutes() - totalMinutes);

    const hora12 = formatTime12(fechaRef.getHours(), fechaRef.getMinutes());

    const preview = document.getElementById('previewSalidaText');
    if (preview) {
        let txtTiempo = (h > 0) ? `${h}h ${m}m` : `${m} min`;
        preview.innerHTML = `Salida Oficial: <strong>${formatTime12(hh, mm)}</strong> ➔ Tolerancia de <strong>${txtTiempo}</strong>: Los aprendices podrán marcar salida a partir de las <strong>${hora12}</strong> sin registrar Salida Temprana.`;
    }
}

function formatTime12(h, m) {
    let ampm = h >= 12 ? 'PM' : 'AM';
    let h12 = h % 12;
    h12 = h12 ? h12 : 12;
    let mStr = m < 10 ? '0' + m : m;
    let hStr = h12 < 10 ? '0' + h12 : h12;
    return `${hStr}:${mStr} ${ampm}`;
}
</script>

<?php require __DIR__ . '/../layouts/footer-instructor.php'; ?>
