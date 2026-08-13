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

<div class="white-box">
    <div class="card-title-row">
        <h3>Aprendices inscritos</h3>
        <div class="d-flex gap-2 flex-wrap">
            <input type="text" class="form-control form-control-sm" style="width:220px;" placeholder="Buscar por nombre o documento...">
            <select class="form-select form-select-sm" style="width:200px;">
                <option value="">Todas las fichas</option>
                <?php foreach ($fichas as $ficha): ?>
                    <option><?= htmlspecialchars($ficha) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-sena btn-sm" data-bs-toggle="modal" data-bs-target="#modalAprendiz">
                <i class="bi bi-plus-lg me-1"></i> Nuevo aprendiz
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                    <td colspan="6" class="text-center sena-empty-state py-4">Aún no hay aprendices registrados.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($aprendices as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($a['documento']) ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img class="avatar-sm" src="https://ui-avatars.com/api/?background=random&name=<?= urlencode($a['nombre']) ?>" alt="">
                            <?= htmlspecialchars($a['nombre']) ?>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($a['ficha']) ?></td>
                    <td><?= htmlspecialchars($a['correo']) ?></td>
                    <td><span class="estado-badge estado-<?= $a['estado'] ?>"><?= ucfirst($a['estado']) ?></span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAprendiz" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" title="Historial de asistencia">
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
        <div class="modal-content">
            <!-- TODO: apuntar a POST /admin/aprendices (crear) o PUT /admin/aprendices/{id} (editar) -->
            <form method="post" action="#">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo aprendiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de documento</label>
                            <select class="form-select" name="tipo_documento">
                                <option>Cédula de Ciudadanía</option>
                                <option>Tarjeta de Identidad</option>
                                <option>Cédula de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número de documento</label>
                            <input type="text" class="form-control" name="documento" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control" name="nombres" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control" name="apellidos" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" name="correo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Código llavero RFID</label>
                            <input type="text" class="form-control" name="rfid" placeholder="Ej. RF-00231">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Ficha</label>
                            <select class="form-select" name="ficha_id">
                                <?php foreach ($fichas as $ficha): ?>
                                    <option><?= htmlspecialchars($ficha) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="Matriculado">Matriculado</option>
                                <option value="Retirado">Retirado</option>
                                <option value="Aplazado">Aplazado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Contraseña temporal</label>
                            <input type="password" class="form-control" name="password" placeholder="Se solicitará cambio en el primer ingreso">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena">Guardar aprendiz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>