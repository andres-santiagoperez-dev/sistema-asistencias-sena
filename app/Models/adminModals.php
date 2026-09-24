<?php
/**
 * Modelo de Administrador (AdminModel)
 *
 * Responsabilidad única: Acceso y consulta a los datos de usuarios con
 * rol de Administrador. A diferencia de Instructor/Aprendiz, no existe
 * una tabla complementaria: toda la información vive en 'usuarios'.
 *
 * Existen dos roles de administrador ('Administrador Comercio' y
 * 'Administrador TICs'), por eso los métodos reciben el nombre del rol
 * en vez de asumir uno fijo, igual que Instructor/Aprendiz reciben su área.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class AdminModel
{
    /**
     * Lista todos los administradores del sistema (Comercio y TICs),
     * derivando el área técnica desde el nombre del rol.
     */
    public function listarAdministradores(): array
    {
        try {
            $sql = "SELECT u.id_usuario, u.tipo_documento, u.numero_documento AS documento,
                           u.nombres, u.apellidos,
                           CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                           u.correo, u.telefono, u.estado, r.nombre_rol,
                           CASE
                               WHEN r.nombre_rol LIKE '%comercio%' THEN 'comercio'
                               WHEN r.nombre_rol LIKE '%tic%'      THEN 'Tics'
                               ELSE 'todas'
                           END AS area
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.roles_id_rol
                    WHERE r.nombre_rol LIKE 'Administrador%'
                    ORDER BY u.apellidos, u.nombres";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AdminModel::listarAdministradores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra un nuevo usuario con rol de Administrador.
     * Devuelve el id_usuario creado (lanza excepción si falla).
     *
     * $datosUsuario debe incluir: tipo_documento, documento, nombres, apellidos,
     * correo, telefono (opcional), password (opcional, por defecto 123456).
     * $nombreRol debe ser exactamente 'Administrador Comercio' o 'Administrador TICs'.
     */
    public function crearAdministrador(array $datosUsuario, string $nombreRol): int
    {
        $pdo = Conexion::obtener();

        try {
            $passwordPlana = $datosUsuario['password'] ?? '123456';
            $passwordHash  = password_hash($passwordPlana, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios
                        (tipo_documento, numero_documento, nombres, apellidos, correo, telefono, `contraseña`, estado, roles_id_rol, fecha_creacion)
                    VALUES
                        (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :pass, 'Activo',
                         (SELECT id_rol FROM roles WHERE nombre_rol = :nombre_rol LIMIT 1), NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo_doc'   => $datosUsuario['tipo_documento'] ?? 'CC',
                ':doc'        => $datosUsuario['documento'],
                ':nombres'    => $datosUsuario['nombres'],
                ':apellidos'  => $datosUsuario['apellidos'],
                ':correo'     => $datosUsuario['correo'],
                ':telefono'   => $datosUsuario['telefono'] ?? null,
                ':pass'       => $passwordHash,
                ':nombre_rol' => $nombreRol,
            ]);

            return (int) $pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log("AdminModel::crearAdministrador: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Activa o inactiva la cuenta de un administrador.
     * La condición roles_id_rol evita que este método toque, por error,
     * a un usuario que no sea Administrador.
     */
    public function actualizarEstado(int $idUsuario, string $nuevoEstado): bool
    {
        try {
            $sql = "UPDATE usuarios SET estado = :estado
                    WHERE id_usuario = :id_usuario
                      AND roles_id_rol IN (SELECT id_rol FROM roles WHERE nombre_rol LIKE 'Administrador%')";

            $stmt = Conexion::obtener()->prepare($sql);

            return $stmt->execute([
                ':estado'     => $nuevoEstado,
                ':id_usuario' => $idUsuario,
            ]);
        } catch (Throwable $e) {
            error_log("AdminModel::actualizarEstado: " . $e->getMessage());
            throw $e;
        }
    }
}