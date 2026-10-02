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
$bloques        = $bloques ?? [];
$asistencias    = $asistencias ?? [];
$numeroFicha    = $infoAprendiz['numero_ficha'] ?? 'Sin Ficha';
$programa       = $infoAprendiz['programa'] ?? 'ADSO';
$totalBloques   = count($bloques);

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
                        <i class="bi bi-shield-lock me-1"></i><?= $totalBloques ?> Bloque(s) Semanales
                    </span>
                </div>
                <p class="text-secondary mb-0" style="font-size:14px;">Agenda semanal interactiva estilo Outlook. Consulta tus bloques de formación de 3h y 6h, ambientes y asistencias.</p>
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
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">BLOQUES PROGRAMADOS</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= $totalBloques ?> Bloque(s) Activo(s)</h5>
                    <small class="text-success fw-medium"><i class="bi bi-clock me-1"></i>Bloques de 3h y 6h continuas</small>
                </div>
                <i class="bi bi-calendar-check text-success fs-1 opacity-50"></i>
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
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                    <span class="outlook-badge-readonly">
                        <i class="bi bi-lock-fill me-1"></i>Solo Lectura (Aprendiz)
                    </span>
                    <span class="badge bg-success" id="eventoEstadoBadge">Programada</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6 border-end border-secondary border-opacity-25">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FECHA Y HORARIO</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoFecha"></div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FICHA / PROGRAMA</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoFicha"></div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6 border-end border-secondary border-opacity-25">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">AMBIENTE DE FORMACIÓN</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoAmbiente">
                            <i class="bi bi-geo-alt-fill text-warning me-1"></i><span id="eventoAmbienteTexto">Ambiente TIC</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">INSTRUCTOR A CARGO</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoInstructor">
                            <i class="bi bi-person-video3 text-info me-1"></i><span id="eventoInstructorTexto">Instructor Asignado</span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-bold" style="font-size: 11px;">COMPETENCIA TÉCNICA / DETALLE</small>
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

    const bloquesData     = <?= json_encode($bloques) ?>;
    const numFicha        = <?= json_encode($numeroFicha) ?>;
    const nomPrograma     = <?= json_encode($programa) ?>;
    const asistenciasData = <?= json_encode($asistencias, JSON_UNESCAPED_UNICODE) ?>;

    let outlookHelper = null;

    /**
     * Mapeo de nombres de días en español a índices de Date/FullCalendar (0: Dom, 1: Lun, ..., 6: Sáb)
     */
    const diasMapa = {
        'domingo': 0,
        'lunes': 1,
        'martes': 2,
        'miercoles': 3,
        'miércoles': 3,
        'jueves': 4,
        'viernes': 5,
        'sabado': 6,
        'sábado': 6
    };

    /**
     * Formatea un objeto Date en cadena YYYY-MM-DD local segura (sin desfase UTC)
     */
    function formatFechaLocal(d) {
        if (!d) return '';
        const dateObj = (d instanceof Date) ? d : new Date(d);
        if (isNaN(dateObj.getTime())) return '';
        const y = dateObj.getFullYear();
        const m = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }

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

        // Eventos reales de los bloques asignados a la ficha del aprendiz
        events: function(fetchInfo, successCallback, failureCallback) {
            const events = [];
            const startDate = new Date(fetchInfo.start);
            const endDate = new Date(fetchInfo.end);
            const hoyStr = formatFechaLocal(new Date());

            bloquesData.forEach(function(bloque) {
                const diaNombre = (bloque.dias_semana || '').trim().toLowerCase();
                const targetDay = diasMapa[diaNombre];
                if (targetDay === undefined) return;

                const horaIni = (bloque.raw_hora_entrada || '06:00').substring(0, 5);
                const horaFin = (bloque.raw_hora_salida || '12:00').substring(0, 5);
                const duracion = parseInt(bloque.duracion_horas, 10) || 3;
                const es6Horas = (duracion >= 6 || bloque.tipo_bloque === '6H_CONTINUO');
                const tipoLabel = es6Horas ? 'Jornada Continua (6h)' : 'Bloque ' + duracion + 'h';

                // Iterar las fechas del rango visible y pintar únicamente en el día de la semana correspondiente
                const curr = new Date(startDate);
                while (curr <= endDate) {
                    if (curr.getDay() === targetDay) {
                        const dateStr = formatFechaLocal(curr);

                        // Buscar si existe registro de asistencia para este bloque específico
                        let registro = asistenciasData.find(function(a) {
                            if (a.fecha !== dateStr) return false;
                            if (a.horarios_id_horario && parseInt(a.horarios_id_horario, 10) === parseInt(bloque.id_horario, 10)) {
                                return true;
                            }
                            return false;
                        });

                        // Fallback por proximidad horaria si horarios_id_horario no estuviera fijado
                        if (!registro) {
                            registro = asistenciasData.find(function(a) {
                                if (a.fecha !== dateStr) return false;
                                if (a.hora_entrada) {
                                    return (a.hora_entrada >= horaIni && a.hora_entrada <= horaFin) ||
                                           (a.hora_entrada >= '05:30:00' && horaIni <= '07:00');
                                }
                                return false;
                            });
                        }

                        // Configuración visual por defecto (Programada / Futura)
                        let estadoBadge = 'Programada';
                        let color = '#0078d4'; // Azul Outlook
                        let titulo = tipoLabel + ': ' + (bloque.ambiente ? bloque.ambiente : nomPrograma);
                        let descripcion = 'Jornada de formación técnica (' + tipoLabel + '). Ambiente: ' + (bloque.ambiente || 'Por asignar') + '. Competencia: ' + (bloque.competencia || 'General') + '.';
                        let horaMarcada = null;

                        if (registro) {
                            horaMarcada = registro.hora_entrada ? registro.hora_entrada.substring(0, 5) : null;
                            const estRaw = (registro.estado || '').toLowerCase();
                            const estExc = (registro.estado_excusa || '').toLowerCase();

                            if (estExc === 'aprobado' || estRaw === 'justificada') {
                                estadoBadge = 'Justificada';
                                color = '#9b59b6'; // Púrpura SENA
                                titulo = tipoLabel + ': Justificada';
                                descripcion = 'Inasistencia formalmente justificada con soporte médico validado.';
                            } else if (estRaw === 'asistio') {
                                estadoBadge = 'Asistió (A Tiempo)';
                                color = '#39a900'; // Verde SENA
                                titulo = tipoLabel + ': Asistió';
                                descripcion = 'Asistencia registrada con éxito' + (horaMarcada ? ' a las ' + horaMarcada : '') + ' en ' + (bloque.ambiente || 'ambiente asignado') + '.';
                            } else if (estRaw === 'retardo') {
                                const minRet = registro.minutos_retardo || 15;
                                estadoBadge = 'Retardo (' + minRet + ' min)';
                                color = '#f39c12'; // Ámbar / Naranja
                                titulo = tipoLabel + ': Retardo (' + minRet + 'm)';
                                descripcion = 'Ingreso registrado con retardo (' + minRet + ' min) a las ' + (horaMarcada || '') + '.';
                            } else if (estRaw === 'inasistencia') {
                                estadoBadge = 'Inasistencia';
                                color = '#eb4b4b'; // Rojo
                                titulo = tipoLabel + ': Inasistencia';
                                descripcion = 'Inasistencia registrada en este bloque. Recuerda que puedes radicar tu excusa médica si cuentas con soporte.';
                            }
                        } else if (dateStr < hoyStr) {
                            // Día anterior sin registro biométrico
                            estadoBadge = 'Sin Registro';
                            color = '#6c757d'; // Gris
                            titulo = tipoLabel + ': Sin Registro';
                            descripcion = 'Sesión pasada en la que no se detectó marcación de asistencia.';
                        }

                        events.push({
                            id: 'bloque_' + bloque.id_horario + '_' + dateStr,
                            title: titulo,
                            start: dateStr + 'T' + horaIni + ':00',
                            end: dateStr + 'T' + horaFin + ':00',
                            backgroundColor: color,
                            borderColor: color,
                            textColor: '#ffffff',
                            extendedProps: {
                                id_horario: bloque.id_horario,
                                categoria: 'formacion',
                                bloqueLabel: tipoLabel,
                                fechaRaw: dateStr,
                                ficha: bloque.numero_ficha || numFicha,
                                programa: bloque.programa || nomPrograma,
                                horarioBloque: horaIni + ' - ' + horaFin,
                                ambiente: bloque.ambiente || 'Ambiente por asignar',
                                instructor: bloque.instructor_nombre || 'Instructor asignado',
                                competencia: bloque.competencia || 'Sin competencia asignada',
                                estado: estadoBadge,
                                horaMarcada: horaMarcada,
                                description: descripcion
                            }
                        });
                    }
                    curr.setDate(curr.getDate() + 1);
                }
            });

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
            document.getElementById('eventoAmbienteTexto').innerText = info.event.extendedProps.ambiente || 'Ambiente por asignar';
            document.getElementById('eventoInstructorTexto').innerText = info.event.extendedProps.instructor || 'Instructor asignado';
            document.getElementById('eventoDesc').innerText = (info.event.extendedProps.competencia ? 'Competencia: ' + info.event.extendedProps.competencia + '. ' : '') + (info.event.extendedProps.description || 'Sin notas adicionales.');
            
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
