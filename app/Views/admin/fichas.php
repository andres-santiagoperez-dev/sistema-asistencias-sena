<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

$fichas = [];
$instructores = [];

try {
    // 1. Consulta de Fichas con conteo real de aprendices
    $stmt = $pdo->query("
        SELECT 
            f.id_ficha,
            f.numero_ficha as codigo,
            f.programa,
            CONCAT(u.nombres, ' ', u.apellidos) as instructor,
            TIME_FORMAT(h.hora_entrada, '%h:%i %p') as entrada,
            TIME_FORMAT(h.hora_salida, '%h:%i %p') as salida,
            COUNT(a.id_aprendiz) as aprendices
        FROM fichas f
        LEFT JOIN instructores i ON f.Instructores_id_instructor1 = i.id_instructor
        LEFT JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
        LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
        LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
        GROUP BY f.id_ficha, f.numero_ficha, f.programa, u.nombres, u.apellidos, h.hora_entrada, h.hora_salida
        ORDER BY f.numero_ficha
    ");
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Instructores para el selector del modal
    $stmtIns = $pdo->query("
        SELECT i.id_instructor, CONCAT(u.nombres, ' ', u.apellidos) as nombre
        FROM instructores i
        JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
        ORDER BY u.apellidos
    ");
    $instructores = $stmtIns->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Fallback
}

if (empty($fichas)) {
    $fichas = [
        ['id_ficha' => 1, 'codigo' => '2670123', 'programa' => 'Análisis y Desarrollo de Software', 'instructor' => 'Juan Camilo Vanegas', 'entrada' => '06:00 AM', 'salida' => '12:00 PM', 'aprendices' => 32],
        ['id_ficha' => 2, 'codigo' => '2670456', 'programa' => 'Programación de Software',          'instructor' => 'Diana Marcela Ríos',  'entrada' => '01:00 PM', 'salida' => '06:00 PM', 'aprendices' => 28],
        ['id_ficha' => 3, 'codigo' => '2670789', 'programa' => 'Contabilidad y Finanzas',           'instructor' => 'Juan Camilo Vanegas', 'entrada' => '06:00 AM', 'salida' => '12:00 PM', 'aprendices' => 25],
    ];
}

$tituloPagina = 'Fichas y Horarios';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 181, 202, 0.15); color: #24b5ca;">
                <i class="bi bi-journal-bookmark-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Gestión de Fichas y Horarios</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Administración de programas formativos, asignación de instructores líderes y jornadas.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
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
            <p class="text-secondary mb-0" style="font-size:13px;">Listado maestro con conteo en vivo de aprendices por grupo formativo</p>
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
                    <th>Instructor a Cargo</th>
                    <th>Horario de Formación</th>
                    <th>Aprendices</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($fichas as $f): ?>
                <tr>
                    <td><strong class="text-theme-primary fs-6"><?= htmlspecialchars($f['codigo']) ?></strong></td>
                    <td>
                        <span class="fw-semibold text-theme-primary"><?= htmlspecialchars($f['programa']) ?></span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($f['instructor'] ?? 'Instructor') ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid #39a900;">
                            <span class="text-theme-primary"><?= htmlspecialchars($f['instructor'] ?? 'Sin Asignar') ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="text-info"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($f['entrada'] ?? '06:00 AM') ?> &ndash; <?= htmlspecialchars($f['salida'] ?? '12:00 PM') ?></span>
                    </td>
                    <td>
                        <span class="badge-sena badge-on-time">
                            <i class="bi bi-people-fill"></i> <?= (int)$f['aprendices'] ?> aprendices
                        </span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-sena-outline me-1" data-bs-toggle="modal" data-bs-target="#modalFicha" title="Editar Ficha">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="aprendices.php?ficha=<?= urlencode($f['codigo']) ?>" class="btn btn-sm btn-sena-outline me-1" title="Ver Aprendices">
                            <i class="bi bi-people"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar Ficha" onclick="return confirm('¿Confirmas eliminar esta ficha?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear / Editar Ficha -->
<div class="modal fade" id="modalFicha" tabindex="-1" aria-labelledby="modalFichaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="#">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalFichaLabel">
                        <i class="bi bi-journal-plus text-success me-2"></i>Gestión de Ficha Formatíva
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Código o Número de Ficha *</label>
                            <input type="text" class="form-control" name="codigo" required placeholder="Ej. 2670123" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Programa de Formación *</label>
                            <input type="text" class="form-control" name="programa" required placeholder="Ej. Análisis y Desarrollo de Software" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-secondary small fw-bold">Instructor Titular a Cargo *</label>
                            <select class="form-select" name="instructor_id" required style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="">Selecciona un instructor de la lista...</option>
                                <?php if (!empty($instructores)): ?>
                                    <?php foreach ($instructores as $ins): ?>
                                        <option value="<?= $ins['id_instructor'] ?>"><?= htmlspecialchars($ins['nombre']) ?></option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option>Juan Camilo Vanegas</option>
                                    <option>Diana Marcela Ríos</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Hora de Entrada *</label>
                            <input type="time" class="form-control" name="hora_entrada" required value="06:00" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Hora de Salida *</label>
                            <input type="time" class="form-control" name="hora_salida" required value="12:00" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-secondary small fw-bold">Jornada Académica</label>
                            <select class="form-select" name="jornada" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option>Diurna / Mañana (Lunes a Viernes)</option>
                                <option>Tarde (Lunes a Viernes)</option>
                                <option>Nocturna</option>
                                <option>Mixta / Fines de Semana</option>
                            </select>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorFichas');
    var tabla = document.getElementById('tablaFichas');
    if (!tabla || !buscador) return;
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
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

