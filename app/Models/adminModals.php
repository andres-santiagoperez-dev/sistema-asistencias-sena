<?php
/**
 * Modelo de Administrador (AdminModel)
 *
 * Responsabilidad única: Acceso y consulta a los datos de usuarios con
 * rol de Administrador. A diferencia de Instructor/Aprendiz, no existe
 * una tabla complementaria: toda la información vive en 'usuarios'.
 *
 * Nota: existen dos roles de administrador (Administrador Comercio y
 * Administrador TICs), por eso los métodos reciben el nombre del rol
 * en vez de asumir uno fijo.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class AdminModel
{
    /**
     * Crea un usuario con rol de Administrador.
     * Devuelve el id_usuario creado o false si falla.
     *
     * $datos debe incluir las llaves:
     *   tipo_documento, numero_documento, nombres, apellidos, correo,
     *   contrasena_hash, estado, nombre_rol (ej: 'Administrador Comercio' o 'Administrador TICs')
     */
    public function crear(array $datos): int|false
    {
        $sql = "INSERT INTO usuarios
                    (tipo_documento, numero_documento, nombres, apellidos, correo, `contraseña`, estado, roles_id_rol, fecha_creacion)
                VALUES
                    (:tipo_documento, :numero_documento, :nombres, :apellidos, :correo, :contrasena, :estado,
                     (SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol LIMIT 1), NOW())";

        try {
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':tipo_documento'   => $datos['tipo_documento'],
                ':numero_documento' => $datos['numero_documento'],
                ':nombres'          => $datos['nombres'],
                ':apellidos'        => $datos['apellidos'],
                ':correo'           => $datos['correo'],
                ':contrasena'       => $datos['contrasena_hash'],
                ':estado'           => $datos['estado'],
                ':nombre_rol'       => $datos['nombre_rol'],
            ]);

            return (int) Conexion::obtener()->lastInsertId();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene un administrador por su id_usuario.
     */
    public function obtenerPorId(int $idUsuario): array|false
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.telefono, u.estado, u.roles_id_rol, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                WHERE u.id_usuario = :id_usuario
                  AND r.nombre_rol LIKE 'Administrador%'
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_usuario' => $idUsuario]);

        return $stmt->fetch();
    }

    /**
     * Busca un administrador por su número de documento (validar duplicados o login).
     */
    public function buscarPorDocumento(string $numeroDocumento): array|false
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.`contraseña`, u.estado, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                WHERE u.numero_documento = :numero_documento
                  AND r.nombre_rol LIKE 'Administrador%'
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':numero_documento' => $numeroDocumento]);

        return $stmt->fetch();
    }

    /**
     * Lista todos los administradores (ambos tipos: Comercio y TICs).
     */
    public function listarTodos(): array
    {
        $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento, u.nombres,
                       u.apellidos, u.correo, u.estado, r.nombre_rol
                FROM usuarios u
                INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                WHERE r.nombre_rol LIKE 'Administrador%'
                ORDER BY u.apellidos, u.nombres";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Actualiza los datos de un administrador. Permite además cambiar
     * su tipo de rol (de Comercio a TICs o viceversa) si nombre_rol viene informado.
     *
     * $datos debe incluir las llaves:
     *   tipo_documento, numero_documento, nombres, apellidos, correo, estado
     *   y opcionalmente nombre_rol
     */
    public function editar(int $idUsuario, array $datos): bool
    {
        if (!empty($datos['nombre_rol'])) {
            $sql = "UPDATE usuarios
                    SET tipo_documento = :tipo_documento,
                        numero_documento = :numero_documento,
                        nombres = :nombres,
                        apellidos = :apellidos,
                        correo = :correo,
                        estado = :estado,
                        roles_id_rol = (SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol LIMIT 1)
                    WHERE id_usuario = :id_usuario";
        } else {
            $sql = "UPDATE usuarios
                    SET tipo_documento = :tipo_documento,
                        numero_documento = :numero_documento,
                        nombres = :nombres,
                        apellidos = :apellidos,
                        correo = :correo,
                        estado = :estado
                    WHERE id_usuario = :id_usuario";
        }

        $params = [
            ':tipo_documento'   => $datos['tipo_documento'],
            ':numero_documento' => $datos['numero_documento'],
            ':nombres'          => $datos['nombres'],
            ':apellidos'        => $datos['apellidos'],
            ':correo'           => $datos['correo'],
            ':estado'           => $datos['estado'],
            ':id_usuario'       => $idUsuario,
        ];
        if (!empty($datos['nombre_rol'])) {
            $params[':nombre_rol'] = $datos['nombre_rol'];
        }

        $stmt = Conexion::obtener()->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Cambia el estado de un administrador (Activo/Inactivo).
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
     * Elimina físicamente un administrador (solo la fila en 'usuarios',
     * no hay tabla complementaria que limpiar).
     */
    public function eliminar(int $idUsuario): bool
    {
        $stmt = Conexion::obtener()->prepare("DELETE FROM usuarios WHERE id_usuario = :id_usuario");

        return $stmt->execute([':id_usuario' => $idUsuario]);
    }
}