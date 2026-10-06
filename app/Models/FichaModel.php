<?php
/**
 * Modelo de Fichas (FichaModel)
 * 
 * Responsabilidad:
 * 1. Consultar fichas con conteo de aprendices y lista de instructores asignados (N:M).
 * 2. Filtrar por área técnica ('comercio' o 'Tics') según el administrador en sesión.
 * 3. Proveer listados de instructores y horarios para formularios.
 * 4. Registrar fichas y gestionar la asignación múltiple en 'instructores_has_fichas'.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class FichaModel
{
    /**
     * Lista las fichas del centro con conteo de aprendices e instructores asociados.
     * Si se pasa un área específica ('comercio' o 'Tics'), filtra los resultados.
     */
    public function listarFichas(?string $area = null): array
    {
        try {
            $sql = "SELECT 
                        f.id_ficha, 
                        f.numero_ficha AS codigo, 
                        f.programa, 
                        f.estado, 
                        f.area, 
                        COALESCE(
                            GROUP_CONCAT(
                                DISTINCT CONCAT(h.dias_semana, ' (', TIME_FORMAT(h.hora_entrada, '%h:%i %p'), ' - ', TIME_FORMAT(h.hora_salida, '%h:%i %p'), ')')
                                ORDER BY FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada
                                SEPARATOR '<br>'
                            ),
                            'Sin horario asignado'
                        ) AS jornada_resumen,
                        COUNT(DISTINCT a.id_aprendiz) AS aprendices, 
                        COALESCE(GROUP_CONCAT(DISTINCT CONCAT(u.nombres, ' ', u.apellidos) SEPARATOR ', '), 'Sin instructor') AS instructores, 
                        GROUP_CONCAT(DISTINCT i.id_instructor) AS instructores_ids
                    FROM fichas f
                    LEFT JOIN horarios h ON f.id_ficha = h.fichas_id_ficha AND h.estado = 'Activo'
                    LEFT JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN instructores i ON ihf.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha";

            $params = [];
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE f.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " GROUP BY f.id_ficha, f.numero_ficha, f.programa, f.estado, f.area
                      ORDER BY f.numero_ficha ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("FichaModel::listarFichas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los instructores disponibles para asignar en los selectores.
     */
    public function listarInstructoresDisponibles(?string $area = null): array
    {
        try {
            $sql = "SELECT i.id_instructor, i.area, CONCAT(u.nombres, ' ', u.apellidos) AS nombre
                    FROM instructores i
                    INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario";

            $params = [];
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE i.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " ORDER BY u.apellidos ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("FichaModel::listarInstructoresDisponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los horarios configurados en el sistema.
     */
    public function listarHorarios(): array
    {
        try {
            $sql = "SELECT id_horario, 
                           dias_semana, 
                           TIME_FORMAT(hora_entrada, '%h:%i %p') AS entrada, 
                           TIME_FORMAT(hora_salida, '%h:%i %p') AS salida
                    FROM horarios
                    ORDER BY hora_entrada ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute();

            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("FichaModel::listarHorarios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Registra una nueva ficha y vincula sus instructores en la tabla N:M.
     */
    public function crearFicha(string $numeroFicha, string $programa, string $area, array $instructoresIds = [], ?int $idHorario = null): int
    {
        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 1. Insertar ficha
            $sqlFicha = "INSERT INTO fichas (numero_ficha, programa, estado, area) 
                         VALUES (:numero, :programa, 'Activa', :area)";
            $stmtFicha = $pdo->prepare($sqlFicha);
            $stmtFicha->execute([
                ':numero'   => $numeroFicha,
                ':programa' => $programa,
                ':area'     => $area
            ]);

            $idFicha = (int)$pdo->lastInsertId();

            // 2. Asociar instructores en tabla intermedia
            if (!empty($instructoresIds)) {
                $sqlRel = "INSERT INTO instructores_has_fichas (instructores_id_instructor, fichas_id_ficha, minutos_retardo, minutos_salida) 
                           VALUES (:id_instructor, :id_ficha, 15, 0)";
                $stmtRel = $pdo->prepare($sqlRel);

                foreach ($instructoresIds as $idInstructor) {
                    $idInstInt = (int)$idInstructor;
                    if ($idInstInt > 0) {
                        $stmtRel->execute([
                            ':id_instructor' => $idInstInt,
                            ':id_ficha'      => $idFicha
                        ]);
                    }
                }
            }

            $pdo->commit();
            return $idFicha;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("FichaModel::crearFicha: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene una ficha por su ID.
     */
    public function obtenerFichaPorId(int $idFicha): array|false
    {
        try {
            $stmt = Conexion::obtener()->prepare("SELECT id_ficha, numero_ficha, programa, area, estado FROM fichas WHERE id_ficha = :id LIMIT 1");
            $stmt->execute([':id' => $idFicha]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
        } catch (Throwable $e) {
            error_log("FichaModel::obtenerFichaPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los bloques de horario asignados a una ficha con su instructor, ambiente y competencia.
     */
    public function obtenerBloquesPorFicha(int $idFicha): array
    {
        try {
            $sql = "SELECT h.id_horario,
                           h.fichas_id_ficha,
                           h.instructores_id_instructor,
                           h.fecha,
                           h.dias_semana,
                           TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada,
                           TIME_FORMAT(h.hora_salida, '%h:%i %p')  AS salida,
                           h.hora_entrada                          AS raw_hora_entrada,
                           h.hora_salida                           AS raw_hora_salida,
                           h.duracion_horas,
                           h.tipo_bloque,
                           COALESCE(h.ambiente, 'Ambiente por asignar') AS ambiente,
                           COALESCE(h.competencia, 'Competencia general') AS competencia,
                           h.estado,
                           COALESCE(CONCAT(u.nombres, ' ', u.apellidos), 'Sin instructor') AS instructor_nombre
                    FROM horarios h
                    LEFT JOIN instructores i ON h.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios u     ON i.usuarios_id_usuario = u.id_usuario
                    WHERE h.fichas_id_ficha = :id_ficha
                    ORDER BY COALESCE(h.fecha, '2099-12-31') ASC, FIELD(h.dias_semana, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'), h.hora_entrada ASC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute([':id_ficha' => $idFicha]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log("FichaModel::obtenerBloquesPorFicha: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inserta o actualiza un bloque de horario para una ficha.
     */
    public function guardarBloqueHorario(
        int $idFicha,
        ?int $idInstructor,
        string $diasSemana,
        string $horaEntrada,
        string $horaSalida,
        int $duracionHoras = 3,
        string $tipoBloque = '3H_INDIVIDUAL',
        ?string $ambiente = null,
        ?string $competencia = null,
        ?int $idHorario = null,
        ?string $fecha = null
    ): int|bool {
        $pdo = Conexion::obtener();
        try {
            $tEnt = strtotime($horaEntrada);
            $tSal = strtotime($horaSalida);
            if ($tSal && $tEnt && $tSal > $tEnt) {
                $duracionReal = (int)round(($tSal - $tEnt) / 3600);
                if ($duracionReal > 6) {
                    throw new InvalidArgumentException("Límite de jornada excedido: La duración máxima permitida para un bloque continuo de formación es de 6 horas. No es posible asignar un bloque continuo de {$duracionReal} horas.");
                }
                if ($duracionHoras <= 0 || $duracionHoras > 6) {
                    $duracionHoras = $duracionReal;
                }
            }

            if ($tipoBloque === '3H_INDIVIDUAL' || empty($tipoBloque)) {
                if ($duracionHoras >= 6) {
                    $tipoBloque = '6H_CONTINUO';
                } elseif ($duracionHoras === 4 && substr($horaEntrada, 0, 2) >= '18') {
                    $tipoBloque = '4H_NOCTURNA';
                }
            }

            // Validar colisión / traslape de horario si se asigna un instructor
            if ($idInstructor && $idInstructor > 0) {
                $sqlConflicto = "SELECT h.id_horario, 
                                        h.fecha,
                                        TIME_FORMAT(h.hora_entrada, '%H:%i') AS hora_entrada, 
                                        TIME_FORMAT(h.hora_salida, '%H:%i') AS hora_salida, 
                                        f.numero_ficha,
                                        COALESCE(CONCAT(u.nombres, ' ', u.apellidos), 'El instructor') AS instructor_nombre
                                 FROM horarios h
                                 JOIN fichas f ON h.fichas_id_ficha = f.id_ficha
                                 LEFT JOIN instructores i ON h.instructores_id_instructor = i.id_instructor
                                 LEFT JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                                 WHERE h.instructores_id_instructor = :id_inst
                                   AND h.estado = 'Activo'
                                   AND (:id_h IS NULL OR h.id_horario != :id_h_diff)
                                   AND (
                                       (:fecha IS NOT NULL AND (h.fecha = :fecha_eq OR (h.fecha IS NULL AND h.dias_semana = :dia)))
                                       OR (:fecha_null IS NULL AND h.dias_semana = :dia2)
                                   )
                                   AND h.hora_entrada < :sal
                                   AND h.hora_salida > :ent
                                 LIMIT 1";
                $stmtC = $pdo->prepare($sqlConflicto);
                $stmtC->execute([
                    ':id_inst'    => $idInstructor,
                    ':dia'        => $diasSemana,
                    ':dia2'       => $diasSemana,
                    ':fecha'      => $fecha,
                    ':fecha_eq'   => $fecha,
                    ':fecha_null' => $fecha,
                    ':id_h'       => $idHorario,
                    ':id_h_diff'  => $idHorario,
                    ':ent'        => $horaEntrada,
                    ':sal'        => $horaSalida
                ]);
                $conflicto = $stmtC->fetch(PDO::FETCH_ASSOC);

                if ($conflicto) {
                    $nombreDocente = $conflicto['instructor_nombre'];
                    $hIniConf = $conflicto['hora_entrada'];
                    $hFinConf = $conflicto['hora_salida'];
                    $fichaConf = $conflicto['numero_ficha'];
                    $hIniNueva = substr($horaEntrada, 0, 5);
                    $hFinNueva = substr($horaSalida, 0, 5);
                    $detFecha = !empty($conflicto['fecha']) ? " del " . date('d/m/Y', strtotime($conflicto['fecha'])) : " el día {$diasSemana}";
                    throw new InvalidArgumentException("Conflicto de Horario: {$nombreDocente} ya tiene asignada la Ficha {$fichaConf}{$detFecha} de {$hIniConf} a {$hFinConf}. Un instructor no puede tener dos clases simultáneas ({$hIniNueva} - {$hFinNueva}).");
                }
            }

            if ($idHorario && $idHorario > 0) {
                $sql = "UPDATE horarios 
                        SET instructores_id_instructor = :id_inst,
                            fecha = :fecha,
                            dias_semana = :dias_semana,
                            hora_entrada = :hora_entrada,
                            hora_salida = :hora_salida,
                            duracion_horas = :duracion,
                            tipo_bloque = :tipo_bloque,
                            ambiente = :ambiente,
                            competencia = :competencia,
                            estado = 'Activo'
                        WHERE id_horario = :id_h AND fichas_id_ficha = :id_f";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_inst'      => ($idInstructor > 0 ? $idInstructor : null),
                    ':fecha'        => !empty($fecha) ? $fecha : null,
                    ':dias_semana'  => $diasSemana,
                    ':hora_entrada' => $horaEntrada,
                    ':hora_salida'  => $horaSalida,
                    ':duracion'     => $duracionHoras,
                    ':tipo_bloque'  => $tipoBloque,
                    ':ambiente'     => trim($ambiente ?: 'Ambiente por asignar'),
                    ':competencia'  => trim($competencia ?: 'Competencia técnica'),
                    ':id_h'         => $idHorario,
                    ':id_f'         => $idFicha
                ]);
                return $idHorario;
            } else {
                $sql = "INSERT INTO horarios 
                        (fichas_id_ficha, instructores_id_instructor, fecha, dias_semana, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia, estado)
                        VALUES (:id_f, :id_inst, :fecha, :dias_semana, :hora_entrada, :hora_salida, :duracion, :tipo_bloque, :ambiente, :competencia, 'Activo')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_f'         => $idFicha,
                    ':id_inst'      => ($idInstructor > 0 ? $idInstructor : null),
                    ':fecha'        => !empty($fecha) ? $fecha : null,
                    ':dias_semana'  => $diasSemana,
                    ':hora_entrada' => $horaEntrada,
                    ':hora_salida'  => $horaSalida,
                    ':duracion'     => $duracionHoras,
                    ':tipo_bloque'  => $tipoBloque,
                    ':ambiente'     => trim($ambiente ?: 'Ambiente por asignar'),
                    ':competencia'  => trim($competencia ?: 'Competencia técnica')
                ]);
                $nuevoId = (int)$pdo->lastInsertId();

                if ($idInstructor && $idInstructor > 0) {
                    $stmtRel = $pdo->prepare("INSERT IGNORE INTO instructores_has_fichas (instructores_id_instructor, fichas_id_ficha, minutos_retardo, minutos_salida) VALUES (:id_inst, :id_f, 15, 0)");
                    $stmtRel->execute([':id_inst' => $idInstructor, ':id_f' => $idFicha]);
                }

                return $nuevoId;
            }
        } catch (Throwable $e) {
            error_log("FichaModel::guardarBloqueHorario: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Elimina un bloque de horario.
     */
    public function eliminarBloqueHorario(int $idHorario): bool
    {
        try {
            $stmt = Conexion::obtener()->prepare("DELETE FROM horarios WHERE id_horario = :id");
            return $stmt->execute([':id' => $idHorario]);
        } catch (Throwable $e) {
            error_log("FichaModel::eliminarBloqueHorario: " . $e->getMessage());
            return false;
        }
    }
}
