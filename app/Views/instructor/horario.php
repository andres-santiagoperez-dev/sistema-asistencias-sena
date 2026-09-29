<?php
/**
 * Vista de Presentación: Mi Horario & Calendario (Instructor)
 * Sistema de Control de Asistencia SENA
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

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-calendar-check fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Mi Horario & Agenda de Formación</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Consulta horarios de clase, novedades y registra notas u observaciones sobre tus jornadas académicas.</p>
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

<!-- Modal Detalle Evento & Notas de Clase -->
<div class="modal fade" id="modalEvento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika">
            <div class="modal-header modal-header-nalika border-bottom border-secondary border-opacity-25">
                <h5 class="modal-title text-theme-primary fw-bold" id="eventoTitulo">
                    <i class="bi bi-info-circle text-info me-2"></i>Detalle del Evento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Información Principal de la Clase -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6 border-end border-secondary border-opacity-25">
                        <small class="text-secondary d-block">Fecha y Hora</small>
                        <strong class="text-theme-primary fs-6" id="eventoFecha"></strong>
                    </div>
                    <div class="col-md-6" id="eventoFichaContainer">
                        <small class="text-secondary d-block mb-1">Ficha / Programa</small>
                        <span class="badge bg-success text-wrap text-start" id="eventoFichaBadge"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <small class="text-secondary d-block">Detalles de la Formación</small>
                    <p class="text-theme-primary mb-0" id="eventoDesc"></p>
                </div>

                <!-- Sección de Notas y Observaciones de Clase -->
                <div class="border-top border-secondary border-opacity-25 pt-3 mt-3">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="text-theme-primary fw-bold mb-0">
                            <i class="bi bi-journal-text text-warning me-2"></i>Notas & Observaciones de la Clase
                        </h6>
                        <button class="btn btn-sm btn-sena-outline" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFormNota" aria-expanded="false" aria-controls="collapseFormNota">
                            <i class="bi bi-plus-circle me-1"></i> Nueva Nota
                        </button>
                    </div>

                    <!-- Alert Feedback -->
                    <div id="notaFeedbackAlert" class="alert d-none py-2 px-3 mb-3" role="alert"></div>

                    <!-- Formulario de Nueva Nota (Collapsible) -->
                    <div class="collapse mb-3" id="collapseFormNota">
                        <div class="card card-body bg-dark border border-secondary border-opacity-50 p-3">
                            <h6 class="text-success fw-bold fs-6 mb-2"><i class="bi bi-pencil-square me-1"></i>Registrar Novedad / Nota de Clase</h6>
                            <form id="formNuevaNota">
                                <input type="hidden" id="notaFechaInput" name="fecha">
                                <input type="hidden" id="notaFichaInput" name="numero_ficha">

                                <div class="mb-2">
                                    <label class="form-label small text-secondary mb-1">Tipo / Título de la Nota:</label>
                                    <select class="form-select form-select-sm" name="titulo" id="notaTituloInput">
                                        <option value="Salida temprana de aprendices">Salida temprana de aprendices (Reunión / Evento)</option>
                                        <option value="Llegada con retardo instructor">Llegada con retardo instructor (Falla / Movilidad)</option>
                                        <option value="Cambio de ambiente / aula">Cambio de ambiente de formación / aula</option>
                                        <option value="Falla técnica / fluido eléctrico">Falla técnica o fluido eléctrico</option>
                                        <option value="Observación general de clase" selected>Observación general de clase</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-secondary mb-1">Observación o Explicación:</label>
                                    <textarea class="form-control form-control-sm" name="observacion" id="notaObservacionInput" rows="3" placeholder="Ej: Los aprendices salieron 20 minutos antes debido a reunión de voceros, o me retrasé por falla mecánica en el transporte..."></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseFormNota">Cancelar</button>
                                    <button type="submit" id="btnGuardarNota" class="btn btn-sena-green btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Guardar Nota
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Notas Registradas -->
                    <div id="contenedorNotasClase" class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                        <!-- Se llena dinámicamente con JavaScript -->
                    </div>
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
        var notasGlobales = <?= json_encode($notas) ?>;
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
                                fechaRaw: dateStr,
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

            // 2. Agregar novedades (retardos, excusas y notas)
            novedadesData.forEach(function(nov) {
                if (filtroFichaSeleccionada && nov.ficha && nov.ficha !== filtroFichaSeleccionada) {
                    return;
                }

                var bg = '#f39c12';
                var border = '#e67e22';

                if (nov.tipo === 'excusa') {
                    bg = '#9b59b6';
                    border = '#8e44ad';
                } else if (nov.tipo === 'nota') {
                    bg = '#2980b9';
                    border = '#1f618d';
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
                
                var fichaProps = info.event.extendedProps.ficha ? ('Ficha ' + info.event.extendedProps.ficha + (info.event.extendedProps.programa ? ' - ' + info.event.extendedProps.programa : '')) : 'General / Todas las Fichas';
                document.getElementById('eventoFichaBadge').innerText = fichaProps;
                document.getElementById('eventoDesc').innerText = info.event.extendedProps.description || 'Sin detalles adicionales.';

                // Configurar campos ocultos del formulario de nota
                var dateStr = info.event.extendedProps.fechaRaw || (info.event.start ? info.event.start.toISOString().split('T')[0] : '');
                var fichaNum = info.event.extendedProps.ficha || '';

                document.getElementById('notaFechaInput').value = dateStr;
                document.getElementById('notaFichaInput').value = fichaNum;

                // Ocultar mensajes de feedback previos
                var alertEl = document.getElementById('notaFeedbackAlert');
                if (alertEl) alertEl.classList.add('d-none');

                // Renderizar notas guardadas para esta fecha y ficha
                renderizarNotasModal(dateStr, fichaNum);

                var modalEl = document.getElementById('modalEvento');
                if (modalEl) {
                    var myModal = new bootstrap.Modal(modalEl);
                    myModal.show();
                }
            }
        });

        calendar.render();

        // Renderiza la lista de notas dentro del modal para la fecha/ficha seleccionada
        function renderizarNotasModal(fecha, numeroFicha) {
            var contenedor = document.getElementById('contenedorNotasClase');
            if (!contenedor) return;

            var notasFiltradas = notasGlobales.filter(function(n) {
                var matchFecha = (n.fecha === fecha);
                var matchFicha = (!numeroFicha || !n.numero_ficha || n.numero_ficha === numeroFicha);
                return matchFecha && matchFicha;
            });

            if (notasFiltradas.length === 0) {
                contenedor.innerHTML = '<div class="text-secondary small italic py-2"><i class="bi bi-info-circle me-1"></i>No hay observaciones registradas aún para esta fecha de clase.</div>';
                return;
            }

            var html = '';
            notasFiltradas.forEach(function(n) {
                html += '<div class="p-2.5 rounded bg-dark border border-secondary border-opacity-25 d-flex justify-content-between align-items-start gap-2">' +
                            '<div>' +
                                '<div class="d-flex align-items-center gap-2 mb-1">' +
                                    '<span class="badge bg-warning text-dark style="font-size:11px;">' + (n.titulo || 'Observación') + '</span>' +
                                    (n.numero_ficha ? '<small class="text-info">Ficha ' + n.numero_ficha + '</small>' : '') +
                                '</div>' +
                                '<p class="text-theme-primary mb-1 small" style="white-space: pre-wrap;">' + escapeHtml(n.observacion) + '</p>' +
                                '<small class="text-secondary" style="font-size:11px;"><i class="bi bi-clock me-1"></i>' + (n.fecha_creacion || n.fecha) + '</small>' +
                            '</div>' +
                            '<button type="button" class="btn btn-outline-danger btn-sm border-0 btn-eliminar-nota" data-id="' + n.id_nota + '" title="Eliminar nota">' +
                                '<i class="bi bi-trash"></i>' +
                            '</button>' +
                        '</div>';
            });

            contenedor.innerHTML = html;

            // Escuchar clics en botones de eliminar nota
            var btnsEliminar = contenedor.querySelectorAll('.btn-eliminar-nota');
            btnsEliminar.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var idNota = this.getAttribute('data-id');
                    if (confirm('¿Deseas eliminar esta observación de clase?')) {
                        eliminarNotaAjax(idNota, fecha, numeroFicha);
                    }
                });
            });
        }

        // Guardar nota vía AJAX
        var formNota = document.getElementById('formNuevaNota');
        if (formNota) {
            formNota.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(formNota);
                var btn = document.getElementById('btnGuardarNota');
                if (btn) btn.disabled = true;

                fetch('index.php?page=instructor_horario&action=guardarNota', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (btn) btn.disabled = false;
                    var alertEl = document.getElementById('notaFeedbackAlert');
                    
                    if (data.success) {
                        var fechaVal = document.getElementById('notaFechaInput').value;
                        var fichaVal = document.getElementById('notaFichaInput').value;
                        var tituloVal = document.getElementById('notaTituloInput').value;
                        var obsVal = document.getElementById('notaObservacionInput').value;

                        // Agregar localmente a notasGlobales
                        var nuevaNotaObj = {
                            id_nota: Date.now(),
                            fecha: fechaVal,
                            numero_ficha: fichaVal,
                            titulo: tituloVal,
                            observacion: obsVal,
                            fecha_creacion: 'Hace un momento'
                        };
                        notasGlobales.unshift(nuevaNotaObj);

                        // Agregar a novedadesData para FullCalendar
                        novedadesData.push({
                            tipo: 'nota',
                            id_nota: nuevaNotaObj.id_nota,
                            fecha: fechaVal,
                            ficha: fichaVal,
                            titulo: '📝 Nota: ' + tituloVal,
                            descripcion: obsVal
                        });

                        document.getElementById('notaObservacionInput').value = '';
                        
                        // Cerrar formulario desplegable
                        var collapseEl = document.getElementById('collapseFormNota');
                        if (collapseEl && bootstrap.Collapse) {
                            var bsCollapse = bootstrap.Collapse.getInstance(collapseEl);
                            if (bsCollapse) bsCollapse.hide();
                        }

                        renderizarNotasModal(fechaVal, fichaVal);
                        calendar.refetchEvents();

                        if (alertEl) {
                            alertEl.className = 'alert alert-success py-2 px-3 mb-3';
                            alertEl.innerText = data.message || 'Nota guardada exitosamente.';
                        }
                    } else {
                        if (alertEl) {
                            alertEl.className = 'alert alert-danger py-2 px-3 mb-3';
                            alertEl.innerText = data.message || 'No se pudo guardar la nota.';
                        }
                    }
                })
                .catch(function(err) {
                    if (btn) btn.disabled = false;
                    console.error(err);
                });
            });
        }

        // Eliminar nota vía AJAX
        function eliminarNotaAjax(idNota, fecha, numeroFicha) {
            var formData = new FormData();
            formData.append('id_nota', idNota);

            fetch('index.php?page=instructor_horario&action=eliminarNota', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    notasGlobales = notasGlobales.filter(function(n) { return n.id_nota != idNota; });
                    novedadesData = novedadesData.filter(function(n) { return n.id_nota != idNota; });
                    renderizarNotasModal(fecha, numeroFicha);
                    calendar.refetchEvents();
                } else {
                    alert(data.message || 'No se pudo eliminar la nota.');
                }
            })
            .catch(function(err) { console.error(err); });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;")
                       .replace(/</g, "&lt;")
                       .replace(/>/g, "&gt;")
                       .replace(/"/g, "&quot;")
                       .replace(/'/g, "&#039;");
        }

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
