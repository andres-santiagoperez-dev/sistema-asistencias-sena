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
            $sql = "SELECT a.id_aprendiz, a.codigo_rfid, f.id_ficha, f.numero_ficha, f.programa, f.area,
                           h.id_horario, h.hora_entrada, h.hora_salida
                    FROM aprendices a
                    LEFT JOIN fichas f ON a.fichas_id_ficha = f.id_ficha
                    LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
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
     * Consulta el historial completo de marcaciones del aprendiz con filtros opcionales.
     */
    public function obtenerHistorialAsistencia(int $idAprendiz, ?string $inicio = null, ?string $fin = null): array
    {
        try {
            $sql = "SELECT a.id_registro, a.fecha, a.hora_entrada, a.hora_salida, a.minutos_retardo, a.estado,
                           de.estado AS estado_excusa
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

            $sql .= " ORDER BY a.fecha DESC";

            $stmt = Conexion::obtener()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            error_log("AprendizModel::obtenerHistorialAsistencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula los 4 KPIs estadísticos de asistencia para el tablero del aprendiz.
     */
    public function obtenerEstadisticasHistorial(int $idAprendiz, ?string $inicio = null, ?string $fin = null): array
    {
        $default = [
            'a_tiempo' => 0,
            'retardos' => 0,
            'inasistencias' => 0,
            'inasistencias_justificadas' => 0
        ];

        try {
            $sql = "SELECT 
                        COALESCE(SUM(CASE WHEN a.estado = 'Asistio' THEN 1 ELSE 0 END), 0) AS a_tiempo,
                        COALESCE(SUM(CASE WHEN a.estado = 'Retardo' THEN 1 ELSE 0 END), 0) AS retardos,
                        COALESCE(SUM(CASE WHEN a.estado = 'Inasistencia' AND (de.estado IS NULL OR de.estado != 'Aprobada') THEN 1 ELSE 0 END), 0) AS inasistencias,
                        COALESCE(SUM(CASE WHEN a.estado = 'Inasistencia' AND de.estado = 'Aprobada' THEN 1 ELSE 0 END), 0) AS inasistencias_justificadas
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
                    'a_tiempo'                  => (int)($res['a_tiempo'] ?? 0),
                    'retardos'                  => (int)($res['retardos'] ?? 0),
                    'inasistencias'             => (int)($res['inasistencias'] ?? 0),
                    'inasistencias_justificadas' => (int)($res['inasistencias_justificadas'] ?? 0)
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
                    LEFT JOIN horarios h ON f.Horarios_id_horario = h.id_horario
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
}
