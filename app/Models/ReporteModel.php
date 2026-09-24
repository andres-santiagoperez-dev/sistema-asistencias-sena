<?php
/**
 * Modelo de Reportes (ReporteModel)
 * 
 * Responsabilidad:
 * 1. Consultar y consolidar las métricas de asistencia de aprendices en un rango de fechas.
 * 2. Segmentar los datos por ficha, término de búsqueda y área técnica ('comercio' o 'Tics').
 * 3. Obtener el listado de fichas formativas disponibles para poblar los filtros.
 * 4. Calcular los indicadores clave consolidados (inasistencias, retardos, aprendices en riesgo).
 * 5. Programación Defensiva: Cada método captura cualquier fallo de base de datos (Throwable)
 *    y retorna estructuras seguras para evitar interrumpir la carga del sistema.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

class ReporteModel
{
    /**
     * Obtiene el consolidado de asistencia agrupado por aprendiz en un rango de fechas dado.
     * Permite filtrar opcionalmente por ficha formativa, término de búsqueda y área técnica.
     * 
     * @param string      $desde     Fecha inicial en formato Y-m-d.
     * @param string      $hasta     Fecha final en formato Y-m-d.
     * @param string|null $ficha     Número de ficha o etiqueta completa para filtrar.
     * @param string|null $busqueda  Texto para buscar por nombre, apellido o documento.
     * @param string|null $area      Área técnica ('comercio', 'Tics' o null para todas).
     * @return array                 Listado de aprendices con sus métricas acumuladas.
     */
    public function obtenerConsolidadoAsistencia(
        string $desde,
        string $hasta,
        ?string $ficha = null,
        ?string $busqueda = null,
        ?string $area = null
    ): array {
        try {
            $pdo = Conexion::obtener();

            $sql = "SELECT 
                        ap.id_aprendiz,
                        CONCAT(u.nombres, ' ', u.apellidos) AS aprendiz,
                        u.numero_documento AS documento,
                        u.correo,
                        f.id_ficha,
                        f.numero_ficha,
                        f.programa,
                        f.area,
                        CONCAT(f.numero_ficha, ' - ', f.programa) AS ficha,
                        COALESCE(
                            GROUP_CONCAT(DISTINCT CONCAT(ui.nombres, ' ', ui.apellidos) SEPARATOR ', '),
                            'Sin instructor asignado'
                        ) AS instructor,
                        COUNT(DISTINCT CASE WHEN LOWER(a.estado) = 'inasistencia' THEN a.id_registro END) AS diasInasistencia,
                        COUNT(DISTINCT CASE WHEN LOWER(a.estado) = 'retardo' THEN a.id_registro END) AS totalRetardos,
                        COALESCE(SUM(CASE WHEN LOWER(a.estado) = 'retardo' THEN COALESCE(a.minutos_retardo, 15) ELSE 0 END), 0) AS minutosRetardo
                    FROM aprendices ap
                    INNER JOIN usuarios u ON ap.usuarios_id_usuario = u.id_usuario
                    INNER JOIN fichas f ON ap.fichas_id_ficha = f.id_ficha
                    LEFT JOIN instructores_has_fichas ihf ON f.id_ficha = ihf.fichas_id_ficha
                    LEFT JOIN instructores ins ON ihf.instructores_id_instructor = ins.id_instructor
                    LEFT JOIN usuarios ui ON ins.usuarios_id_usuario = ui.id_usuario
                    LEFT JOIN asistencia a ON ap.id_aprendiz = a.aprendices_id_aprendiz 
                        AND a.fecha BETWEEN :desde AND :hasta
                    WHERE 1=1";

            $params = [
                ':desde' => $desde,
                ':hasta' => $hasta
            ];

            // 1. Filtro por Área Técnica
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " AND f.area = :area";
                $params[':area'] = $area;
            }

            // 2. Filtro por Ficha
            if ($ficha !== null && $ficha !== '' && $ficha !== 'todas') {
                $sql .= " AND (f.numero_ficha = :ficha_num OR CONCAT(f.numero_ficha, ' - ', f.programa) = :ficha_txt)";
                $params[':ficha_num'] = $ficha;
                $params[':ficha_txt'] = $ficha;
            }

            // 3. Filtro por Búsqueda (Nombre o Documento)
            if ($busqueda !== null && trim($busqueda) !== '') {
                $sql .= " AND (CONCAT(u.nombres, ' ', u.apellidos) LIKE :busq_nom OR u.numero_documento LIKE :busq_doc)";
                $params[':busq_nom'] = '%' . trim($busqueda) . '%';
                $params[':busq_doc'] = '%' . trim($busqueda) . '%';
            }

            $sql .= " GROUP BY ap.id_aprendiz, u.id_usuario, u.nombres, u.apellidos, u.numero_documento, u.correo,
                               f.id_ficha, f.numero_ficha, f.programa, f.area
                      ORDER BY diasInasistencia DESC, minutosRetardo DESC, u.apellidos ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];

        } catch (Throwable $e) {
            error_log("ReporteModel::obtenerConsolidadoAsistencia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el listado de fichas formativas disponibles para poblar los selectores de filtro.
     * 
     * @param string|null $area Área técnica para filtrar ('comercio', 'Tics' o null).
     * @return array            Lista de fichas con identificador, número y nombre de programa.
     */
    public function obtenerFichasParaFiltro(?string $area = null): array
    {
        try {
            $pdo = Conexion::obtener();

            $sql = "SELECT id_ficha, numero_ficha, programa, area,
                           CONCAT(numero_ficha, ' - ', programa) AS etiqueta
                    FROM fichas";

            $params = [];
            if ($area !== null && $area !== '' && $area !== 'todas') {
                $sql .= " WHERE area = :area";
                $params[':area'] = $area;
            }

            $sql .= " ORDER BY numero_ficha ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll() ?: [];

        } catch (Throwable $e) {
            error_log("ReporteModel::obtenerFichasParaFiltro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula los totales cuantitativos a partir de un arreglo de consolidado de asistencia.
     * 
     * @param array $reporte Arreglo de registros devuelto por obtenerConsolidadoAsistencia.
     * @return array         Métricas calculadas (totalInasistencias, totalMinutosRetardo, etc.).
     */
    public function calcularMetricas(array $reporte): array
    {
        $totalInasistencias  = 0;
        $totalMinutosRetardo = 0;
        $totalRetardos       = 0;
        $aprendicesEnRiesgo  = 0;

        foreach ($reporte as $item) {
            $inasistencias = (int)($item['diasInasistencia'] ?? 0);
            $minutos       = (int)($item['minutosRetardo'] ?? 0);
            $retardos      = (int)($item['totalRetardos'] ?? 0);

            $totalInasistencias  += $inasistencias;
            $totalMinutosRetardo += $minutos;
            $totalRetardos       += $retardos;

            // Se considera en riesgo si acumula 3 o más inasistencias en el periodo
            if ($inasistencias >= 3) {
                $aprendicesEnRiesgo++;
            }
        }

        return [
            'totalInasistencias'  => $totalInasistencias,
            'totalMinutosRetardo' => $totalMinutosRetardo,
            'totalRetardos'       => $totalRetardos,
            'aprendicesEnRiesgo'  => $aprendicesEnRiesgo,
            'totalAprendices'     => count($reporte)
        ];
    }
}
