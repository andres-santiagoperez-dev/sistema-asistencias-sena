<?php
/**
<<<<<<< HEAD
 * Modelo de Instructor (InstructorModel)
 *
 * Responsabilidad única: Acceso y consulta a los datos de instructores,
 * que abarcan la tabla 'usuarios' (datos personales/cuenta) y la tabla
 * 'instructores' (datos específicos del rol: especialidad, área).
 *
 * No maneja sesiones. El hashing/generación de contraseñas se resuelve
 * en el controlador (igual que en UserModel) y aquí solo se recibe el
 * hash ya calculado.
=======
 * Modelo de Instructores (InstructorModel)
 * 
 * Responsabilidad:
 * 1. Consultar instructores con sus fichas asignadas (N:M) y área técnica.
 * 2. Filtrar por área técnica ('comercio' o 'Tics') según el administrador en sesión.
 * 3. Consultar las fichas a cargo de un instructor con sus minutos de tolerancia configurados.
 * 4. Actualizar la tolerancia de retardo de un instructor para una ficha específica.
 * 5. Registrar nuevos instructores (Usuario + Perfil de Instructor) mediante transacción.
>>>>>>> c6a7b3f (feat(instructors): migrate instructor management to strict MVC architecture)
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class InstructorModel
{
    /**
<<<<<<< HEAD
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
=======
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
>>>>>>> c6a7b3f (feat(instructors): migrate instructor management to strict MVC architecture)

        return $stmt->fetchAll();
    }

    /**
<<<<<<< HEAD
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
=======
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
>>>>>>> c6a7b3f (feat(instructors): migrate instructor management to strict MVC architecture)
        ]);
    }

    /**
<<<<<<< HEAD
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
=======
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
>>>>>>> c6a7b3f (feat(instructors): migrate instructor management to strict MVC architecture)
        }
    }
}
