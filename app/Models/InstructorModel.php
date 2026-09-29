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
        try {
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
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE i.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " GROUP BY i.id_instructor, i.area, i.especialidad, u.id_usuario, u.tipo_documento, 
                               u.numero_documento, u.nombres, u.apellidos, u.correo, u.telefono, u.estado
                      ORDER BY u.apellidos ASC, u.nombres ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::listarInstructores: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca los datos de un instructor por su ID principal.
     */
    public function buscarPorId(int $idInstructor): array|false
    {
        try {
            $sql = "SELECT i.id_instructor, i.area, i.especialidad, u.id_usuario, u.tipo_documento,
                           u.numero_documento, u.nombres, u.apellidos, u.correo, u.telefono, u.estado
                    FROM instructores i
                    INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                    WHERE i.id_instructor = :id_instructor
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_instructor' => $idInstructor]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("InstructorModel::buscarPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las fichas asignadas a un instructor específico junto con su tolerancia y horario.
     */
    public function obtenerFichasAsignadas(int $idInstructor): array
    {
        try {
            $sql = "SELECT f.id_ficha, 
                           f.numero_ficha, 
                           f.programa, 
                           f.area,
                           ihf.minutos_retardo,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p') AS salida,
                           h.hora_entrada AS raw_hora_entrada,
                           h.hora_salida AS raw_hora_salida,
                           h.dias_semana,
                           COUNT(DISTINCT a.id_aprendiz) AS total_aprendices
                    FROM instructores_has_fichas ihf
                    INNER JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha
                    LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                    WHERE ihf.instructores_id_instructor = :id_instructor
                    GROUP BY f.id_ficha, f.numero_ficha, f.programa, f.area, ihf.minutos_retardo, h.hora_entrada, h.hora_salida, h.dias_semana
                    ORDER BY f.numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_instructor' => $idInstructor]);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerFichasAsignadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Asegura la existencia de la tabla notas_clase en la base de datos.
     */
    public function asegurarTablaNotasClase(): void
    {
        try {
            $pdo = Conexion::obtener();
            $sql = "CREATE TABLE IF NOT EXISTS notas_clase (
                id_nota INT(11) NOT NULL AUTO_INCREMENT,
                fecha DATE NOT NULL,
                titulo VARCHAR(150) NULL DEFAULT 'Observación de clase',
                observacion TEXT NOT NULL,
                fecha_creacion DATETIME NULL DEFAULT CURRENT_TIMESTAMP(),
                instructores_id_instructor INT(11) NOT NULL,
                fichas_id_ficha INT(11) NULL,
                PRIMARY KEY (id_nota),
                INDEX fk_notas_clase_inst (instructores_id_instructor ASC),
                INDEX fk_notas_clase_fichas (fichas_id_ficha ASC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log("InstructorModel::asegurarTablaNotasClase: " . $e->getMessage());
        }
    }

    /**
     * Guarda una nueva nota u observación de clase para el instructor.
     */
    public function guardarNotaClase(int $idInstructor, ?int $idFicha, string $fecha, string $observacion, string $titulo = 'Observación de clase'): bool
    {
        $this->asegurarTablaNotasClase();
        try {
            $sql = "INSERT INTO notas_clase (fecha, titulo, observacion, instructores_id_instructor, fichas_id_ficha)
                    VALUES (:fecha, :titulo, :observacion, :id_instructor, :id_ficha)";
            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':fecha'         => $fecha,
                ':titulo'        => $titulo,
                ':observacion'   => $observacion,
                ':id_instructor' => $idInstructor,
                ':id_ficha'      => $idFicha ?: null,
            ]);
        } catch (Throwable $e) {
            error_log("InstructorModel::guardarNotaClase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las notas u observaciones asociadas a las clases del instructor.
     */
    public function obtenerNotasClase(int $idInstructor, ?string $fecha = null, ?string $numeroFicha = null): array
    {
        $this->asegurarTablaNotasClase();
        try {
            $sql = "SELECT n.id_nota,
                           n.fecha,
                           n.titulo,
                           n.observacion,
                           n.fecha_creacion,
                           f.id_ficha,
                           f.numero_ficha,
                           f.programa
                    FROM notas_clase n
                    LEFT JOIN fichas f ON n.fichas_id_ficha = f.id_ficha
                    WHERE n.instructores_id_instructor = :id_instructor";
            
            $params = [':id_instructor' => $idInstructor];

            if (!empty($fecha)) {
                $sql .= " AND n.fecha = :fecha";
                $params[':fecha'] = $fecha;
            }

            if (!empty($numeroFicha) && $numeroFicha !== 'todas') {
                $sql .= " AND f.numero_ficha = :ficha";
                $params[':ficha'] = $numeroFicha;
            }

            $sql .= " ORDER BY n.fecha_creacion DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerNotasClase: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Elimina una nota u observación de clase existente.
     */
    public function eliminarNotaClase(int $idNota, int $idInstructor): bool
    {
        $this->asegurarTablaNotasClase();
        try {
            $sql = "DELETE FROM notas_clase 
                    WHERE id_nota = :id_nota AND instructores_id_instructor = :id_instructor";
            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':id_nota'       => $idNota,
                ':id_instructor' => $idInstructor
            ]);
        } catch (Throwable $e) {
            error_log("InstructorModel::eliminarNotaClase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el resumen de novedades de asistencia (retardos, excusas y notas) para el calendario del instructor.
     */
    public function obtenerNovedadesCalendario(int $idInstructor): array
    {
        try {
            $pdo = Conexion::obtener();
            $novedades = [];

            // 1. Agrupar retardos por fecha y ficha
            $sqlRetardos = "SELECT 
                                a.fecha,
                                f.numero_ficha,
                                f.programa,
                                COUNT(a.id_registro) AS cant_retardos
                            FROM asistencia a
                            INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                            INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                            INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                            WHERE ihf.instructores_id_instructor = :id
                              AND a.estado = 'Retardo'
                            GROUP BY a.fecha, f.id_ficha, f.numero_ficha, f.programa";
            $stmtRet = $pdo->prepare($sqlRetardos);
            $stmtRet->execute([':id' => $idInstructor]);
            $retardos = $stmtRet->fetchAll() ?: [];

            foreach ($retardos as $r) {
                $novedades[] = [
                    'tipo'        => 'retardo',
                    'fecha'       => $r['fecha'],
                    'ficha'       => $r['numero_ficha'],
                    'programa'    => $r['programa'],
                    'titulo'      => 'Novedad: ' . $r['cant_retardos'] . ' Retardo(s) (Ficha ' . $r['numero_ficha'] . ')',
                    'descripcion' => $r['cant_retardos'] . ' aprendiz(ces) registraron ingreso con retardo en la ficha ' . $r['numero_ficha'] . '.'
                ];
            }

            // 2. Excusas médicas por fecha de inicio/fin y ficha
            $sqlExcusas = "SELECT DISTINCT
                               e.id_excusa,
                               e.motivo,
                               e.fecha_inicio,
                               e.fecha_fin,
                               f.numero_ficha,
                               f.programa,
                               CONCAT(u.nombres, ' ', u.apellidos) AS aprendiz
                           FROM excusas_medicas e
                           INNER JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                           INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                           INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                           INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                           INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                           WHERE ihf.instructores_id_instructor = :id";
            $stmtExc = $pdo->prepare($sqlExcusas);
            $stmtExc->execute([':id' => $idInstructor]);
            $excusas = $stmtExc->fetchAll() ?: [];

            foreach ($excusas as $e) {
                $novedades[] = [
                    'tipo'        => 'excusa',
                    'fecha'       => $e['fecha_inicio'],
                    'fecha_fin'   => $e['fecha_fin'],
                    'ficha'       => $e['numero_ficha'],
                    'programa'    => $e['programa'],
                    'titulo'      => 'Excusa: ' . $e['motivo'] . ' (' . $e['aprendiz'] . ')',
                    'descripcion' => 'Excusa médica radicada por ' . $e['aprendiz'] . ' (Ficha ' . $e['numero_ficha'] . ') por motivo: ' . $e['motivo'] . '.'
                ];
            }

            // 3. Notas / Observaciones registradas por el instructor
            $notas = $this->obtenerNotasClase($idInstructor);
            foreach ($notas as $n) {
                $novedades[] = [
                    'tipo'        => 'nota',
                    'id_nota'     => $n['id_nota'],
                    'fecha'       => $n['fecha'],
                    'ficha'       => $n['numero_ficha'] ?? '',
                    'programa'    => $n['programa'] ?? '',
                    'titulo'      => '📝 Nota: ' . ($n['titulo'] ?: 'Observación de clase'),
                    'descripcion' => $n['observacion']
                ];
            }

            return $novedades;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerNovedadesCalendario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza la tolerancia de minutos de retardo de un instructor para una ficha específica.
     */
    public function actualizarToleranciaFicha(int $idInstructor, int $idFicha, int $minutosRetardo): bool
    {
        try {
            $sql = "UPDATE instructores_has_fichas 
                    SET minutos_retardo = :minutos
                    WHERE instructores_id_instructor = :id_instructor AND fichas_id_ficha = :id_ficha";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':minutos'       => $minutosRetardo,
                ':id_instructor' => $idInstructor,
                ':id_ficha'      => $idFicha
            ]);
        } catch (Throwable $e) {
            error_log("InstructorModel::actualizarToleranciaFicha: " . $e->getMessage());
            return false;
        }
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

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("InstructorModel::crearInstructor: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene el ID del instructor a partir de su ID de usuario en sesión.
     */
    public function obtenerIdInstructorPorUsuario(int $idUsuario): int|false
    {
        try {
            $sql = "SELECT id_instructor 
                    FROM instructores 
                    WHERE usuarios_id_usuario = :id_usuario 
                    LIMIT 1";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            $id = $stmt->fetchColumn();
            return $id ? (int)$id : false;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerIdInstructorPorUsuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcula las 4 métricas KPI del dashboard del instructor a través de la relación relacional correcta.
     */
    public function obtenerEstadisticasDashboard(int $idInstructor): array
    {
        $stats = [
            'fichasAsignadas'   => 0,
            'aprendicesACargo'  => 0,
            'retardosHoy'       => 0,
            'excusasPendientes' => 0,
        ];

        try {
            $pdo = Conexion::obtener();

            // 1. Fichas asignadas
            $stmtFichas = $pdo->prepare("SELECT COUNT(DISTINCT fichas_id_ficha) FROM instructores_has_fichas WHERE instructores_id_instructor = :id");
            $stmtFichas->execute([':id' => $idInstructor]);
            $stats['fichasAsignadas'] = (int)$stmtFichas->fetchColumn();

            // 2. Aprendices a cargo
            $stmtApr = $pdo->prepare("
                SELECT COUNT(DISTINCT a.id_aprendiz) 
                FROM aprendices a
                INNER JOIN instructores_has_fichas ihf ON a.fichas_id_ficha = ihf.fichas_id_ficha
                WHERE ihf.instructores_id_instructor = :id
            ");
            $stmtApr->execute([':id' => $idInstructor]);
            $stats['aprendicesACargo'] = (int)$stmtApr->fetchColumn();

            // 3. Retardos de hoy
            $stmtRet = $pdo->prepare("
                SELECT COUNT(asi.id_registro) 
                FROM asistencia asi
                INNER JOIN aprendices a ON asi.aprendices_id_aprendiz = a.id_aprendiz
                INNER JOIN instructores_has_fichas ihf ON a.fichas_id_ficha = ihf.fichas_id_ficha
                WHERE ihf.instructores_id_instructor = :id 
                  AND asi.estado = 'Retardo' 
                  AND asi.fecha = CURRENT_DATE()
            ");
            $stmtRet->execute([':id' => $idInstructor]);
            $stats['retardosHoy'] = (int)$stmtRet->fetchColumn();

            // 4. Excusas pendientes
            try {
                $stmtExc = $pdo->prepare("
                    SELECT COUNT(DISTINCT de.id_detalle_excusa)
                    FROM detalle_excusas de
                    INNER JOIN excusas_medicas e ON de.excusas_medicas_id_excusa = e.id_excusa
                    INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
                    INNER JOIN aprendices a ON asi.aprendices_id_aprendiz = a.id_aprendiz
                    INNER JOIN instructores_has_fichas ihf ON a.fichas_id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id AND de.estado = 'Pendiente'
                ");
                $stmtExc->execute([':id' => $idInstructor]);
                $stats['excusasPendientes'] = (int)$stmtExc->fetchColumn();
            } catch (Throwable $e) {
                $stats['excusasPendientes'] = 0;
            }

            return $stats;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerEstadisticasDashboard: " . $e->getMessage());
            return $stats;
        }
    }

    /**
     * Obtiene el listado de fichas asignadas al instructor con su horario y conteo de aprendices.
     */
    public function obtenerFichasConConteo(int $idInstructor): array
    {
        try {
            $sql = "SELECT f.id_ficha,
                           f.numero_ficha                          AS codigo,
                           f.programa                               AS programa,
                           CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha_completa,
                           h.dias_semana                            AS jornada,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p')  AS entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p')   AS salida,
                           COUNT(DISTINCT a.id_aprendiz)            AS aprendices
                    FROM instructores_has_fichas ihf
                    INNER JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha
                    LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                    WHERE ihf.instructores_id_instructor = :id
                    GROUP BY f.id_ficha, f.numero_ficha, f.programa, h.dias_semana, h.hora_entrada, h.hora_salida
                    ORDER BY f.numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id' => $idInstructor]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerFichasConConteo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el directorio de aprendices pertenecientes a las fichas a cargo del instructor.
     */
    public function obtenerAprendicesPorInstructor(int $idInstructor, ?string $filtroFicha = null): array
    {
        try {
            $sql = "SELECT a.id_aprendiz,
                           u.numero_documento                        AS documento,
                           CONCAT(u.nombres, ' ', u.apellidos)        AS nombre,
                           u.correo,
                           u.telefono,
                           CONCAT(f.numero_ficha, ' - ', f.programa)  AS ficha,
                           f.numero_ficha,
                           f.programa,
                           a.estado_matricula                         AS estado
                    FROM aprendices a
                    INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id";

            $params = [':id' => $idInstructor];

            if (!empty($filtroFicha) && $filtroFicha !== 'todas') {
                $sql .= " AND (CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha OR f.numero_ficha = :ficha)";
                $params[':ficha'] = $filtroFicha;
            }

            $sql .= " ORDER BY u.apellidos ASC, u.nombres ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            $aprendices = $stmt->fetchAll() ?: [];

            foreach ($aprendices as &$apr) {
                $apr['estado'] = mb_strtolower($apr['estado'] ?? '');
            }
            unset($apr);

            return $aprendices;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerAprendicesPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta las marcaciones de asistencia de aprendices en las fichas del instructor para una fecha dada.
     */
    public function obtenerIngresosPorInstructor(int $idInstructor, string $fecha, ?string $filtroFicha = null): array
    {
        try {
            $sql = "SELECT a.id_registro,
                           CONCAT(u.nombres, ' ', u.apellidos)        AS aprendiz,
                           CONCAT(f.numero_ficha, ' - ', f.programa)  AS ficha,
                           a.hora_entrada                             AS entrada,
                           a.hora_salida                              AS salida,
                           a.estado                                   AS estado,
                           a.minutos_retardo
                    FROM asistencia a
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u   ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f     ON ap.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id
                      AND a.fecha = :fecha";

            $params = [
                ':id'    => $idInstructor,
                ':fecha' => $fecha
            ];

            if (!empty($filtroFicha) && $filtroFicha !== 'todas') {
                $sql .= " AND (CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha OR f.numero_ficha = :ficha)";
                $params[':ficha'] = $filtroFicha;
            }

            $sql .= " ORDER BY a.hora_entrada DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerIngresosPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene la lista simple de fichas asignadas al instructor para alimentar filtros en selectores.
     */
    public function obtenerFichasFiltro(int $idInstructor): array
    {
        try {
            $sql = "SELECT DISTINCT 
                        f.id_ficha,
                        f.numero_ficha,
                        f.programa,
                        CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha
                    FROM fichas f
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id
                    ORDER BY f.numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id' => $idInstructor]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerFichasFiltro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las excusas médicas asociadas a las fichas del instructor.
     */
    public function obtenerExcusasPorInstructor(int $idInstructor, ?string $estado = null): array
    {
        try {
            $sql = "SELECT DISTINCT
                        e.id_excusa AS id,
                        CONCAT(u.nombres, ' ', u.apellidos) AS aprendiz,
                        CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha,
                        e.fecha_inicio AS fechaInasistencia,
                        e.fecha_radicacion AS fechaRadicado,
                        e.archivo_soporte AS soporte,
                        e.estado
                    FROM excusas_medicas e
                    INNER JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id";

            $params = [':id' => $idInstructor];

            if (!empty($estado) && $estado !== 'todas') {
                $sql .= " AND e.estado = :estado";
                $params[':estado'] = $estado;
            }

            $sql .= " ORDER BY e.fecha_radicacion DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerExcusasPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza el estado de una excusa médica (Aprobada / Rechazada).
     */
    public function actualizarEstadoExcusa(int $idExcusa, string $nuevoEstado): bool
    {
        try {
            $sql = "UPDATE excusas_medicas 
                    SET estado = :estado, fecha_evaluacion = NOW() 
                    WHERE id_excusa = :id_excusa";
            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':estado'    => $nuevoEstado,
                ':id_excusa' => $idExcusa
            ]);
        } catch (Throwable $e) {
            error_log("InstructorModel::actualizarEstadoExcusa: " . $e->getMessage());
            return false;
        }
    }
}

