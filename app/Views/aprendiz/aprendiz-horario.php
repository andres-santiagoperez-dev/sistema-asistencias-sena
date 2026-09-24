<?php
/**
 * Vista: Mi Horario & Calendario (Aprendiz)
 * Sistema de Control de Asistencia SENA
 * 
 * Pura presentación MVC: consume variables entregadas por HorarioController
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=aprendiz_horario');
    exit();
}

$tituloPagina   = 'Mi Horario & Calendario';
$numeroFicha    = $infoAprendiz['numero_ficha'] ?? 'Sin Ficha';
$programa       = $infoAprendiz['programa'] ?? 'ADSO';
$horaEntradaRaw = $infoAprendiz['hora_entrada'] ?? '06:00:00';
$horaSalidaRaw  = $infoAprendiz['hora_salida'] ?? '12:00:00';
$horarioStr     = date('h:i A', strtotime($horaEntradaRaw)) . ' - ' . date('h:i A', strtotime($horaSalidaRaw));

require_once dirname(__DIR__) . '/layouts/header-aprendiz.php';
?>

<!-- Welcome Header -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
            <i class="bi bi-calendar-check fs-2"></i>
        </div>
        <div>
            <h3 class="text-theme-primary fw-bold mb-1">Mi Horario de Formación</h3>
            <p class="text-secondary mb-0" style="font-size:14px;">Consulta tus horarios asignados, novedades y eventos académicos.</p>
        </div>
    </div>
</div>

<!-- Resumen Horario Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24b5ca !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold">FICHA & PROGRAMA</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1"><?= htmlspecialchars($numeroFicha) ?></h5>
                    <small class="text-info"><?= htmlspecialchars($programa) ?></small>
                </div>
                <i class="bi bi-mortarboard text-info fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #24caa1 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold">JORNADA ASIGNADA</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1">Lunes a Viernes</h5>
                    <small class="text-success"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($horarioStr) ?></small>
                </div>
                <i class="bi bi-sun text-success fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-sm-12">
        <div class="card border-0 p-3 card-stat-ux" style="border-left: 4px solid #f39c12 !important;">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-secondary fw-bold">LÍMITE DE INGRESO</small>
                    <h5 class="text-theme-primary fw-bold mb-0 mt-1">Margen de Tolerancia</h5>
                    <small class="text-warning"><i class="bi bi-hourglass-split me-1"></i>15 Minutos tras la entrada</small>
                </div>
                <i class="bi bi-hourglass-split text-warning fs-1 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Calendario FullCalendar -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 card-stat-ux shadow-sm">
            <div class="card-body p-3">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Evento (Modal Existente) -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-nalika">
            <div class="modal-header modal-header-nalika">
                <h5 class="modal-title text-theme-primary fw-bold" id="eventoTitulo"><i class="bi bi-info-circle text-info me-2"></i>Detalle de Clase</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3 border-bottom border-secondary border-opacity-25 pb-3">
                    <small class="text-secondary d-block">Fecha y Hora</small>
                    <strong class="text-theme-primary fs-5" id="eventoFecha"></strong>
                </div>
                <div>
                    <small class="text-secondary d-block">Descripción</small>
                    <p class="text-theme-primary mb-0" id="eventoDesc"></p>
                </div>
            </div>
            <div class="modal-footer border-0">
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

<!-- Init FullCalendar Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        var horaEntrada = <?= json_encode(substr($horaEntradaRaw, 0, 5)) ?>;
        var horaSalida  = <?= json_encode(substr($horaSalidaRaw, 0, 5)) ?>;
        var numFicha    = <?= json_encode($numeroFicha) ?>;

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
                var events = [];
                var currentDate = new Date(fetchInfo.start);
                var endDate = new Date(fetchInfo.end);

                while (currentDate <= endDate) {
                    var day = currentDate.getDay();
                    // 1: Lunes a 5: Viernes
                    if (day >= 1 && day <= 5) {
                        var dateStr = currentDate.toISOString().split('T')[0];
                        events.push({
                            title: 'Formación ADSO',
                            start: dateStr + 'T' + horaEntrada + ':00',
                            end: dateStr + 'T' + horaSalida + ':00',
                            className: 'event-clase',
                            extendedProps: {
                                description: 'Jornada regular de formación técnica para la ficha ' + numFicha + '.'
                            }
                        });
                    }
                    currentDate.setDate(currentDate.getDate() + 1);
                }

                // Evento de novedad/retardo de ejemplo
                var today = new Date();
                var lastWeek = new Date(today);
                lastWeek.setDate(today.getDate() - 3);
                if (lastWeek.getDay() === 0 || lastWeek.getDay() === 6) {
                    lastWeek.setDate(lastWeek.getDate() - 2);
                }

                events.push({
                    title: 'Novedad: Retardo',
                    start: lastWeek.toISOString().split('T')[0] + 'T' + horaEntrada + ':00',
                    allDay: true,
                    className: 'event-novedad',
                    extendedProps: {
                        description: 'Ingreso posterior a los 15 minutos de tolerancia.'
                    }
                });

                successCallback(events);
            },
            eventClick: function(info) {
                document.getElementById('eventoTitulo').innerHTML = '<i class="bi bi-info-circle text-info me-2"></i>' + info.event.title;
                
                var startFormat = info.event.start.toLocaleString('es-CO', { 
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                });
                var endFormat = info.event.end ? ' - ' + info.event.end.toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' }) : '';
                
                document.getElementById('eventoFecha').innerText = startFormat + endFormat;
                document.getElementById('eventoDesc').innerText = info.event.extendedProps.description || 'Sin detalles adicionales.';
                
                var modalEl = document.getElementById('modalEvento');
                if (modalEl) {
                    var myModal = new bootstrap.Modal(modalEl);
                    myModal.show();
                }
            }
        });

        calendar.render();
    });
</script>
<?php
$scriptsExtra = ob_get_clean();

require_once dirname(__DIR__) . '/layouts/footer-aprendiz.php';
?>
