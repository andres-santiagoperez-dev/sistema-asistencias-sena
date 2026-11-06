<?php
/**
 * Controlador de Soporte Médico Seguro (SoporteController)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Proteger la descarga y visualización de soportes médicos digitales.
 * 2. Impedir accesos no autorizados mediante validación estricta de roles y pertenencia.
 * 3. Prevenir vulnerabilidades de Path Traversal sanitizando las rutas en disco.
 * 4. Despachar el archivo con las cabeceras HTTP adecuadas (inline) para su renderizado
 *    en modales (iframe / img) sin exponer la ruta física ni permitir acceso directo.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

require_once BASE_PATH . '/Controllers/BaseController.php';

class SoporteController extends BaseController
{
    /**
     * Valida la sesión y autorización del usuario y despacha de forma segura el archivo soporte.
     */
    public function ver(): void
    {
        $this->requireAuth();

        $archivo = basename(trim($_GET['archivo'] ?? ''));

        if (empty($archivo) || $archivo === 'sin_archivo') {
            http_response_code(404);
            exit('Archivo soporte no encontrado o no especificado.');
        }

        $uploadDir = realpath(dirname(BASE_PATH) . '/public/uploads');
        if ($uploadDir === false) {
            http_response_code(404);
            exit('Directorio de soportes no disponible.');
        }

        $rutaCompleta = $uploadDir . DIRECTORY_SEPARATOR . $archivo;
        $realRuta = realpath($rutaCompleta);

        // Prevenir Directory Traversal asegurando que el archivo resida dentro de uploads
        if ($realRuta === false || !str_starts_with($realRuta, $uploadDir) || !is_file($realRuta)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        // Control de autorización según rol
        $idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
        $rol       = strtolower($_SESSION['nombre_rol'] ?? '');

        if (!$this->tienePermisoSobreSoporte($idUsuario, $rol, $archivo)) {
            http_response_code(404); // Respuesta 404 unificada para no revelar la existencia del archivo
            exit('Archivo no encontrado.');
        }

        // Detección de tipo MIME real en servidor
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $realRuta) ?: 'application/octet-stream';
        finfo_close($finfo);

        // Limpiar cualquier búfer de salida previo para evitar archivos corruptos
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Cabeceras HTTP de entrega segura
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . rawurlencode($archivo) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . filesize($realRuta));
        header('Cache-Control: private, max-age=3600');

        readfile($realRuta);
        exit;
    }

    /**
     * Verifica en base de datos si el usuario autenticado tiene potestad para visualizar el archivo.
     */
    private function tienePermisoSobreSoporte(int $idUsuario, string $rol, string $archivo): bool
    {
        // Administrador tiene acceso de auditoría institucional
        if (str_contains($rol, 'admin')) {
            return true;
        }

        try {
            $pdo = Conexion::obtener();

            // Si es Aprendiz: debe ser el titular de la excusa médica radicada
            if (str_contains($rol, 'aprendiz')) {
                $sql = "SELECT 1 
                        FROM excusas_medicas e
                        INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
                        INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                        WHERE e.archivo_soporte = :archivo 
                          AND ap.usuarios_id_usuario = :id_usuario
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':archivo'    => $archivo,
                    ':id_usuario' => $idUsuario
                ]);
                return (bool)$stmt->fetchColumn();
            }

            // Si es Instructor: el aprendiz debe pertenecer a sus fichas asignadas o a un bloque que dictó
            if (str_contains($rol, 'instructor')) {
                $sql = "SELECT 1 
                        FROM excusas_medicas e
                        INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
                        INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                        LEFT JOIN instructores_has_fichas ihf ON ap.fichas_id_ficha = ihf.fichas_id_ficha
                        LEFT JOIN instructores inst ON inst.usuarios_id_usuario = :id_usuario
                        WHERE e.archivo_soporte = :archivo
                          AND (
                              asi.instructores_id_instructor = inst.id_instructor 
                              OR ihf.instructores_id_instructor = inst.id_instructor
                              OR EXISTS (
                                  SELECT 1 FROM detalle_excusas de 
                                  WHERE de.excusas_medicas_id_excusa = e.id_excusa 
                                    AND de.instructores_id_instructor = inst.id_instructor
                              )
                          )
                        LIMIT 1";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':archivo'    => $archivo,
                    ':id_usuario' => $idUsuario
                ]);
                return (bool)$stmt->fetchColumn();
            }

            return false;
        } catch (Throwable $e) {
            error_log("SoporteController::tienePermisoSobreSoporte: " . $e->getMessage());
            return false;
        }
    }
}
