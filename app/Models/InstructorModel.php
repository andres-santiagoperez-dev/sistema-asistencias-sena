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
                           h.fecha,
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
                    GROUP BY h.id_horario, h.fichas_id_ficha, h.fecha, f.numero_ficha, f.programa, f.area,
                             h.dias_semana, h.hora_entrada, h.hora_salida, h.duracion_horas, h.tipo_bloque,
                             h.ambiente, h.competencia, ihf.minutos_retardo, ihf.minutos_salida
                    ORDER BY COALESCE(h.fecha, '2099-12-31') ASC, FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada ASC";

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
    /* * Verifica preventivamente si un número de documento ya está registrado en el sistema.
     */ 
    public function existeDocumento(string $documento, ?int $excluirIdUsuario = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE numero_documento = :doc";
            $params = [':doc' => trim($documento)];
            if ($excluirIdUsuario !== null) {
                $sql .= " AND id_usuario != :id_excluir";
                $params[':id_excluir'] = $excluirIdUsuario;
            }
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            error_log("InstructorModel::existeDocumento: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Verifica preventivamente si un correo electrónico ya está registrado en el sistema.
     */
    public function existeCorreo(string $correo, ?int $excluirIdUsuario = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE correo = :correo";
            $params = [':correo' => trim($correo)];
            if ($excluirIdUsuario !== null) {
                $sql .= " AND id_usuario != :id_excluir";
                $params[':id_excluir'] = $excluirIdUsuario;
            }
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            error_log("InstructorModel::existeCorreo: " . $e->getMessage());
            return false;
        }
    }
    /**
    /**
     * Registra un nuevo instructor en el sistema (Usuario + Registro técnico).
     */
    public function crearInstructor(array $datosUsuario, string $area, string $especialidad): int
    {
        $doc    = trim($datosUsuario['documento'] ?? '');
        $correo = trim($datosUsuario['correo'] ?? '');
        // Validaciones preventivas a nivel de modelo
        if ($this->existeDocumento($doc)) {
            throw new InvalidArgumentException("El número de documento '{$doc}' ya se encuentra registrado en el sistema.");
        }
        if ($this->existeCorreo($correo)) {
            throw new InvalidArgumentException("El correo electrónico '{$correo}' ya se encuentra registrado en el sistema.");
        }
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

            // 4. Excusas pendientes directas del instructor
            try {
                $stmtExc = $pdo->prepare("
                    SELECT COUNT(DISTINCT de.id_detalle)
                    FROM detalle_excusas de
                    WHERE de.instructores_id_instructor = :id 
                      AND de.estado = 'Pendiente'
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

            // Incorporar fichas asignadas al instructor mediante SUPLENCIA ACEPTADA y vigente hoy
            $sqlSupl = "SELECT DISTINCT 
                            f.id_ficha,
                            f.numero_ficha,
                            f.programa,
                            CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha_completa,
                            s.id_suplencia,
                            s.instructor_titular_id,
                            s.motivo AS suplencia_motivo,
                            CONCAT(u_tit.nombres, ' ', u_tit.apellidos) AS nombre_titular,
                            COALESCE(ihf.minutos_retardo, 15)        AS minutos_retardo,
                            COALESCE(ihf.minutos_salida, 0)          AS minutos_salida,
                            COUNT(DISTINCT a.id_aprendiz)            AS aprendices
                        FROM suplencias_horarios s
                        INNER JOIN fichas f ON s.fichas_id_ficha = f.id_ficha
                        INNER JOIN instructores i_tit ON s.instructor_titular_id = i_tit.id_instructor
                        INNER JOIN usuarios u_tit ON i_tit.usuarios_id_usuario = u_tit.id_usuario
                        LEFT JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha AND ihf.instructores_id_instructor = s.instructor_titular_id
                        LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha
                        WHERE s.instructor_suplente_id = :id_sup
                          AND s.estado = 'Aceptada'
                          AND CURDATE() BETWEEN s.fecha_inicio AND s.fecha_fin
                        GROUP BY f.id_ficha, f.numero_ficha, f.programa, s.id_suplencia, s.instructor_titular_id, s.motivo, u_tit.nombres, u_tit.apellidos, ihf.minutos_retardo, ihf.minutos_salida";
            $stmtSupl = Conexion::obtener()->prepare($sqlSupl);
            $stmtSupl->execute([':id_sup' => $idInstructor]);
            $fichasSuplencias = $stmtSupl->fetchAll(PDO::FETCH_ASSOC) ?: [];

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

            // Integrar horarios y fichas de suplencia
            $idsFichasExistentes = array_column($fichas, 'id_ficha');
            foreach ($fichasSuplencias as $fs) {
                $fs['es_suplencia'] = true;
                $fs['dias_asignados'] = $diaSemanaActual;
                $fs['jornada_resumen'] = 'Suplencia autorizada';

                // Consultar horarios del titular para esta ficha hoy
                $stmtHorTit = Conexion::obtener()->prepare(
                    "SELECT hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente
                     FROM horarios
                     WHERE fichas_id_ficha = :id_f AND instructores_id_instructor = :id_tit AND dias_semana = :dia AND estado = 'Activo'
                     ORDER BY hora_entrada ASC LIMIT 1"
                );
                $stmtHorTit->execute([
                    ':id_f'   => $fs['id_ficha'],
                    ':id_tit' => $fs['instructor_titular_id'],
                    ':dia'    => $diaSemanaActual
                ]);
                $bloqueTit = $stmtHorTit->fetch(PDO::FETCH_ASSOC);
                if ($bloqueTit) {
                    $bloquesHoyPorFicha[(int)$fs['id_ficha']] = $bloqueTit;
                }

                if (!in_array($fs['id_ficha'], $idsFichasExistentes)) {
                    $fichas[] = $fs;
                } else {
                    foreach ($fichas as &$fExist) {
                        if ($fExist['id_ficha'] == $fs['id_ficha']) {
                            $fExist['es_suplencia'] = true;
                            $fExist['nombre_titular'] = $fs['nombre_titular'];
                            $fExist['id_suplencia'] = $fs['id_suplencia'];
                        }
                    }
                    unset($fExist);
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
                    $f['jornada'] = $this->formatearDiasResumen($f['dias_asignados'] ?? null);
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
     * Formatea una lista de días a un resumen elegante y compacto.
     */
    private function formatearDiasResumen(?string $diasStr): string
    {
        if (empty($diasStr)) {
            return 'Sin asignar';
        }
        $dias = array_map('trim', explode(',', $diasStr));
        $dias = array_values(array_filter($dias));
        if (count($dias) >= 4) {
            $primer = reset($dias);
            $ultimo = end($dias);
            return $primer . ' a ' . $ultimo;
        }
        $abrev = [
            'Lunes' => 'Lun',
            'Martes' => 'Mar',
            'Miercoles' => 'Mié',
            'Miércoles' => 'Mié',
            'Jueves' => 'Jue',
            'Viernes' => 'Vie',
            'Sabado' => 'Sáb',
            'Sábado' => 'Sáb',
            'Domingo' => 'Dom',
        ];
        $res = [];
        foreach ($dias as $d) {
            $res[] = $abrev[$d] ?? $d;
        }
        return implode(', ', $res);
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
                        asi.observaciones,
                        f.numero_ficha,
                        f.programa,
                        COALESCE(CONCAT(u_inst.nombres, ' ', u_inst.apellidos), 'Instructor Registrado') AS instructor_nombre,
                        u_inst.correo AS instructor_correo,
                        e.motivo AS excusa_motivo,
                        e.observaciones AS excusa_observacion,
                        e.archivo_soporte AS excusa_soporte,
                        de.observaciones AS excusa_obs_docente,
                        COALESCE(de.estado, 'Pendiente') AS excusa_estado,
                        TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS horario_entrada,
                        TIME_FORMAT(h.hora_salida, '%h:%i %p') AS horario_salida
                    FROM asistencia asi
                    INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    LEFT JOIN instructores inst ON asi.instructores_id_instructor = inst.id_instructor
                    LEFT JOIN usuarios u_inst ON inst.usuarios_id_usuario = u_inst.id_usuario
                    LEFT JOIN excusas_medicas e ON asi.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa
                    LEFT JOIN horarios h ON asi.horarios_id_horario = h.id_horario
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
     * Guarda o actualiza la observación/nota de una marcación o inasistencia específica.
     */
    public function guardarObservacionAsistencia(int $idRegistro, string $observacion): bool
    {
        try {
            $sql = "UPDATE asistencia SET observaciones = :obs WHERE id_registro = :id";
            $stmt = Conexion::obtener()->prepare($sql);
            return $stmt->execute([
                ':obs' => $observacion ?: null,
                ':id'  => $idRegistro
            ]);
        } catch (Throwable $e) {
            error_log("InstructorModel::guardarObservacionAsistencia: " . $e->getMessage());
            return false;
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
                           COALESCE(a.metodo_registro, 'RFID')        AS metodo_registro,
                           a.minutos_retardo,
                           a.horarios_id_horario,
                           a.suplencias_id_suplencia,
                           sup.estado AS suplencia_estado,
                           CONCAT(u_tit.nombres, ' ', u_tit.apellidos) AS nombre_titular,
                           CONCAT(u_sup.nombres, ' ', u_sup.apellidos) AS nombre_suplente,
                           h.duracion_horas,
                           h.tipo_bloque,
                           h.ambiente,
                           h.competencia,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p')    AS bloque_entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p')     AS bloque_salida,
                           llam.id_llamado,
                           llam.numero_folio                          AS llamado_folio,
                           llam.numero_infraccion                     AS llamado_infraccion,
                           (SELECT COUNT(*) FROM asistencia a2 
                            WHERE a2.aprendices_id_aprendiz = a.aprendices_id_aprendiz 
                              AND a2.instructores_id_instructor = :id_cnt 
                              AND a2.metodo_registro = 'MANUAL'
                           )                                          AS total_manuales_aprendiz
                    FROM asistencia a
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u   ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f     ON ap.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN horarios h    ON a.horarios_id_horario = h.id_horario
                    LEFT JOIN llamados_atencion llam ON a.id_registro = llam.asistencia_id_registro
                    LEFT JOIN suplencias_horarios sup ON a.suplencias_id_suplencia = sup.id_suplencia
                    LEFT JOIN instructores it ON sup.instructor_titular_id = it.id_instructor
                    LEFT JOIN usuarios u_tit ON it.usuarios_id_usuario = u_tit.id_usuario
                    LEFT JOIN instructores isup ON sup.instructor_suplente_id = isup.id_instructor
                    LEFT JOIN usuarios u_sup ON isup.usuarios_id_usuario = u_sup.id_usuario
                    WHERE (
                        ihf.instructores_id_instructor = :id 
                        OR a.instructores_id_instructor = :id_inst_asis
                        OR sup.instructor_suplente_id = :id_sup
                        OR sup.instructor_titular_id = :id_tit
                    )
                      AND a.fecha = :fecha";

            $params = [
                ':id'           => $idInstructor,
                ':id_inst_asis' => $idInstructor,
                ':id_sup'       => $idInstructor,
                ':id_tit'       => $idInstructor,
                ':id_cnt'       => $idInstructor,
                ':fecha'        => $fecha
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
    public function registrarAsistenciaPorCodigo(int $idInstructor, string $codigoScanned, bool $esManual = false, ?string $fechaSimulada = null, ?string $horaSimulada = null): array
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
                                 u.id_usuario, u.numero_documento, u.nombres, u.apellidos, u.correo,
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

            // Determinar fecha y día actual
            $diasEspanol = [
                1 => 'Lunes',
                2 => 'Martes',
                3 => 'Miercoles',
                4 => 'Jueves',
                5 => 'Viernes',
                6 => 'Sabado',
                7 => 'Domingo'
            ];
            $fechaHoy   = $fechaSimulada ?: date('Y-m-d');
            $horaActual = $horaSimulada ?: date('H:i:s');
            $diaSemanaActual = $diasEspanol[(int)date('N', strtotime($fechaHoy))] ?? 'Lunes';
            $tsActual   = strtotime($fechaHoy . ' ' . $horaActual);

            // 2. Verificar si el instructor tiene una suplencia ACEPTADA y vigente hoy para esta ficha
            $sqlSuplCheck = "SELECT s.id_suplencia, s.instructor_titular_id, s.horarios_id_horario, s.motivo,
                                    ihf.minutos_retardo, ihf.minutos_salida,
                                    CONCAT(u.nombres, ' ', u.apellidos) AS nombre_titular
                             FROM suplencias_horarios s
                             LEFT JOIN instructores_has_fichas ihf ON ihf.instructores_id_instructor = s.instructor_titular_id AND ihf.fichas_id_ficha = s.fichas_id_ficha
                             LEFT JOIN instructores it ON s.instructor_titular_id = it.id_instructor
                             LEFT JOIN usuarios u ON it.usuarios_id_usuario = u.id_usuario
                             WHERE s.instructor_suplente_id = :id_inst
                               AND s.fichas_id_ficha = :id_ficha
                               AND s.estado = 'Aceptada'
                               AND :fechaHoy BETWEEN s.fecha_inicio AND s.fecha_fin
                             LIMIT 1";
            $stmtSuplCheck = $pdo->prepare($sqlSuplCheck);
            $stmtSuplCheck->execute([
                ':id_inst'  => $idInstructor,
                ':id_ficha' => $idFicha,
                ':fechaHoy' => $fechaHoy
            ]);
            $suplenciaActiva = $stmtSuplCheck->fetch(PDO::FETCH_ASSOC);

            $esSuplencia = false;
            $fichaInst = null;

            if ($suplenciaActiva) {
                $esSuplencia = true;
                $fichaInst = [
                    'minutos_retardo' => $suplenciaActiva['minutos_retardo'] ?? 15,
                    'minutos_salida'  => $suplenciaActiva['minutos_salida'] ?? 0
                ];
            } else {
                // Verificar si la ficha pertenece a sus asignadas regularmente
                $sqlFichaInst = "SELECT minutos_retardo, minutos_salida FROM instructores_has_fichas 
                                 WHERE instructores_id_instructor = :id_inst AND fichas_id_ficha = :id_ficha 
                                 LIMIT 1";
                $stmtFichaInst = $pdo->prepare($sqlFichaInst);
                $stmtFichaInst->execute([
                    ':id_inst'  => $idInstructor,
                    ':id_ficha' => $idFicha
                ]);
                $fichaInst = $stmtFichaInst->fetch(PDO::FETCH_ASSOC);
            }

            if (!$fichaInst) {
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "El aprendiz {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) no pertenece a sus fichas asignadas ni a ninguna suplencia activa hoy."
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

            // Consultar TODOS los bloques activos de la ficha para HOY con sus respectivos instructores
            $sqlTodosBloques = "SELECT h.id_horario, h.hora_entrada, h.hora_salida, h.duracion_horas, 
                                       h.tipo_bloque, h.ambiente, h.competencia, h.instructores_id_instructor,
                                       CONCAT(u.nombres, ' ', u.apellidos) AS nombre_instructor
                                FROM horarios h
                                INNER JOIN instructores i ON h.instructores_id_instructor = i.id_instructor
                                INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                                WHERE h.fichas_id_ficha = :id_ficha
                                  AND h.dias_semana = :dia_semana
                                  AND h.estado = 'Activo'
                                ORDER BY h.hora_entrada ASC";
            $stmtTodosBloques = $pdo->prepare($sqlTodosBloques);
            $stmtTodosBloques->execute([
                ':id_ficha'   => $idFicha,
                ':dia_semana' => $diaSemanaActual
            ]);
            $todosBloquesHoy = $stmtTodosBloques->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (empty($todosBloquesHoy)) {
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "La Ficha {$aprendiz['numero_ficha']} no tiene programada ninguna clase el día de hoy ({$diaSemanaActual})."
                ];
            }

            // 5. Determinar bloques activos en la ventana horaria actual (30 min antes de inicio hasta 30 min después de fin)
            $bloquesActivosAhora = [];
            foreach ($todosBloquesHoy as $bh) {
                $tsInicio = strtotime($fechaHoy . ' ' . $bh['hora_entrada']);
                $tsFin    = strtotime($fechaHoy . ' ' . $bh['hora_salida']);
                $tsVentanaInicio = $tsInicio - (30 * 60); // 30 minutos antes para registrar entrada temprana
                $tsVentanaFin    = $tsFin + (30 * 60);    // 30 minutos después para registrar salida

                if ($tsActual >= $tsVentanaInicio && $tsActual <= $tsVentanaFin) {
                    $bloquesActivosAhora[] = $bh;
                }
            }

            $bloqueSeleccionado = null;

            if (!empty($bloquesActivosAhora)) {
                // Filtrar los bloques activos que pertenecen al instructor que escanea O de los cuales es suplente aceptado
                $bloquesPropiosActivos = array_values(array_filter($bloquesActivosAhora, function($ba) use ($idInstructor, $esSuplencia, $suplenciaActiva) {
                    if ((int)$ba['instructores_id_instructor'] === $idInstructor) {
                        return true;
                    }
                    if ($esSuplencia && (int)$ba['instructores_id_instructor'] === (int)$suplenciaActiva['instructor_titular_id']) {
                        if (!empty($suplenciaActiva['horarios_id_horario'])) {
                            return (int)$ba['id_horario'] === (int)$suplenciaActiva['horarios_id_horario'];
                        }
                        return true;
                    }
                    return false;
                }));

                if (count($bloquesPropiosActivos) === 1) {
                    $bloqueSeleccionado = $bloquesPropiosActivos[0];
                } elseif (count($bloquesPropiosActivos) > 1) {
                    // Si el mismo instructor tiene bloques contiguos, buscar el que tenga entrada o salida pendiente
                    foreach ($bloquesPropiosActivos as $bpa) {
                        $stmtChk = $pdo->prepare("SELECT id_registro, hora_entrada, hora_salida 
                                                   FROM asistencia 
                                                   WHERE aprendices_id_aprendiz = :id_ap 
                                                     AND fecha = :fecha 
                                                     AND horarios_id_horario = :id_h 
                                                   LIMIT 1");
                        $stmtChk->execute([
                            ':id_ap' => $idAprendiz,
                            ':fecha' => $fechaHoy,
                            ':id_h'  => $bpa['id_horario']
                        ]);
                        $regBloque = $stmtChk->fetch(PDO::FETCH_ASSOC);

                        if (!$regBloque || empty($regBloque['hora_salida'])) {
                            $bloqueSeleccionado = $bpa;
                            break;
                        }
                    }
                    if (!$bloqueSeleccionado) {
                        $bloqueSeleccionado = $bloquesPropiosActivos[0];
                    }
                } else {
                    // Hay clase activa en este momento, pero pertenece a OTRO docente (ej. Vladimir)
                    $bloqueAjeno = $bloquesActivosAhora[0];
                    $hIniFmt = date('h:i A', strtotime($bloqueAjeno['hora_entrada']));
                    $hFinFmt = date('h:i A', strtotime($bloqueAjeno['hora_salida']));
                    $ambienteDesc = !empty($bloqueAjeno['ambiente']) ? " en ambiente {$bloqueAjeno['ambiente']}" : '';

                    return [
                        'success' => false,
                        'type'    => 'danger',
                        'message' => "Marcación no permitida: En este momento la Ficha {$aprendiz['numero_ficha']} tiene clase programada con el instructor {$bloqueAjeno['nombre_instructor']} ({$hIniFmt} - {$hFinFmt}{$ambienteDesc}). Únicamente el docente titular puede registrar la asistencia de este bloque."
                    ];
                }
            } else {
                // Ningún bloque está en su ventana horaria en este momento
                $bloquesDelInstructorHoy = array_filter($todosBloquesHoy, function($b) use ($idInstructor, $esSuplencia, $suplenciaActiva) {
                    if ((int)$b['instructores_id_instructor'] === $idInstructor) {
                        return true;
                    }
                    if ($esSuplencia && (int)$b['instructores_id_instructor'] === (int)$suplenciaActiva['instructor_titular_id']) {
                        if (!empty($suplenciaActiva['horarios_id_horario'])) {
                            return (int)$b['id_horario'] === (int)$suplenciaActiva['horarios_id_horario'];
                        }
                        return true;
                    }
                    return false;
                });

                if (empty($bloquesDelInstructorHoy)) {
                    $primerBloqueFicha = $todosBloquesHoy[0];
                    $hIni = date('h:i A', strtotime($primerBloqueFicha['hora_entrada']));
                    $hFin = date('h:i A', strtotime($primerBloqueFicha['hora_salida']));
                    return [
                        'success' => false,
                        'type'    => 'warning',
                        'message' => "Usted no tiene bloques de formación programados hoy con la Ficha {$aprendiz['numero_ficha']}. La formación de hoy está asignada a {$primerBloqueFicha['nombre_instructor']} ({$hIni} - {$hFin})."
                    ];
                }

                $primerBloquePropio = reset($bloquesDelInstructorHoy);
                $horaInicioPropia = date('h:i A', strtotime($primerBloquePropio['hora_entrada']));
                return [
                    'success' => false,
                    'type'    => 'warning',
                    'message' => "Fuera de horario: Su clase con la Ficha {$aprendiz['numero_ficha']} está programada para las {$horaInicioPropia}. La marcación se habilita 30 minutos antes del inicio de la formación."
                ];
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

                $metodoRegistro = $esManual ? 'MANUAL' : 'RFID';

                $sqlInsert = "INSERT INTO asistencia 
                              (fecha, hora_entrada, hora_salida, estado, metodo_registro, minutos_retardo, aprendices_id_aprendiz, instructores_id_instructor, horarios_id_horario, suplencias_id_suplencia)
                              VALUES (:fecha, :hora_entrada, NULL, :estado, :metodo_registro, :minutos_retardo, :id_aprendiz, :id_instructor, :id_horario, :id_suplencia)";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':fecha'           => $fechaHoy,
                    ':hora_entrada'    => $horaActual,
                    ':estado'          => $estado,
                    ':metodo_registro' => $metodoRegistro,
                    ':minutos_retardo' => $minutosRetardo,
                    ':id_aprendiz'     => $idAprendiz,
                    ':id_instructor'   => $idInstructor,
                    ':id_horario'      => $idHorarioSeleccionado,
                    ':id_suplencia'    => $esSuplencia ? (int)$suplenciaActiva['id_suplencia'] : null
                ]);
                $idRegistroAsistencia = (int)$pdo->lastInsertId();

                $horaFormateada = date('h:i A', strtotime($horaActual));
                $badgeTipo = ($estado === 'Asistio') ? 'success' : 'warning';
                $msgText = ($estado === 'Asistio')
                    ? "Entrada A TIEMPO [{$tipoLabel}{$ambienteInfo}] para {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) a las {$horaFormateada}."
                    : "Entrada CON RETARDO ({$minutosRetardo} min) [{$tipoLabel}{$ambienteInfo}] para {$nombreComp} (Ficha {$aprendiz['numero_ficha']}) a las {$horaFormateada}.";

                if ($esSuplencia) {
                    $msgText = "[Suplencia · Titular: {$suplenciaActiva['nombre_titular']}] " . $msgText;
                }

                // Control de Marcación Manual y Llamados de Atención Individuales
                $alertaLlamado = false;
                $datosLlamado = null;
                $totalManuales = 0;

                if ($esManual) {
                    // Contar total de asistencias manuales del aprendiz con este docente
                    $sqlCountManual = "SELECT COUNT(*) FROM asistencia 
                                       WHERE aprendices_id_aprendiz = :id_apr 
                                         AND instructores_id_instructor = :id_inst 
                                         AND metodo_registro = 'MANUAL'";
                    $stmtCount = $pdo->prepare($sqlCountManual);
                    $stmtCount->execute([
                        ':id_apr'  => $idAprendiz,
                        ':id_inst' => $idInstructor
                    ]);
                    $totalManuales = (int)$stmtCount->fetchColumn();

                    // Cada vez que tenga 3 o más marcaciones manuales, se genera un llamado formal individual
                    if ($totalManuales >= 3) {
                        $sqlCountLlam = "SELECT COUNT(*) FROM llamados_atencion 
                                         WHERE aprendices_id_aprendiz = :id_apr 
                                           AND instructores_id_instructor = :id_inst";
                        $stmtCountLlam = $pdo->prepare($sqlCountLlam);
                        $stmtCountLlam->execute([
                            ':id_apr'  => $idAprendiz,
                            ':id_inst' => $idInstructor
                        ]);
                        $numeroInfraccion = ((int)$stmtCountLlam->fetchColumn()) + 1;

                        $folio = 'LLAM-' . date('Ymd') . '-F' . $aprendiz['numero_ficha'] . '-A' . $idAprendiz . '-N' . $numeroInfraccion;

                        $sqlInsLlamado = "INSERT INTO llamados_atencion 
                                          (numero_folio, asistencia_id_registro, aprendices_id_aprendiz, instructores_id_instructor, fichas_id_ficha, numero_infraccion, fecha_generacion, motivo, estado)
                                          VALUES 
                                          (:folio, :id_asistencia, :id_apr, :id_inst, :id_ficha, :num_inf, NOW(), 'No portar carné o llavero RFID institucional en formación', 'Generado')";
                        $stmtInsLlam = $pdo->prepare($sqlInsLlamado);
                        $stmtInsLlam->execute([
                            ':folio'         => $folio,
                            ':id_asistencia' => $idRegistroAsistencia,
                            ':id_apr'        => $idAprendiz,
                            ':id_inst'       => $idInstructor,
                            ':id_ficha'      => $idFicha,
                            ':num_inf'       => $numeroInfraccion
                        ]);
                        $idLlamadoNuevo = (int)$pdo->lastInsertId();

                        $alertaLlamado = true;
                        $datosLlamado = [
                            'id_llamado'        => $idLlamadoNuevo,
                            'folio'             => $folio,
                            'numero_infraccion' => $numeroInfraccion,
                            'total_manuales'    => $totalManuales,
                            'aprendiz'          => $nombreComp,
                            'documento'         => $aprendiz['numero_documento'],
                            'ficha'             => $aprendiz['numero_ficha'],
                            'programa'          => $aprendiz['programa'],
                            'fecha'             => $fechaHoy,
                            'hora'              => $horaFormateada
                        ];

                        $msgText .= " · ⚠️ LLAMADO DE ATENCIÓN formal generado (Infracción #{$numeroInfraccion} - {$totalManuales} sin llavero).";

                        // Notificación formal por correo si tiene email
                        if (!empty($aprendiz['correo'])) {
                            try {
                                require_once BASE_PATH . '/Services/MailService.php';
                                $mailService = new \App\Services\MailService();
                                $resMailCarnet = $mailService->enviarLlamadoAtencion([
                                    'tipo'              => 'falta_carnet',
                                    'destinatarioEmail' => $aprendiz['correo'],
                                    'nombreAprendiz'    => $nombreComp,
                                    'documento'         => $aprendiz['numero_documento'],
                                    'programa'          => $aprendiz['programa'],
                                    'numeroFicha'       => $aprendiz['numero_ficha'],
                                    'fecha'             => $fechaHoy,
                                    'hora'              => $horaFormateada,
                                    'numeroInfraccion'  => $numeroInfraccion,
                                    'totalManuales'     => $totalManuales,
                                    'folioLlamado'      => $folio,
                                    'nombreInstructor'  => $bloqueSeleccionado['nombre_instructor'] ?? 'Instructor SENA',
                                    'bloque'            => $tipoLabel . $ambienteInfo
                                ]);
                                if (!empty($resMailCarnet['success'])) {
                                    $pdo->prepare("UPDATE llamados_atencion SET notificado_correo = 1 WHERE id_llamado = :id")
                                        ->execute([':id' => $idLlamadoNuevo]);
                                    $msgText .= " Copia enviada a {$aprendiz['correo']}.";
                                }
                            } catch (\Throwable $eMailC) {
                                error_log("Error enviando correo falta carné: " . $eMailC->getMessage());
                            }
                        }
                    } else {
                        $msgText .= " · Entrada manual ({$totalManuales}/2 permitidas sin llavero).";
                    }
                }

                // AUTOMATIZACIÓN: Notificar al aprendiz con llamado de atención escrito por retardo si aplica
                $correoNotificado = false;
                if ($estado === 'Retardo' && !empty($aprendiz['correo']) && !$alertaLlamado) {
                    try {
                        require_once BASE_PATH . '/Services/MailService.php';
                        $mailService = new \App\Services\MailService();
                        $resMail = $mailService->enviarLlamadoAtencion([
                            'tipo'              => 'retardo',
                            'destinatarioEmail' => $aprendiz['correo'],
                            'nombreAprendiz'    => $nombreComp,
                            'documento'         => $aprendiz['numero_documento'],
                            'programa'          => $aprendiz['programa'],
                            'numeroFicha'       => $aprendiz['numero_ficha'],
                            'fecha'             => $fechaHoy,
                            'hora'              => $horaFormateada,
                            'minutosRetardo'    => $minutosRetardo,
                            'nombreInstructor'  => $bloqueSeleccionado['nombre_instructor'] ?? 'Instructor SENA',
                            'bloque'            => $tipoLabel . $ambienteInfo
                        ]);

                        if (!empty($resMail['success'])) {
                            $correoNotificado = true;
                            $msgText .= " · Llamado por retardo enviado a {$aprendiz['correo']}.";
                        }
                    } catch (\Throwable $eMail) {
                        error_log("Excepción al enviar correo de retardo: " . $eMail->getMessage());
                    }
                }

                return [
                    'success'           => true,
                    'type'              => $alertaLlamado ? 'danger' : $badgeTipo,
                    'action'            => 'entrada',
                    'estado'            => $estado,
                    'metodo_registro'   => $metodoRegistro,
                    'aprendiz'          => $nombreComp,
                    'ficha'             => $aprendiz['numero_ficha'] . ' - ' . $aprendiz['programa'],
                    'hora'              => $horaFormateada,
                    'codigo'            => $aprendiz['codigo_rfid'] ?: $aprendiz['numero_documento'],
                    'bloque'            => $tipoLabel . $ambienteInfo,
                    'message'           => $msgText,
                    'correo_notificado' => $correoNotificado,
                    'correo_aprendiz'   => $aprendiz['correo'] ?? '',
                    'es_manual'         => $esManual,
                    'alerta_llamado'    => $alertaLlamado,
                    'datos_llamado'     => $datosLlamado,
                    'total_manuales'    => $totalManuales
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
                    INNER JOIN asistencia asi ON asi.excusas_medicas_id_excusa = e.id_excusa 
                                              AND asi.instructores_id_instructor = :id_asi
                    LEFT JOIN detalle_excusas de ON e.id_excusa = de.excusas_medicas_id_excusa 
                                                AND de.instructores_id_instructor = :id_de
                    INNER JOIN aprendices ap ON asi.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    WHERE asi.instructores_id_instructor = :id";

            $params = [
                ':id_asi' => $idInstructor,
                ':id_de'  => $idInstructor,
                ':id'     => $idInstructor
            ];

            if (!empty($estado) && $estado !== 'todas') {
                $sql .= " AND (de.estado = :estado OR (de.estado IS NULL AND :estado_null = 'Pendiente'))";
                $params[':estado'] = $estado;
                $params[':estado_null'] = $estado;
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

            // 1. Verificar si ya existe registro en detalle_excusas para este instructor específico
            $stmtCheck = $pdo->prepare("SELECT id_detalle FROM detalle_excusas WHERE excusas_medicas_id_excusa = :id_excusa AND instructores_id_instructor = :id_instructor LIMIT 1");
            $stmtCheck->execute([
                ':id_excusa'     => $idExcusa,
                ':id_instructor' => $idInstructor
            ]);
            $idDetalle = $stmtCheck->fetchColumn();

            if ($idDetalle) {
                $sqlDetalle = "UPDATE detalle_excusas 
                               SET estado = :estado, 
                                   fecha_evaluacion = NOW(), 
                                   observaciones = :observaciones 
                               WHERE id_detalle = :id_detalle";
                $stmtDet = $pdo->prepare($sqlDetalle);
                $stmtDet->execute([
                    ':estado'        => $estadoDetalle,
                    ':observaciones' => $observaciones,
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

            // 2. Sincronizar tabla asistencia EXCLUSIVAMENTE para los bloques de este instructor
            $nuevoEstadoAsistencia = ($estadoDetalle === 'Aprobado') ? 'Justificada' : 'Inasistencia';
            $sqlAsistencia = "UPDATE asistencia 
                              SET estado = :estado_asistencia 
                              WHERE excusas_medicas_id_excusa = :id_excusa 
                                AND instructores_id_instructor = :id_instructor";
            $stmtAsi = $pdo->prepare($sqlAsistencia);
            $stmtAsi->execute([
                ':estado_asistencia' => $nuevoEstadoAsistencia,
                ':id_excusa'         => $idExcusa,
                ':id_instructor'     => $idInstructor
            ]);

            // 3. Sincronizar estado global en excusas_medicas si todos los instructores han evaluado
            $stmtPendientes = $pdo->prepare("SELECT COUNT(*) FROM detalle_excusas WHERE excusas_medicas_id_excusa = :id_excusa AND estado = 'Pendiente'");
            $stmtPendientes->execute([':id_excusa' => $idExcusa]);
            $quedanPendientes = (int)$stmtPendientes->fetchColumn();

            if ($quedanPendientes === 0) {
                $stmtAprobados = $pdo->prepare("SELECT COUNT(*) FROM detalle_excusas WHERE excusas_medicas_id_excusa = :id_excusa AND estado = 'Aprobado'");
                $stmtAprobados->execute([':id_excusa' => $idExcusa]);
                $hayAprobados = (int)$stmtAprobados->fetchColumn();

                $estadoGlobal = ($hayAprobados > 0) ? 'Aprobada' : 'Rechazada';
                $stmtGlobal = $pdo->prepare("UPDATE excusas_medicas SET estado = :estado_global, fecha_evaluacion = NOW() WHERE id_excusa = :id_excusa");
                $stmtGlobal->execute([
                    ':estado_global' => $estadoGlobal,
                    ':id_excusa'     => $idExcusa
                ]);
            }

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

    /**
     * Obtiene los datos detallados de un llamado de atención para visualización o impresión oficial.
     */
    public function obtenerLlamadoPorId(int $idLlamado, ?int $idInstructor = null): ?array
    {
        try {
            $sql = "SELECT llam.*,
                           CONCAT(u_apr.nombres, ' ', u_apr.apellidos) AS aprendiz_nombre,
                           u_apr.tipo_documento,
                           u_apr.numero_documento,
                           u_apr.correo AS aprendiz_correo,
                           f.numero_ficha,
                           f.programa,
                           f.area,
                           CONCAT(u_inst.nombres, ' ', u_inst.apellidos) AS instructor_nombre,
                           u_inst.correo AS instructor_correo,
                           i.especialidad,
                           a.hora_entrada,
                           a.fecha AS fecha_asistencia
                    FROM llamados_atencion llam
                    INNER JOIN aprendices ap ON llam.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u_apr ON ap.usuarios_id_usuario = u_apr.id_usuario
                    INNER JOIN fichas f ON llam.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores i ON llam.instructores_id_instructor = i.id_instructor
                    INNER JOIN usuarios u_inst ON i.usuarios_id_usuario = u_inst.id_usuario
                    LEFT JOIN asistencia a ON llam.asistencia_id_registro = a.id_registro
                    WHERE llam.id_llamado = :id";
            $params = [':id' => $idLlamado];
            if ($idInstructor !== null) {
                $sql .= " AND llam.instructores_id_instructor = :id_inst";
                $params[':id_inst'] = $idInstructor;
            }
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerLlamadoPorId: " . $e->getMessage());
            return null;
        }
    }


    // ============================================================
    // MÓDULO DE GESTIÓN Y APROBACIÓN DE SUPLENCIAS DOCENTES
    // ============================================================

    /**
     * Registra una nueva solicitud de suplencia para una ficha y rango de fechas.
     */
    public function solicitarSuplencia(array $datos): array
    {
        try {
            $pdo = Conexion::obtener();
            $idTitular   = (int)($datos['instructor_titular_id'] ?? 0);
            $idSuplente  = (int)($datos['instructor_suplente_id'] ?? 0);
            $idFicha     = (int)($datos['fichas_id_ficha'] ?? 0);
            $idHorario   = !empty($datos['horarios_id_horario']) ? (int)$datos['horarios_id_horario'] : null;
            $fechaInicio = trim($datos['fecha_inicio'] ?? '');
            $fechaFin    = trim($datos['fecha_fin'] ?? '');
            $motivo      = trim($datos['motivo'] ?? 'Permiso Personal');
            $descripcion = trim($datos['descripcion_motivo'] ?? '');

            if ($idTitular <= 0 || $idSuplente <= 0 || $idFicha <= 0) {
                return ['success' => false, 'message' => 'Parámetros obligatorios incompletos para solicitar la suplencia.'];
            }
            if ($idTitular === $idSuplente) {
                return ['success' => false, 'message' => 'El instructor suplente no puede ser el mismo instructor titular.'];
            }
            if (empty($fechaInicio) || empty($fechaFin) || $fechaFin < $fechaInicio) {
                return ['success' => false, 'message' => 'El rango de fechas no es válido. La fecha final debe ser igual o posterior a la inicial.'];
            }

            // Verificar si ya existe una solicitud pendiente o aceptada para la misma ficha y fechas
            $sqlCheck = "SELECT id_suplencia FROM suplencias_horarios 
                         WHERE instructor_titular_id = :id_tit 
                           AND fichas_id_ficha = :id_ficha 
                           AND estado IN ('Pendiente', 'Aceptada')
                           AND fecha_inicio <= :ff 
                           AND fecha_fin >= :fi
                         LIMIT 1";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([
                ':id_tit'   => $idTitular,
                ':id_ficha' => $idFicha,
                ':ff'       => $fechaFin,
                ':fi'       => $fechaInicio
            ]);
            if ($stmtCheck->fetch()) {
                return [
                    'success' => false, 
                    'message' => 'Ya existe una suplencia activa o pendiente de respuesta para esta ficha en ese rango de fechas.'
                ];
            }

            $sqlIns = "INSERT INTO suplencias_horarios 
                       (instructor_titular_id, instructor_suplente_id, fichas_id_ficha, horarios_id_horario, fecha_inicio, fecha_fin, motivo, descripcion_motivo, estado)
                       VALUES 
                       (:id_tit, :id_sup, :id_fic, :id_hor, :fi, :ff, :motivo, :desc, 'Pendiente')";
            $stmtIns = $pdo->prepare($sqlIns);
            $stmtIns->execute([
                ':id_tit' => $idTitular,
                ':id_sup' => $idSuplente,
                ':id_fic' => $idFicha,
                ':id_hor' => $idHorario,
                ':fi'     => $fechaInicio,
                ':ff'     => $fechaFin,
                ':motivo' => $motivo,
                ':desc'   => $descripcion ?: null
            ]);

            return [
                'success'      => true,
                'id_suplencia' => (int)$pdo->lastInsertId(),
                'message'      => 'Solicitud de suplencia enviada exitosamente. El instructor designado deberá aceptarla para poder tomar asistencia.'
            ];
        } catch (Throwable $e) {
            error_log("InstructorModel::solicitarSuplencia: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar la solicitud de suplencia: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene las solicitudes de suplencia pendientes que han sido asignadas a este instructor.
     */
    public function obtenerSuplenciasPendientes(int $idInstructor): array
    {
        try {
            $sql = "SELECT s.*, 
                           f.numero_ficha, f.programa, f.area,
                           CONCAT(u_tit.nombres, ' ', u_tit.apellidos) AS nombre_titular,
                           u_tit.correo AS correo_titular,
                           i_tit.especialidad AS especialidad_titular
                    FROM suplencias_horarios s
                    INNER JOIN fichas f ON s.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores i_tit ON s.instructor_titular_id = i_tit.id_instructor
                    INNER JOIN usuarios u_tit ON i_tit.usuarios_id_usuario = u_tit.id_usuario
                    WHERE s.instructor_suplente_id = :id
                      AND s.estado = 'Pendiente'
                    ORDER BY s.fecha_solicitud DESC";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id' => $idInstructor]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerSuplenciasPendientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene las suplencias activas o recientes donde el instructor es titular o suplente.
     */
    public function obtenerMisSuplencias(int $idInstructor): array
    {
        try {
            $sql = "SELECT s.*, 
                           f.numero_ficha, f.programa,
                           CONCAT(u_tit.nombres, ' ', u_tit.apellidos) AS nombre_titular,
                           u_tit.correo AS correo_titular,
                           CONCAT(u_sup.nombres, ' ', u_sup.apellidos) AS nombre_suplente,
                           u_sup.correo AS correo_suplente,
                           CASE 
                               WHEN s.instructor_suplente_id = :id_sup THEN 'SUPLENTE'
                               ELSE 'TITULAR'
                           END AS mi_rol
                    FROM suplencias_horarios s
                    INNER JOIN fichas f ON s.fichas_id_ficha = f.id_ficha
                    INNER JOIN instructores i_tit ON s.instructor_titular_id = i_tit.id_instructor
                    INNER JOIN usuarios u_tit ON i_tit.usuarios_id_usuario = u_tit.id_usuario
                    INNER JOIN instructores i_sup ON s.instructor_suplente_id = i_sup.id_instructor
                    INNER JOIN usuarios u_sup ON i_sup.usuarios_id_usuario = u_sup.id_usuario
                    WHERE s.instructor_titular_id = :id_tit OR s.instructor_suplente_id = :id_sup2
                    ORDER BY s.fecha_solicitud DESC
                    LIMIT 20";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':id_sup'  => $idInstructor,
                ':id_tit'  => $idInstructor,
                ':id_sup2' => $idInstructor
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerMisSuplencias: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Permite al instructor suplente Aceptar o Rechazar una solicitud de suplencia.
     */
    public function responderSuplencia(int $idSuplencia, int $idInstructorSuplente, string $accion, ?string $observacion = null): array
    {
        try {
            $pdo = Conexion::obtener();
            $accionClean = strtolower(trim($accion));
            if (!in_array($accionClean, ['aceptar', 'rechazar', 'aceptada', 'rechazada'])) {
                return ['success' => false, 'message' => 'Acción no permitida. Solo puede aceptar o rechazar.'];
            }
            $nuevoEstado = in_array($accionClean, ['aceptar', 'aceptada']) ? 'Aceptada' : 'Rechazada';

            $sqlVerif = "SELECT id_suplencia, estado FROM suplencias_horarios 
                         WHERE id_suplencia = :id AND instructor_suplente_id = :id_inst LIMIT 1";
            $stmtVerif = $pdo->prepare($sqlVerif);
            $stmtVerif->execute([
                ':id'      => $idSuplencia,
                ':id_inst' => $idInstructorSuplente
            ]);
            $suplencia = $stmtVerif->fetch(PDO::FETCH_ASSOC);

            if (!$suplencia) {
                return ['success' => false, 'message' => 'La solicitud de suplencia no existe o no está dirigida a usted.'];
            }
            if ($suplencia['estado'] !== 'Pendiente') {
                return ['success' => false, 'message' => "Esta suplencia ya fue respondida anteriormente ({$suplencia['estado']})."];
            }

            $sqlUpd = "UPDATE suplencias_horarios 
                       SET estado = :nuevo_estado,
                           fecha_respuesta = NOW(),
                           observaciones_respuesta = :obs
                       WHERE id_suplencia = :id";
            $stmtUpd = $pdo->prepare($sqlUpd);
            $stmtUpd->execute([
                ':nuevo_estado' => $nuevoEstado,
                ':obs'          => $observacion ?: null,
                ':id'           => $idSuplencia
            ]);

            $msg = ($nuevoEstado === 'Aceptada') 
                ? '¡Suplencia aceptada con éxito! La ficha y sus horarios se han habilitado en su Control de Ingresos para las fechas asignadas.'
                : 'La solicitud de suplencia ha sido rechazada.';

            return [
                'success'      => true,
                'nuevo_estado' => $nuevoEstado,
                'message'      => $msg
            ];
        } catch (Throwable $e) {
            error_log("InstructorModel::responderSuplencia: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al responder la suplencia: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene la lista de todos los demás instructores activos disponibles para seleccionar como suplente.
     */
    public function obtenerInstructoresParaSuplencia(int $idInstructorActual): array
    {
        try {
            $sql = "SELECT i.id_instructor, 
                           CONCAT(u.nombres, ' ', u.apellidos) AS nombre_completo,
                           u.correo, 
                           i.especialidad
                    FROM instructores i
                    INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                    WHERE i.id_instructor != :id AND u.estado = 'Activo'
                    ORDER BY u.nombres ASC, u.apellidos ASC";
            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id' => $idInstructorActual]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerInstructoresParaSuplencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el directorio ligero de aprendices activos pertenecientes a las fichas asignadas
     * o bajo suplencia del instructor, para alimentar la base de datos local IndexedDB (Offline).
     */
    public function obtenerDirectorioOfflineInstructor(int $idInstructor): array
    {
        try {
            $sql = "SELECT a.id_aprendiz,
                           a.codigo_rfid,
                           a.fichas_id_ficha,
                           a.estado_matricula,
                           u.id_usuario,
                           u.numero_documento,
                           u.nombres,
                           u.apellidos,
                           u.correo,
                           f.numero_ficha,
                           f.programa
                    FROM aprendices a
                    INNER JOIN usuarios u ON a.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    WHERE a.fichas_id_ficha IN (
                        SELECT fichas_id_ficha FROM instructores_has_fichas WHERE instructores_id_instructor = :id_inst1
                        UNION
                        SELECT fichas_id_ficha FROM suplencias_horarios 
                        WHERE instructor_suplente_id = :id_inst2 AND estado = 'Aceptada' AND CURRENT_DATE BETWEEN fecha_inicio AND fecha_fin
                    )
                    AND a.estado_matricula != 'Retirado'
                    ORDER BY u.apellidos ASC, u.nombres ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([
                ':id_inst1' => $idInstructor,
                ':id_inst2' => $idInstructor
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("InstructorModel::obtenerDirectorioOfflineInstructor: " . $e->getMessage());
            return [];
        }
    }
}
