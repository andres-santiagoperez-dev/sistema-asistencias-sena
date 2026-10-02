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
                           COALESCE(ihf.minutos_retardo, 15) AS minutos_retardo,
                           COALESCE(ihf.minutos_salida, 0) AS minutos_salida,
                           COUNT(DISTINCT a.id_aprendiz) AS total_aprendices
                    FROM instructores_has_fichas ihf
                    INNER JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                    WHERE ihf.instructores_id_instructor = :id_instructor
                    GROUP BY f.id_ficha, f.numero_ficha, f.programa, f.area, ihf.minutos_retardo, ihf.minutos_salida
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
     * Obtiene los bloques de horario asignados al instructor (3h y 6h) con días específicos, ambiente y competencia.
     */
    public function obtenerHorariosBloquesInstructor(int $idInstructor): array
    {
        try {
            $sql = "SELECT h.id_horario,
                           h.fichas_id_ficha,
                           f.numero_ficha,
                           f.programa,
                           f.area,
                           h.dias_semana,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p') AS salida,
                           TIME_FORMAT(h.hora_entrada, '%H:%i') AS raw_hora_entrada,
                           TIME_FORMAT(h.hora_salida, '%H:%i') AS raw_hora_salida,
                           h.duracion_horas,
                           h.tipo_bloque,
                           COALESCE(h.ambiente, 'Ambiente por asignar') AS ambiente,
                           COALESCE(h.competencia, 'Sin competencia asignada') AS competencia,
                           COALESCE(ihf.minutos_retardo, 15) AS minutos_retardo,
                           COALESCE(ihf.minutos_salida, 0) AS minutos_salida,
                           COUNT(DISTINCT a.id_aprendiz) AS total_aprendices
                    FROM horarios h
                    INNER JOIN fichas f ON h.fichas_id_ficha = f.id_ficha
                    LEFT JOIN instructores_has_fichas ihf 
                           ON (ihf.fichas_id_ficha = f.id_ficha AND ihf.instructores_id_instructor = :id_inst_tol)
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                    WHERE h.instructores_id_instructor = :id_instructor
                      AND h.estado = 'Activo'
                    GROUP BY h.id_horario, h.fichas_id_ficha, f.numero_ficha, f.programa, f.area,
                             h.dias_semana, h.hora_entrada, h.hora_salida, h.duracion_horas, h.tipo_bloque,
                             h.ambiente, h.competencia, ihf.minutos_retardo, ihf.minutos_salida
                    ORDER BY FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':id_instructor'   => $idInstructor,
                ':id_inst_tol'     => $idInstructor
            ]);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerHorariosBloquesInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el resumen de novedades de asistencia (retardos y excusas) para el calendario del instructor.
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

            return $novedades;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerNovedadesCalendario: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualiza la tolerancia de minutos de retardo (entrada) y salida de un instructor para una ficha específica.
     */
    public function actualizarToleranciaFicha(int $idInstructor, int $idFicha, int $minutosRetardo, int $minutosSalida = 0): bool
    {
        try {
            $sql = "UPDATE instructores_has_fichas 
                    SET minutos_retardo = :minutos,
                        minutos_salida  = :minutos_salida
                    WHERE instructores_id_instructor = :id_instructor AND fichas_id_ficha = :id_ficha";

            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':minutos'        => max(0, $minutosRetardo),
                ':minutos_salida' => max(0, $minutosSalida),
                ':id_instructor'  => $idInstructor,
                ':id_ficha'       => $idFicha
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
                           f.numero_ficha,
                           f.numero_ficha                          AS codigo,
                           f.programa                               AS programa,
                           CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha_completa,
                           COALESCE(
                               GROUP_CONCAT(
                                   DISTINCT CONCAT(h.dias_semana, ' (', TIME_FORMAT(h.hora_entrada, '%h:%i %p'), ' - ', TIME_FORMAT(h.hora_salida, '%h:%i %p'), ')')
                                   ORDER BY FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada
                                   SEPARATOR '<br>'
                               ),
                               'Sin horario asignado'
                           ) AS jornada_resumen,
                           GROUP_CONCAT(DISTINCT h.dias_semana ORDER BY FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo') SEPARATOR ', ') AS dias_asignados,
                           MIN(h.hora_entrada)                     AS min_hora_entrada,
                           MAX(h.hora_salida)                      AS max_hora_salida,
                           COALESCE(ihf.minutos_retardo, 15)        AS minutos_retardo,
                           COALESCE(ihf.minutos_salida, 0)          AS minutos_salida,
                           COUNT(DISTINCT a.id_aprendiz)            AS aprendices
                    FROM instructores_has_fichas ihf
                    INNER JOIN fichas f ON ihf.fichas_id_ficha = f.id_ficha
                    LEFT JOIN horarios h ON f.id_ficha = h.fichas_id_ficha AND (h.instructores_id_instructor = :id_inst OR h.instructores_id_instructor IS NULL) AND h.estado = 'Activo'
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                    WHERE ihf.instructores_id_instructor = :id
                    GROUP BY f.id_ficha, f.numero_ficha, f.programa, ihf.minutos_retardo, ihf.minutos_salida
                    ORDER BY f.numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':id'      => $idInstructor,
                ':id_inst' => $idInstructor
            ]);
            $fichas = $stmt->fetchAll() ?: [];

            $today = date('Y-m-d');
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

            // Consultar bloques de hoy para este instructor
            $stmtHoy = Conexion::obtener()->prepare(
                "SELECT fichas_id_ficha, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente
                 FROM horarios
                 WHERE instructores_id_instructor = :id AND dias_semana = :dia AND estado = 'Activo'
                 ORDER BY hora_entrada ASC"
            );
            $stmtHoy->execute([
                ':id'  => $idInstructor,
                ':dia' => $diaSemanaActual
            ]);
            $bloquesHoyTodos = $stmtHoy->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
            // Agrupar bloques de hoy por ficha
            $bloquesHoyPorFicha = [];
            foreach ($bloquesHoyTodos as $bh) {
                $idF = (int)$bh['fichas_id_ficha'];
                if (!isset($bloquesHoyPorFicha[$idF])) {
                    $bloquesHoyPorFicha[$idF] = $bh;
                }
            }

            foreach ($fichas as &$f) {
                $idF = (int)$f['id_ficha'];
                $tieneClaseHoy = isset($bloquesHoyPorFicha[$idF]);

                if ($tieneClaseHoy) {
                    $bloqueHoy = $bloquesHoyPorFicha[$idF];
                    $rawEntrada = $bloqueHoy['hora_entrada'];
                    $rawSalida  = $bloqueHoy['hora_salida'];
                    $f['jornada'] = 'Hoy (' . $diaSemanaActual . ')';
                } else {
                    $rawEntrada = $f['min_hora_entrada'] ?: '07:00:00';
                    $rawSalida  = $f['max_hora_salida'] ?: '13:00:00';
                    $f['jornada'] = $f['dias_asignados'] ?: 'Sin asignar';
                }

                $f['raw_hora_entrada'] = $rawEntrada;
                $f['raw_hora_salida']  = $rawSalida;
                $f['entrada']          = date('h:i A', strtotime($today . ' ' . $rawEntrada));
                $f['salida']           = date('h:i A', strtotime($today . ' ' . $rawSalida));
                $f['tiene_clase_hoy']  = $tieneClaseHoy;

                $tsEntrada = strtotime($today . ' ' . $rawEntrada);
                $tsSalida  = strtotime($today . ' ' . $rawSalida);

                $tsLimiteEntrada = $tsEntrada + ((int)$f['minutos_retardo'] * 60);
                $tsLimiteSalida  = $tsSalida - ((int)$f['minutos_salida'] * 60);

                $f['limite_entrada']     = date('h:i A', $tsLimiteEntrada);
                $f['limite_salida']      = date('h:i A', $tsLimiteSalida);
                $f['raw_limite_entrada'] = date('H:i', $tsLimiteEntrada);
                $f['raw_limite_salida']  = date('H:i', $tsLimiteSalida);

                // Formateo de texto de tolerancia
                $hEnt = intdiv((int)$f['minutos_retardo'], 60);
                $mEnt = (int)$f['minutos_retardo'] % 60;
                $txtEnt = '';
                if ($hEnt > 0) $txtEnt .= "{$hEnt}h ";
                if ($mEnt > 0 || $hEnt === 0) $txtEnt .= "{$mEnt}m";
                $f['texto_tolerancia_entrada'] = trim($txtEnt);

                $hSal = intdiv((int)$f['minutos_salida'], 60);
                $mSal = (int)$f['minutos_salida'] % 60;
                $txtSal = '';
                if ($hSal > 0) $txtSal .= "{$hSal}h ";
                if ($mSal > 0 || $hSal === 0) $txtSal .= "{$mSal}m";
                $f['texto_tolerancia_salida'] = trim($txtSal);
            }
            unset($f);

            return $fichas;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerFichasConConteo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta el directorio de aprendices pertenecientes a las fichas a cargo del instructor,
     * calculando los contadores globales de inasistencias (de todos los instructores).
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
                           a.estado_matricula                         AS estado,
                           (SELECT COUNT(*) FROM asistencia asi WHERE asi.aprendices_id_aprendiz = a.id_aprendiz AND asi.estado = 'Inasistencia') AS inasistencias_sin_justificar,
                           (SELECT COUNT(*) FROM asistencia asi WHERE asi.aprendices_id_aprendiz = a.id_aprendiz AND asi.estado = 'Justificada') AS inasistencias_justificadas,
                           (SELECT COUNT(*) FROM asistencia asi WHERE asi.aprendices_id_aprendiz = a.id_aprendiz AND (asi.estado = 'Inasistencia' OR asi.estado = 'Justificada')) AS total_inasistencias,
                           (SELECT COUNT(*) FROM asistencia asi WHERE asi.aprendices_id_aprendiz = a.id_aprendiz AND (asi.estado = 'Retardo' OR asi.estado = 'Salida Temprana')) AS total_retardos,
                           (SELECT COUNT(*) FROM asistencia asi WHERE asi.aprendices_id_aprendiz = a.id_aprendiz AND asi.excusas_medicas_id_excusa IS NOT NULL) AS total_excusas
                    FROM aprendices a
                    INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f   ON a.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id";

            $params = [':id' => $idInstructor];

            if (!empty($filtroFicha) && $filtroFicha !== 'todas') {
                $sql .= " AND (CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha_comp OR f.numero_ficha = :ficha_num OR f.id_ficha = :ficha_id)";
                $params[':ficha_comp'] = $filtroFicha;
                $params[':ficha_num']  = $filtroFicha;
                $params[':ficha_id']   = is_numeric($filtroFicha) ? (int)$filtroFicha : 0;
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
     * Obtiene el historial completo e ilimitado de inasistencias y asistencias de un aprendiz
     * registrado por cualquier instructor en la base de datos.
     */
    public function obtenerHistorialGlobalAprendiz(int $idAprendiz): array
    {
        try {
            $sql = "SELECT 
                        asi.id_registro,
                        asi.fecha,
                        asi.hora_entrada,
                        asi.hora_salida,
                        asi.estado,
                        asi.minutos_retardo,
                        f.numero_ficha,
                        f.programa,
                        COALESCE(CONCAT(u_inst.nombres, ' ', u_inst.apellidos), 'Instructor Registrado') AS instructor_nombre,
                        e.motivo AS excusa_motivo,
                        COALESCE(de.estado, 'Pendiente') AS excusa_estado
                    FROM asistencia asi
                    INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    LEFT JOIN instructores inst ON asi.instructores_id_instructor = inst.id_instructor
                    LEFT JOIN usuarios u_inst ON inst.usuarios_id_usuario = u_inst.id_usuario
                    LEFT JOIN excusas_medicas e ON asi.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa
                    WHERE asi.aprendices_id_aprendiz = :id_aprendiz
                    ORDER BY asi.fecha DESC, asi.id_registro DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_aprendiz' => $idAprendiz]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerHistorialGlobalAprendiz: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Consulta las marcaciones de asistencia de aprendices en las fichas del instructor para una fecha dada.
     */
    public function obtenerIngresosPorInstructor(int $idInstructor, string $fecha, ?string $filtroFicha = null): array
    {
        try {
            $sql = "SELECT DISTINCT 
                           a.id_registro,
                           u.numero_documento                        AS documento,
                           ap.codigo_rfid,
                           CONCAT(u.nombres, ' ', u.apellidos)        AS aprendiz,
                           CONCAT(f.numero_ficha, ' - ', f.programa)  AS ficha,
                           f.numero_ficha,
                           a.hora_entrada                             AS entrada,
                           a.hora_salida                              AS salida,
                           a.estado                                   AS estado,
                           a.minutos_retardo,
                           a.horarios_id_horario,
                           h.duracion_horas,
                           h.tipo_bloque,
                           h.ambiente,
                           h.competencia,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p')    AS bloque_entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p')     AS bloque_salida
                    FROM asistencia a
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u   ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f     ON ap.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN horarios h    ON a.horarios_id_horario = h.id_horario
                    WHERE ihf.instructores_id_instructor = :id
                      AND a.fecha = :fecha";

            $params = [
                ':id'    => $idInstructor,
                ':fecha' => $fecha
            ];

            if (!empty($filtroFicha) && $filtroFicha !== 'todas') {
                $sql .= " AND (CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha_comp OR f.numero_ficha = :ficha_num OR f.id_ficha = :ficha_id)";
                $params[':ficha_comp'] = $filtroFicha;
                $params[':ficha_num']  = $filtroFicha;
                $params[':ficha_id']   = is_numeric($filtroFicha) ? (int)$filtroFicha : 0;
            }

            $sql .= " ORDER BY a.hora_entrada DESC, a.id_registro DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerIngresosPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Procesa la marcación de asistencia por escaneo de código (RFID o número de documento)
     * para las fichas a cargo de un instructor.
     */
    public function registrarAsistenciaPorCodigo(int $idInstructor, string $codigoScanned): array
    {
        $codigoClean = trim($codigoScanned);
        if (empty($codigoClean)) {
            return [
                'success' => false,
                'type'    => 'danger',
                'message' => 'Por favor escanee o ingrese un código válido.'
            ];
        }

        try {
            $pdo = Conexion::obtener();

            // 1. Buscar el aprendiz en la base de datos por RFID o Documento
            $sqlBuscar = "SELECT a.id_aprendiz, a.codigo_rfid, a.estado_matricula, a.fichas_id_ficha,
                                 u.id_usuario, u.numero_documento, u.nombres, u.apellidos,
                                 f.id_ficha, f.numero_ficha, f.programa
                          FROM aprendices a
                          INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                          INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                          WHERE a.codigo_rfid = :c1 OR u.numero_documento = :c2 OR a.id_aprendiz = :c3
                          LIMIT 1";

            $stmtBuscar = $pdo->prepare($sqlBuscar);
            $stmtBuscar->execute([':c1' => $codigoClean, ':c2' => $codigoClean, ':c3' => $codigoClean]);
            $aprendiz = $stmtBuscar->fetch(PDO::FETCH_ASSOC);

            if (!$aprendiz) {
                return [
                    'success' => false,
                    'type'    => 'danger',
                    'message' => "Código/Documento '{$codigoClean}' no encontrado en el sistema."
                ];
            }

            $idAprendiz = (int)$aprendiz['id_aprendiz'];
            $idFicha    = (int)$aprendiz['fichas_id_ficha'];
            $nombreComp = trim($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']);

            // 2. Verificar que la ficha pertenece a las fichas asignadas al instructor
            $sqlFichaInst = "SELECT minutos_retardo, minutos_salida FROM instructores_has_fichas 
                             WHERE instructores_id_instructor = :id_inst AND fichas_id_ficha = :id_ficha 
                             LIMIT 1";
            $stmtFichaInst = $pdo->prepare($sqlFichaInst);
            $stmtFichaInst->execute([
                ':id_inst'  => $idInstructor,
                ':id_ficha' => $idFicha
            ]);
            $fichaInst = $stmtFichaInst->fetch(PDO::FETCH_ASSOC);

            if (!$fichaInst) {
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "El aprendiz {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) no pertenece a sus fichas asignadas."
                ];
            }

            // 3. Verificar estado de matrícula
            if (in_array(strtolower($aprendiz['estado_matricula']), ['retirado', 'aplazado'])) {
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "Atención: El aprendiz {$nombreComp} se encuentra en estado '{$aprendiz['estado_matricula']}'."
                ];
            }

            // 4. Determinar día actual y buscar bloques de horario activos
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
            $fechaHoy   = date('Y-m-d');
            $horaActual = date('H:i:s');

            // Consultar bloques de horario asignados para HOY a la ficha con este instructor
            $sqlBloques = "SELECT id_horario, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia
                           FROM horarios
                           WHERE fichas_id_ficha = :id_ficha
                             AND instructores_id_instructor = :id_inst
                             AND dias_semana = :dia_semana
                             AND estado = 'Activo'
                           ORDER BY hora_entrada ASC";
            $stmtBloques = $pdo->prepare($sqlBloques);
            $stmtBloques->execute([
                ':id_ficha'   => $idFicha,
                ':id_inst'    => $idInstructor,
                ':dia_semana' => $diaSemanaActual
            ]);
            $bloquesHoy = $stmtBloques->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Si no hay bloques específicos hoy con este instructor, verificar si hay bloques activos de la ficha hoy
            if (empty($bloquesHoy)) {
                $stmtBloquesGen = $pdo->prepare("SELECT id_horario, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia
                                                 FROM horarios
                                                 WHERE fichas_id_ficha = :id_ficha
                                                   AND dias_semana = :dia_semana
                                                   AND estado = 'Activo'
                                                 ORDER BY hora_entrada ASC");
                $stmtBloquesGen->execute([
                    ':id_ficha'   => $idFicha,
                    ':dia_semana' => $diaSemanaActual
                ]);
                $bloquesHoy = $stmtBloquesGen->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            // Si aún no hay bloques para el día de la semana actual (ej. pruebas en días no programados),
            // tomar los bloques activos configurados de la ficha como respaldo
            if (empty($bloquesHoy)) {
                $stmtFallback = $pdo->prepare("SELECT id_horario, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia, dias_semana
                                               FROM horarios
                                               WHERE fichas_id_ficha = :id_ficha
                                                 AND estado = 'Activo'
                                               ORDER BY FIELD(dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), hora_entrada ASC");
                $stmtFallback->execute([':id_ficha' => $idFicha]);
                $bloquesHoy = $stmtFallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            if (empty($bloquesHoy)) {
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "La Ficha {$aprendiz['numero_ficha']} no cuenta con bloques de horario configurados."
                ];
            }

            // 5. Seleccionar el bloque de horario correspondiente al momento del escaneo
            $bloqueSeleccionado = null;
            if (count($bloquesHoy) === 1) {
                $bloqueSeleccionado = $bloquesHoy[0];
            } else {
                // Evaluar cuál bloque corresponde al momento actual
                foreach ($bloquesHoy as $b) {
                    $stmtChk = $pdo->prepare("SELECT id_registro, hora_entrada, hora_salida, estado 
                                               FROM asistencia 
                                               WHERE aprendices_id_aprendiz = :id_ap 
                                                 AND fecha = :fecha 
                                                 AND horarios_id_horario = :id_h 
                                               LIMIT 1");
                    $stmtChk->execute([
                        ':id_ap' => $idAprendiz,
                        ':fecha' => $fechaHoy,
                        ':id_h'  => $b['id_horario']
                    ]);
                    $regBloque = $stmtChk->fetch(PDO::FETCH_ASSOC);

                    $tsFin = strtotime($fechaHoy . ' ' . $b['hora_salida']);
                    $tsAct = strtotime($fechaHoy . ' ' . $horaActual);

                    // Si no ha registrado entrada en este bloque y no ha finalizado
                    if (!$regBloque && $tsAct <= ($tsFin + 1800)) {
                        $bloqueSeleccionado = $b;
                        break;
                    }

                    // Si ya registró entrada pero no salida en este bloque
                    if ($regBloque && empty($regBloque['hora_salida'])) {
                        $bloqueSeleccionado = $b;
                        break;
                    }
                }

                // Fallback por proximidad horaria
                if (!$bloqueSeleccionado) {
                    $bloqueSeleccionado = $bloquesHoy[0];
                    $tsAct = strtotime($fechaHoy . ' ' . $horaActual);
                    $menorDiff = abs($tsAct - strtotime($fechaHoy . ' ' . $bloquesHoy[0]['hora_entrada']));
                    foreach ($bloquesHoy as $b) {
                        $diff = abs($tsAct - strtotime($fechaHoy . ' ' . $b['hora_entrada']));
                        if ($diff < $menorDiff) {
                            $menorDiff = $diff;
                            $bloqueSeleccionado = $b;
                        }
                    }
                }
            }

            $idHorarioSeleccionado = (int)$bloqueSeleccionado['id_horario'];

            // 6. Consultar si ya existe registro de asistencia para este bloque específico hoy
            $sqlAsistencia = "SELECT id_registro, hora_entrada, hora_salida, estado 
                              FROM asistencia 
                              WHERE aprendices_id_aprendiz = :id_aprendiz 
                                AND fecha = :fecha 
                                AND (horarios_id_horario = :id_horario OR (horarios_id_horario IS NULL AND instructores_id_instructor = :id_inst))
                              LIMIT 1";
            $stmtAsistencia = $pdo->prepare($sqlAsistencia);
            $stmtAsistencia->execute([
                ':id_aprendiz' => $idAprendiz,
                ':fecha'       => $fechaHoy,
                ':id_horario'  => $idHorarioSeleccionado,
                ':id_inst'     => $idInstructor
            ]);
            $regExistente = $stmtAsistencia->fetch(PDO::FETCH_ASSOC);

            $duracion = (int)($bloqueSeleccionado['duracion_horas'] ?? 3);
            $es6h = ($duracion >= 6 || ($bloqueSeleccionado['tipo_bloque'] ?? '') === '6H_CONTINUO');
            $tipoLabel = $es6h ? 'Jornada Continua (6h)' : 'Bloque ' . $duracion . 'h';
            $ambienteInfo = !empty($bloqueSeleccionado['ambiente']) ? ' · ' . $bloqueSeleccionado['ambiente'] : '';

            if (!$regExistente) {
                // --- CASO 1: REGISTRAR ENTRADA ---
                $horarioEntrada = $bloqueSeleccionado['hora_entrada'] ?: '07:00:00';
                $toleranciaMins  = (int)($fichaInst['minutos_retardo'] ?? 15);

                $tsEntradaEsperada  = strtotime($fechaHoy . ' ' . $horarioEntrada);
                $tsEntradaConTol    = $tsEntradaEsperada + ($toleranciaMins * 60);
                $tsHoraActual       = strtotime($fechaHoy . ' ' . $horaActual);

                $estado = 'Asistio';
                $minutosRetardo = 0;

                if ($tsHoraActual > $tsEntradaConTol) {
                    $estado = 'Retardo';
                    $minutosRetardo = (int)ceil(($tsHoraActual - $tsEntradaEsperada) / 60);
                }

                $sqlInsert = "INSERT INTO asistencia 
                              (fecha, hora_entrada, hora_salida, estado, minutos_retardo, aprendices_id_aprendiz, instructores_id_instructor, horarios_id_horario)
                              VALUES (:fecha, :hora_entrada, NULL, :estado, :minutos_retardo, :id_aprendiz, :id_instructor, :id_horario)";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':fecha'          => $fechaHoy,
                    ':hora_entrada'   => $horaActual,
                    ':estado'         => $estado,
                    ':minutos_retardo'=> $minutosRetardo,
                    ':id_aprendiz'    => $idAprendiz,
                    ':id_instructor'  => $idInstructor,
                    ':id_horario'     => $idHorarioSeleccionado
                ]);

                $horaFormateada = date('h:i A', strtotime($horaActual));
                $badgeTipo = ($estado === 'Asistio') ? 'success' : 'warning';
                $msgText = ($estado === 'Asistio')
                    ? "Entrada A TIEMPO [{$tipoLabel}{$ambienteInfo}] para {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) a las {$horaFormateada}."
                    : "Entrada CON RETARDO ({$minutosRetardo} min) [{$tipoLabel}{$ambienteInfo}] para {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) a las {$horaFormateada}.";

                return [
                    'success'  => true,
                    'type'     => $badgeTipo,
                    'action'   => 'entrada',
                    'estado'   => $estado,
                    'aprendiz' => $nombreComp,
                    'ficha'    => $aprendiz['numero_ficha'] . ' - ' . $aprendiz['programa'],
                    'hora'     => $horaFormateada,
                    'codigo'   => $aprendiz['codigo_rfid'] ?: $aprendiz['numero_documento'],
                    'bloque'   => $tipoLabel . $ambienteInfo,
                    'message'  => $msgText
                ];

            } else if (empty($regExistente['hora_salida'])) {
                // --- CASO 2: REGISTRAR SALIDA ---
                $horarioSalida = $bloqueSeleccionado['hora_salida'] ?: '13:00:00';
                $toleranciaSalidaMins = (int)($fichaInst['minutos_salida'] ?? 0);

                $tsSalidaEsperada = strtotime($fechaHoy . ' ' . $horarioSalida);
                $tsSalidaPermitida = $tsSalidaEsperada - ($toleranciaSalidaMins * 60);
                $tsHoraActual      = strtotime($fechaHoy . ' ' . $horaActual);

                $nuevoEstado = $regExistente['estado'];
                // Si la persona sale antes de la hora permitida con tolerancia y figuraba como Asistió
                if ($tsHoraActual < $tsSalidaPermitida && $regExistente['estado'] === 'Asistio') {
                    $nuevoEstado = 'Salida Temprana';
                }

                $sqlUpdate = "UPDATE asistencia 
                              SET hora_salida = :hora_salida,
                                  estado = :estado,
                                  horarios_id_horario = COALESCE(horarios_id_horario, :id_horario)
                              WHERE id_registro = :id_registro";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':hora_salida' => $horaActual,
                    ':estado'      => $nuevoEstado,
                    ':id_horario'  => $idHorarioSeleccionado,
                    ':id_registro' => $regExistente['id_registro']
                ]);

                $horaFormateada = date('h:i A', strtotime($horaActual));

                return [
                    'success'  => true,
                    'type'     => 'info',
                    'action'   => 'salida',
                    'estado'   => $nuevoEstado,
                    'aprendiz' => $nombreComp,
                    'ficha'    => $aprendiz['numero_ficha'] . ' - ' . $aprendiz['programa'],
                    'hora'     => $horaFormateada,
                    'codigo'   => $aprendiz['codigo_rfid'] ?: $aprendiz['numero_documento'],
                    'bloque'   => $tipoLabel . $ambienteInfo,
                    'message'  => "Salida registrada [{$tipoLabel}{$ambienteInfo}] para {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) a las {$horaFormateada}."
                ];

            } else {
                // --- CASO 3: YA REGISTRÓ ENTRADA Y SALIDA ---
                $hEntrada = date('h:i A', strtotime($regExistente['hora_entrada']));
                $hSalida  = date('h:i A', strtotime($regExistente['hora_salida']));

                return [
                    'success' => false,
                    'type'    => 'secondary',
                    'message' => "{$nombreComp} ya completó su registro de hoy [{$tipoLabel}]: Entrada {$hEntrada} / Salida {$hSalida}."
                ];
            }

        } catch (Throwable $e) {
            error_log("InstructorModel::registrarAsistenciaPorCodigo: " . $e->getMessage());
            return [
                'success' => false,
                'type'    => 'danger',
                'message' => 'Ocurrió un error inesperado al procesar la marcación.'
            ];
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
     * Obtiene las excusas médicas asociadas a las fichas del instructor con su estado relacional en detalle_excusas.
     */
    public function obtenerExcusasPorInstructor(int $idInstructor, ?string $estado = null): array
    {
        try {
            $sql = "SELECT 
                        e.id_excusa AS id,
                        de.id_detalle,
                        COALESCE(de.estado, 'Pendiente') AS estado,
                        e.motivo,
                        e.archivo_soporte AS soporte,
                        e.observaciones AS observaciones_aprendiz,
                        de.observaciones AS observaciones_instructor,
                        e.fecha_radicacion AS fechaRadicado,
                        de.fecha_evaluacion,
                        e.fecha_inicio AS fechaInasistencia,
                        e.fecha_fin,
                        CONCAT(u.nombres, ' ', u.apellidos) AS aprendiz,
                        u.numero_documento AS documento,
                        CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha,
                        f.numero_ficha,
                        f.programa,
                        COUNT(DISTINCT asi.id_registro) AS total_inasistencias
                    FROM excusas_medicas e
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa
                    INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa
                    INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    WHERE ihf.instructores_id_instructor = :id";

            $params = [':id' => $idInstructor];

            if (!empty($estado) && $estado !== 'todas') {
                $sql .= " AND (de.estado = :estado OR (de.estado IS NULL AND :estado = 'Pendiente'))";
                $params[':estado'] = $estado;
            }

            $sql .= " GROUP BY e.id_excusa, de.id_detalle, de.estado, e.motivo, e.archivo_soporte, 
                               e.observaciones, de.observaciones, e.fecha_radicacion, de.fecha_evaluacion, 
                               e.fecha_inicio, e.fecha_fin, u.nombres, u.apellidos, u.numero_documento, 
                               f.numero_ficha, f.programa
                      ORDER BY e.fecha_radicacion DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerExcusasPorInstructor: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Evalúa una excusa médica (Aprobado / Rechazado) mediante transacción atómica,
     * actualizando detalle_excusas y sincronizando las inasistencias en la tabla asistencia.
     */
    public function evaluarExcusa(int $idExcusa, int $idInstructor, string $decision, ?string $observaciones = null): bool
    {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            $estadoDetalle = (stripos($decision, 'aprob') !== false) ? 'Aprobado' : 'Rechazado';

            // 1. Verificar si ya existe registro en detalle_excusas o crearlo/actualizarlo
            $stmtCheck = $pdo->prepare("SELECT id_detalle FROM detalle_excusas WHERE excusas_medicas_id_excusa = :id_excusa LIMIT 1");
            $stmtCheck->execute([':id_excusa' => $idExcusa]);
            $idDetalle = $stmtCheck->fetchColumn();

            if ($idDetalle) {
                $sqlDetalle = "UPDATE detalle_excusas 
                               SET estado = :estado, 
                                   fecha_evaluacion = NOW(), 
                                   observaciones = :observaciones, 
                                   instructores_id_instructor = :id_instructor
                               WHERE id_detalle = :id_detalle";
                $stmtDet = $pdo->prepare($sqlDetalle);
                $stmtDet->execute([
                    ':estado'        => $estadoDetalle,
                    ':observaciones' => $observaciones,
                    ':id_instructor' => $idInstructor,
                    ':id_detalle'    => $idDetalle
                ]);
            } else {
                $sqlDetalle = "INSERT INTO detalle_excusas (estado, fecha_evaluacion, observaciones, excusas_medicas_id_excusa, instructores_id_instructor)
                               VALUES (:estado, NOW(), :observaciones, :id_excusa, :id_instructor)";
                $stmtDet = $pdo->prepare($sqlDetalle);
                $stmtDet->execute([
                    ':estado'        => $estadoDetalle,
                    ':observaciones' => $observaciones,
                    ':id_excusa'     => $idExcusa,
                    ':id_instructor' => $idInstructor
                ]);
            }

            // 2. Sincronizar tabla asistencia
            $nuevoEstadoAsistencia = ($estadoDetalle === 'Aprobado') ? 'Justificada' : 'Inasistencia';
            $sqlAsistencia = "UPDATE asistencia 
                              SET estado = :estado_asistencia 
                              WHERE excusas_medicas_id_excusa = :id_excusa";
            $stmtAsi = $pdo->prepare($sqlAsistencia);
            $stmtAsi->execute([
                ':estado_asistencia' => $nuevoEstadoAsistencia,
                ':id_excusa'         => $idExcusa
            ]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("InstructorModel::evaluarExcusa: " . $e->getMessage());
            return false;
        }
    }
}

