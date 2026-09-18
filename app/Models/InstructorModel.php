<?php
/**
 * Modelo de Instructor (InstructorModel)
 *
 * Responsabilidad única: Acceso y consulta a los datos de instructores,
 * que abarcan la tabla 'usuarios' (datos personales/cuenta) y la tabla
 * 'instructores' (datos específicos del rol: especialidad, área).
 *
 * No maneja sesiones. El hashing/generación de contraseñas se resuelve
 * en el controlador (igual que en UserModel) y aquí solo se recibe el
 * hash ya calculado.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class InstructorModel
{
    /**
     * Crea un usuario con rol Instructor y su registro asociado en 'instructores'.
     * Devuelve el id_usuario creado o false si falla.
     *
     * $datos debe incluir las llaves:
     *   tipo_documento, numero_documento, nombres, apellidos, correo,
     *   contrasena_hash, estado, especialidad
     */
    public function crear(array $datos): int|false
    {
        $pdo = Conexion::obtener();

        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO usuarios
                        (tipo_documento, numero_documento, nombres, apellidos, correo, `contraseña`, estado, roles_id_rol, fecha_creacion)
                    VALUES
                        (:tipo_documento, :numero_documento, :nombres, :apellidos, :correo, :contrasena, :estado,
                         (SELECT id_rol FROM roles WHERE nombre_rol = 'Instructor' LIMIT 1), NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo_documento'   => $datos['tipo_documento'],
                ':numero_documento' => $datos['numero_documento'],
                ':nombres'          => $datos['nombres'],
                ':apellidos'        => $datos['apellidos'],
                ':correo'           => $datos['correo'],
                ':contrasena'       => $datos['contrasena_hash'],
                ':estado'           => $datos['estado'],
            ]);

            $idUsuario = (int) $pdo->lastInsertId();

            $sql2 = "INSERT INTO instructores (especialidad, usuarios_id_usuario)
                      VALUES (:especialidad, :id_usuario)";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':especialidad' => $datos['especialidad'],
                ':id_usuario'   => $idUsuario,
            ]);

            $pdo->commit();
            return $idUsuario;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Obtiene un instructor por su id_usuario, con los datos combinados
     * de 'usuarios' e 'instructores'. Ideal para precargar el formulario de edición.
     */
    public function obtenerPorId(int $idUsuario): array|false
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.telefono, u.estado, u.roles_id_rol, r.nombre_rol,
                       i.id_instructor, i.especialidad, i.area
                FROM usuarios u
                INNER JOIN instructores i ON i.usuarios_id_usuario = u.id_usuario
                INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                WHERE u.id_usuario = :id_usuario
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetch();
    }

    /**
     * Busca un instructor por su número de documento (útil para validar duplicados
     * o para autenticación, igual que buscarPorDocumento en UserModel).
     */
    public function buscarPorDocumento(string $numeroDocumento): array|false
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.`contraseña`, u.estado, r.nombre_rol,
                       i.id_instructor, i.especialidad, i.area
                FROM usuarios u
                INNER JOIN instructores i ON i.usuarios_id_usuario = u.id_usuario
                INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                WHERE u.numero_documento = :numero_documento
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':numero_documento' => $numeroDocumento]);

        return $stmt->fetch();
    }

    /**
     * Lista todos los instructores (para la vista de tabla/listado en el panel admin).
     */
    public function listarTodos(): array
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.estado,
                       i.id_instructor, i.especialidad, i.area
                FROM usuarios u
                INNER JOIN instructores i ON i.usuarios_id_usuario = u.id_usuario
                WHERE u.roles_id_rol = (SELECT id_rol FROM roles WHERE nombre_rol = 'Instructor' LIMIT 1)
                ORDER BY u.apellidos, u.nombres";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Actualiza los datos de usuario + especialidad de un instructor existente.
     *
     * $datos debe incluir las llaves:
     *   tipo_documento, numero_documento, nombres, apellidos, correo,
     *   estado, especialidad
     */
    public function editar(int $idUsuario, array $datos): bool
    {
        $pdo = Conexion::obtener();

        try {
            $pdo->beginTransaction();

            $sql = "UPDATE usuarios
                    SET tipo_documento = :tipo_documento,
                        numero_documento = :numero_documento,
                        nombres = :nombres,
                        apellidos = :apellidos,
                        correo = :correo,
                        estado = :estado
                    WHERE id_usuario = :id_usuario";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo_documento'   => $datos['tipo_documento'],
                ':numero_documento' => $datos['numero_documento'],
                ':nombres'          => $datos['nombres'],
                ':apellidos'        => $datos['apellidos'],
                ':correo'           => $datos['correo'],
                ':estado'           => $datos['estado'],
                ':id_usuario'       => $idUsuario,
            ]);

            $sql2 = "UPDATE instructores
                      SET especialidad = :especialidad
                      WHERE usuarios_id_usuario = :id_usuario";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([
                ':especialidad' => $datos['especialidad'],
                ':id_usuario'   => $idUsuario,
            ]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }

    /**
     * Cambia el estado de un instructor (Activo/Inactivo).
     * Se usa tanto para "activar/inactivar" como base de una eliminación lógica
     * (no se borra el registro, se marca como Inactivo, preservando el historial).
     */
    public function cambiarEstado(int $idUsuario, string $nuevoEstado): bool
    {
        $sql = "UPDATE usuarios SET estado = :estado WHERE id_usuario = :id_usuario";

        $stmt = Conexion::obtener()->prepare($sql);

        return $stmt->execute([
            ':estado'     => $nuevoEstado,
            ':id_usuario' => $idUsuario,
        ]);
    }

    /**
     * Elimina físicamente un instructor (usuario + registro en instructores).
     * Solo usar si tu diseño permite borrado real; si no, prefiere cambiarEstado()
     * para hacer una eliminación lógica y no perder integridad referencial
     * con tablas relacionadas (fichas, calificaciones, etc.).
     */
    public function eliminar(int $idUsuario): bool
    {
        $pdo = Conexion::obtener();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("DELETE FROM instructores WHERE usuarios_id_usuario = :id_usuario");
            $stmt->execute([':id_usuario' => $idUsuario]);

            $stmt2 = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");
            $stmt2->execute([':id_usuario' => $idUsuario]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
