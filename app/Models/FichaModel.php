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
                        TIME_FORMAT(h.hora_entrada, '%h:%i %p') AS entrada, 
                        TIME_FORMAT(h.hora_salida, '%h:%i %p') AS salida, 
                        COUNT(DISTINCT a.id_aprendiz) AS aprendices, 
                        COALESCE(GROUP_CONCAT(DISTINCT CONCAT(u.nombres, ' ', u.apellidos) SEPARATOR ', '), 'Sin instructor') AS instructores, 
                        GROUP_CONCAT(DISTINCT i.id_instructor) AS instructores_ids
                    FROM fichas f
                    LEFT JOIN horarios h ON f.id_ficha = h.fichas_id_ficha
                    LEFT JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN instructores i ON ihf.instructores_id_instructor = i.id_instructor
                    LEFT JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
                    LEFT JOIN aprendices a ON a.fichas_id_ficha = f.id_ficha";

            $params = [];
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE f.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " GROUP BY f.id_ficha, f.numero_ficha, f.programa, f.estado, f.area, h.hora_entrada, h.hora_salida
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
    public function crearFicha(string $numeroFicha, string $programa, string $area, ?int $idHorario = null, array $instructoresIds = []): int
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
                $sqlRel = "INSERT INTO instructores_has_fichas (instructores_id_instructor, fichas_id_ficha, minutos_retardo) 
                           VALUES (:id_instructor, :id_ficha, 10)";
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
}
