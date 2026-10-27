<?php
/**
 * Modelo de Inicio de Sesión (LoginModel)
 * 
 * Responsabilidad única: Consultas y acceso a datos exclusivamente 
 * necesarios para el proceso de autenticación e inicio de sesión.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class LoginModel
{
    /**
     * Consulta un usuario activo por su número de identificación.
     * Trae los datos de autenticación y su rol asignado.
     */
    public function buscarUsuarioPorDocumento(string $documento): array|false
    {
        try {
            $sql = "SELECT u.id_usuario, 
                           u.tipo_documento, 
                           u.numero_documento, 
                           u.nombres, 
                           u.apellidos, 
                           u.correo, 
                           u.telefono, 
                           u.contraseña, 
                           u.estado, 
                           u.roles_id_rol, 
                           r.nombre_rol
                    FROM usuarios u
                    INNER JOIN roles r ON u.roles_id_rol = r.id_rol
                    WHERE u.numero_documento = :documento
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':documento' => $documento]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("LoginModel::buscarUsuarioPorDocumento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta los datos complementarios de un aprendiz para la sesión.
     */
    public function obtenerDetalleAprendiz(int $idUsuario): array|false
    {
        try {
            $sql = "SELECT a.id_aprendiz, a.fichas_id_ficha, a.codigo_rfid, a.estado_matricula, f.numero_ficha, f.programa
                    FROM aprendices a
                    LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    WHERE a.usuarios_id_usuario = :id_usuario
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("LoginModel::obtenerDetalleAprendiz: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta los datos complementarios de un instructor (incluyendo su área técnica).
     */
    public function obtenerDetalleInstructor(int $idUsuario): array|false
    {
        try {
            $sql = "SELECT id_instructor, area, especialidad
                    FROM instructores
                    WHERE usuarios_id_usuario = :id_usuario
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("LoginModel::obtenerDetalleInstructor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca un usuario activo por su correo electrónico.
     */
    public function buscarUsuarioPorCorreo(string $correo): array|false
    {
        try {
            $sql = "SELECT u.id_usuario, 
                           u.tipo_documento, 
                           u.numero_documento, 
                           u.nombres, 
                           u.apellidos, 
                           u.correo, 
                           u.estado
                    FROM usuarios u
                    WHERE LOWER(TRIM(u.correo)) = LOWER(TRIM(:correo))
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':correo' => $correo]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("LoginModel::buscarUsuarioPorCorreo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Invalida códigos anteriores no utilizados y guarda uno nuevo con fecha de expiración.
     */
    public function guardarCodigoRecuperacion(int $idUsuario, string $codigo, int $minutosValidez = 15): bool
    {
        try {
            $db = Conexion::obtener();
            
            // 1. Invalidar códigos previos del usuario
            $stmtInvalidar = $db->prepare(
                "UPDATE recuperacion_contrasenas 
                 SET utilizado = 1 
                 WHERE id_usuario = :id_usuario AND utilizado = 0"
            );
            $stmtInvalidar->execute([':id_usuario' => $idUsuario]);

            // 2. Insertar nuevo código con expiración
            $sql = "INSERT INTO recuperacion_contrasenas 
                        (id_usuario, codigo, expiracion, utilizado, fecha_creacion)
                    VALUES 
                        (:id_usuario, :codigo, DATE_ADD(NOW(), INTERVAL :minutos MINUTE), 0, NOW())";

            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':codigo'     => $codigo,
                ':minutos'    => $minutosValidez
            ]);
        } catch (Throwable $e) {
            error_log("LoginModel::guardarCodigoRecuperacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida si un código de verificación es correcto, no ha sido usado y aún no expira.
     */
    public function validarCodigoRecuperacion(int $idUsuario, string $codigo): array|false
    {
        try {
            $sql = "SELECT id, id_usuario, codigo, expiracion, utilizado
                    FROM recuperacion_contrasenas
                    WHERE id_usuario = :id_usuario 
                      AND codigo = :codigo 
                      AND utilizado = 0 
                      AND expiracion >= NOW()
                    ORDER BY id DESC
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':codigo'     => $codigo
            ]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("LoginModel::validarCodigoRecuperacion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la contraseña encriptada de un usuario.
     */
    public function actualizarContraseña(int $idUsuario, string $nuevaContraseñaHash): bool
    {
        try {
            $sql = "UPDATE usuarios 
                    SET contraseña = :pass 
                    WHERE id_usuario = :id_usuario";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':pass'       => $nuevaContraseñaHash,
                ':id_usuario' => $idUsuario
            ]);
        } catch (Throwable $e) {
            error_log("LoginModel::actualizarContraseña: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un código de recuperación como ya utilizado.
     */
    public function marcarCodigoUtilizado(int $idRecuperacion): bool
    {
        try {
            $sql = "UPDATE recuperacion_contrasenas 
                    SET utilizado = 1 
                    WHERE id = :id";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([':id' => $idRecuperacion]);
        } catch (Throwable $e) {
            error_log("LoginModel::marcarCodigoUtilizado: " . $e->getMessage());
            return false;
        }
    }
}

