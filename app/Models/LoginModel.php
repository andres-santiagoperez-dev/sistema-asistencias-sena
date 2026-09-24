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
            $sql = "SELECT id_aprendiz, fichas_id_ficha, estado_matricula
                    FROM aprendices
                    WHERE usuarios_id_usuario = :id_usuario
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
}
