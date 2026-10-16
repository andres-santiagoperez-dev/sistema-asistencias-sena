<?php
/**
 * Modelo de Aprendices (AprendizModel)
 * 
 * Responsabilidad:
 * 1. Consultar aprendices matriculados con su información de usuario y ficha asignada.
 * 2. Filtrar por área técnica ('comercio' o 'Tics') y por ficha de formación.
 * 3. Consultar fichas disponibles para el filtro y la asignación en el modal.
 * 4. Registrar nuevos aprendices (creación en `usuarios` + registro en `aprendices`) vía transacción.
 * 5. Actualizar el estado de matrícula de un aprendiz.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class AprendizModel
{
    /**
     * Lista los aprendices con sus datos personales, ficha y estado.
     * Permite filtrar opcionalmente por área técnica y/o por ficha específica.
     */
    public function listarAprendices(?string $area = null, ?int $idFicha = null): array
    {
        $sql = "SELECT 
                    a.id_aprendiz,
                    a.codigo_rfid,
                    a.estado_matricula,
                    u.id_usuario,
                    u.tipo_documento,
                    u.numero_documento AS documento,
                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre,
                    u.nombres,
                    u.apellidos,
                    u.correo,
                    u.telefono,
                    u.estado AS estado_usuario,
                    f.id_ficha,
                    f.numero_ficha,
                    f.programa,
                    f.area,
                    CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha_completa
                FROM aprendices a
                INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha";

        $condiciones = [];
        $params      = [];

        if ($area !== null && $area !== '' && $area !== 'todas') {
            $condiciones[] = "f.area = :area";
            $params[':area'] = $area;
        }

        if ($idFicha !== null && $idFicha > 0) {
            $condiciones[] = "f.id_ficha = :id_ficha";
            $params[':id_ficha'] = $idFicha;
        }

        if (!empty($condiciones)) {
            $sql .= " WHERE " . implode(' AND ', $condiciones);
        }

        $sql .= " ORDER BY u.apellidos ASC, u.nombres ASC";

        try {
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::listarAprendices: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el listado de fichas disponibles para los selectores de filtro y asignación.
     */
    public function obtenerFichasParaSelect(?string $area = null): array
    {
        try {
            $sql = "SELECT id_ficha, numero_ficha, programa, area,
                           CONCAT(numero_ficha, ' - ', programa) AS etiqueta
                    FROM fichas";
            
            $params = [];
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE area = :area";
                $params[':area'] = $area;
            }

            $sql .= " ORDER BY numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerFichasParaSelect: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca la información de un aprendiz por su ID.
     */
    public function buscarPorId(int $idAprendiz): array|false
    {
        try {
            $sql = "SELECT 
                        a.id_aprendiz,
                        a.codigo_rfid,
                        a.estado_matricula,
                        u.id_usuario,
                        u.tipo_documento,
                        u.numero_documento AS documento,
                        u.nombres,
                        u.apellidos,
                        u.correo,
                        u.telefono,
                        f.id_ficha,
                        f.numero_ficha,
                        f.programa,
                        f.area
                    FROM aprendices a
                    INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
                    WHERE a.id_aprendiz = :id_aprendiz
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);

            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("AprendizModel::buscarPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un nuevo aprendiz mediante transacción atómica (Usuario + Registro de Aprendiz).
     */
    public function crearAprendiz(array $datosUsuario, int $idFicha, string $codigoRfid, string $estadoMatricula = 'Matriculado'): int
    {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 1. Obtener ID del rol 'Aprendiz'
            $stmtRol = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre_rol LIKE '%aprendiz%' LIMIT 1");
            $stmtRol->execute();
            $rol = $stmtRol->fetch();
            $idRol = $rol ? (int)$rol['id_rol'] : 3;

            // 2. Hash de contraseña por defecto (o suministrada)
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

            // 4. Insertar registro técnico de aprendiz vinculado a la ficha
            $sqlAprendiz = "INSERT INTO aprendices (codigo_rfid, estado_matricula, usuarios_id_usuario, fichas_id_ficha)
                            VALUES (:rfid, :estado, :id_usuario, :id_ficha)";
            $stmtApr = $pdo->prepare($sqlAprendiz);
            $stmtApr->execute([
                ':rfid'       => $codigoRfid,
                ':estado'     => $estadoMatricula,
                ':id_usuario' => $idUsuario,
                ':id_ficha'   => $idFicha
            ]);

            $idAprendiz = (int)$pdo->lastInsertId();

            $pdo->commit();
            return $idAprendiz;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("AprendizModel::crearAprendiz: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza el estado de matrícula de un aprendiz (Matriculado, Retirado, Aplazado).
     */
    public function actualizarEstadoMatricula(int $idAprendiz, string $nuevoEstado): bool
    {
        try {
            $sql = "UPDATE aprendices 
                    SET estado_matricula = :estado
                    WHERE id_aprendiz = :id_aprendiz";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':estado'      => $nuevoEstado,
                ':id_aprendiz' => $idAprendiz
            ]);
        } catch (Throwable $e) {
            error_log("AprendizModel::actualizarEstadoMatricula: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los identificadores de sesión de un aprendiz a partir de su ID de usuario.
     */
    public function obtenerIdAprendizPorUsuario(int $idUsuario): array|false
    {
        try {
            $sql = "SELECT id_aprendiz, fichas_id_ficha, codigo_rfid
                    FROM aprendices
                    WHERE usuarios_id_usuario = :id_usuario
                    LIMIT 1";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_usuario' => $idUsuario]);
            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerIdAprendizPorUsuario: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta la información de horario, ficha y programa formativo para el portal.
     */
    public function obtenerHorarioPorId(int $idAprendiz): array|false
    {
        try {
            $sql = "SELECT a.id_aprendiz, a.codigo_rfid, f.id_ficha, f.numero_ficha, f.programa, f.area
                    FROM aprendices a
                    LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    WHERE a.id_aprendiz = :id_aprendiz
                    LIMIT 1";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerHorarioPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el texto descriptivo de la jornada activa de hoy para el aprendiz.
     * Si hoy tiene formación, devuelve: "Hoy (Lunes): 06:00 AM - 12:00 PM · Lab TIC 1".
     * Si hoy no tiene clase, devuelve los días en que tiene clase o "Sin jornada formativa hoy".
     */
    public function obtenerJornadaActivaAprendiz(int $idAprendiz): string
    {
        try {
            $diasEspanol = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miercoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sabado',
                7 => 'Domingo'
            ];
            $diaSemanaActual = $diasEspanol[(int)date('N')] ?? 'Lunes';

            // 1. Consultar si hay bloque hoy para la ficha del aprendiz
            $sqlHoy = "SELECT h.hora_entrada, h.hora_salida, h.duracion_horas, h.tipo_bloque, h.ambiente
                       FROM aprendices a
                       INNER JOIN horarios h ON a.fichas_id_ficha = h.fichas_id_ficha
                       WHERE a.id_aprendiz = :id_aprendiz
                         AND h.dias_semana = :dia_actual
                         AND h.estado = 'Activo'
                       ORDER BY h.hora_entrada ASC
                       LIMIT 1";
            $stmtHoy = Conexion::obtener()->prepare($sqlHoy);
            $stmtHoy->execute([
                ':id_aprendiz' => $idAprendiz,
                ':dia_actual'  => $diaSemanaActual
            ]);
            $bloqueHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);

            if ($bloqueHoy) {
                $hEnt = date('h:i A', strtotime($bloqueHoy['hora_entrada']));
                $hSal = date('h:i A', strtotime($bloqueHoy['hora_salida']));
                $amb  = !empty($bloqueHoy['ambiente']) ? ' · ' . $bloqueHoy['ambiente'] : '';
                return "Hoy ({$diaSemanaActual}): {$hEnt} - {$hSal}{$amb}";
            }

            // 2. Si hoy no tiene clase, consultar los días asignados en general
            $sqlDias = "SELECT DISTINCT h.dias_semana, MIN(h.hora_entrada) as min_ent, MAX(h.hora_salida) as max_sal
                        FROM aprendices a
                        INNER JOIN horarios h ON a.fichas_id_ficha = h.fichas_id_ficha
                        WHERE a.id_aprendiz = :id_aprendiz AND h.estado = 'Activo'
                        GROUP BY h.dias_semana
                        ORDER BY FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo')";
            $stmtDias = Conexion::obtener()->prepare($sqlDias);
            $stmtDias->execute([':id_aprendiz' => $idAprendiz]);
            $diasAsignados = $stmtDias->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!empty($diasAsignados)) {
                $nombresDias = array_column($diasAsignados, 'dias_semana');
                $diasTexto = implode(', ', $nombresDias);
                $primeraEntrada = date('h:i A', strtotime($diasAsignados[0]['min_ent']));
                $ultimaSalida   = date('h:i A', strtotime($diasAsignados[0]['max_sal']));
                return "Sin clase hoy ({$diaSemanaActual}) · Días: {$diasTexto} ({$primeraEntrada} - {$ultimaSalida})";
            }

            return "Sin jornada formativa asignada";
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerJornadaActivaAprendiz: " . $e->getMessage());
            return "Jornada formativa SENA";
        }
    }

    /**
     * Obtiene todos los bloques de horario activos asignados a la ficha del aprendiz (3h y 6h).
     */
    public function obtenerBloquesHorarioAprendiz(int $idAprendiz): array
    {
        try {
            $sql = "SELECT h.id_horario,
                           h.fichas_id_ficha,
                           h.fecha,
                           h.dias_semana,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p') AS salida,
                           TIME_FORMAT(h.hora_entrada, '%H:%i') AS raw_hora_entrada,
                           TIME_FORMAT(h.hora_salida, '%H:%i') AS raw_hora_salida,
                           h.duracion_horas,
                           h.tipo_bloque,
                           COALESCE(h.ambiente, 'Ambiente por asignar') AS ambiente,
                           COALESCE(h.competencia, 'Sin competencia asignada') AS competencia,
                           COALESCE(CONCAT(u.nombres, ' ', u.apellidos), 'Instructor asignado') AS instructor_nombre,
                           f.numero_ficha,
                           f.programa
                    FROM aprendices a
                    INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    INNER JOIN horarios h ON f.id_ficha = h.fichas_id_ficha
                    LEFT JOIN instructores i ON h.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                    WHERE a.id_aprendiz = :id_aprendiz
                      AND h.estado = 'Activo'
                    ORDER BY COALESCE(h.fecha, '2099-12-31') ASC, FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerBloquesHorarioAprendiz: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el registro de marcación de hoy para el aprendiz (indicador RFID en vivo).
     */
    public function obtenerMarcacionHoy(int $idAprendiz): array|false
    {
        try {
            $sql = "SELECT id_registro, hora_entrada, hora_salida, estado 
                    FROM asistencia 
                    WHERE aprendices_id_aprendiz = :id_aprendiz AND fecha = CURRENT_DATE()
                    LIMIT 1";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerMarcacionHoy: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta el historial completo de marcaciones del aprendiz con el nombre del instructor de cada clase.
     */
    public function obtenerHistorialAsistencia(int $idAprendiz, ?string $inicio = null, ?string $fin = null): array
    {
        try {
            $sql = "SELECT a.id_registro, a.fecha, a.hora_entrada, a.hora_salida, a.minutos_retardo, a.estado,
                           a.horarios_id_horario,
                           h.duracion_horas,
                           h.tipo_bloque,
                           h.ambiente,
                           h.competencia,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS bloque_hora_entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p')  AS bloque_hora_salida,
                           de.estado AS estado_excusa,
                           e.motivo AS excusa_motivo,
                           COALESCE(CONCAT(inst_u.nombres, ' ', inst_u.apellidos), 'Instructor Registrado') AS instructor_nombre
                    FROM asistencia a
                    LEFT JOIN instructores inst ON a.instructores_id_instructor = inst.id_instructor
                    LEFT JOIN usuarios inst_u ON inst.usuarios_id_usuario = inst_u.id_usuario
                    LEFT JOIN horarios h ON a.horarios_id_horario = h.id_horario
                    LEFT JOIN excusas_medicas e ON a.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa
                    WHERE a.aprendices_id_aprendiz = :id_aprendiz";
            
            $params = [':id_aprendiz' => $idAprendiz];

            if (!empty($inicio) && !empty($fin)) {
                $sql .= " AND a.fecha BETWEEN :inicio AND :fin";
                $params[':inicio'] = $inicio;
                $params[':fin']    = $fin;
            }

            $sql .= " ORDER BY a.fecha DESC, a.id_registro DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerHistorialAsistencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula los KPIs estadísticos de asistencia para el tablero del aprendiz,
     * incluyendo el contador global de inasistencias totales.
     */
    public function obtenerEstadisticasHistorial(int $idAprendiz, ?string $inicio = null, ?string $fin = null): array
    {
        $default = [
            'a_tiempo'                   => 0,
            'retardos'                   => 0,
            'inasistencias'              => 0,
            'inasistencias_justificadas'  => 0,
            'total_inasistencias_global' => 0
        ];

        try {
            $sql = "SELECT 
                        COALESCE(SUM(CASE WHEN a.estado = 'Asistio' THEN 1 ELSE 0 END), 0) AS a_tiempo,
                        COALESCE(SUM(CASE WHEN a.estado = 'Retardo' OR a.estado = 'Salida Temprana' THEN 1 ELSE 0 END), 0) AS retardos,
                        COALESCE(SUM(CASE WHEN a.estado = 'Inasistencia' AND (de.estado IS NULL OR (de.estado != 'Aprobada' AND de.estado != 'Aprobado')) THEN 1 ELSE 0 END), 0) AS inasistencias,
                        COALESCE(SUM(CASE WHEN a.estado = 'Justificada' OR (a.estado = 'Inasistencia' AND (de.estado = 'Aprobada' OR de.estado = 'Aprobado')) THEN 1 ELSE 0 END), 0) AS inasistencias_justificadas,
                        COALESCE(SUM(CASE WHEN a.estado = 'Inasistencia' OR a.estado = 'Justificada' THEN 1 ELSE 0 END), 0) AS total_inasistencias_global
                    FROM asistencia a
                    LEFT JOIN excusas_medicas e ON a.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa
                    WHERE a.aprendices_id_aprendiz = :id_aprendiz";

            $params = [':id_aprendiz' => $idAprendiz];

            if (!empty($inicio) && !empty($fin)) {
                $sql .= " AND a.fecha BETWEEN :inicio AND :fin";
                $params[':inicio'] = $inicio;
                $params[':fin']    = $fin;
            }

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch();

            if ($res) {
                return [
                    'a_tiempo'                   => (int)($res['a_tiempo'] ?? 0),
                    'retardos'                   => (int)($res['retardos'] ?? 0),
                    'inasistencias'              => (int)($res['inasistencias'] ?? 0),
                    'inasistencias_justificadas'  => (int)($res['inasistencias_justificadas'] ?? 0),
                    'total_inasistencias_global' => (int)($res['total_inasistencias_global'] ?? 0)
                ];
            }

            return $default;
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerEstadisticasHistorial: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * Consulta la ficha técnica completa del aprendiz, formación e instructor líder asignado.
     */
    public function obtenerPerfilCompleto(int $idAprendiz): array|false
    {
        try {
            $sql = "SELECT a.id_aprendiz, a.codigo_rfid, a.estado_matricula,
                           u.id_usuario, u.tipo_documento, u.numero_documento AS documento,
                           u.nombres, u.apellidos, u.correo, u.telefono,
                           f.id_ficha, f.numero_ficha, f.programa, f.area,
                           h.hora_entrada, h.hora_salida,
                           inst_u.nombres AS instructor_nombres,
                           inst_u.apellidos AS instructor_apellidos,
                           inst_u.correo AS instructor_correo
                    FROM aprendices a
                    INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                    LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    LEFT JOIN horarios h ON f.id_ficha = h.fichas_id_ficha
                    LEFT JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN instructores i ON ihf.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios inst_u ON i.usuarios_id_usuario = inst_u.id_usuario
                    WHERE a.id_aprendiz = :id_aprendiz
                    LIMIT 1";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetch() ?: false;
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerPerfilCompleto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza la información de contacto (correo y teléfono) del usuario aprendiz.
     */
    public function actualizarContacto(int $idUsuario, string $correo, ?string $telefono = null): bool
    {
        try {
            $sql = "UPDATE usuarios 
                    SET correo = :correo, telefono = :telefono 
                    WHERE id_usuario = :id_usuario";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':correo'     => $correo,
                ':telefono'   => $telefono,
                ':id_usuario' => $idUsuario
            ]);
        } catch (Throwable $e) {
            error_log("AprendizModel::actualizarContacto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Consulta las inasistencias del aprendiz pendientes de justificación médica
     * que aún no han sido vinculadas a ninguna excusa médica radicada.
     */
    public function obtenerInasistenciasSinExcusa(int $idAprendiz): array
    {
        try {
            $sql = "SELECT asi.id_registro, asi.fecha, asi.hora_entrada, asi.hora_salida, asi.estado
                    FROM asistencia asi
                    WHERE asi.aprendices_id_aprendiz = :id_aprendiz
                      AND asi.estado = 'Inasistencia'
                      AND asi.excusas_medicas_id_excusa IS NULL
                    ORDER BY asi.fecha DESC, asi.id_registro DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerInasistenciasSinExcusa: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el historial de excusas radicadas por el aprendiz, incorporando
     * el estado relacional de evaluación desde detalle_excusas y comentarios docentes.
     */
    public function obtenerHistorialExcusas(int $idAprendiz): array
    {
        try {
            $sql = "SELECT 
                        e.id_excusa AS id,
                        e.motivo,
                        e.archivo_soporte AS soporte,
                        e.observaciones AS observaciones_aprendiz,
                        e.fecha_radicacion AS fecha_radicado,
                        e.fecha_inicio,
                        e.fecha_fin,
                        COALESCE(de.estado, 'Pendiente') AS estado,
                        de.fecha_evaluacion,
                        de.observaciones AS observaciones_instructor,
                        CONCAT(inst_u.nombres, ' ', inst_u.apellidos) AS instructor_evaluador,
                        COUNT(DISTINCT CASE WHEN asi.instructores_id_instructor = de.instructores_id_instructor THEN asi.id_registro END) AS total_inasistencias
                    FROM excusas_medicas e
                    INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN detalle_excusas de ON de.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN instructores i ON de.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios inst_u ON i.usuarios_id_usuario = inst_u.id_usuario
                    WHERE asi.aprendices_id_aprendiz = :id_aprendiz
                    GROUP BY e.id_excusa, de.id_detalle, de.estado, e.motivo, e.archivo_soporte,
                             e.observaciones, de.observaciones, e.fecha_radicacion, de.fecha_evaluacion,
                             e.fecha_inicio, e.fecha_fin, inst_u.nombres, inst_u.apellidos
                    ORDER BY e.fecha_radicacion DESC, e.id_excusa DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerHistorialExcusas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Radica una nueva excusa médica mediante transacción atómica:
     * 1. Valida las inasistencias pertenecientes al aprendiz sin justificar.
     * 2. Calcula las fechas de inicio y fin.
     * 3. Inserta la excusa médica en excusas_medicas.
     * 4. Asigna el registro de evaluación inicial 'Pendiente' en detalle_excusas.
     * 5. Vincula los registros de inasistencia en la tabla asistencia.
     */
    public function radicarExcusa(int $idAprendiz, array $inasistenciasIds, string $motivo, ?string $archivoSoporte = null, ?string $observaciones = null): bool
    {
        $inasistenciasValidas = array_values(array_filter(array_map('intval', $inasistenciasIds), fn($id) => $id > 0));
        if (empty($inasistenciasValidas)) {
            return false;
        }

        $motivosValidos = [
            'enfermedad'  => 'Enfermedad',
            'cita medica' => 'Cita Medica',
            'cita médica' => 'Cita Medica',
            'calamidad'   => 'Calamidad',
            'otro'        => 'Otro'
        ];
        $motivoLimpio = mb_strtolower(trim($motivo));
        $motivoFinal = $motivosValidos[$motivoLimpio] ?? 'Otro';

        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            $placeholders = implode(',', array_fill(0, count($inasistenciasValidas), '?'));

            // 1. Validar pertenencia de las inasistencias y calcular rango de fechas
            $sqlFechas = "SELECT MIN(fecha) AS min_fecha, MAX(fecha) AS max_fecha, COUNT(*) AS total
                          FROM asistencia 
                          WHERE id_registro IN ($placeholders)
                            AND aprendices_id_aprendiz = ?
                            AND estado = 'Inasistencia'
                            AND excusas_medicas_id_excusa IS NULL";
            $paramsFechas = array_merge($inasistenciasValidas, [$idAprendiz]);
            $stmtFechas = $pdo->prepare($sqlFechas);
            $stmtFechas->execute($paramsFechas);
            $datosFechas = $stmtFechas->fetch();

            if (!$datosFechas || (int)$datosFechas['total'] === 0) {
                $pdo->rollBack();
                return false;
            }

            $fechaInicio = $datosFechas['min_fecha'] ?: date('Y-m-d');
            $fechaFin    = $datosFechas['max_fecha'] ?: $fechaInicio;

            // 2. Determinar TODOS los instructores a cargo de los bloques de inasistencia seleccionados
            $sqlInsts = "SELECT DISTINCT instructores_id_instructor 
                         FROM asistencia 
                         WHERE id_registro IN ($placeholders) 
                           AND instructores_id_instructor IS NOT NULL";
            $stmtInsts = $pdo->prepare($sqlInsts);
            $stmtInsts->execute($inasistenciasValidas);
            $instructoresAfectados = $stmtInsts->fetchAll(PDO::FETCH_COLUMN);

            // Respaldo de seguridad si alguna asistencia no tenía instructor
            if (empty($instructoresAfectados)) {
                $sqlFichaInst = "SELECT ihf.instructores_id_instructor 
                                 FROM aprendices a
                                 INNER JOIN instructores_has_fichas ihf ON a.fichas_id_ficha = ihf.fichas_id_ficha
                                 WHERE a.id_aprendiz = ?
                                 LIMIT 1";
                $stmtFichaInst = $pdo->prepare($sqlFichaInst);
                $stmtFichaInst->execute([$idAprendiz]);
                $instFallback = $stmtFichaInst->fetchColumn();
                if ($instFallback) {
                    $instructoresAfectados = [(int)$instFallback];
                } else {
                    $instructoresAfectados = [(int)$pdo->query("SELECT id_instructor FROM instructores LIMIT 1")->fetchColumn()];
                }
            }

            // 3. Insertar la excusa médica
            $sqlExcusa = "INSERT INTO excusas_medicas (motivo, archivo_soporte, observaciones, fecha_radicacion, fecha_inicio, fecha_fin)
                          VALUES (:motivo, :archivo, :observaciones, NOW(), :fecha_inicio, :fecha_fin)";
            $stmtExc = $pdo->prepare($sqlExcusa);
            $stmtExc->execute([
                ':motivo'        => $motivoFinal,
                ':archivo'       => $archivoSoporte ?: 'sin_archivo',
                ':observaciones' => !empty($observaciones) ? $observaciones : null,
                ':fecha_inicio'  => $fechaInicio,
                ':fecha_fin'     => $fechaFin
            ]);
            $idExcusa = (int)$pdo->lastInsertId();

            // 4. Insertar detalle de evaluación inicial para CADA instructor involucrado
            $sqlDetalle = "INSERT INTO detalle_excusas (estado, observaciones, excusas_medicas_id_excusa, instructores_id_instructor)
                           VALUES ('Pendiente', NULL, :id_excusa, :id_instructor)";
            $stmtDet = $pdo->prepare($sqlDetalle);
            foreach ($instructoresAfectados as $idInst) {
                if ($idInst > 0) {
                    $stmtDet->execute([
                        ':id_excusa'     => $idExcusa,
                        ':id_instructor' => (int)$idInst
                    ]);
                }
            }

            // 5. Vincular inasistencias a la excusa médica
            $sqlUpdate = "UPDATE asistencia 
                          SET excusas_medicas_id_excusa = ? 
                          WHERE id_registro IN ($placeholders) 
                            AND aprendices_id_aprendiz = ?";
            $paramsUpdate = array_merge([$idExcusa], $inasistenciasValidas, [$idAprendiz]);
            $stmtUpd = $pdo->prepare($sqlUpdate);
            $stmtUpd->execute($paramsUpdate);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("AprendizModel::radicarExcusa: " . $e->getMessage());
            return false;
        }
    }
}
