<?php
/**
 * Modelo de Dashboard (DashboardModel)
 * 
 * Responsabilidad:
 * 1. Consolidar métricas y KPIs cuantitativos en tiempo real para el panel de control.
 * 2. Calcular datos agrupados para gráficas estadísticas (ApexCharts: asistencia semanal y balance de excusas).
 * 3. Consultar las últimas marcaciones de asistencia en sede.
 * 4. Respetar el filtrado por área técnica ('comercio' o 'Tics') del administrador en sesión.
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
        $pdo = Conexion::obtener();
        $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

        // 1. Total Aprendices Activos
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

        // 2. Retardos Hoy
        $sqlRetardos = "SELECT COUNT(DISTINCT a.id_asistencia)
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

        // 3. Inasistencias Hoy
        $sqlInasistencias = "SELECT COUNT(DISTINCT a.id_asistencia)
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

        // 4. Excusas Pendientes
        $sqlExcusas = "SELECT COUNT(DISTINCT e.id_excusa)
                       FROM excusas_medicas e
                       LEFT JOIN asistencia a ON a.excusas_medicas_id_excusa = e.id_excusa
                       LEFT JOIN aprendices ap ON a.aprendices_id_aprendiz = ap.id_aprendiz
                       LEFT JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                       WHERE LOWER(e.estado) = 'pendiente'";
        $paramsExcusas = [];
        if ($filtroArea) {
            $sqlExcusas .= " AND (f.area = :area OR f.area IS NULL)";
            $paramsExcusas[':area'] = $area;
        }
        $stmtExc = $pdo->prepare($sqlExcusas);
        $stmtExc->execute($paramsExcusas);
        $excusasPendientes = (int)$stmtExc->fetchColumn();

        return [
            'aprendices'        => $totalAprendices,
            'retardosHoy'       => $retardosHoy,
            'inasistenciasHoy'  => $inasistenciasHoy,
            'excusasPendientes' => $excusasPendientes,
        ];
    }

    /**
     * Genera la distribución de asistencia diaria de la semana actual (Lunes a Viernes)
     * para la gráfica de barras de ApexCharts.
     */
    public function obtenerAsistenciaSemanalAdmin(?string $area = null): array
    {
        $pdo = Conexion::obtener();
        $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

        // Calculamos lunes y viernes de la semana en curso
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

        // Mapeo a los 5 días laborales: Lunes (2), Martes (3), Miércoles (4), Jueves (5), Viernes (6)
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
    }

    /**
     * Obtiene el balance de excusas médicas (Aprobadas, Pendientes, Rechazadas)
     * para alimentar el gráfico de dona de ApexCharts.
     */
    public function obtenerBalanceExcusasAdmin(?string $area = null): array
    {
        $pdo = Conexion::obtener();
        $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

        $sql = "SELECT 
                    SUM(CASE WHEN LOWER(e.estado) = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas,
                    SUM(CASE WHEN LOWER(e.estado) = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN LOWER(e.estado) = 'rechazada' THEN 1 ELSE 0 END) AS rechazadas
                FROM excusas_medicas e
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
    }

    /**
     * Obtiene las últimas marcaciones de ingreso en sede en tiempo real.
     */
    public function obtenerUltimosIngresosAdmin(?string $area = null, int $limite = 6): array
    {
        $pdo = Conexion::obtener();
        $filtroArea = ($area !== null && $area !== '' && $area !== 'todas');

        $sql = "SELECT 
                    a.id_asistencia,
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

        return $stmt->fetchAll();
    }
}
