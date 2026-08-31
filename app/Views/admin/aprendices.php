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
               a.estado_matricula              AS estado
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

<div class="sena-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="sena-title mb-1">Aprendices inscritos</h5>
            <p class="text-secondary mb-0" style="font-size:13px;">Gestión de aprendices en el sistema</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" style="width:220px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);" placeholder="Buscar por nombre o documento...">
            <select class="form-select form-select-sm" style="width:200px; background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichas as $ficha): ?>
                    <option><?= htmlspecialchars($ficha) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="sena-btn btn-sm" data-bs-toggle="modal" data-bs-target="#modalAprendiz">
                <i class="bi bi-plus-lg me-1"></i> Nuevo aprendiz
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sena-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre completo</th>
                    <th>Ficha</th>
                    <th>Correo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($aprendices)): ?>
                <tr>
                    <td colspan="6" class="text-center sena-empty-state py-5">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2 d-block"></i>
                        Aún no hay aprendices registrados.
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['documento']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="" style="width: 32px; height: 32px; border-radius: 50%;">
                            <strong><?= htmlspecialchars($a['nombre']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($a['ficha']) ?></td>
                    <td><?= htmlspecialchars($a['correo']) ?></td>
                    <td>
                        <?php 
                        $badgeClass = 'bg-secondary';
                        if ($a['estado'] == 'matriculado') $badgeClass = 'bg-success';
                        else if ($a['estado'] == 'aplazado') $badgeClass = 'bg-warning text-dark';
                        else if ($a['estado'] == 'retirado') $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge rounded-pill <?= $badgeClass ?>"><?= ucfirst($a['estado']) ?></span>
                    </td>
                    <td class="text-end">
                        <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAprendiz" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="sena-btn sena-btn-outline-primary btn-sm" title="Historial de asistencia">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        <!-- TODO: confirmar y enviar DELETE /admin/aprendices/{id} -->
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal crear / editar aprendiz -->
<div class="modal fade" id="modalAprendiz" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: var(--sena-card-bg); color: var(--text-main); border: 1px solid var(--border-color);">
            <!-- TODO: apuntar a POST /admin/aprendices (crear) o PUT /admin/aprendices/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title sena-title mb-0">Nuevo aprendiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Tipo de documento</label>
                            <select class="form-select" name="tipo_documento" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option>Cédula de Ciudadanía</option>
                                <option>Tarjeta de Identidad</option>
                                <option>Cédula de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Número de documento</label>
                            <input type="text" class="form-control" name="documento" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Nombres</label>
                            <input type="text" class="form-control" name="nombres" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary" style="font-size:13px;">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary" style="font-size:13px;">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" required style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary" style="font-size:13px;">Código llavero RFID</label>
                            <input type="text" class="form-control" name="rfid" placeholder="Ej. RF-00231" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-secondary" style="font-size:13px;">Ficha</label>
                            <select class="form-select" name="ficha_id" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <?php foreach ($fichas as $ficha): ?>
                                    <option><?= htmlspecialchars($ficha) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary" style="font-size:13px;">Estado</label>
                            <select class="form-select" name="estado" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                                <option value="Matriculado">Matriculado</option>
                                <option value="Retirado">Retirado</option>
                                <option value="Aplazado">Aplazado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-secondary" style="font-size:13px;">Contraseña temporal</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio en el primer ingreso" style="background-color: var(--input-bg); color: var(--text-main); border-color: var(--border-color);">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="sena-btn">Guardar aprendiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
