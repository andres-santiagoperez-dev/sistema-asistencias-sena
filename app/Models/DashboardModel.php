<?php
/**
 * Modelo de Dashboard (DashboardModel)
 * 
 * Responsabilidad:
 * 1. Consolidar métricas y KPIs cuantitativos en tiempo real para el panel de control.
 * 2. Calcular datos agrupados para gráficas estadísticas (ApexCharts: asistencia semanal y balance de excusas).
 * 3. Consultar las últimas marcaciones de asistencia en sede.
 * 4. Programación Defensiva: Cada método captura cualquier fallo de base de datos (Throwable)
 *    y retorna valores de contingencia (0, N/A, listas vacías) para nunca romper la interfaz.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class DashboardModel
{
    /**
     * Obtiene los 4 KPIs principales del administrador:
     * - Aprendices activos en sede
     * - Retardos registrados hoy
     * - Inasistencias registradas hoy
     * - Excusas médicas pendientes de auditar
     */
    public function obtenerEstadisticasAdmin(?string $area = null): array
    {
        $defaultStats = [
            'aprendices'        => 0,
            'retardosHoy'       => 0,
            'inasistenciasHoy'  => 0,
            'excusasPendientes' => 0,
        ];

        try {
            $pdo = Conexion::obtener();
            $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

            // 1. Total Aprendices Activos
            $totalAprendices = 0;
            try {
                $sqlAprendices = "SELECT COUNT(DISTINCT ap.id_aprendiz)
                                  FROM aprendices ap
                                  INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                                  INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                                  WHERE u.estado = 'Activo' AND ap.estado_matricula = 'Matriculado'";
                $paramsAprendices = [];
                if ($filtroArea) {
                    $sqlAprendices .= " AND f.area = :area";
                    $paramsAprendices[':area'] = $area;
                }
                $stmtApr = $pdo->prepare($sqlAprendices);
                $stmtApr->execute($paramsAprendices);
                $totalAprendices = (int)$stmtApr->fetchColumn();
            } catch (Throwable $e) {
                error_log("DashboardModel::obtenerEstadisticasAdmin [aprendices]: " . $e->getMessage());
            }

            // 2. Retardos Hoy
            $retardosHoy = 0;
            try {
                $sqlRetardos = "SELECT COUNT(DISTINCT a.id_registro)
                                FROM asistencia a
                                INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                                INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                                WHERE a.fecha = CURDATE() AND LOWER(a.estado) = 'retardo'";
                $paramsRetardos = [];
                if ($filtroArea) {
                    $sqlRetardos .= " AND f.area = :area";
                    $paramsRetardos[':area'] = $area;
                }
                $stmtRet = $pdo->prepare($sqlRetardos);
                $stmtRet->execute($paramsRetardos);
                $retardosHoy = (int)$stmtRet->fetchColumn();
            } catch (Throwable $e) {
                error_log("DashboardModel::obtenerEstadisticasAdmin [retardos]: " . $e->getMessage());
            }

            // 3. Inasistencias Hoy
            $inasistenciasHoy = 0;
            try {
                $sqlInasistencias = "SELECT COUNT(DISTINCT a.id_registro)
                                     FROM asistencia a
                                     INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                                     INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                                     WHERE a.fecha = CURDATE() AND LOWER(a.estado) = 'inasistencia'";
                $paramsInasistencias = [];
                if ($filtroArea) {
                    $sqlInasistencias .= " AND f.area = :area";
                    $paramsInasistencias[':area'] = $area;
                }
                $stmtInas = $pdo->prepare($sqlInasistencias);
                $stmtInas->execute($paramsInasistencias);
                $inasistenciasHoy = (int)$stmtInas->fetchColumn();
            } catch (Throwable $e) {
                error_log("DashboardModel::obtenerEstadisticasAdmin [inasistencias]: " . $e->getMessage());
            }

            // 4. Excusas Pendientes (consulta detalle_excusas)
            $excusasPendientes = 0;
            try {
                $sqlExcusas = "SELECT COUNT(DISTINCT e.id_excusa)
                               FROM excusas_medicas e
                               LEFT JOIN detalle_excusas de ON de.excusas_medicas_id_excusa = e.id_excusa
                               LEFT JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                               LEFT JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                               LEFT JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                               WHERE (de.estado IS NULL OR LOWER(de.estado) = 'pendiente')";
                $paramsExcusas = [];
                if ($filtroArea) {
                    $sqlExcusas .= " AND (f.area = :area OR f.area IS NULL)";
                    $paramsExcusas[':area'] = $area;
                }
                $stmtExc = $pdo->prepare($sqlExcusas);
                $stmtExc->execute($paramsExcusas);
                $excusasPendientes = (int)$stmtExc->fetchColumn();
            } catch (Throwable $e) {
                error_log("DashboardModel::obtenerEstadisticasAdmin [excusas]: " . $e->getMessage());
            }

            return [
                'aprendices'        => $totalAprendices,
                'retardosHoy'       => $retardosHoy,
                'inasistenciasHoy'  => $inasistenciasHoy,
                'excusasPendientes' => $excusasPendientes,
            ];

        } catch (Throwable $e) {
            error_log("DashboardModel::obtenerEstadisticasAdmin [fatal]: " . $e->getMessage());
            return $defaultStats;
        }
    }

    /**
     * Genera la distribución de asistencia diaria de la semana actual (Lunes a Viernes)
     * para la gráfica de barras de ApexCharts.
     */
    public function obtenerAsistenciaSemanalAdmin(?string $area = null): array
    {
        $defaultChart = [
            'categorias'    => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
            'a_tiempo'      => [0, 0, 0, 0, 0],
            'retardos'      => [0, 0, 0, 0, 0],
            'inasistencias' => [0, 0, 0, 0, 0]
        ];

        try {
            $pdo = Conexion::obtener();
            $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

            $sql = "SELECT 
                        DAYOFWEEK(a.fecha) AS dia_semana,
                        SUM(CASE WHEN LOWER(a.estado) IN ('asistio', 'a tiempo', 'a-tiempo') THEN 1 ELSE 0 END) AS a_tiempo,
                        SUM(CASE WHEN LOWER(a.estado) = 'retardo' THEN 1 ELSE 0 END) AS retardos,
                        SUM(CASE WHEN LOWER(a.estado) = 'inasistencia' THEN 1 ELSE 0 END) AS inasistencias
                    FROM asistencia a
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    WHERE YEARWEEK(a.fecha, 1) = YEARWEEK(CURDATE(), 1)";

            $params = [];
            if ($filtroArea) {
                $sql .= " AND f.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " GROUP BY DAYOFWEEK(a.fecha) ORDER BY dia_semana ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $aTiempo       = [0, 0, 0, 0, 0];
            $retardos      = [0, 0, 0, 0, 0];
            $inasistencias = [0, 0, 0, 0, 0];

            foreach ($filas as $fila) {
                $indice = (int)$fila['dia_semana'] - 2; // Lunes=2 => indice 0
                if ($indice >= 0 && $indice <= 4) {
                    $aTiempo[$indice]       = (int)$fila['a_tiempo'];
                    $retardos[$indice]      = (int)$fila['retardos'];
                    $inasistencias[$indice] = (int)$fila['inasistencias'];
                }
            }

            return [
                'categorias'    => ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'],
                'a_tiempo'      => $aTiempo,
                'retardos'      => $retardos,
                'inasistencias' => $inasistencias
            ];

        } catch (Throwable $e) {
            error_log("DashboardModel::obtenerAsistenciaSemanalAdmin: " . $e->getMessage());
            return $defaultChart;
        }
    }

    /**
     * Obtiene el balance de excusas médicas (Aprobadas, Pendientes, Rechazadas)
     * para alimentar el gráfico de dona de ApexCharts.
     */
    public function obtenerBalanceExcusasAdmin(?string $area = null): array
    {
        try {
            $pdo = Conexion::obtener();
            $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

            $sql = "SELECT 
                        SUM(CASE WHEN LOWER(de.estado) IN ('aprobada', 'aprobado') THEN 1 ELSE 0 END) AS aprobadas,
                        SUM(CASE WHEN (de.estado IS NULL OR LOWER(de.estado) = 'pendiente') THEN 1 ELSE 0 END) AS pendientes,
                        SUM(CASE WHEN LOWER(de.estado) IN ('rechazada', 'rechazado') THEN 1 ELSE 0 END) AS rechazadas
                    FROM excusas_medicas e
                    LEFT JOIN detalle_excusas de ON de.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                    LEFT JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    LEFT JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha";

            $params = [];
            if ($filtroArea) {
                $sql .= " WHERE (f.area = :area OR f.area IS NULL)";
                $params[':area'] = $area;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch();

            return [
                (int)($resultado['aprobadas'] ?? 0),
                (int)($resultado['pendientes'] ?? 0),
                (int)($resultado['rechazadas'] ?? 0)
            ];

        } catch (Throwable $e) {
            error_log("DashboardModel::obtenerBalanceExcusasAdmin: " . $e->getMessage());
            return [0, 0, 0];
        }
    }

    /**
     * Obtiene las últimas marcaciones de ingreso en sede en tiempo real.
     */
    public function obtenerUltimosIngresosAdmin(?string $area = null, int $limite = 6): array
    {
        try {
            $pdo = Conexion::obtener();
            $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

            $sql = "SELECT 
                        a.id_registro AS id_asistencia,
                        a.id_registro,
                        a.fecha,
                        a.hora_entrada AS hora,
                        a.hora_salida,
                        a.estado,
                        u.numero_documento AS documento,
                        CONCAT(u.nombres, ' ', u.apellidos) AS aprendiz,
                        f.numero_ficha,
                        f.programa,
                        f.area,
                        CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha
                    FROM asistencia a
                    INNER JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                    INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha";

            $params = [];
            if ($filtroArea) {
                $sql .= " WHERE f.area = :area";
                $params[':area'] = $area;
            }

            $sql .= " ORDER BY a.fecha DESC, a.hora_entrada DESC LIMIT " . (int)$limite;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];

        } catch (Throwable $e) {
            error_log("DashboardModel::obtenerUltimosIngresosAdmin: " . $e->getMessage());
            return [];
        }
    }
}
