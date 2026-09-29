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
$novedades    = $novedades ?? [];
$notas        = $notas ?? [];
$filtroFicha  = $filtroFicha ?? '';

// Calcular estadísticas rápidas
$totalFichas     = count($fichas);
$totalAprendices = array_sum(array_column($fichas, 'total_aprendices'));
$primeraFicha    = $fichas[0] ?? null;
$jornadaTexto    = $primeraFicha ? ($primeraFicha['entrada'] . ' - ' . $primeraFicha['salida']) : 'Sin Horario';

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
                        <i class="bi bi-pencil-square me-1"></i>Modo Gestión & Observaciones
                    </span>
                </div>
                <p class="text-secondary mb-0" style="font-size:14px;">Agenda semanal estilo Outlook. Gestiona tus jornadas, registra observaciones y supervisa novedades de clase.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-outline btn-sm" id="btnTopNuevaNota">
                <i class="bi bi-journal-plus me-1"></i> Nueva Observación
            </button>
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

            <!-- Indicador Modo Gestión -->
            <div class="p-2.5 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25 text-secondary" style="font-size: 11.5px;">
                <div class="d-flex align-items-center gap-1.5 text-success fw-bold mb-1">
                    <i class="bi bi-pencil-square"></i> Modo Gestión Activo
                </div>
                <p class="mb-1 text-secondary">Haz clic en cualquier sesión o espacio para registrar observaciones, salidas tempranas o retardos sobre tus clases.</p>
                <div class="text-primary fw-semibold"><i class="bi bi-lightning-charge me-1"></i>Sincronización Inmediata</div>
            </div>
        </div>

        <!-- Contenedor del Calendario Principal (FullCalendar estilo Outlook) -->
        <div class="outlook-main-grid-container">
            <div id="calendar" class="outlook-theme-fc"></div>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL DE GESTIÓN Y OBSERVACIONES (INSTRUCTOR)
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
                        <h5 class="modal-title fw-bold mb-0 text-theme-primary" id="eventoTitulo">Detalle de Sesión & Observaciones</h5>
                        <small class="text-secondary" id="eventoSubtitulo">Gestión Académica de Clase</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25 flex-wrap gap-2">
                    <span class="outlook-badge-editable">
                        <i class="bi bi-pencil-fill me-1"></i>Modo Gestión & Edición (Docente)
                    </span>
                    <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green btn-sm py-1 px-3">
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
                        <small class="text-secondary d-block fw-bold" style="font-size: 11px;">FICHA / GRUPO</small>
                        <span class="badge bg-success text-wrap text-start mt-1 p-2" id="eventoFichaBadge"></span>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-secondary d-block fw-bold" style="font-size: 11px;">DETALLE DE LA FORMACIÓN</small>
                    <p class="text-theme-primary mt-1 mb-0 p-2 rounded bg-dark bg-opacity-25 border border-secondary border-opacity-25" id="eventoDesc" style="font-size: 13px;"></p>
                </div>

                <!-- SECCIÓN: REGISTRO DE OBSERVACIONES DE CLASE -->
                <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-theme-primary fw-bold mb-0">
                            <i class="bi bi-journal-text text-warning me-2"></i>Observaciones & Novedades de la Sesión
                        </h6>
                        <button class="btn btn-sm btn-sena-outline" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormNota" aria-expanded="false" aria-controls="collapseFormNota">
                            <i class="bi bi-plus-circle me-1"></i> Añadir Observación
                        </button>
                    </div>

                    <!-- Alert Feedback AJAX -->
                    <div id="notaFeedbackAlert" class="alert d-none py-2 px-3 mb-3" role="alert"></div>

                    <!-- Formulario Desplegable para Agregar Observación -->
                    <div class="collapse mb-3" id="collapseFormNota">
                        <div class="card card-body bg-dark border border-secondary border-opacity-50 p-3 shadow-sm">
                            <h6 class="text-success fw-bold fs-6 mb-2">
                                <i class="bi bi-pencil-square me-1"></i>Registrar Novedad / Observación Docente
                            </h6>
                            <form id="formNuevaNota">
                                <input type="hidden" id="notaFechaInput" name="fecha">
                                <input type="hidden" id="notaFichaInput" name="numero_ficha">

                                <div class="mb-2">
                                    <label class="form-label small text-secondary mb-1 fw-bold">Tipo / Asunto:</label>
                                    <select class="form-select form-select-sm" name="titulo" id="notaTituloInput">
                                        <option value="Salida temprana de aprendices">Salida temprana de aprendices (Reunión / Evento institucional)</option>
                                        <option value="Llegada con retardo instructor">Llegada con retardo instructor (Movilidad / Fuerza mayor)</option>
                                        <option value="Cambio de ambiente / aula">Cambio de ambiente de formación / aula técnica</option>
                                        <option value="Falla técnica / fluido eléctrico">Falla técnica / corte de fluido eléctrico</option>
                                        <option value="Observación general de clase" selected>Observación pedagógica general de clase</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-secondary mb-1 fw-bold">Detalle o Justificación:</label>
                                    <textarea class="form-control form-control-sm" name="observacion" id="notaObservacionInput" rows="3" placeholder="Describe lo ocurrido en la jornada de formación..."></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseFormNota">Cancelar</button>
                                    <button type="submit" id="btnGuardarNota" class="btn btn-sena-green btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Guardar Observación
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Historial de Observaciones Registradas -->
                    <div id="contenedorNotasClase" class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                        <!-- Se llena dinámicamente con JavaScript -->
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between pt-0">
                <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green btn-sm">
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

    const fichasData    = <?= json_encode($fichas) ?>;
    let novedadesData   = <?= json_encode($novedades) ?>;
    let notasGlobales   = <?= json_encode($notas) ?>;
    let fichaFiltroActiva = <?= json_encode($filtroFicha) ?>;

    let outlookHelper = null;

    // Paleta de colores atractiva estilo Outlook/SENA para diferenciar fichas
    const coloresFichas = [
        '#39a900', // Verde SENA
        '#0078d4', // Azul Outlook
        '#9b59b6', // Púrpura
        '#f39c12', // Naranja Ámbar
        '#e74c3c'  // Carmesí
    ];

    const mapaColores = {};
    fichasData.forEach(function(f, index) {
        mapaColores[f.numero_ficha] = coloresFichas[index % coloresFichas.length];
    });

    /**
     * Generador de eventos de formación y novedades
     */
    function obtenerEventos(fetchInfo) {
        const events = [];
        const startDate = new Date(fetchInfo.start);
        const endDate = new Date(fetchInfo.end);

        // 1. Clases regulares de las fichas
        fichasData.forEach(function(ficha) {
            // Filtrar si hay ficha específica seleccionada en el menú superior
            if (fichaFiltroActiva && ficha.numero_ficha !== fichaFiltroActiva) {
                return;
            }

            const horaEntrada = ficha.raw_hora_entrada ? ficha.raw_hora_entrada.substring(0, 5) : '06:00';
            const horaSalida  = ficha.raw_hora_salida ? ficha.raw_hora_salida.substring(0, 5) : '12:00';
            const colorFicha  = mapaColores[ficha.numero_ficha] || '#39a900';

            const curr = new Date(startDate);
            while (curr <= endDate) {
                const day = curr.getDay(); // 1: Lun a 5: Vie
                if (day >= 1 && day <= 5) {
                    const dateStr = curr.toISOString().split('T')[0];
                    events.push({
                        title: 'Formación ' + ficha.programa,
                        start: dateStr + 'T' + horaEntrada + ':00',
                        end: dateStr + 'T' + horaSalida + ':00',
                        backgroundColor: colorFicha,
                        borderColor: colorFicha,
                        textColor: '#ffffff',
                        extendedProps: {
                            categoria: 'formacion',
                            fechaRaw: dateStr,
                            ficha: ficha.numero_ficha,
                            programa: ficha.programa,
                            jornada: (ficha.entrada || horaEntrada) + ' - ' + (ficha.salida || horaSalida),
                            aprendices: ficha.total_aprendices || 0,
                            description: 'Sesión regular de formación técnica para la ficha ' + ficha.numero_ficha + ' (' + ficha.programa + '). Total aprendices: ' + (ficha.total_aprendices || 0) + '.'
                        }
                    });
                }
                curr.setDate(curr.getDate() + 1);
            }
        });

        // 2. Novedades y Notas de clase
        novedadesData.forEach(function(nov) {
            if (fichaFiltroActiva && nov.ficha && nov.ficha !== fichaFiltroActiva) {
                return;
            }
            if (nov.tipo === 'nota' && !verNotas) return;
            if (nov.tipo === 'excusa' && !verExcusas) return;

            let bg = '#f39c12';
            let border = '#e67e22';

            if (nov.tipo === 'excusa') {
                bg = '#9b59b6';
                border = '#8e44ad';
            } else if (nov.tipo === 'nota') {
                bg = '#0078d4';
                border = '#005a9e';
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
                    description: nov.descripcion,
                    isNota: nov.tipo === 'nota'
                }
            });
        });

        return events;
    }

    // Instancia de FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        hiddenDays: [0, 6], // Semana laboral por defecto
        locale: 'es',
        headerToolbar: false, // Usamos la barra de comandos Outlook
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

        // Encabezados personalizados estilo Outlook
        dayHeaderContent: function(arg) {
            return OutlookCalendarHelper.renderHeaderCell(arg);
        },

        // Renderizado de eventos con tarjetas estilo Outlook
        eventContent: function(eventInfo) {
            return OutlookCalendarHelper.renderEventContent(eventInfo);
        },

        // Proveedor de eventos
        events: function(fetchInfo, successCallback, failureCallback) {
            const evts = obtenerEventos(fetchInfo);
            successCallback(evts);
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

    // Botón superior "Nueva Observación"
    document.getElementById('btnTopNuevaNota')?.addEventListener('click', () => {
        const todayStr = new Date().toISOString().split('T')[0];
        const primeraFichaNum = fichasData.length > 0 ? fichasData[0].numero_ficha : '';
        abrirModalGestion({
            title: 'Nueva Observación Docente',
            start: new Date(),
            extendedProps: {
                fechaRaw: todayStr,
                ficha: primeraFichaNum,
                programa: 'Formación SENA',
                description: 'Registra observaciones pedagógicas o novedades de clase para el historial.'
            }
        });
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
     * Abre el modal de gestión y carga las observaciones registradas
     */
    function abrirModalGestion(eventObj) {
        document.getElementById('eventoTitulo').innerText = eventObj.title;

        const startFormat = eventObj.start ? eventObj.start.toLocaleString('es-CO', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
            hour: '2-digit', minute: '2-digit'
        }) : '';
        const endFormat = eventObj.end ? ' - ' + eventObj.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';

        document.getElementById('eventoFecha').innerText = startFormat + endFormat;

        const fichaNum = eventObj.extendedProps.ficha || '';
        const prog = eventObj.extendedProps.programa || '';
        const fichaBadgeText = fichaNum ? ('Ficha ' + fichaNum + (prog ? ' - ' + prog : '')) : 'Todas las Fichas';
        document.getElementById('eventoFichaBadge').innerText = fichaBadgeText;

        document.getElementById('eventoDesc').innerText = eventObj.extendedProps.description || 'Sin detalles registrados.';

        const dateStr = eventObj.extendedProps.fechaRaw || (eventObj.start ? eventObj.start.toISOString().split('T')[0] : '');

        // Rellenar campos ocultos del formulario
        document.getElementById('notaFechaInput').value = dateStr;
        document.getElementById('notaFichaInput').value = fichaNum;

        // Ocultar alertas de feedback previas
        const alertEl = document.getElementById('notaFeedbackAlert');
        if (alertEl) alertEl.classList.add('d-none');

        // Cargar notas guardadas en el modal
        renderizarNotasModal(dateStr, fichaNum);

        const modalEl = document.getElementById('modalEvento');
        if (modalEl) {
            const myModal = new bootstrap.Modal(modalEl);
            myModal.show();
        }
    }

    /**
     * Renderiza las notas en el modal con posibilidad de eliminarlas
     */
    function renderizarNotasModal(fecha, numeroFicha) {
        const contenedor = document.getElementById('contenedorNotasClase');
        if (!contenedor) return;

        const notasFiltradas = notasGlobales.filter(function(n) {
            const matchFecha = (n.fecha === fecha);
            const matchFicha = (!numeroFicha || !n.numero_ficha || n.numero_ficha === numeroFicha);
            return matchFecha && matchFicha;
        });

        if (notasFiltradas.length === 0) {
            contenedor.innerHTML = '<div class="text-secondary small py-2"><i class="bi bi-info-circle me-1"></i>No hay observaciones registradas aún para esta jornada.</div>';
            return;
        }

        let html = '';
        notasFiltradas.forEach(function(n) {
            html += `
                <div class="p-2.5 rounded bg-dark border border-secondary border-opacity-25 d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25" style="font-size:11px;">
                                ${escapeHtml(n.titulo || 'Observación')}
                            </span>
                            ${n.numero_ficha ? `<small class="text-info">Ficha ${escapeHtml(n.numero_ficha)}</small>` : ''}
                        </div>
                        <p class="text-theme-primary mb-1 small" style="white-space: pre-wrap;">${escapeHtml(n.observacion)}</p>
                        <small class="text-secondary" style="font-size:11px;"><i class="bi bi-clock me-1"></i>${n.fecha_creacion || n.fecha}</small>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-eliminar-nota" data-id="${n.id_nota}" title="Eliminar observación">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
        });

        contenedor.innerHTML = html;

        // Escuchar clics en eliminar nota
        const btnsEliminar = contenedor.querySelectorAll('.btn-eliminar-nota');
        btnsEliminar.forEach(btn => {
            btn.addEventListener('click', function() {
                const idNota = this.getAttribute('data-id');
                if (confirm('¿Deseas eliminar esta observación de clase?')) {
                    eliminarNotaAjax(idNota, fecha, numeroFicha);
                }
            });
        });
    }

    /**
     * Envío de nueva nota vía AJAX
     */
    const formNota = document.getElementById('formNuevaNota');
    if (formNota) {
        formNota.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(formNota);
            const btn = document.getElementById('btnGuardarNota');
            if (btn) btn.disabled = true;

            fetch('index.php?page=instructor_horario&action=guardarNota', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (btn) btn.disabled = false;
                const alertEl = document.getElementById('notaFeedbackAlert');

                if (data.success) {
                    const fechaVal   = document.getElementById('notaFechaInput').value;
                    const fichaVal   = document.getElementById('notaFichaInput').value;
                    const tituloVal  = document.getElementById('notaTituloInput').value;
                    const obsVal     = document.getElementById('notaObservacionInput').value;

                    const nuevaNota = {
                        id_nota: Date.now(),
                        fecha: fechaVal,
                        numero_ficha: fichaVal,
                        titulo: tituloVal,
                        observacion: obsVal,
                        fecha_creacion: 'Hace un momento'
                    };
                    notasGlobales.unshift(nuevaNota);

                    novedadesData.push({
                        tipo: 'nota',
                        id_nota: nuevaNota.id_nota,
                        fecha: fechaVal,
                        ficha: fichaVal,
                        titulo: '📝 ' + tituloVal,
                        descripcion: obsVal
                    });

                    document.getElementById('notaObservacionInput').value = '';

                    // Cerrar collapse del formulario
                    const collapseEl = document.getElementById('collapseFormNota');
                    if (collapseEl && bootstrap.Collapse) {
                        const bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                        if (bsCollapse) bsCollapse.hide();
                    }

                    renderizarNotasModal(fechaVal, fichaVal);
                    calendar.refetchEvents();

                    if (alertEl) {
                        alertEl.className = 'alert alert-success py-2 px-3 mb-3';
                        alertEl.innerText = data.message || 'Observación guardada correctamente.';
                    }
                } else {
                    if (alertEl) {
                        alertEl.className = 'alert alert-danger py-2 px-3 mb-3';
                        alertEl.innerText = data.message || 'Error al guardar la observación.';
                    }
                }
            })
            .catch(err => {
                if (btn) btn.disabled = false;
                console.error(err);
            });
        });
    }

    /**
     * Eliminar nota vía AJAX
     */
    function eliminarNotaAjax(idNota, fecha, numeroFicha) {
        const formData = new FormData();
        formData.append('id_nota', idNota);

        fetch('index.php?page=instructor_horario&action=eliminarNota', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                notasGlobales = notasGlobales.filter(n => n.id_nota != idNota);
                novedadesData = novedadesData.filter(n => n.id_nota != idNota);
                renderizarNotasModal(fecha, numeroFicha);
                calendar.refetchEvents();
            } else {
                alert(data.message || 'No se pudo eliminar la observación.');
            }
        })
        .catch(err => console.error(err));
    }
});
</script>
<?php
$scriptsExtra = ob_get_clean();

require __DIR__ . '/../layouts/footer-instructor.php';
?>
