<?php
/**
 * Vista: Mi Horario & Calendario (Aprendiz)
 * Sistema de Control de Asistencia SENA
 * 
 * Rediseño basado en Microsoft Outlook Calendar con identidad visual SENA.
 * Modo: Solo Lectura / Consulta Académica.
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=aprendiz_horario');
    exit();
}

$tituloPagina   = 'Mi Horario & Calendario';
$infoAprendiz   = $infoAprendiz ?? [];
$asistencias    = $asistencias ?? [];
$numeroFicha    = $infoAprendiz['numero_ficha'] ?? 'Sin Ficha';
$programa       = $infoAprendiz['programa'] ?? 'ADSO';
$horaEntradaRaw = $infoAprendiz['hora_entrada'] ?? '06:00:00';
$horaSalidaRaw  = $infoAprendiz['hora_salida'] ?? '12:00:00';
$horarioStr     = date('h:i A', strtotime($horaEntradaRaw)) . ' - ' . date('h:i A', strtotime($horaSalidaRaw));

require_once dirname(__DIR__) . '/layouts/header-aprendiz.php';
?>

<!-- Estilos Dedicados del Sistema de Calendario Outlook -->
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/outlook-calendar.css?v=<?= time() ?>">

<!-- Banner de Bienvenida Institucional SENA -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(0, 120, 212, 0.15); color: #0078d4;">
                <i class="bi bi-calendar-week fs-2"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="text-theme-primary fw-bold mb-0">Mi Horario de Formación</h3>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                        <i class="bi bi-shield-lock me-1"></i>Modo Consulta
                    </span>
                </div>
                <p class="text-secondary mb-0" style="font-size:14px;">Agenda semanal interactiva estilo Outlook. Consulta tus jornadas académicas y estado de ingreso.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?page=aprendiz_historial" class="btn btn-sena-outline btn-sm">
                <i class="bi bi-clock-history me-1"></i> Mi Historial de Asistencia
            </a>
        </div>
    </div>
</div>

<!-- Tarjetas KPI de Resumen Rápido -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #0078d4 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">FICHA & PROGRAMA</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= htmlspecialchars($numeroFicha) ?></h5>
                    <small class="text-primary fw-medium"><?= htmlspecialchars($programa) ?></small>
                </div>
                <i class="bi bi-mortarboard text-primary fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #39a900 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">JORNADA DE FORMACIÓN</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1">Lunes a Viernes</h5>
                    <small class="text-success fw-medium"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($horarioStr) ?></small>
                </div>
                <i class="bi bi-sun text-success fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">MARGEN DE INGRESO</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1">15 Minutos de Tolerancia</h5>
                    <small class="text-warning fw-medium"><i class="bi bi-hourglass-split me-1"></i>Registro biométrico / RFID</small>
                </div>
                <i class="bi bi-hourglass-split text-warning fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     CONTENEDOR DEL CALENDARIO ESTILO OUTLOOK
     ========================================== -->
<div class="outlook-calendar-wrapper">
    <!-- 1. Barra de Comandos / Ribbon Superior -->
    <div class="outlook-command-bar">
        <!-- Grupo de Navegación y Vistas -->
        <div class="outlook-command-group">
            <button type="button" class="outlook-btn outlook-btn-today" id="btnOutlookToday">
                <i class="bi bi-calendar-event me-1 text-primary"></i> Hoy
            </button>
            
            <div class="d-flex align-items-center">
                <button type="button" class="outlook-btn outlook-btn-nav" id="btnOutlookPrev" title="Semana Anterior">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button type="button" class="outlook-btn outlook-btn-nav" id="btnOutlookNext" title="Semana Siguiente">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>

            <!-- Título del Rango de Fechas -->
            <div class="outlook-date-title" id="outlookDateTitle">
                Cargando fechas...
            </div>
        </div>

        <!-- Grupo de Selectores de Vista estilo Outlook -->
        <div class="outlook-command-group">
            <button type="button" class="outlook-btn outlook-btn-view" data-view="day" title="Vista Diaria">
                <i class="bi bi-calendar-day"></i> Día
            </button>
            <button type="button" class="outlook-btn outlook-btn-view active" data-view="workWeek" title="Semana Laboral (Lunes a Viernes)">
                <i class="bi bi-calendar-week"></i> Semana laboral
            </button>
            <button type="button" class="outlook-btn outlook-btn-view" data-view="week" title="Semana Completa">
                <i class="bi bi-calendar-range"></i> Semana
            </button>
            <button type="button" class="outlook-btn outlook-btn-view" data-view="month" title="Vista Mensual">
                <i class="bi bi-calendar3"></i> Mes
            </button>
            <button type="button" class="outlook-btn outlook-btn-view" data-view="list" title="Agenda / Lista">
                <i class="bi bi-view-list"></i> Agenda
            </button>

            <span class="border-end border-secondary border-opacity-25 mx-1" style="height: 20px;"></span>

            <!-- Botones de Acción -->
            <button type="button" class="outlook-btn" id="btnOutlookPrint" title="Imprimir Horario">
                <i class="bi bi-printer"></i> Imprimir
            </button>
            <button type="button" class="outlook-btn" id="btnToggleRail" title="Alternar panel lateral">
                <i class="bi bi-layout-sidebar"></i>
            </button>
        </div>
    </div>

    <!-- 2. Cuerpo del Calendario (Riel Lateral + Rejilla Principal) -->
    <div class="outlook-body-layout">
        <!-- Riel Lateral (Mini Calendario + Filtros + Categorías) -->
        <div class="outlook-rail-sidebar" id="outlookRailSidebar">
            <!-- Mini Calendario Interactivo -->
            <div class="outlook-mini-calendar" id="outlookMiniCalendar"></div>

            <hr class="border-secondary border-opacity-25 my-1">

            <!-- Tarjeta Informativa de Solo Lectura -->
            <div class="p-2.5 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25 text-secondary" style="font-size: 11.5px;">
                <div class="d-flex align-items-center gap-1.5 text-primary fw-bold mb-1">
                    <i class="bi bi-info-circle-fill"></i> Modo Solo Lectura
                </div>
                <p class="mb-1 text-secondary">Tu horario está sincronizado con el centro de formación. La asistencia se toma automáticamente al presentar tu carné RFID o huella.</p>
                <div class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Horario Oficial SENA</div>
            </div>
        </div>

        <!-- Contenedor del Calendario Principal (FullCalendar con aspecto Outlook) -->
        <div class="outlook-main-grid-container">
            <div id="calendar" class="outlook-theme-fc"></div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL DE DETALLE DE SESIÓN (SOLO LECTURA)
     ========================================== -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-outlook">
            <div class="modal-header modal-header-outlook">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded bg-primary bg-opacity-25 text-primary">
                        <i class="bi bi-calendar-event fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-theme-primary" id="eventoTitulo">Detalle de Sesión</h5>
                        <small class="text-secondary" id="eventoSubtitulo">Horario de Formación Académica</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                    <span class="outlook-badge-readonly">
                        <i class="bi bi-lock-fill me-1"></i>Solo Lectura (Aprendiz)
                    </span>
                    <span class="badge bg-success" id="eventoEstadoBadge">Programada</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FECHA Y HORARIO</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoFecha"></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FICHA / PROGRAMA</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoFicha"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-bold" style="font-size: 11px;">AMBIENTE DE FORMACIÓN</small>
                    <div class="text-theme-primary mt-1" id="eventoAmbiente">Ambiente Técnico de Software / TIC</div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-bold" style="font-size: 11px;">DESCRIPCIÓN & NOVEDADES</small>
                    <p class="text-theme-primary mt-1 mb-0 p-2.5 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25" id="eventoDesc" style="font-size: 13px;"></p>
                </div>

                <div class="alert alert-info py-2 px-3 mb-0 d-flex align-items-center gap-2" style="font-size: 12px;">
                    <i class="bi bi-shield-check fs-5"></i>
                    <div>Recuerda ingresar dentro de los 15 minutos de tolerancia para registrar tu asistencia como <strong>A Tiempo</strong>.</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<!-- FullCalendar v6 CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<!-- Helper de Calendario Estilo Outlook -->
<script src="<?= BASE_URL ?>/public/assets/js/outlook-calendar.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const horaEntrada = <?= json_encode(substr($horaEntradaRaw, 0, 5)) ?>;
    const horaSalida  = <?= json_encode(substr($horaSalidaRaw, 0, 5)) ?>;
    const numFicha    = <?= json_encode($numeroFicha) ?>;
    const nomPrograma = <?= json_encode($programa) ?>;
    const asistenciasData = <?= json_encode($asistencias, JSON_UNESCAPED_UNICODE) ?>;

    let outlookHelper = null;

    /**
     * Calcula los bloques de 3 horas correspondientes a la jornada diaria.
     * Ejemplo: 06:00 a 12:00 (6 horas) -> Bloque 1 (06:00 - 09:00), Bloque 2 (09:00 - 12:00).
     */
    function calcularBloques(inicio, fin) {
        const [hIni, mIni] = inicio.split(':').map(Number);
        const [hFin, mFin] = fin.split(':').map(Number);
        const duracionMinutos = (hFin * 60 + mFin) - (hIni * 60 + mIni);

        // Si la jornada dura 5 horas o más (típicamente 6 horas = 360 min)
        if (duracionMinutos >= 300) {
            const minutosCorte = hIni * 60 + mIni + 180; // +3 horas (180 min)
            const hCorte = Math.floor(minutosCorte / 60);
            const mCorte = minutosCorte % 60;
            const horaCorte = String(hCorte).padStart(2, '0') + ':' + String(mCorte).padStart(2, '0');

            return [
                { num: 1, label: 'Bloque 1 (3 Horas)', start: inicio, end: horaCorte },
                { num: 2, label: 'Bloque 2 (3 Horas)', start: horaCorte, end: fin }
            ];
        }

        // Si la jornada es de 3 horas o personalizada
        return [
            { num: 1, label: 'Bloque Único (3 Horas)', start: inicio, end: fin }
        ];
    }

    // Indexar el historial de asistencias por fecha
    const asistenciasPorFecha = {};
    asistenciasData.forEach(function(item) {
        if (!item.fecha) return;
        if (!asistenciasPorFecha[item.fecha]) {
            asistenciasPorFecha[item.fecha] = [];
        }
        asistenciasPorFecha[item.fecha].push(item);
    });

    // Ordenar por hora_entrada o id_registro ascendente para emparejar Bloque 1 y Bloque 2
    Object.keys(asistenciasPorFecha).forEach(function(f) {
        asistenciasPorFecha[f].sort(function(a, b) {
            if (a.hora_entrada && b.hora_entrada) {
                return a.hora_entrada.localeCompare(b.hora_entrada);
            }
            return (a.id_registro || 0) - (b.id_registro || 0);
        });
    });

    // Instancia de FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        hiddenDays: [0, 6], // Semana Laboral: Lunes a Viernes por defecto como Outlook
        locale: 'es',
        headerToolbar: false, // Ocultar toolbar nativo, usamos el ribbon Outlook
        firstDay: 1, // Lunes
        slotMinTime: '06:00:00',
        slotMaxTime: '21:00:00',
        slotDuration: '00:30:00',
        slotLabelInterval: '01:00:00',
        scrollTime: '06:00:00',
        nowIndicator: true,
        allDaySlot: true,
        allDayText: 'Todo el día',
        expandRows: true,
        height: 'auto',

        // Encabezados de Columna personalizados estilo Outlook (Número grande + Nombre abajo)
        dayHeaderContent: function(arg) {
            return OutlookCalendarHelper.renderHeaderCell(arg);
        },

        // Renderizador de Tarjetas de Eventos estilo Outlook
        eventContent: function(eventInfo) {
            return OutlookCalendarHelper.renderEventContent(eventInfo);
        },

        // Eventos de la Ficha del Aprendiz divididos en Bloques Individuales de 3 Horas
        events: function(fetchInfo, successCallback, failureCallback) {
            const events = [];
            const currentDate = new Date(fetchInfo.start);
            const endDate = new Date(fetchInfo.end);
            const hoy = new Date();
            const hoyStr = hoy.toISOString().split('T')[0];
            const bloquesJornada = calcularBloques(horaEntrada, horaSalida);

            while (currentDate <= endDate) {
                const day = currentDate.getDay(); // 1: Lun a 5: Vie
                if (day >= 1 && day <= 5) {
                    const dateStr = currentDate.toISOString().split('T')[0];
                    const asistenciasDia = asistenciasPorFecha[dateStr] || [];

                    bloquesJornada.forEach(function(bloque, index) {
                        // Buscar el registro de asistencia correspondiente a este bloque
                        let registro = null;
                        if (asistenciasDia.length > 0) {
                            if (asistenciasDia.length >= 2) {
                                // Mapeo exacto por posición del bloque (0 -> Bloque 1, 1 -> Bloque 2)
                                registro = asistenciasDia[index] || null;
                            } else {
                                const unico = asistenciasDia[0];
                                // Si solo hay 1 registro en el día, verificar a qué bloque se aproxima
                                if (index === 0) {
                                    if (!unico.hora_salida || unico.hora_salida <= bloque.end || !unico.hora_entrada || unico.hora_entrada < '09:00:00') {
                                        registro = unico;
                                    }
                                } else if (index === 1) {
                                    if (unico.hora_entrada && unico.hora_entrada >= '08:45:00') {
                                        registro = unico;
                                    }
                                }
                            }
                        }

                        // Configuración visual por defecto (Programada / Futura)
                        let titulo = bloque.label + ': ' + nomPrograma;
                        let estadoBadge = 'Programada';
                        let color = '#0078d4'; // Azul Outlook
                        let descripcion = 'Jornada técnica presencial de ' + nomPrograma + ' correspondiente al ' + bloque.label + ' (' + bloque.start + ' - ' + bloque.end + ').';
                        let horaMarcada = null;

                        if (registro) {
                            horaMarcada = registro.hora_entrada ? registro.hora_entrada.substring(0, 5) : null;
                            const estRaw = (registro.estado || '').toLowerCase();
                            const estExc = (registro.estado_excusa || '').toLowerCase();

                            if (estExc === 'aprobado' || estRaw === 'justificada') {
                                estadoBadge = 'Justificada';
                                color = '#9b59b6'; // Púrpura SENA
                                titulo = bloque.label + ': Justificada';
                                descripcion = 'Inasistencia formalmente justificada con soporte médico validado por el instructor.';
                            } else if (estRaw === 'asistio') {
                                estadoBadge = 'Asistió (A Tiempo)';
                                color = '#39a900'; // Verde SENA
                                titulo = bloque.label + ': Asistió';
                                descripcion = 'Asistencia registrada con éxito en el bloque' + (horaMarcada ? ' a las ' + horaMarcada : '') + '.';
                            } else if (estRaw === 'retardo') {
                                const minRet = registro.minutos_retardo || 15;
                                estadoBadge = 'Retardo (' + minRet + ' min)';
                                color = '#f39c12'; // Ámbar / Naranja
                                titulo = bloque.label + ': Retardo';
                                descripcion = 'Ingreso registrado con ' + minRet + ' minutos de retraso fuera de la tolerancia.';
                            } else if (estRaw === 'inasistencia') {
                                estadoBadge = 'Inasistencia';
                                color = '#eb4b4b'; // Rojo
                                titulo = bloque.label + ': Inasistencia';
                                descripcion = 'Inasistencia registrada en este bloque individual de 3 horas. Recuerda que puedes radicar tu excusa médica si tienes soporte.';
                            }
                        } else if (dateStr < hoyStr) {
                            // Día anterior sin registro biométrico
                            estadoBadge = 'Sin Registro';
                            color = '#6c757d'; // Gris
                            titulo = bloque.label + ': Sin Registro';
                            descripcion = 'Sesión pasada en la que no se detectó marcación biométrica o RFID.';
                        }

                        events.push({
                            id: 'bloque_' + dateStr + '_' + bloque.num,
                            title: titulo,
                            start: dateStr + 'T' + bloque.start + ':00',
                            end: dateStr + 'T' + bloque.end + ':00',
                            backgroundColor: color,
                            borderColor: color,
                            textColor: '#ffffff',
                            extendedProps: {
                                categoria: 'formacion',
                                bloqueNum: bloque.num,
                                bloqueLabel: bloque.label,
                                fechaRaw: dateStr,
                                ficha: numFicha,
                                programa: nomPrograma,
                                horarioBloque: bloque.start + ' - ' + bloque.end,
                                ambiente: 'Ambiente TIC 1 - Sede Comercio',
                                estado: estadoBadge,
                                horaMarcada: horaMarcada,
                                description: descripcion
                            }
                        });
                    });
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }

            successCallback(events);
        },

        // Clic en evento: Abre el Modal de Solo Lectura estilo Outlook
        eventClick: function(info) {
            document.getElementById('eventoTitulo').innerText = info.event.title;
            
            const startFormat = info.event.start.toLocaleString('es-CO', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
            const endFormat = info.event.end ? ' - ' + info.event.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
            
            const bloqueExtra = info.event.extendedProps.bloqueLabel ? ' [' + info.event.extendedProps.bloqueLabel + ']' : '';
            document.getElementById('eventoFecha').innerText = startFormat + endFormat + bloqueExtra;
            document.getElementById('eventoFicha').innerText = (info.event.extendedProps.ficha || numFicha) + ' (' + (info.event.extendedProps.programa || nomPrograma) + ')';
            document.getElementById('eventoAmbiente').innerText = info.event.extendedProps.ambiente || 'Ambiente TIC 1';
            document.getElementById('eventoDesc').innerText = info.event.extendedProps.description || 'Sin notas adicionales.';
            
            const badgeEl = document.getElementById('eventoEstadoBadge');
            if (badgeEl) {
                badgeEl.innerText = info.event.extendedProps.estado || 'Programada';
                badgeEl.style.backgroundColor = info.event.backgroundColor || '#0078d4';
            }

            const modalEl = document.getElementById('modalEvento');
            if (modalEl) {
                const myModal = new bootstrap.Modal(modalEl);
                myModal.show();
            }
        },

        // Al cambiar de fecha o vista, sincronizar con el helper de Outlook
        datesSet: function(info) {
            if (outlookHelper) {
                outlookHelper.syncFromMainCalendar(info.view);
            }
        }
    });

    calendar.render();

    // Inicializar Helper de Outlook (Mini calendario, barra de títulos y vistas)
    outlookHelper = new OutlookCalendarHelper({
        mainCalendar: calendar,
        miniCalendarEl: document.getElementById('outlookMiniCalendar'),
        titleEl: document.getElementById('outlookDateTitle'),
        railSidebarEl: document.getElementById('outlookRailSidebar')
    });

    // Enlazar botones de navegación de la barra Outlook
    document.getElementById('btnOutlookToday')?.addEventListener('click', () => {
        calendar.today();
    });

    document.getElementById('btnOutlookPrev')?.addEventListener('click', () => {
        calendar.prev();
    });

    document.getElementById('btnOutlookNext')?.addEventListener('click', () => {
        calendar.next();
    });

    // Botones de cambio de vista estilo Outlook
    const viewButtons = document.querySelectorAll('.outlook-btn-view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const vista = this.getAttribute('data-view');
            outlookHelper.cambiarVista(vista);
        });
    });

    // Botón de alternancia de riel lateral
    document.getElementById('btnToggleRail')?.addEventListener('click', () => {
        outlookHelper.toggleRail();
    });

    // Botón de Impresión
    document.getElementById('btnOutlookPrint')?.addEventListener('click', () => {
        window.print();
    });


});
</script>
<?php
$scriptsExtra = ob_get_clean();

require_once dirname(__DIR__) . '/layouts/footer-aprendiz.php';
?>
