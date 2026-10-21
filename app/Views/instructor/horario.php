<?php
/**
 * Vista de Presentación: Mi Horario & Calendario (Instructor)
 * Sistema de Control de Asistencia SENA
 * 
 * Rediseño basado en Microsoft Outlook Calendar con identidad visual SENA.
 * Modo: Gestión & Observaciones (Editable / Registro de Novedades de Clase).
 * 
 * @var string $tituloPagina
 * @var array  $fichas
 * @var array  $novedades
 * @var array  $notas
 * @var string|null $filtroFicha
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=instructor_horario');
    exit();
}

$tituloPagina = $tituloPagina ?? 'Mi Horario & Calendario';
$fichas       = $fichas ?? [];
$bloques      = $bloques ?? [];
$novedades    = $novedades ?? [];
$filtroFicha  = $filtroFicha ?? '';

// Calcular estadísticas rápidas
$totalFichas     = count($fichas);
$totalAprendices = array_sum(array_column($fichas, 'total_aprendices'));
$totalBloques    = count($bloques);

require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Estilos Dedicados del Sistema de Calendario Outlook -->
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/outlook-calendar.css?v=<?= time() ?>">

<!-- Banner de Bienvenida Institucional SENA -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(57, 169, 0, 0.15); color: #39a900;">
                <i class="bi bi-calendar-check fs-2"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="text-theme-primary fw-bold mb-0">Mi Horario & Agenda de Formación</h3>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2.5 py-1 rounded-pill" style="font-size: 11px;">
                        <i class="bi bi-clock-history me-1"></i><?= $totalBloques ?> Bloque(s) Semanales
                    </span>
                </div>
                <p class="text-secondary mb-0" style="font-size:14px;">Agenda semanal estilo Outlook. Supervisa tus bloques de 3h y 6h, ambientes asignados y asistencia.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green btn-sm">
                <i class="bi bi-door-open me-1"></i> Control de Ingresos
            </a>
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

        <!-- Grupo de Acciones y Vistas -->
        <div class="outlook-command-group">
            <!-- Selector de Vistas estilo Outlook -->
            <button type="button" class="outlook-btn outlook-btn-view" data-view="day" title="Vista Diaria">
                <i class="bi bi-calendar-day"></i> Día
            </button>
            <button type="button" class="outlook-btn outlook-btn-view active" data-view="workWeek" title="Semana de Formación (Lunes a Sábado)">
                <i class="bi bi-calendar-week"></i> Semana formativa
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

            <!-- Filtro de Fichas estilo Outlook Filter -->
            <div class="dropdown">
                <button class="outlook-btn dropdown-toggle" type="button" id="dropdownFiltroFichas" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-funnel"></i> <span id="labelFichaFiltro">Todas las Fichas</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark p-2 shadow-lg" aria-labelledby="dropdownFiltroFichas" style="min-width: 240px; background-color: var(--outlook-card-bg); border-color: var(--outlook-border);">
                    <li>
                        <button class="dropdown-item py-1.5 px-2 rounded item-filtro-ficha active" data-ficha="">
                            <i class="bi bi-grid-fill me-2 text-primary"></i> Todas las Fichas (<?= $totalFichas ?>)
                        </button>
                    </li>
                    <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>
                    <?php foreach ($fichas as $f): ?>
                        <li>
                            <button class="dropdown-item py-1.5 px-2 rounded item-filtro-ficha" data-ficha="<?= htmlspecialchars($f['numero_ficha']) ?>">
                                <span class="badge bg-success me-1.5"><?= htmlspecialchars($f['numero_ficha']) ?></span>
                                <small class="text-truncate d-inline-block" style="max-width: 140px;"><?= htmlspecialchars($f['programa']) ?></small>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

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
        <!-- Riel Lateral (Mini Calendario + Fichas Asignadas + Leyenda) -->
        <div class="outlook-rail-sidebar" id="outlookRailSidebar">
            <!-- Mini Calendario Interactivo -->
            <div class="outlook-mini-calendar" id="outlookMiniCalendar"></div>

            <hr class="border-secondary border-opacity-25 my-1">

            <!-- Indicador Modo Bloques -->
            <div class="p-2.5 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25 text-secondary" style="font-size: 11.5px;">
                <div class="d-flex align-items-center gap-1.5 text-success fw-bold mb-1">
                    <i class="bi bi-calendar-week"></i> Bloques de Formación
                </div>
                <p class="mb-1 text-secondary">Visualiza tus bloques activos de 3h y 6h asignados por ficha, ambiente y competencia técnica.</p>

            </div>
        </div>

        <!-- Contenedor del Calendario Principal (FullCalendar estilo Outlook) -->
        <div class="outlook-main-grid-container">
            <div id="calendar" class="outlook-theme-fc"></div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL DE DETALLE DE SESIÓN DE FORMACIÓN (INSTRUCTOR)
     ========================================== -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-outlook">
            <div class="modal-header modal-header-outlook">
                <div class="d-flex align-items-center gap-2">
                    <div class="p-2 rounded bg-success bg-opacity-25 text-success">
                        <i class="bi bi-calendar2-check fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-theme-primary" id="eventoTitulo">Detalle de Formación</h5>
                        <small class="text-secondary" id="eventoSubtitulo">Bloque Académico Programado</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill" id="eventoTipoBadge">
                        <i class="bi bi-clock me-1"></i>Bloque de Formación
                    </span>
                    <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" id="btnModalIngresos" class="btn btn-sena-green btn-sm py-1 px-3">
                        <i class="bi bi-door-open me-1"></i> Pasar Asistencia en Ingresos
                    </a>
                </div>

                <!-- Ficha y Horario de la sesión -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 border-end border-secondary border-opacity-25">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FECHA Y HORARIO</small>
                        <strong class="text-theme-primary fs-6 mt-1 d-block" id="eventoFecha"></strong>
                    </div>
                    <div class="col-sm-6" id="eventoFichaContainer">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FICHA / PROGRAMA</small>
                        <span class="badge bg-success text-wrap text-start mt-1 p-2" id="eventoFichaBadge"></span>
                    </div>
                </div>

                <!-- Ambiente y Tolerancias -->
                <div class="row g-3 mb-3">
                    <div class="col-sm-6 border-end border-secondary border-opacity-25">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">AMBIENTE DE FORMACIÓN</small>
                        <div class="text-theme-primary fw-semibold mt-1" id="eventoAmbiente">
                            <i class="bi bi-geo-alt-fill text-warning me-1"></i><span id="eventoAmbienteTexto">Ambiente por asignar</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">TOLERANCIAS DE ASISTENCIA</small>
                        <div class="text-theme-primary mt-1" id="eventoTolerancias" style="font-size: 13px;">
                            <span class="badge bg-success bg-opacity-25 text-success me-1"><i class="bi bi-shield-check me-1"></i>Retardo: <span id="eventoTolRetardo">15 min</span></span>
                            <span class="badge bg-secondary bg-opacity-25 text-secondary"><i class="bi bi-door-closed me-1"></i>Salida: <span id="eventoTolSalida">0 min</span></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-bold" style="font-size: 11px;">COMPETENCIA TÉCNICA / DETALLE</small>
                    <p class="text-theme-primary mt-1 mb-0 p-2.5 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25" id="eventoDesc" style="font-size: 13px;"></p>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between pt-0">
                <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" id="btnModalIngresosFooter" class="btn btn-sena-green btn-sm">
                    <i class="bi bi-door-open me-1"></i> Control de Ingresos
                </a>
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

    const fichasData      = <?= json_encode($fichas) ?>;
    const bloquesData     = <?= json_encode($bloques) ?>;
    let novedadesData     = <?= json_encode($novedades) ?>;
    let fichaFiltroActiva = <?= json_encode($filtroFicha) ?>;

    let outlookHelper = null;

    /**
     * Mapeo de nombres de días en español a índices de FullCalendar/Date (0: Dom, 1: Lun, ..., 6: Sáb)
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

    // Paleta de colores atractiva estilo Outlook/SENA para diferenciar fichas
    const coloresFichas = [
        '#39a900', // Verde SENA
        '#0078d4', // Azul Outlook
        '#9b59b6', // Púrpura
        '#f39c12', // Naranja Ámbar
        '#e74c3c', // Carmesí
        '#20c997'  // Verde Agua
    ];

    const mapaColores = {};
    fichasData.forEach(function(f, index) {
        mapaColores[f.numero_ficha] = coloresFichas[index % coloresFichas.length];
    });

    /**
     * Generador de eventos de formación reales y novedades
     */
    function obtenerEventos(fetchInfo) {
        const events = [];
        const startDate = new Date(fetchInfo.start);
        const endDate = new Date(fetchInfo.end);

        // 1. Bloques reales de formación registrados en la base de datos
        bloquesData.forEach(function(bloque) {
            // Filtrar si hay ficha específica seleccionada en el menú superior
            if (fichaFiltroActiva && String(bloque.numero_ficha) !== String(fichaFiltroActiva)) {
                return;
            }

            const diaNombre = (bloque.dias_semana || '').trim().toLowerCase();
            const targetDay = diasMapa[diaNombre];
            if (targetDay === undefined) return;

            const colorFicha = mapaColores[bloque.numero_ficha] || '#39a900';
            const horaIni = (bloque.raw_hora_entrada || '06:00').substring(0, 5);
            const horaFin = (bloque.raw_hora_salida || '12:00').substring(0, 5);
            const duracion = parseInt(bloque.duracion_horas, 10) || 3;
            let tipoLabel = 'Bloque ' + duracion + 'h';
            if (duracion >= 6 || bloque.tipo_bloque === '6H_CONTINUO') {
                tipoLabel = 'Jornada Continua (6h)';
            } else if (duracion === 4 || bloque.tipo_bloque === '4H_NOCTURNA') {
                tipoLabel = 'Jornada Nocturna (4h)';
            }

            function crearEventoInstructor(dateStr) {
                const eventTitle = bloque.programa + (bloque.ambiente ? ' · ' + bloque.ambiente : '');

                events.push({
                    id: 'bloque_' + bloque.id_horario + '_' + dateStr,
                    title: eventTitle,
                    start: dateStr + 'T' + horaIni + ':00',
                    end: dateStr + 'T' + horaFin + ':00',
                    backgroundColor: colorFicha,
                    borderColor: colorFicha,
                    textColor: '#ffffff',
                    extendedProps: {
                        id_horario: bloque.id_horario,
                        categoria: 'formacion',
                        bloqueLabel: tipoLabel,
                        fechaRaw: dateStr,
                        ficha: bloque.numero_ficha,
                        programa: bloque.programa,
                        ambiente: bloque.ambiente || 'Ambiente por asignar',
                        competencia: bloque.competencia || 'Sin competencia asignada',
                        duracion_horas: duracion,
                        tipo_bloque: bloque.tipo_bloque,
                        jornada: horaIni + ' - ' + horaFin,
                        toleranciaEntrada: (bloque.minutos_retardo ?? 15),
                        toleranciaSalida: (bloque.minutos_salida ?? 0),
                        aprendices: bloque.total_aprendices || 0,
                        description: 'Sesión de formación programada para la ficha ' + bloque.numero_ficha + ' (' + bloque.programa + '). Ambiente: ' + (bloque.ambiente || 'Por asignar') + '. Competencia: ' + (bloque.competencia || 'General') + '. Total aprendices: ' + (bloque.total_aprendices || 0) + '.'
                    }
                });
            }

            if (bloque.fecha) {
                const fStr = String(bloque.fecha).substring(0, 10);
                if (fStr >= formatFechaLocal(startDate) && fStr <= formatFechaLocal(endDate)) {
                    crearEventoInstructor(fStr);
                }
            } else {
                // Fallback para horarios genéricos sin fecha asignada
                const curr = new Date(startDate);
                while (curr <= endDate) {
                    if (curr.getDay() === targetDay) {
                        crearEventoInstructor(formatFechaLocal(curr));
                    }
                    curr.setDate(curr.getDate() + 1);
                }
            }
        });

        // 2. Novedades de asistencia (retardos y excusas)
        novedadesData.forEach(function(nov) {
            if (fichaFiltroActiva && nov.ficha && String(nov.ficha) !== String(fichaFiltroActiva)) {
                return;
            }

            let bg = '#f39c12';
            let border = '#e67e22';

            if (nov.tipo === 'excusa') {
                bg = '#9b59b6';
                border = '#8e44ad';
            }

            events.push({
                title: nov.titulo,
                start: nov.fecha,
                end: nov.fecha_fin || nov.fecha,
                allDay: true,
                backgroundColor: bg,
                borderColor: border,
                textColor: '#ffffff',
                extendedProps: {
                    categoria: nov.tipo,
                    fechaRaw: nov.fecha,
                    ficha: nov.ficha,
                    programa: nov.programa,
                    description: nov.descripcion
                }
            });
        });

        return events;
    }

    // Instancia de FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        hiddenDays: [0], // Lunes a Sábado visibles (solo Domingo oculto)
        locale: 'es',
        headerToolbar: false, // Usamos la barra de comandos Outlook
        firstDay: 1, // Lunes
        slotMinTime: '06:00:00',
        slotMaxTime: '22:30:00',
        slotDuration: '00:30:00',
        slotLabelInterval: '01:00:00',
        scrollTime: '06:00:00',
        nowIndicator: true,
        allDaySlot: true,
        allDayText: 'Todo el día',
        expandRows: true,
        height: 'auto',
        dayMaxEvents: 2,
        views: {
            dayGridMonth: {
                dayMaxEvents: 2,
                fixedWeekCount: false
            }
        },

        // Encabezados personalizados estilo Outlook
        dayHeaderContent: function(arg) {
            return OutlookCalendarHelper.renderHeaderCell(arg);
        },

        // Renderizado de eventos con tarjetas estilo Outlook
        eventContent: function(eventInfo) {
            return OutlookCalendarHelper.renderEventContent(eventInfo);
        },

        // Proveedor defensivo de eventos
        events: function(fetchInfo, successCallback, failureCallback) {
            try {
                const evts = obtenerEventos(fetchInfo);
                successCallback(evts);
            } catch (err) {
                console.error('Error al generar eventos del calendario:', err);
                successCallback([]);
            }
        },

        // Clic en evento de formación / novedad
        eventClick: function(info) {
            abrirModalGestion(info.event);
        },

        // Sincronización con el helper de Outlook
        datesSet: function(info) {
            if (outlookHelper) {
                outlookHelper.syncFromMainCalendar(info.view);
            }
        }
    });

    calendar.render();

    // Inicializar Helper de Outlook (Mini calendario, barra de comandos y observador de temas)
    outlookHelper = new OutlookCalendarHelper({
        mainCalendar: calendar,
        miniCalendarEl: document.getElementById('outlookMiniCalendar'),
        titleEl: document.getElementById('outlookDateTitle'),
        railSidebarEl: document.getElementById('outlookRailSidebar')
    });

    // Enlazar botones de la barra Outlook
    document.getElementById('btnOutlookToday')?.addEventListener('click', () => {
        calendar.today();
    });

    document.getElementById('btnOutlookPrev')?.addEventListener('click', () => {
        calendar.prev();
    });

    document.getElementById('btnOutlookNext')?.addEventListener('click', () => {
        calendar.next();
    });

    // Botones de vista
    const viewButtons = document.querySelectorAll('.outlook-btn-view');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const vista = this.getAttribute('data-view');
            outlookHelper.cambiarVista(vista);
        });
    });

    // Alternar panel lateral
    document.getElementById('btnToggleRail')?.addEventListener('click', () => {
        outlookHelper.toggleRail();
    });

    // Imprimir
    document.getElementById('btnOutlookPrint')?.addEventListener('click', () => {
        window.print();
    });

    // Filtros por dropdown de fichas
    const itemsFiltro = document.querySelectorAll('.item-filtro-ficha');
    itemsFiltro.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            itemsFiltro.forEach(i => i.classList.remove('active'));
            this.classList.add('active');

            fichaFiltroActiva = this.getAttribute('data-ficha');
            const labelEl = document.getElementById('labelFichaFiltro');
            if (labelEl) {
                labelEl.innerText = fichaFiltroActiva ? ('Ficha ' + fichaFiltroActiva) : 'Todas las Fichas';
            }

            calendar.refetchEvents();
        });
    });

    /**
     * Abre el modal de inspección del bloque de formación
     */
    function abrirModalGestion(eventObj) {
        document.getElementById('eventoTitulo').innerText = eventObj.title;

        const startFormat = eventObj.start ? eventObj.start.toLocaleString('es-CO', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }) : '';
        const endFormat = eventObj.end ? ' - ' + eventObj.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
        const tipoBadge = eventObj.extendedProps.bloqueLabel || (eventObj.extendedProps.categoria === 'excusa' ? 'Excusa Médica' : 'Novedad de Asistencia');

        document.getElementById('eventoFecha').innerText = startFormat + endFormat;
        document.getElementById('eventoTipoBadge').innerText = tipoBadge;

        const fichaNum = eventObj.extendedProps.ficha || '';
        const prog = eventObj.extendedProps.programa || '';
        const fichaBadgeText = fichaNum ? ('Ficha ' + fichaNum + (prog ? ' · ' + prog : '')) : 'Todas las Fichas';
        document.getElementById('eventoFichaBadge').innerText = fichaBadgeText;

        document.getElementById('eventoAmbienteTexto').innerText = eventObj.extendedProps.ambiente || 'Ambiente por asignar';
        document.getElementById('eventoTolRetardo').innerText = (eventObj.extendedProps.toleranciaEntrada ?? 15) + ' min';
        document.getElementById('eventoTolSalida').innerText = (eventObj.extendedProps.toleranciaSalida ?? 0) + ' min';
        document.getElementById('eventoDesc').innerText = eventObj.extendedProps.description || 'Sin detalles registrados.';

        const urlIngresos = '<?= BASE_URL ?>/index.php?page=instructor_ingresos' + (fichaNum ? '&ficha=' + encodeURIComponent(fichaNum + (prog ? ' - ' + prog : '')) : '');
        const btnIngresos = document.getElementById('btnModalIngresos');
        const btnIngresosFooter = document.getElementById('btnModalIngresosFooter');
        if (btnIngresos) btnIngresos.href = urlIngresos;
        if (btnIngresosFooter) btnIngresosFooter.href = urlIngresos;

        const modalEl = document.getElementById('modalEvento');
        if (modalEl) {
            const myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        }
    }
});
</script>
<?php
$scriptsExtra = ob_get_clean();

require __DIR__ . '/../layouts/footer-instructor.php';
?>
