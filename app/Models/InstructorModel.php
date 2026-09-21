<?php
/**
 * Modelo de Instructores (InstructorModel)
 * 
 * Responsabilidad:
 * 1. Consultar instructores con sus fichas asignadas (N:M) y área técnica.
 * 2. Filtrar por área técnica ('comercio' o 'Tics') según el administrador en sesión.
 * 3. Consultar las fichas a cargo de un instructor con sus minutos de tolerancia configurados.
 * 4. Actualizar la tolerancia de retardo de un instructor para una ficha específica.
 * 5. Registrar nuevos instructores (Usuario + Perfil de Instructor) mediante transacción.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class InstructorModel
{
    /**
     * Lista los instructores con sus datos personales, área, especialidad y fichas asignadas.
     * Permite filtrar opcionalmente por área técnica.
     */
    public function listarInstructores(?string $area = null): array
    {
        $sql = "SELECT 
                    i.id_instructor,
                    i.area,
                    i.especialidad,
                    u.id_usuario,
                    u.tipo_documento,
                    u.numero_documento AS documento,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    u.estado,
                    COUNT(DISTINCT ihf.fichas_id_ficha) AS total_fichas,
                    COALESCE(GROUP_CONCAT(DISTINCT f.numero_ficha SEPARATOR ', '), 'Sin fichas') AS fichas_asignadas
                FROM instructores i
                INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                LEFT JOIN instructores_has_fichas ihf ON i.id_instructor = ihf.instructores_id_instructor
                LEFT JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha";

        $params = [];
        if ($area !== null && $area !== '') {
            $sql .= " WHERE i.area = :area";
            $params[':area'] = $area;
        }

        $sql .= " GROUP BY i.id_instructor, i.area, i.especialidad, u.id_usuario, u.tipo_documento, 
                           u.numero_documento, u.nombres, u.apellidos, u.correo, u.telefono, u.estado
                  ORDER BY u.apellidos ASC, u.nombres ASC";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Busca los datos de un instructor por su ID principal.
     */
    public function buscarPorId(int $idInstructor): array|false
    {
        $sql = "SELECT i.id_instructor, i.area, i.especialidad, u.id_usuario, u.tipo_documento,
                       u.numero_documento, u.nombres, u.apellidos, u.correo, u.telefono, u.estado
                FROM instructores i
                INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                WHERE i.id_instructor = :id_instructor
                LIMIT 1";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_instructor' => $idInstructor]);

        return $stmt->fetch();
    }

    /**
     * Obtiene las fichas asignadas a un instructor específico junto con su tolerancia y horario.
     */
    public function obtenerFichasAsignadas(int $idInstructor): array
    {
        $sql = "SELECT f.id_ficha, 
                       f.numero_ficha, 
                       f.programa, 
                       f.area,
                       ihf.minutos_retardo,
                       TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada,
                       TIME_FORMAT(h.hora_salida, '%h:%i %p') AS salida,
                       h.dias_semana
                FROM instructores_has_fichas ihf
                INNER JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha
                LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                WHERE ihf.instructores_id_instructor = :id_instructor
                ORDER BY f.numero_ficha ASC";

        $stmt = Conexion::obtener()->prepare($sql);
        $stmt->execute([':id_instructor' => $idInstructor]);

        return $stmt->fetchAll();
    }

    /**
     * Actualiza la tolerancia de minutos de retardo de un instructor para una ficha específica.
     */
    public function actualizarToleranciaFicha(int $idInstructor, int $idFicha, int $minutosRetardo): bool
    {
        $sql = "UPDATE instructores_has_fichas 
                SET minutos_retardo = :minutos
                WHERE instructores_id_instructor = :id_instructor AND fichas_id_ficha = :id_ficha";

        $stmt = Conexion::obtener()->prepare($sql);
        return $stmt->execute([
            ':minutos'       => $minutosRetardo,
            ':id_instructor' => $idInstructor,
            ':id_ficha'      => $idFicha
        ]);
    }

    /**
     * Registra un nuevo instructor en el sistema (Usuario + Registro técnico).
     */
    public function crearInstructor(array $datosUsuario, string $area, string $especialidad): int
    {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 1. Obtener id del rol 'Instructor'
            $stmtRol = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre_rol LIKE '%instructor%' LIMIT 1");
            $stmtRol->execute();
            $rol = $stmtRol->fetch();
            $idRol = $rol ? (int)$rol['id_rol'] : 2;

            // 2. Hash de contraseña por defecto (o la provista)
            $passwordPlana = $datosUsuario['password'] ?? '123456';
            $passwordHash  = password_hash($passwordPlana, PASSWORD_BCRYPT);

            // 3. Insertar usuario base
            $sqlUsuario = "INSERT INTO usuarios (tipo_documento, numero_documento, nombres, apellidos, correo, telefono, contraseña, estado, roles_id_rol)
                           VALUES (:tipo_doc, :doc, :nombres, :apellidos, :correo, :telefono, :pass, 'Activo', :rol)";
            $stmtUser = $pdo->prepare($sqlUsuario);
            $stmtUser->execute([
                ':tipo_doc'  => $datosUsuario['tipo_documento'] ?? 'CC',
                ':doc'       => $datosUsuario['documento'],
                ':nombres'   => $datosUsuario['nombres'],
                ':apellidos' => $datosUsuario['apellidos'],
                ':correo'    => $datosUsuario['correo'],
                ':telefono'  => $datosUsuario['telefono'] ?? null,
                ':pass'      => $passwordHash,
                ':rol'       => $idRol
            ]);

            $idUsuario = (int)$pdo->lastInsertId();

            // 4. Insertar instructor técnico con su área
            $sqlInst = "INSERT INTO instructores (area, especialidad, usuarios_id_usuario)
                        VALUES (:area, :especialidad, :id_usuario)";
            $stmtInst = $pdo->prepare($sqlInst);
            $stmtInst->execute([
                ':area'         => $area,
                ':especialidad' => $especialidad,
                ':id_usuario'   => $idUsuario
            ]);

            $idInstructor = (int)$pdo->lastInsertId();

            $pdo->commit();
            return $idInstructor;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
