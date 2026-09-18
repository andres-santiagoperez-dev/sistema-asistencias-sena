<?php
/**
 * Modelo de Usuario (UserModel)
 * 
 * Responsabilidad única: Acceso y consulta a los datos de la tabla 'usuarios'
 * y sus perfiles asociados (aprendiz, instructor).
 * No maneja sesiones ni contraseñas (eso corresponde al controlador).
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class UserModel
{
    /**
     * Busca un usuario por su número de identificación (documento).
     * Incluye los datos del rol asignado.
     */
    public function buscarPorDocumento(string $numeroDocumento): array|false
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres, u.apellidos, 
                       u.correo, u.telefono, u.contraseña, u.estado, u.roles_id_rol, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON u.roles_id_rol = r.id_rol
                WHERE u.numero_documento = :numero_documento
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':numero_documento' => $numeroDocumento]);

        return $stmt->fetch();
    }

    /**
     * Obtiene la información complementaria de un aprendiz por su id de usuario.
     */
    public function obtenerDatosAprendiz(int $idUsuario): array|false
    {
        $sql = "SELECT id_aprendiz, fichas_id_ficha, estado_matricula 
                FROM aprendices 
                WHERE usuarios_id_usuario = :id_usuario 
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetch();
    }

    /**
     * Obtiene la información complementaria de un instructor (incluyendo su área).
     */
    public function obtenerDatosInstructor(int $idUsuario): array|false
    {
        $sql = "SELECT id_instructor, area, especialidad 
                FROM instructores 
                WHERE usuarios_id_usuario = :id_usuario 
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetch();
    }
}
