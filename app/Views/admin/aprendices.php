<?php
/**
 * @var array $aprendices
 * @var array $fichas
 */

require_once __DIR__ . '/../../config/database.php';
$pdo = Database::conectar();

$aprendices = [];
$fichas     = [];

try {
    $stmt = $pdo->query("
        SELECT u.numero_documento              AS documento,
               CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
               CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha,
               u.correo                        AS correo,
               a.estado_matricula              AS estadoñ
        FROM aprendices a
        INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
        INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
        ORDER BY u.apellidos, u.nombres
    ");
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalizamos el estado a minúsculas para que calce con las clases CSS (estado-matriculado, estado-retirado, estado-aplazado)
    foreach ($aprendices as &$a) {
        $a['estado'] = mb_strtolower($a['estado']);
    }
    unset($a);
} catch (PDOException $e) {
    $aprendices = [];
}

try {
    $stmt = $pdo->query("SELECT CONCAT(numero_ficha, ' - ', programa) AS ficha FROM fichas ORDER BY numero_ficha");
    $fichas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $fichas = [];
}

$tituloPagina = 'Aprendices';
require __DIR__ . '/../layouts/header.php';
?>

<!-- Welcome Banner -->
<div class="sena-welcome-banner mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="p-3 rounded-circle" style="background: rgba(36, 202, 161, 0.15); color: #24caa1;">
                <i class="bi bi-people-fill fs-2"></i>
            </div>
            <div>
                <h3 class="text-theme-primary fw-bold mb-1">Administración de Aprendices</h3>
                <p class="text-secondary mb-0" style="font-size:14px;">Directorio maestro de aprendices matriculados, asignación de fichas y llaveros RFID.</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sena-green" data-bs-toggle="modal" data-bs-target="#modalAprendiz">
                <i class="bi bi-person-plus-fill me-1"></i> Nuevo Aprendiz
            </button>
        </div>
    </div>
</div>

<div class="card border-0 card-stat-ux p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h5 class="text-theme-primary fw-bold mb-1"><i class="bi bi-list-stars me-2 text-info"></i>Listado General de Aprendices</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Búsqueda por nombre, documento de identidad o filtrado por ficha formativa</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="width: 240px;">
                <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--nk-border-color); color: var(--nk-text-muted);">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="buscadorAprendicesAdmin" class="form-control form-control-sm border-start-0" placeholder="Buscar aprendiz..." style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
            </div>
            <select id="filtroFichaAdmin" class="form-select form-select-sm" style="width:220px; background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichas as $ficha): ?>
                    <option value="<?= htmlspecialchars($ficha) ?>"><?= htmlspecialchars($ficha) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tablaAprendicesAdmin" class="table table-nalika-dark table-hover w-100 align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre Completo</th>
                    <th>Ficha Asignada</th>
                    <th>Correo Electrónico</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="6" class="text-center text-secondary py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        Aún no hay aprendices registrados en la base de datos.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): ?>
                <tr>
                    <td>
                        <span class="badge bg-dark text-secondary border border-secondary border-opacity-25 px-2.5 py-1.5 font-monospace" style="font-size: 12px;">
                            <?= htmlspecialchars($a['documento']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2.5">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="" style="width: 36px; height: 36px; border-radius: 50%; border: 2px solid #39a900;">
                            <strong class="text-theme-primary"><?= htmlspecialchars($a['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><span class="text-theme-primary fw-medium"><?= htmlspecialchars($a['ficha']) ?></span></td>
                    <td><span class="text-secondary small"><?= htmlspecialchars($a['correo']) ?></span></td>
                    <td>
                        <?php if ($a['estado'] == 'matriculado'): ?>
                            <span class="badge-sena badge-on-time"><i class="bi bi-check-circle-fill"></i> Matriculado</span>
                        <?php elseif ($a['estado'] == 'aplazado'): ?>
                            <span class="badge-sena badge-late"><i class="bi bi-pause-circle-fill"></i> Aplazado</span>
                        <?php else: ?>
                            <span class="badge-sena badge-absent"><i class="bi bi-x-circle-fill"></i> <?= ucfirst($a['estado']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-sena-outline me-1" data-bs-toggle="modal" data-bs-target="#modalAprendiz" title="Editar datos">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="ingresos.php?ficha=<?= urlencode($a['ficha']) ?>" class="btn btn-sm btn-sena-outline me-1" title="Ver Asistencia">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar Aprendiz" onclick="return confirm('¿Estás seguro de eliminar este aprendiz?')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Crear / Editar Aprendiz -->
<div class="modal fade" id="modalAprendiz" tabindex="-1" aria-labelledby="modalAprendizLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content modal-content-nalika border" style="background-color: var(--nk-card-bg); border-color: var(--nk-border-color) !important; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <form method="post" action="#">
                <div class="modal-header border-bottom" style="border-color: var(--nk-border-color) !important;">
                    <h5 class="modal-title text-theme-primary fw-bold" id="modalAprendizLabel">
                        <i class="bi bi-person-plus-fill text-success me-2"></i>Gestión de Aprendiz
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1) grayscale(100%) brightness(200%);"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option>Cédula de Ciudadanía</option>
                                <option>Tarjeta de Identidad</option>
                                <option>Cédula de Extranjería</option>
                                <option>Permiso Especial de Permanencia</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Número de Documento *</label>
                            <input type="text" class="form-control" name="documento" required placeholder="Ej. 1020304050" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Nombres *</label>
                            <input type="text" class="form-control" name="nombres" required placeholder="Nombres del aprendiz" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold">Apellidos *</label>
                            <input type="text" class="form-control" name="apellidos" required placeholder="Apellidos del aprendiz" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary small fw-bold">Correo Electrónico (Institucional o Personal) *</label>
                            <input type="email" class="form-control" name="correo" required placeholder="ejemplo@soy.sena.edu.co" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">Código Llavero RFID</label>
                            <input type="text" class="form-control" name="rfid" placeholder="Ej. RFID-88421" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary small fw-bold">Ficha y Programa Asignado *</label>
                            <select class="form-select" name="ficha_id" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <?php foreach ($fichas as $ficha): ?>
                                    <option><?= htmlspecialchars($ficha) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small fw-bold">Estado Matrícula *</label>
                            <select class="form-select" name="estado" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                                <option value="Matriculado">Matriculado</option>
                                <option value="Retirado">Retirado</option>
                                <option value="Aplazado">Aplazado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold">Contraseña Temporal de Acceso</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio obligatorio en su primer inicio de sesión" style="background-color: var(--nk-bg-inset); color: var(--nk-text-primary); border-color: var(--nk-border-color);">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top" style="border-color: var(--nk-border-color) !important;">
                    <button type="button" class="btn btn-sena-outline" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green"><i class="bi bi-check2-circle me-1"></i> Guardar Aprendiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var buscador = document.getElementById('buscadorAprendicesAdmin');
    var filtroFicha = document.getElementById('filtroFichaAdmin');
    var tabla = document.getElementById('tablaAprendicesAdmin');
    if (!tabla) return;
    var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    function filtrar() {
        var texto = (buscador ? buscador.value : '').toLowerCase();
        var fichaSel = (filtroFicha ? filtroFicha.value : '').toLowerCase();

        for (var i = 0; i < filas.length; i++) {
            var fila = filas[i];
            if (fila.cells.length < 6) continue;
            var contenidoFila = fila.textContent.toLowerCase();
            var fichaFila = fila.cells[2].textContent.toLowerCase();

            var coincideTexto = !texto || contenidoFila.indexOf(texto) > -1;
            var coincideFicha = !fichaSel || fichaFila.indexOf(fichaSel) > -1;

            if (coincideTexto && coincideFicha) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        }
    }

    if (buscador) buscador.addEventListener('input', filtrar);
    if (filtroFicha) filtroFicha.addEventListener('change', filtrar);
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

