<?php
/**
 * Vista de Presentación: Mi Horario & Calendario (Instructor)
 * Sistema de Control de Asistencia SENA
 * 
 * @var string $tituloPagina
 * @var array  $fichas
 * @var array  $novedades
 * @var string|null $filtroFicha
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=instructor_horario');
    exit();
}

$tituloPagina = $tituloPagina ?? 'Mi Horario & Calendario';
$fichas       = $fichas ?? [];
$novedades    = $novedades ?? [];
$filtroFicha  = $filtroFicha ?? '';

// Calcular estadísticas rápidas
$totalFichas     = count($fichas);
$totalAprendices = array_sum(array_column($fichas, 'total_aprendices'));
$primeraFicha    = $fichas[0] ?? null;
$jornadaTexto    = $primeraFicha ? ($primeraFicha['entrada'] . ' - ' . $primeraFicha['salida']) : 'Sin Horario';

require __DIR__ . '/../layouts/header-instructor.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-calendar-check fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Mi Horario & Calendario Formativo</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Consulta las sesiones de clase programadas por ficha, jornadas académicas y novedades de asistencia.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green">
                <i class="bi bi-door-open me-1"></i> Control de Ingresos
            </a>
        </div>
    </div>
</div>

<!-- KPI / Resumen Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24b5ca !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">FICHAS ASIGNADAS</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= $totalFichas ?> Ficha(s) Activa(s)</h5>
                    <small class="text-info">
                        <?= $totalFichas > 0 ? htmlspecialchars(implode(', ', array_column($fichas, 'numero_ficha'))) : 'Sin Fichas' ?>
                    </small>
                </div>
                <i class="bi bi-journal-bookmark text-info fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24caa1 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">JORNADA DE FORMACIÓN</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1">Lunes a Viernes</h5>
                    <small class="text-success"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($jornadaTexto) ?></small>
                </div>
                <i class="bi bi-clock-history text-success fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold text-uppercase" style="font-size:11px;">TOTAL APRENDICES A CARGO</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= $totalAprendices ?> Aprendices</h5>
                    <small class="text-warning"><i class="bi bi-people me-1"></i>En grupo(s) asignado(s)</small>
                </div>
                <i class="bi bi-people text-warning fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filtro de Fichas y Calendario -->
<div class="card border-0 card-stat-ux shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar3 text-success fs-5"></i>
            <h5 class="text-theme-primary fw-bold mb-0">Agenda de Formación</h5>
        </div>

        <?php if ($totalFichas > 1): ?>
        <div class="d-flex align-items-center gap-2">
            <label for="selectFichaFiltro" class="text-secondary small fw-bold mb-0">Filtrar por Ficha:</label>
            <select id="selectFichaFiltro" class="form-select form-select-sm" style="min-width: 200px;">
                <option value="">Todas las Fichas (<?= $totalFichas ?>)</option>
                <?php foreach ($fichas as $f): ?>
                    <option value="<?= htmlspecialchars($f['numero_ficha']) ?>" <?= ($filtroFicha === $f['numero_ficha']) ? 'selected' : '' ?>>
                        Ficha <?= htmlspecialchars($f['numero_ficha']) ?> - <?= htmlspecialchars($f['programa']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-3">
        <div id="calendar"></div>
    </div>
</div>

<!-- Modal Detalle Evento -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-nalika">
            <div class="modal-header modal-header-nalika">
                <h5 class="modal-title text-theme-primary fw-bold" id="eventoTitulo">
                    <i class="bi bi-info-circle text-info me-2"></i>Detalle del Evento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <small class="text-secondary d-block">Fecha y Hora</small>
                    <strong class="text-theme-primary fs-5" id="eventoFecha"></strong>
                </div>
                <div class="mb-3 border-bottom border-secondary border-opacity-25 pb-3" id="eventoFichaContainer">
                    <small class="text-secondary d-block">Ficha / Programa</small>
                    <span class="badge bg-success" id="eventoFichaBadge"></span>
                </div>
                <div>
                    <small class="text-secondary d-block">Descripción</small>
                    <p class="text-theme-primary mb-0" id="eventoDesc"></p>
                </div>
            </div>
            <div class="modal-footer border-0 d-flex justify-content-between">
                <a href="<?= BASE_URL ?>/index.php?page=instructor_ingresos" class="btn btn-sena-green btn-sm">
                    <i class="bi bi-door-open me-1"></i> Ir a Control de Ingresos
                </a>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<!-- FullCalendar v6 CDN -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        var fichasData    = <?= json_encode($fichas) ?>;
        var novedadesData = <?= json_encode($novedades) ?>;
        var filtroFichaEl = document.getElementById('selectFichaFiltro');

        // Paleta de colores atractiva para diferenciar fichas
        var coloresFichas = [
            '#39a900', // Verde SENA
            '#24b5ca', // Azul Cyan
            '#9b59b6', // Púrpura
            '#f39c12', // Naranja
            '#e74c3c'  // Rojo
        ];

        // Mapear color fijo por número de ficha
        var mapaColores = {};
        fichasData.forEach(function(f, index) {
            mapaColores[f.numero_ficha] = coloresFichas[index % coloresFichas.length];
        });

        function obtenerEventos(fetchInfo, filtroFichaSeleccionada) {
            var events = [];
            var startDate = new Date(fetchInfo.start);
            var endDate = new Date(fetchInfo.end);

            // 1. Generar sesiones de clases regulares por cada ficha
            fichasData.forEach(function(ficha) {
                if (filtroFichaSeleccionada && ficha.numero_ficha !== filtroFichaSeleccionada) {
                    return;
                }

                var horaEntrada = ficha.raw_hora_entrada ? ficha.raw_hora_entrada.substring(0, 5) : '06:00';
                var horaSalida  = ficha.raw_hora_salida ? ficha.raw_hora_salida.substring(0, 5) : '12:00';
                var colorFicha  = mapaColores[ficha.numero_ficha] || '#39a900';

                var curr = new Date(startDate);
                while (curr <= endDate) {
                    var day = curr.getDay(); // 0: Dom, 1: Lun ... 6: Sáb
                    // Lunes a Viernes
                    if (day >= 1 && day <= 5) {
                        var dateStr = curr.toISOString().split('T')[0];
                        events.push({
                            title: 'Formación Ficha ' + ficha.numero_ficha + ' (' + ficha.programa + ')',
                            start: dateStr + 'T' + horaEntrada + ':00',
                            end: dateStr + 'T' + horaSalida + ':00',
                            backgroundColor: colorFicha,
                            borderColor: colorFicha,
                            textColor: '#ffffff',
                            extendedProps: {
                                ficha: ficha.numero_ficha,
                                programa: ficha.programa,
                                jornada: ficha.entrada + ' - ' + ficha.salida,
                                aprendices: ficha.total_aprendices,
                                description: 'Jornada regular de formación técnica para la ficha ' + ficha.numero_ficha + ' (' + ficha.programa + '). Total aprendices a cargo: ' + (ficha.total_aprendices || 0) + '.'
                            }
                        });
                    }
                    curr.setDate(curr.getDate() + 1);
                }
            });

            // 2. Agregar novedades de retardo y excusas
            novedadesData.forEach(function(nov) {
                if (filtroFichaSeleccionada && nov.ficha !== filtroFichaSeleccionada) {
                    return;
                }

                var isRetardo = nov.tipo === 'retardo';
                events.push({
                    title: nov.titulo,
                    start: nov.fecha,
                    end: nov.fecha_fin || nov.fecha,
                    allDay: true,
                    backgroundColor: isRetardo ? '#f39c12' : '#9b59b6',
                    borderColor: isRetardo ? '#e67e22' : '#8e44ad',
                    textColor: '#ffffff',
                    extendedProps: {
                        ficha: nov.ficha,
                        programa: nov.programa,
                        description: nov.descripcion
                    }
                });
            });

            return events;
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'es',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                list: 'Agenda'
            },
            firstDay: 1, // Lunes
            height: 700,
            events: function(fetchInfo, successCallback, failureCallback) {
                var filtroFicha = filtroFichaEl ? filtroFichaEl.value : '';
                var events = obtenerEventos(fetchInfo, filtroFicha);
                successCallback(events);
            },
            eventClick: function(info) {
                document.getElementById('eventoTitulo').innerHTML = '<i class="bi bi-info-circle text-info me-2"></i>' + info.event.title;
                
                var startFormat = info.event.start ? info.event.start.toLocaleString('es-CO', { 
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }) : '';
                var endFormat = info.event.end ? ' - ' + info.event.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
                
                document.getElementById('eventoFecha').innerText = startFormat + endFormat;
                
                var fichaProps = info.event.extendedProps.ficha ? ('Ficha ' + info.event.extendedProps.ficha + (info.event.extendedProps.programa ? ' - ' + info.event.extendedProps.programa : '')) : 'General';
                document.getElementById('eventoFichaBadge').innerText = fichaProps;
                
                document.getElementById('eventoDesc').innerText = info.event.extendedProps.description || 'Sin detalles adicionales.';
                
                var modalEl = document.getElementById('modalEvento');
                if (modalEl) {
                    var myModal = new bootstrap.Modal(modalEl);
                    myModal.show();
                }
            }
        });

        calendar.render();

        // Escuchar cambio en el filtro de fichas
        if (filtroFichaEl) {
            filtroFichaEl.addEventListener('change', function() {
                calendar.refetchEvents();
            });
        }
    });
</script>
<?php
$scriptsExtra = ob_get_clean();

require __DIR__ . '/../layouts/footer-instructor.php';
?>
