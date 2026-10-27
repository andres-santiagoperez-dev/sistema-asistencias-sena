<?php
/**
 * Vista: Mi Perfil & Ficha (Aprendiz)
 * Sistema de Control de Asistencia SENA
 * 
 * Pura presentación MVC: consume variables entregadas por PerfilController
 */

if (!defined('BASE_PATH')) {
    header('Location: ../../../index.php?page=aprendiz_perfil');
    exit();
}

$tituloPagina     = 'Mi Perfil & Ficha';
$numeroFicha      = $numeroFicha ?? ($perfil['numero_ficha'] ?? 'Sin Ficha');
$programa         = $programa ?? ($perfil['programa'] ?? 'ADSO');
$codigoRfid       = $codigoRfid ?? ($perfil['codigo_rfid'] ?? 'Sin Asignar');
$horarioStr       = $horarioStr ?? '06:00 - 12:00';
$instructorNombre = $instructorNombre ?? 'Sin Asignar';
$instructorCorreo = $instructorCorreo ?? 'No disponible';
$mensajeExito     = $mensajeExito ?? '';
$mensajeError     = $mensajeError ?? '';
$perfil           = $perfil ?? [];

require_once dirname(__DIR__) . '/layouts/header-aprendiz.php';
?>

<style>
    .profile-hero {
        background: linear-gradient(135deg, var(--nk-card-header) 0%, var(--nk-card-bg) 100%);
        border-radius: 15px;
        padding: 40px 20px;
        position: relative;
        overflow: hidden;
        border: 1px solid var(--nk-border-color);
    }
    .profile-hero::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(36, 181, 202, 0.1) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #24caa1;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        margin-bottom: 15px;
    }
</style>

<!-- Hero Section -->
<div class="profile-hero text-center mb-4">
    <img src="https://ui-avatars.com/api/?background=24caa1&color=fff&size=150&name=<?= urlencode($_SESSION['nombre_completo'] ?? 'Aprendiz SENA') ?>" alt="Foto Aprendiz" class="profile-avatar">
    <h2 class="text-theme-primary fw-bold mb-1"><?= htmlspecialchars($_SESSION['nombre_completo'] ?? 'Nombre del Aprendiz') ?></h2>
    <h6 class="text-info mb-3">Aprendiz Activo SENA</h6>
    <div class="d-flex justify-content-center gap-3">
        <span class="badge bg-success px-3 py-2" style="font-size:13px;"><i class="bi bi-upc-scan me-1"></i>RFID: <?= htmlspecialchars($codigoRfid) ?></span>
        <span class="badge" style="background:var(--nk-border-color); border:1px solid #314a73; font-size:13px;"><i class="bi bi-bookmark-star me-1"></i>Ficha: <?= htmlspecialchars($numeroFicha) ?></span>
    </div>
</div>

<!-- Alertas de Retroalimentación -->
<?php if (!empty($mensajeExito)): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($mensajeExito) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($mensajeError)): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 mb-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($mensajeError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-5">
    <!-- Información Personal -->
    <div class="col-lg-6">
        <div class="card border-0 card-stat-ux h-100 shadow-sm">
            <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h5 class="text-theme-primary mb-0"><i class="bi bi-person-lines-fill me-2 text-success"></i>Información Personal</h5>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="bi bi-pencil-square me-1"></i>Editar Contacto
                </button>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-success"><i class="bi bi-card-text"></i></div>
                            <div>
                                <small class="text-secondary d-block">Documento de Identidad</small>
                                <strong class="text-theme-primary">
                                    <?= htmlspecialchars($perfil['tipo_documento'] ?? 'CC') ?> <?= htmlspecialchars($perfil['documento'] ?? ($perfil['numero_documento'] ?? 'Sin Documento')) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-primary"><i class="bi bi-person"></i></div>
                            <div>
                                <small class="text-secondary d-block">Nombres</small>
                                <strong class="text-theme-primary"><?= htmlspecialchars($perfil['nombres'] ?? '') ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-primary"><i class="bi bi-person-badge"></i></div>
                            <div>
                                <small class="text-secondary d-block">Apellidos</small>
                                <strong class="text-theme-primary"><?= htmlspecialchars($perfil['apellidos'] ?? '') ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-warning"><i class="bi bi-telephone"></i></div>
                            <div>
                                <small class="text-secondary d-block">Teléfono / Celular</small>
                                <strong class="text-theme-primary"><?= !empty($perfil['telefono']) ? htmlspecialchars($perfil['telefono']) : 'No registrado' ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-info"><i class="bi bi-envelope-at"></i></div>
                            <div>
                                <small class="text-secondary d-block">Correo Electrónico</small>
                                <strong class="text-theme-primary" style="word-break: break-all;"><?= htmlspecialchars($perfil['correo'] ?? 'Sin correo') ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-danger"><i class="bi bi-upc-scan"></i></div>
                            <div>
                                <small class="text-secondary d-block">Código RFID Asignado</small>
                                <strong class="text-theme-primary"><?= htmlspecialchars($codigoRfid) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Formación -->
    <div class="col-lg-6">
        <div class="card border-0 card-stat-ux h-100 shadow-sm">
            <div class="card-header border-bottom border-secondary border-opacity-25 bg-transparent py-3">
                <h5 class="text-theme-primary mb-0"><i class="bi bi-mortarboard me-2 text-info"></i>Detalles de Formación</h5>
            </div>
            <div class="card-body p-4">
                <div class="p-3 mb-4 rounded" style="background: rgba(36, 181, 202, 0.1); border: 1px solid #24b5ca;">
                    <h6 class="text-info fw-bold mb-1">Programa de Formación</h6>
                    <h5 class="text-theme-primary mb-0"><?= htmlspecialchars($programa) ?></h5>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-success"><i class="bi bi-bookmark-check"></i></div>
                            <div>
                                <small class="text-secondary d-block">Número de Ficha</small>
                                <strong class="text-theme-primary"><?= htmlspecialchars($numeroFicha) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="info-group">
                            <div class="info-icon text-primary"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <small class="text-secondary d-block">Horario Habitual</small>
                                <strong class="text-theme-primary"><?= htmlspecialchars($horarioStr) ?></strong>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="info-group">
                            <div class="info-icon text-success"><i class="bi bi-person-video3"></i></div>
                            <div class="w-100">
                                <small class="text-secondary d-block">Instructor Líder Asignado</small>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <strong class="text-theme-primary"><?= htmlspecialchars($instructorNombre) ?></strong>
                                    <?php if (!empty($instructorCorreo) && $instructorCorreo !== 'No disponible'): ?>
                                        <a href="mailto:<?= htmlspecialchars($instructorCorreo) ?>" class="btn btn-sm btn-outline-success border-0">
                                            <i class="bi bi-envelope me-1"></i> Contactar
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Perfil (Modal Existente) -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-nalika">
            <div class="modal-header modal-header-nalika">
                <h5 class="modal-title text-theme-primary fw-bold" id="editProfileModalLabel">
                    <i class="bi bi-pencil-square me-2 text-success"></i>Editar Información de Contacto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/index.php?page=aprendiz_perfil" method="POST">
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>Solo puedes modificar tu teléfono y correo. Para otros cambios de ficha o documento, contacta a coordinación.
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Teléfono / Celular</label>
                        <input type="text" name="telefono" class="form-control form-control-sena" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>" placeholder="Ej. 3001234567">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">Correo Electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="correo" class="form-control form-control-sena" value="<?= htmlspecialchars($perfil['correo'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sena-green btn-sm"><i class="bi bi-save me-1"></i>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once dirname(__DIR__) . '/layouts/footer-aprendiz.php';
?>
