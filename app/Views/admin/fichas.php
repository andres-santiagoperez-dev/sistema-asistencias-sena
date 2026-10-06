<?php
/**
 * Vista: Gestión de Fichas y Horarios (admin/fichas.php)
 * 
 * Responsabilidad: Solo presentación visual (HTML + Blade PHP).
 * No realiza consultas SQL ni maneja lógica de conexión.
 * Recibe del FichaController: $fichas, $instructores, $horarios, $adminArea, $mensaje, $error.
 */

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../../config/init.php';
    header('Location: ' . BASE_URL . '/index.php?page=fichas');
    exit;
}

$tituloPagina = 'Fichas y Horarios';
require_once BASE_PATH . '/Views/layouts/header.php';
?>

<!-- Alertas de estado -->
<?php if (!empty($mensaje)): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-journal-bookmark-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Gestión de Fichas y Horarios</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">
                    Administración de programas formativos, asignación múltiple de instructores y jornadas.
                    <?php if ($adminArea !== 'todas'): ?>
                        <span class="badge bg-success ms-1">Área: <?= strtoupper(htmlspecialchars($adminArea)) ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-outline d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalImportarHorarioExcel" title="Cargar malla trimestral oficial desde Excel">
                <i class="bi bi-file-earmark-excel text-success fs-5"></i> Importar Horario SENA
            </button>
            <button type="button" class="btn btn-sena-green" data-bs-toggle="modal" data-bs-target="#modalFicha">
                <i class="bi bi-plus-circle me-1"></i> Nueva Ficha
            </button>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-collection me-2 text-info"></i>Fichas Registradas en el Sistema</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Listado maestro con instructores asignados y aprendices vinculados</p>
        </div>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorFichas" class="form-control form-control-sm border-start-0" placeholder="Buscar ficha o programa..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaFichas" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Código Ficha</th>
                    <th>Programa de Formación</th>
                    <th>Área</th>
                    <th>Instructores Asignados</th>
                    <th>Horario</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($fichas)): ?>
                <?php foreach ($fichas as $f): ?>
                    <tr>
                        <td><strong class="text-theme-primary fs-6"><?= htmlspecialchars($f['codigo']) ?></strong></td>
                        <td>
                            <span class="fw-semibold text-theme-primary"><?= htmlspecialchars($f['programa']) ?></span>
                        </td>
                        <td>
                            <?php $esTics = (strtolower($f['area']) === 'tics'); ?>
                            <span class="badge <?= $esTics ? 'bg-info text-dark' : 'bg-warning text-dark' ?>" style="font-size: 11px; padding: 5px 10px;">
                                <?= htmlspecialchars(strtoupper($f['area'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge-fill text-success fs-5"></i>
                                <span class="text-theme-primary small"><?= htmlspecialchars($f['instructores']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="text-theme-primary small" style="line-height: 1.4;">
                                <?= $f['jornada_resumen'] ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge-sena badge-on-time">
                                <i class="bi bi-people-fill"></i> <?= (int)$f['aprendices'] ?> aprendices
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-sena-outline me-1" 
                                    onclick="abrirModalHorarios(<?= (int)$f['id_ficha'] ?>, '<?= htmlspecialchars(addslashes($f['codigo']), ENT_QUOTES) ?> - <?= htmlspecialchars(addslashes($f['programa']), ENT_QUOTES) ?>')" 
                                    title="Gestionar Horarios">
                                <i class="bi bi-calendar-week"></i>
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=aprendices&ficha=<?= (int)$f['id_ficha'] ?>" class="btn btn-sm btn-sena-outline me-1" title="Ver Aprendices">
                                <i class="bi bi-people"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i> No se encontraron fichas registradas para esta área.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear Ficha -->
<div class="modal fade" id="modalFicha" tabindex="-1" aria-labelledby="modalFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="<?= BASE_URL ?>/index.php?page=fichas">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalFichaLabel">
                        <i class="bi bi-journal-plus text-success me-2"></i>Registrar Nueva Ficha Formativa
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Código o Número de Ficha *</label>
                            <input type="text" class="form-control" name="codigo" required placeholder="Ej. 2694521" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Programa de Formación *</label>
                            <input type="text" class="form-control" name="programa" required placeholder="Ej. Análisis y Desarrollo de Software" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>

                        <!-- Área Técnica -->
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Área Técnica *</label>
                            <?php if ($adminArea !== 'todas'): ?>
                                <input type="hidden" name="area" value="<?= htmlspecialchars($adminArea) ?>">
                                <input type="text" class="form-control" value="<?= strtoupper(htmlspecialchars($adminArea)) ?>" readonly style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <?php else: ?>
                                <select class="form-select" name="area" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="comercio">Comercio</option>
                                    <option value="Tics">TICs</option>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Nota informativa sobre Horarios -->
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="alert alert-info py-2 px-3 mb-0 w-100 d-flex align-items-center gap-2" style="font-size: 11.5px; background: rgba(57,169,0,0.1); border: 1px solid rgba(57,169,0,0.25); color: var(--nk-text-primary); border-radius: 8px;">
                                <i class="bi bi-calendar-check text-success fs-5"></i>
                                <div>Los bloques de horario (3h/6h) se asocian a la ficha por sesión con sus ambientes y competencias.</div>
                            </div>
                        </div>

                        <!-- Instructores Asignados (Relación N:M) -->
                        <div class="col-md-12">
                            <label class="form-label text-secondary small fw-bold">Instructores Asignados (Selección múltiple) *</label>
                            <select class="form-select" name="instructor_ids[]" multiple size="4" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <?php foreach ($instructores as $ins): ?>
                                    <option value="<?= $ins['id_instructor'] ?>">
                                        <?= htmlspecialchars($ins['nombre']) ?> &mdash; Área: <?= strtoupper($ins['area']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-secondary" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i>Mantén presionada la tecla <strong>Ctrl</strong> (o Cmd en Mac) para seleccionar más de un instructor.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green"><i class="bi bi-check-circle me-1"></i> Guardar Ficha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gestionar Horarios y Bloques -->
<div class="modal fade" id="modalGestionHorarios" tabindex="-1" aria-labelledby="modalGestionHorariosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                <h5 class="modal-title text-theme-primary fw-bold" id="modalGestionHorariosLabel">
                    <i class="bi bi-calendar-week text-success me-2"></i>Gestión de Horarios: <span id="modalHorarioFichaTitulo" class="text-success"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalHorariosAlerta" class="alert d-none py-2 px-3 mb-3" style="font-size: 13px;"></div>

                <!-- Bloques Existentes -->
                <div class="mb-4">
                    <h6 class="text-theme-primary fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-clock-history text-success"></i> Bloques de Formación Configurados
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0 align-middle" style="background-color: var(--nk-bg-inset); border-radius: 8px; overflow: hidden; font-size: 13px;">
                            <thead>
                                <tr class="text-secondary" style="border-bottom: 1px solid var(--nk-border-color);">
                                    <th>Día</th>
                                    <th>Horario</th>
                                    <th>Duración / Bloque</th>
                                    <th>Instructor Asignado</th>
                                    <th>Ambiente</th>
                                    <th>Competencia</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="listaBloquesHorario">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-secondary">
                                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div> Cargando bloques de horario...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr style="border-color: var(--nk-border-color);">

                <!-- Formulario Agregar Nuevo Bloque -->
                <div>
                    <h6 class="text-theme-primary fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle text-success"></i> Agregar Bloque de Horario
                    </h6>
                    <form id="formNuevoBloque">
                        <input type="hidden" name="id_ficha" id="horarioFichaId" value="">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">Día de la Semana *</label>
                                <select class="form-select form-select-sm" name="dias_semana" id="bloqueDiaSemana" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="Lunes">Lunes</option>
                                    <option value="Martes">Martes</option>
                                    <option value="Miercoles">Miércoles</option>
                                    <option value="Jueves">Jueves</option>
                                    <option value="Viernes">Viernes</option>
                                    <option value="Sabado">Sábado</option>
                                    <option value="Domingo">Domingo</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-secondary small fw-bold">Tipo de Bloque *</label>
                                <select class="form-select form-select-sm" name="tipo_bloque" id="bloqueTipo" required onchange="ajustarTipoBloque(this.value)" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="6H_CONTINUO" selected>6H Continuo (Jornada continua 6h)</option>
                                    <option value="3H_INDIVIDUAL">3H Individual (1 bloque de 3h)</option>
                                    <option value="6H_PARTICIONADO">6H Particionado (Doble sesión)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Duración *</label>
                                <select class="form-select form-select-sm" name="duracion_horas" id="bloqueDuracion" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="6" selected>6 Horas (Jornada Continua)</option>
                                    <option value="4">4 Horas (Nocturna 18:00 - 22:00)</option>
                                    <option value="3">3 Horas (Bloque Individual)</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Hora Entrada *</label>
                                <input type="time" class="form-control form-control-sm" name="hora_entrada" id="bloqueHoraEntrada" value="06:00" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-secondary small fw-bold">Hora Salida *</label>
                                <input type="time" class="form-control form-control-sm" name="hora_salida" id="bloqueHoraSalida" value="12:00" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Instructor Responsable</label>
                                <select class="form-select form-select-sm" name="id_instructor" id="bloqueInstructor" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                    <option value="">-- Sin instructor / Por asignar --</option>
                                    <?php foreach ($instructores as $ins): ?>
                                        <option value="<?= $ins['id_instructor'] ?>">
                                            <?= htmlspecialchars($ins['nombre']) ?> (<?= strtoupper($ins['area']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Ambiente de Formación</label>
                                <input type="text" class="form-control form-control-sm" name="ambiente" id="bloqueAmbiente" placeholder="Ej. Lab TIC 1, Aula 204" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-secondary small fw-bold">Competencia a Desarrollar</label>
                                <input type="text" class="form-control form-control-sm" name="competencia" id="bloqueCompetencia" placeholder="Ej. Desarrollar componentes de software" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            </div>
                            <div class="col-12 text-end mt-3">
                                <button type="submit" class="btn btn-sena-green btn-sm" id="btnGuardarBloque">
                                    <i class="bi bi-save me-1"></i> Guardar Bloque de Horario
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                <button type="button" class="btn btn-sena-outline btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar Horario SENA desde Excel -->
<div class="modal fade" id="modalImportarHorarioExcel" tabindex="-1" aria-labelledby="modalImportarHorarioExcelLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                <h5 class="modal-title text-theme-primary fw-bold" id="modalImportarHorarioExcelLabel">
                    <i class="bi bi-file-earmark-excel text-success me-2"></i>Importar Malla Horaria Oficial del SENA (.xlsx)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
            </div>
            
            <form id="formImportarExcel" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center gap-2" style="font-size: 12.5px; background: rgba(36, 181, 202, 0.1); border: 1px solid rgba(36, 181, 202, 0.25); color: var(--nk-text-primary); border-radius: 8px;">
                        <i class="bi bi-info-circle-fill text-info fs-5 flex-shrink-0"></i>
                        <div>
                            Sube la malla trimestral oficial en formato <strong>.xlsx</strong>. El sistema extraerá automáticamente el número de ficha, detectará los bloques continuos (6h) o individuales (3h), registrará o mapeará a los instructores y configurará sus ambientes y competencias.
                        </div>
                    </div>

                    <div id="alertaImportacionExcel" class="alert d-none py-2 px-3 mb-3" style="font-size: 13px;"></div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">
                                <i class="bi bi-calendar-event text-success me-1"></i>Fecha de Inicio de Formación (Corte) *
                            </label>
                            <input type="date" class="form-control" name="fecha_inicio_corte" id="inputFechaInicioCorte" value="<?= date('Y-m-d') ?>" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <div class="form-text text-secondary" style="font-size: 11px;">
                                Se importarán solo las sesiones desde esta fecha en adelante. Semanas anteriores se omitirán.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">
                                <i class="bi bi-file-earmark-excel text-success me-1"></i>Seleccionar archivo Excel (.xlsx) *
                            </label>
                            <input type="file" class="form-control" name="archivo_excel" id="inputArchivoExcel" accept=".xlsx, .xls" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                            <div class="form-text text-secondary" style="font-size: 11px;">
                                Plantilla oficial de programación horaria del SENA.
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Resultados (oculto inicialmente) -->
                    <div id="panelResultadosExcel" class="d-none mt-3 p-3 border rounded" style="background-color: var(--nk-bg-inset); border-color: var(--nk-border-color) !important;">
                        <h6 class="text-success fw-bold mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Importación Finalizada con Éxito
                        </h6>

                        <div class="alert alert-secondary py-1 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="font-size: 12px; background: rgba(255,255,255,0.04); border: 1px solid var(--nk-border-color); border-radius: 6px;">
                            <span><i class="bi bi-calendar-range text-info me-1"></i>Periodo detectado: <strong id="resPeriodo" class="text-light">-</strong></span>
                            <span><i class="bi bi-calendar-check text-success me-1"></i>Vigente desde: <strong id="resCorte" class="text-success">-</strong></span>
                            <span><i class="bi bi-clock-history text-warning me-1"></i>Semanas pasadas omitidas: <strong id="resOmitidas" class="text-warning">0</strong></span>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-4">
                                <div class="p-2 rounded border" style="background: rgba(57,169,0,0.1); border-color: rgba(57,169,0,0.3) !important;">
                                    <small class="text-secondary d-block">Ficha</small>
                                    <strong class="text-theme-primary fs-6" id="resFicha">-</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 rounded border" style="background: rgba(36,181,202,0.1); border-color: rgba(36,181,202,0.3) !important;">
                                    <small class="text-secondary d-block">Bloques Procesados</small>
                                    <strong class="text-info fs-6" id="resBloques">-</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-2 rounded border" style="background: rgba(243,156,18,0.1); border-color: rgba(243,156,18,0.3) !important;">
                                    <small class="text-secondary d-block">Instructores</small>
                                    <strong class="text-warning fs-6" id="resInstructores">-</strong>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                            <table class="table table-dark table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                                <thead>
                                    <tr class="text-secondary">
                                        <th>Día</th>
                                        <th>Horario</th>
                                        <th>Bloque</th>
                                        <th>Instructor</th>
                                        <th>Ambiente</th>
                                    </tr>
                                </thead>
                                <tbody id="resListaBloques"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline btn-sm" data-bs-dismiss="modal" id="btnCerrarModalExcel">Cerrar</button>
                    <button type="submit" class="btn btn-sena-green btn-sm" id="btnProcesarExcel">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Iniciar Importación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var horarioFichaActual = null;
var huboCambiosHorario = false;

function ajustarTipoBloque(tipo) {
    var duracionSel = document.getElementById('bloqueDuracion');
    var entrada = document.getElementById('bloqueHoraEntrada');
    var salida = document.getElementById('bloqueHoraSalida');

    if (tipo === '3H_INDIVIDUAL') {
        duracionSel.value = '3';
        if (entrada.value === '06:00') salida.value = '09:00';
        else if (entrada.value === '12:00') salida.value = '15:00';
    } else {
        duracionSel.value = '6';
        if (entrada.value === '06:00') salida.value = '12:00';
        else if (entrada.value === '12:00') salida.value = '18:00';
    }
}

function abrirModalHorarios(idFicha, titulo) {
    horarioFichaActual = idFicha;
    huboCambiosHorario = false;
    document.getElementById('modalHorarioFichaTitulo').textContent = titulo;
    document.getElementById('horarioFichaId').value = idFicha;
    
    var alertBox = document.getElementById('modalHorariosAlerta');
    alertBox.className = 'alert d-none py-2 px-3 mb-3';
    alertBox.textContent = '';

    var modalEl = document.getElementById('modalGestionHorarios');
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();

    cargarBloques(idFicha);
}

function cargarBloques(idFicha) {
    var tbody = document.getElementById('listaBloquesHorario');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary"><div class="spinner-border spinner-border-sm text-success me-2" role="status"></div> Cargando bloques...</td></tr>';

    fetch('<?= BASE_URL ?>/index.php?page=fichas&action=obtener_bloques&id_ficha=' + encodeURIComponent(idFicha))
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (!data.success) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">' + (data.error || 'Error al cargar bloques.') + '</td></tr>';
                return;
            }

            if (!data.bloques || data.bloques.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-3"><i class="bi bi-calendar-x me-1"></i> No hay bloques de horario configurados para esta ficha.</td></tr>';
                return;
            }

            var html = '';
            data.bloques.forEach(function(b) {
                var badgeTipo = '<span class="badge bg-secondary" style="font-size: 11px;">3h Bloque</span>';
                if (b.duracion_horas == 6 || b.tipo_bloque === '6H_CONTINUO') {
                    badgeTipo = '<span class="badge bg-success" style="font-size: 11px;">6h Continua</span>';
                } else if (b.duracion_horas == 4 || b.tipo_bloque === '4H_NOCTURNA') {
                    badgeTipo = '<span class="badge bg-info text-dark" style="font-size: 11px;">4h Nocturna</span>';
                }
                
                html += '<tr>' +
                    '<td><strong class="text-theme-primary">' + escapeHtml(b.dias_semana) + '</strong></td>' +
                    '<td><span class="text-theme-primary fw-semibold">' + escapeHtml(b.entrada) + ' - ' + escapeHtml(b.salida) + '</span></td>' +
                    '<td>' + badgeTipo + '</td>' +
                    '<td><i class="bi bi-person-badge text-success me-1"></i>' + escapeHtml(b.instructor_nombre) + '</td>' +
                    '<td><span class="badge bg-dark border text-light">' + escapeHtml(b.ambiente) + '</span></td>' +
                    '<td><small class="text-secondary">' + escapeHtml(b.competencia) + '</small></td>' +
                    '<td class="text-end">' +
                        '<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarBloqueHorario(' + b.id_horario + ')" title="Eliminar este bloque">' +
                            '<i class="bi bi-trash"></i>' +
                        '</button>' +
                    '</td>' +
                '</tr>';
            });
            tbody.innerHTML = html;
        })
        .catch(function(err) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Error de comunicación con el servidor.</td></tr>';
        });
}

function eliminarBloqueHorario(idHorario) {
    if (!confirm('¿Confirma que desea eliminar este bloque de horario?')) return;

    var fd = new FormData();
    fd.append('id_horario', idHorario);

    fetch('<?= BASE_URL ?>/index.php?page=fichas&action=eliminar_bloque', {
        method: 'POST',
        body: fd
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var alertBox = document.getElementById('modalHorariosAlerta');
        if (data.success) {
            alertBox.className = 'alert alert-success py-2 px-3 mb-3';
            alertBox.textContent = data.mensaje;
            alertBox.classList.remove('d-none');
            huboCambiosHorario = true;
            cargarBloques(horarioFichaActual);
        } else {
            alertBox.className = 'alert alert-danger py-2 px-3 mb-3';
            alertBox.textContent = data.error || 'No se pudo eliminar el bloque.';
            alertBox.classList.remove('d-none');
        }
    })
    .catch(function() {
        alert('Error al intentar eliminar el bloque.');
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorFichas');
    var tabla = document.getElementById('tablaFichas');
    if (tabla && buscador) {
        var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        buscador.addEventListener('input', function() {
            var texto = this.value.toLowerCase();
            for (var i = 0; i < filas.length; i++) {
                var fila = filas[i];
                if (fila.cells.length < 5) continue;
                var contenido = fila.textContent.toLowerCase();
                fila.style.display = (!texto || contenido.indexOf(texto) > -1) ? '' : 'none';
            }
        });
    }

    var horaEnt = document.getElementById('bloqueHoraEntrada');
    var horaSal = document.getElementById('bloqueHoraSalida');
    var durSelect = document.getElementById('bloqueDuracion');

    function validarHorasBloque() {
        if (!horaEnt || !horaSal || !durSelect) return true;
        var pEnt = (horaEnt.value || '').split(':').map(Number);
        var pSal = (horaSal.value || '').split(':').map(Number);
        if (pEnt.length === 2 && pSal.length === 2) {
            var diff = (pSal[0] + pSal[1]/60) - (pEnt[0] + pEnt[1]/60);
            if (diff > 6) {
                alert('Límite de jornada excedido: La duración máxima permitida para un bloque continuo es de 6 horas. Ajustando hora de salida.');
                var maxH = Math.min(23, pEnt[0] + 6);
                horaSal.value = (maxH < 10 ? '0' : '') + maxH + ':' + (pEnt[1] < 10 ? '0' : '') + pEnt[1];
                durSelect.value = '6';
                return true;
            }
            if (diff === 6) durSelect.value = '6';
            else if (diff === 4 && pEnt[0] >= 18) durSelect.value = '4';
            else if (diff === 3) durSelect.value = '3';
        }
        return true;
    }

    if (horaEnt) horaEnt.addEventListener('change', validarHorasBloque);
    if (horaSal) horaSal.addEventListener('change', validarHorasBloque);

    var formNuevoBloque = document.getElementById('formNuevoBloque');
    if (formNuevoBloque) {
        formNuevoBloque.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btnGuardarBloque');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

            var fd = new FormData(formNuevoBloque);

            fetch('<?= BASE_URL ?>/index.php?page=fichas&action=guardar_bloque', {
                method: 'POST',
                body: fd
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Bloque de Horario';

                var alertBox = document.getElementById('modalHorariosAlerta');
                if (data.success) {
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3';
                    alertBox.textContent = data.mensaje;
                    alertBox.classList.remove('d-none');
                    huboCambiosHorario = true;
                    // Limpiar campos secundarios pero mantener ficha y día
                    document.getElementById('bloqueAmbiente').value = '';
                    document.getElementById('bloqueCompetencia').value = '';
                    cargarBloques(horarioFichaActual);
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3';
                    alertBox.textContent = data.error || 'Error al guardar el bloque.';
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-save me-1"></i> Guardar Bloque de Horario';
                alert('Error al enviar los datos del bloque.');
            });
        });
    }

    var modalEl = document.getElementById('modalGestionHorarios');
    if (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function() {
            if (huboCambiosHorario) {
                window.location.reload();
            }
        });
    }

    // Manejo de la importación masiva de horarios SENA (Excel)
    var formImportarExcel = document.getElementById('formImportarExcel');
    var huboImportacionExitosa = false;

    if (formImportarExcel) {
        formImportarExcel.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = document.getElementById('btnProcesarExcel');
            var alertBox = document.getElementById('alertaImportacionExcel');
            var panelRes = document.getElementById('panelResultadosExcel');

            alertBox.className = 'alert d-none py-2 px-3 mb-3';
            alertBox.textContent = '';
            panelRes.classList.add('d-none');

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Procesando malla oficial...';

            var fd = new FormData(formImportarExcel);

            fetch('<?= BASE_URL ?>/index.php?page=fichas&action=importar_excel_horarios', {
                method: 'POST',
                body: fd
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Iniciar Importación';

                function formatFechaCol(str) {
                    if (!str) return '-';
                    var p = str.split('-');
                    return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : str;
                }

                if (data.success) {
                    huboImportacionExitosa = true;
                    alertBox.className = 'alert alert-success py-2 px-3 mb-3 d-flex align-items-center gap-2';
                    alertBox.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-4 flex-shrink-0"></i><div><strong>' + escapeHtml(data.mensaje) + '</strong><br><small style="opacity: 0.9;">Vigente desde: ' + escapeHtml(formatFechaCol(data.fecha_corte_aplicada)) + ' (' + (data.semanas_procesadas || 0) + ' semanas procesadas, ' + (data.semanas_omitidas_pasadas || 0) + ' semanas omitidas por fecha de inicio).</small></div>';
                    alertBox.classList.remove('d-none');

                    // Rellenar tarjetas de estadísticas y fechas
                    document.getElementById('resPeriodo').textContent = (data.fecha_min_detectada ? formatFechaCol(data.fecha_min_detectada) : '-') + ' al ' + (data.fecha_max_detectada ? formatFechaCol(data.fecha_max_detectada) : '-');
                    document.getElementById('resCorte').textContent = data.fecha_corte_aplicada ? formatFechaCol(data.fecha_corte_aplicada) : '-';
                    document.getElementById('resOmitidas').textContent = (data.semanas_omitidas_pasadas || 0) + ' sem.';
                    document.getElementById('resFicha').textContent = data.numero_ficha + ' (' + data.area.toUpperCase() + ')';
                    document.getElementById('resBloques').textContent = data.total_bloques + ' (' + data.nuevos_insertados + ' nuevos, ' + data.actualizados + ' act.)';
                    document.getElementById('resInstructores').textContent = data.total_instructores + ' docentes';

                    // Rellenar tabla de bloques
                    var tbody = document.getElementById('resListaBloques');
                    var html = '';
                    if (data.bloques && data.bloques.length > 0) {
                        data.bloques.forEach(function(b) {
                            var badgeTipo = b.duracion === '6h' 
                                ? '<span class="badge bg-success">6h Continua</span>' 
                                : '<span class="badge bg-secondary">3h Bloque</span>';

                            html += '<tr>' +
                                '<td><strong>' + escapeHtml(b.dia) + '</strong></td>' +
                                '<td>' + escapeHtml(b.hora_entrada) + ' - ' + escapeHtml(b.hora_salida) + '</td>' +
                                '<td>' + badgeTipo + '</td>' +
                                '<td>' + escapeHtml(b.instructor) + '</td>' +
                                '<td><span class="badge bg-dark border text-light">' + escapeHtml(b.ambiente) + '</span></td>' +
                            '</tr>';
                        });
                    }
                    tbody.innerHTML = html;
                    panelRes.classList.remove('d-none');

                    // Ajustar botón cerrar para indicar recarga
                    var btnCerrar = document.getElementById('btnCerrarModalExcel');
                    btnCerrar.textContent = 'Finalizar y Actualizar';
                    btnCerrar.className = 'btn btn-sena-green btn-sm';
                    btn.classList.add('d-none');
                } else {
                    alertBox.className = 'alert alert-danger py-2 px-3 mb-3 d-flex align-items-start gap-2';
                    alertBox.innerHTML = '<i class="bi bi-calendar-x text-danger fs-4 flex-shrink-0 mt-1"></i><div><strong>No se pudo importar la malla:</strong><div class="mt-1" style="font-size: 12.5px;">' + escapeHtml(data.error || 'Ocurrió un error al procesar el archivo.') + '</div></div>';
                    alertBox.classList.remove('d-none');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Iniciar Importación';
                alertBox.className = 'alert alert-danger py-2 px-3 mb-3 d-flex align-items-center gap-2';
                alertBox.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-danger fs-4 flex-shrink-0"></i><div>Error de comunicación con el servidor al subir el archivo.</div>';
                alertBox.classList.remove('d-none');
            });
        });
    }

    var modalExcelEl = document.getElementById('modalImportarHorarioExcel');
    if (modalExcelEl) {
        modalExcelEl.addEventListener('hidden.bs.modal', function() {
            if (huboImportacionExitosa) {
                window.location.reload();
            }
        });
    }
});
</script>

<?php require_once BASE_PATH . '/Views/layouts/footer.php'; ?>
