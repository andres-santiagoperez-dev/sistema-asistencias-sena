<?php
/**
 * Servicio de Procesamiento de Horarios SENA desde Excel (HorarioExcelService)
 * Sistema de Control de Asistencia SENA
 * 
 * Responsabilidad:
 * 1. Cargar e interpretar la matriz trimestral de horarios oficial del SENA (.xlsx).
 * 2. Extraer metadatos de ficha y programa (celdas E2 y H2).
 * 3. Identificar bloques de formación continuos (6H_CONTINUO) o particionados (3H_INDIVIDUAL).
 * 4. Mapear o registrar automáticamente a los instructores vinculados en la base de datos.
 * 5. Persistir de forma transaccional y atómica los bloques en 'horarios' y en 'instructores_has_fichas'.
 */

defined('BASE_PATH') or exit('Acceso denegado.');

use PhpOffice\PhpSpreadsheet\IOFactory;

class HorarioExcelService
{
    /**
     * Mapeo de columnas del Excel SENA a los días de la semana ENUM de la base de datos.
     */
    private const DIAS_COLUMNAS = [
        'D' => 'Lunes',
        'F' => 'Martes',
        'H' => 'Miercoles',
        'J' => 'Jueves',
        'L' => 'Viernes',
        'N' => 'Sabado'
    ];

    /**
     * Mapeo de nombres de meses en español a su valor numérico.
     */
    private const MESES_MAP = [
        'ENERO'      => 1,
        'FEBRERO'    => 2,
        'MARZO'      => 3,
        'ABRIL'      => 4,
        'MAYO'       => 5,
        'JUNIO'      => 6,
        'JULIO'      => 7,
        'AGOSTO'     => 8,
        'SEPTIEMBRE' => 9,
        'OCTUBRE'    => 10,
        'NOVIEMBRE'  => 11,
        'DICIEMBRE'  => 12
    ];

    /**
     * Procesa una plantilla de horario SENA (.xlsx) y la importa a la base de datos,
     * respetando la fecha de corte y descartando programaciones extemporáneas.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo .xlsx
     * @param string|null $fechaInicioCorte Fecha a partir de la cual rige la formación (YYYY-MM-DD). Si es null, usa hoy.
     * @return array Resumen detallado de la operación
     * @throws RuntimeException Si el archivo es inválido, contiene solo fechas vencidas o no tiene datos
     */
    public function procesarArchivoSena(string $rutaArchivo, ?string $fechaInicioCorte = null): array
    {
        if (!file_exists($rutaArchivo)) {
            throw new RuntimeException("El archivo de horario no existe o no pudo ser leído.");
        }

        // Si no se especifica fecha de corte, tomar la fecha actual del sistema
        if (empty($fechaInicioCorte)) {
            $fechaInicioCorte = date('Y-m-d');
        }

        // 1. Validar extensiones requeridas por PhpSpreadsheet
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("La extensión de PHP 'zip' no está habilitada en el servidor. Por favor habilite 'extension=zip' en su php.ini y reinicie Apache.");
        }

        // 2. Carga del archivo con PhpSpreadsheet
        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Throwable $e) {
            error_log("HorarioExcelService::procesarArchivoSena - Carga fallida: " . $e->getMessage());
            throw new RuntimeException("El formato del archivo no es un Excel válido o está dañado: " . $e->getMessage());
        }

        // 2. Extracción de Metadatos de la Ficha
        $numeroFicha = trim((string)$sheet->getCell('E2')->getFormattedValue());
        if ($numeroFicha === '') {
            $numeroFicha = trim((string)$sheet->getCell('D2')->getFormattedValue());
        }

        // Limpiar el número de ficha (solo dígitos si es posible)
        if (preg_match('/(\d{5,10})/', $numeroFicha, $matches)) {
            $numeroFicha = $matches[1];
        }

        if (empty($numeroFicha)) {
            throw new RuntimeException("No se encontró el número de ficha formativa en la celda E2 del archivo Excel.");
        }

        $programa = trim((string)$sheet->getCell('H2')->getFormattedValue());
        if (empty($programa)) {
            $programa = trim((string)$sheet->getCell('G2')->getFormattedValue());
        }
        if (empty($programa)) {
            $programa = 'Programa Formativo SENA';
        }

        // Deducir área técnica según el programa
        $area = $this->deducirArea($programa);

        // 3. Extracción de los bloques recorriendo la matriz y aplicando el motor de fechas
        $extraccion = $this->extraerBloquesMatriz($sheet, $fechaInicioCorte);
        $bloquesExtraidos = $extraccion['bloques'];

        if (empty($bloquesExtraidos)) {
            $minFmt = !empty($extraccion['fecha_min_detectada']) ? date('d/m/Y', strtotime($extraccion['fecha_min_detectada'])) : 'N/A';
            $corteFmt = date('d/m/Y', strtotime($fechaInicioCorte));
            throw new RuntimeException("No se encontraron sesiones programadas a partir del {$corteFmt}. El archivo contiene semanas previas (iniciando el {$minFmt}) pero ninguna vigente a partir de la fecha de inicio requerida.");
        }

        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 4. Garantizar la existencia de la ficha en la base de datos
            $idFicha = $this->obtenerOCrearFicha($pdo, $numeroFicha, $programa, $area);

            // 5. Reemplazo limpio y atómico: limpiar los horarios previos de esta ficha
            $stmtClean = $pdo->prepare("DELETE FROM horarios WHERE fichas_id_ficha = :id_f");
            $stmtClean->execute([':id_f' => $idFicha]);

            // 6. Mapeo o creación de instructores y persistencia de horarios
            $instructoresCache = [];
            $guardados = 0;
            $actualizados = 0;
            $bloquesResumen = [];

            foreach ($bloquesExtraidos as $b) {
                $nombreInstructor = $b['instructor'];
                if (!isset($instructoresCache[$nombreInstructor])) {
                    $instructoresCache[$nombreInstructor] = $this->obtenerOCrearInstructor($pdo, $nombreInstructor, $area);
                }
                $idInstructor = $instructoresCache[$nombreInstructor];

                // 5.1 Validar colisión / traslape de horario con otra ficha para este instructor en la misma fecha
                $stmtConflicto = $pdo->prepare(
                    "SELECT h.id_horario, 
                            h.fecha,
                            TIME_FORMAT(h.hora_entrada, '%H:%i') AS hora_entrada, 
                            TIME_FORMAT(h.hora_salida, '%H:%i') AS hora_salida, 
                            f.numero_ficha
                     FROM horarios h
                     JOIN fichas f ON h.fichas_id_ficha = f.id_ficha
                     WHERE h.instructores_id_instructor = :id_inst
                       AND h.fichas_id_ficha != :id_f
                       AND h.estado = 'Activo'
                       AND (
                           (h.fecha IS NOT NULL AND h.fecha = :fecha)
                           OR (h.fecha IS NULL AND h.dias_semana = :dia)
                       )
                       AND h.hora_entrada < :sal
                       AND h.hora_salida > :ent
                     LIMIT 1"
                );
                $stmtConflicto->execute([
                    ':id_inst' => $idInstructor,
                    ':id_f'    => $idFicha,
                    ':fecha'   => $b['fecha'],
                    ':dia'     => $b['dia'],
                    ':ent'     => $b['hora_entrada'],
                    ':sal'     => $b['hora_salida']
                ]);
                $conflicto = $stmtConflicto->fetch(PDO::FETCH_ASSOC);

                if ($conflicto) {
                    $hIniConf = $conflicto['hora_entrada'];
                    $hFinConf = $conflicto['hora_salida'];
                    $fichaConf = $conflicto['numero_ficha'];
                    $hIniNueva = substr($b['hora_entrada'], 0, 5);
                    $hFinNueva = substr($b['hora_salida'], 0, 5);
                    $fechaConfFmt = date('d/m/Y', strtotime($b['fecha']));
                    throw new RuntimeException("Conflicto de Horario: El instructor {$nombreInstructor} ya tiene agendada la Ficha {$fichaConf} el día {$b['dia']} {$fechaConfFmt} en el horario {$hIniConf} - {$hFinConf}. Un docente no puede tener dos fichas agendadas al mismo tiempo ({$hIniNueva} - {$hFinNueva}).");
                }

                // 6.2 Insertar el bloque de horario con su fecha exacta
                $stmtInsert = $pdo->prepare(
                    "INSERT INTO horarios (dias_semana, fecha, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia, estado, fichas_id_ficha, instructores_id_instructor)
                     VALUES (:dia, :fecha, :ent, :sal, :dur, :tipo, :amb, :comp, 'Activo', :id_f, :id_inst)"
                );
                $stmtInsert->execute([
                    ':dia'     => $b['dia'],
                    ':fecha'   => $b['fecha'],
                    ':ent'     => $b['hora_entrada'],
                    ':sal'     => $b['hora_salida'],
                    ':dur'     => $b['duracion_horas'],
                    ':tipo'    => $b['tipo_bloque'],
                    ':amb'     => $b['ambiente'],
                    ':comp'    => $b['competencia'],
                    ':id_f'    => $idFicha,
                    ':id_inst' => $idInstructor
                ]);
                $guardados++;

                // Asociar al instructor con la ficha en instructores_has_fichas
                $stmtHasFicha = $pdo->prepare(
                    "INSERT IGNORE INTO instructores_has_fichas (instructores_id_instructor, fichas_id_ficha, minutos_retardo, minutos_salida)
                     VALUES (:id_inst, :id_f, 15, 0)"
                );
                $stmtHasFicha->execute([
                    ':id_inst' => $idInstructor,
                    ':id_f'    => $idFicha
                ]);

                $bloquesResumen[] = [
                    'fecha'        => $b['fecha'],
                    'dia'          => $b['dia'],
                    'hora_entrada' => substr($b['hora_entrada'], 0, 5),
                    'hora_salida'  => substr($b['hora_salida'], 0, 5),
                    'duracion'     => $b['duracion_horas'] . 'h',
                    'tipo_bloque'  => $b['tipo_bloque'],
                    'instructor'   => $nombreInstructor,
                    'ambiente'     => $b['ambiente'],
                    'competencia'  => $b['competencia']
                ];
            }

            $pdo->commit();

            return [
                'success'                  => true,
                'numero_ficha'             => $numeroFicha,
                'programa'                 => $programa,
                'area'                     => $area,
                'total_bloques'            => count($bloquesExtraidos),
                'nuevos_insertados'        => $guardados,
                'actualizados'             => $actualizados,
                'total_instructores'       => count($instructoresCache),
                'instructores'             => array_keys($instructoresCache),
                'fecha_corte_aplicada'     => $fechaInicioCorte,
                'fecha_min_detectada'      => $extraccion['fecha_min_detectada'],
                'fecha_max_detectada'      => $extraccion['fecha_max_detectada'],
                'semana_activa_inicio'     => $extraccion['semana_activa_inicio'] ?? null,
                'semana_activa_fin'        => $extraccion['semana_activa_fin'] ?? null,
                'semanas_omitidas_pasadas' => $extraccion['semanas_omitidas_pasadas'],
                'semanas_procesadas'       => $extraccion['semanas_procesadas'],
                'bloques'                  => $bloquesResumen
            ];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("HorarioExcelService::procesarArchivoSena - Error en transacción: " . $e->getMessage());
            if ($e instanceof PDOException) {
                throw new RuntimeException("Ocurrió un error al registrar los bloques de horario en la base de datos. Por favor consulte al administrador.", 0, $e);
            }
            throw $e;
        }
    }

    /**
     * Recorre la cuadrícula del Excel, reconstruye las fechas por semana, valida vencimiento
     * y extrae los bloques únicos consolidados vigentes a partir de la fecha de corte.
     */
    /**
     * Recorre la cuadrícula del Excel, reconstruye las fechas por semana, valida vencimiento
     * y extrae los bloques únicos consolidados de la semana formativa activa a partir de la fecha de corte.
     */
    private function extraerBloquesMatriz($sheet, string $fechaInicioCorte): array
    {
        $maxRow = $sheet->getHighestRow();
        $baseYear = (int)date('Y');

        $fechaMinGlobal = null;
        $fechaMaxGlobal = null;
        $semanasOmitidas = 0;
        $semanaActiva = null;

        // Fase 1: Escanear todas las semanas para ubicar rangos globales y la semana activa
        for ($r = 1; $r <= $maxRow; $r++) {
            $valD = strtoupper(trim((string)$sheet->getCell('D' . $r)->getFormattedValue()));

            if ($valD === 'LUNES' && is_numeric($sheet->getCell('D' . ($r + 1))->getFormattedValue())) {
                $filaHeader = $r;
                $filaMes = $r - 1;
                $filaNumDia = $r + 1;

                // Reconstruir fechas calendario de esta semana
                $currentMonth = null;
                $fechasSemana = [];
                $lastDayNum = 0;
                $yearSemana = $baseYear;

                foreach (range('D', 'N') as $colLetter) {
                    $valM = strtoupper(trim((string)$sheet->getCell($colLetter . $filaMes)->getFormattedValue()));
                    $cambioMesExplicit = false;
                    if ($valM !== '' && isset(self::MESES_MAP[$valM])) {
                        if ($currentMonth !== null && self::MESES_MAP[$valM] < $currentMonth) {
                            $yearSemana++;
                        }
                        $currentMonth = self::MESES_MAP[$valM];
                        $cambioMesExplicit = true;
                    }

                    if (isset(self::DIAS_COLUMNAS[$colLetter])) {
                        $nomDia = self::DIAS_COLUMNAS[$colLetter];
                        $numDia = (int)$sheet->getCell($colLetter . $filaNumDia)->getFormattedValue();

                        if (!$cambioMesExplicit && $lastDayNum > 20 && $numDia < 10 && $numDia > 0) {
                            if ($currentMonth == 12) {
                                $currentMonth = 1;
                                $yearSemana++;
                            } else {
                                $currentMonth++;
                            }
                        }
                        $lastDayNum = $numDia;

                        if ($numDia > 0 && $currentMonth > 0) {
                            $fStr = sprintf("%04d-%02d-%02d", $yearSemana, $currentMonth, $numDia);
                            $fechasSemana[$nomDia] = $fStr;

                            if ($fechaMinGlobal === null || $fStr < $fechaMinGlobal) $fechaMinGlobal = $fStr;
                            if ($fechaMaxGlobal === null || $fStr > $fechaMaxGlobal) $fechaMaxGlobal = $fStr;
                        }
                    }
                }

                $finSemana = end($fechasSemana);
                $iniSemana = reset($fechasSemana);

                if ($finSemana && $finSemana < $fechaInicioCorte) {
                    $semanasOmitidas++;
                    continue; // Omitir semana histórica previa a la fecha de inicio
                }

                // Recopilar todas las semanas vigentes que alcancen o superen la fecha de corte
                if ($finSemana && $finSemana >= $fechaInicioCorte) {
                    $semanasVigentes[] = [
                        'fila'   => $r,
                        'fechas' => $fechasSemana,
                        'inicio' => $iniSemana,
                        'fin'    => $finSemana
                    ];
                }
            }
        }

        // Fase 2: Validación de fechas totalmente vencidas
        $hoy = date('Y-m-d');
        if ($fechaMaxGlobal !== null && $fechaMaxGlobal < $hoy) {
            $minFmt = date('d/m/Y', strtotime($fechaMinGlobal));
            $maxFmt = date('d/m/Y', strtotime($fechaMaxGlobal));
            throw new RuntimeException("El archivo contiene una programación con fechas pasadas (periodo vencido del {$minFmt} al {$maxFmt}). No es posible importar horarios de periodos culminados.");
        }

        if (empty($semanasVigentes)) {
            $minFmt = !empty($fechaMinGlobal) ? date('d/m/Y', strtotime($fechaMinGlobal)) : 'N/A';
            $corteFmt = date('d/m/Y', strtotime($fechaInicioCorte));
            throw new RuntimeException("No se encontraron sesiones programadas a partir del {$corteFmt}. El archivo contiene semanas previas (iniciando el {$minFmt}) pero ninguna vigente a partir de la fecha de inicio requerida.");
        }

        // Fase 3: Extraer bloques formativos de todas las semanas vigentes con fecha exacta
        $bloquesMap = [];

        foreach ($semanasVigentes as $sem) {
            $filaHeader = $sem['fila'];
            $filaInicioHoras = $filaHeader + 2;

            foreach (self::DIAS_COLUMNAS as $col => $nomDia) {
                $fechaDia = $sem['fechas'][$nomDia] ?? null;
                if (!$fechaDia || $fechaDia < $fechaInicioCorte) {
                    continue; // Omitir días previos a la fecha de corte
                }

                $currentInstructor = null;
                $currentAmbiente = null;
                $currentComp = null;
                $hIni = null;
                $hFin = null;

                for ($hRow = $filaInicioHoras; $hRow <= $filaHeader + 20 && $hRow <= $maxRow; $hRow++) {
                    $valB = $sheet->getCell('B' . $hRow)->getFormattedValue();
                    $valC = $sheet->getCell('C' . $hRow)->getFormattedValue();
                    $contenido = trim((string)$sheet->getCell($col . $hRow)->getFormattedValue());

                    if (!is_numeric($valB) || !is_numeric($valC)) {
                        continue;
                    }

                    if ($contenido !== '' && stripos($contenido, 'festivo') === false) {
                        $partes = $this->parsearCeldaClase($contenido);
                        $inst   = $partes['instructor'];
                        $amb    = $partes['ambiente'];
                        $comp   = $partes['competencia'];

                        if ($currentInstructor === null) {
                            $currentInstructor = $inst;
                            $currentAmbiente   = $amb;
                            $currentComp       = $comp;
                            $hIni              = sprintf("%02d:00:00", $valB);
                            $hFin              = sprintf("%02d:00:00", $valC);
                        } elseif ($currentInstructor === $inst && $currentAmbiente === $amb) {
                            $duracionAcum = (int)$valC - (int)substr($hIni, 0, 2);
                            if ($duracionAcum > 6) {
                                // Particionar: el tope máximo continuo reglamentario es de 6 horas
                                $this->agregarBloqueConsolidado($bloquesMap, $fechaDia, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                                $hIni = sprintf("%02d:00:00", $valB);
                                $hFin = sprintf("%02d:00:00", $valC);
                            } else {
                                $hFin = sprintf("%02d:00:00", $valC);
                            }
                        } else {
                            $this->agregarBloqueConsolidado($bloquesMap, $fechaDia, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                            $currentInstructor = $inst;
                            $currentAmbiente   = $amb;
                            $currentComp       = $comp;
                            $hIni              = sprintf("%02d:00:00", $valB);
                            $hFin              = sprintf("%02d:00:00", $valC);
                        }
                    } else {
                        if ($currentInstructor !== null) {
                            $this->agregarBloqueConsolidado($bloquesMap, $fechaDia, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                            $currentInstructor = null;
                            $currentAmbiente   = null;
                            $currentComp       = null;
                            $hIni              = null;
                            $hFin              = null;
                        }
                    }
                }

                if ($currentInstructor !== null) {
                    $this->agregarBloqueConsolidado($bloquesMap, $fechaDia, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                }
            }
        }

        $primeraSemana = reset($semanasVigentes);
        $ultimaSemana = end($semanasVigentes);

        return [
            'bloques'                  => array_values($bloquesMap),
            'fecha_min_detectada'      => $fechaMinGlobal,
            'fecha_max_detectada'      => $fechaMaxGlobal,
            'semana_activa_inicio'     => $primeraSemana['inicio'],
            'semana_activa_fin'        => $ultimaSemana['fin'],
            'fecha_corte_aplicada'     => $fechaInicioCorte,
            'semanas_omitidas_pasadas' => $semanasOmitidas,
            'semanas_procesadas'       => count($semanasVigentes)
        ];
    }

    /**
     * Agrega un bloque al listado consolidado deduplicado por fecha,
     * aplicando el tope reglamentario de 6 horas continuas y clasificando jornada nocturna.
     */
    private function agregarBloqueConsolidado(
        array &$bloquesMap,
        string $fecha,
        string $dia,
        string $hIni,
        string $hFin,
        string $instructor,
        string $ambiente,
        string $competencia
    ): void {
        $duracion = (int)substr($hFin, 0, 2) - (int)substr($hIni, 0, 2);
        if ($duracion <= 0) {
            return;
        }

        // Si por alguna razón la duración supera 6 horas continuas, recortar al tope de 6h
        if ($duracion > 6) {
            $hFin = sprintf("%02d:00:00", (int)substr($hIni, 0, 2) + 6);
            $duracion = 6;
        }

        if ($duracion >= 6) {
            $tipoBloque = '6H_CONTINUO';
        } elseif ($duracion === 4 && (int)substr($hIni, 0, 2) >= 18) {
            $tipoBloque = '4H_NOCTURNA';
        } else {
            $tipoBloque = '3H_INDIVIDUAL';
        }

        $key = "{$fecha}|{$dia}|{$hIni}|{$hFin}|{$instructor}|{$ambiente}|{$competencia}";

        if (!isset($bloquesMap[$key])) {
            $bloquesMap[$key] = [
                'fecha'          => $fecha,
                'dia'            => $dia,
                'hora_entrada'   => $hIni,
                'hora_salida'    => $hFin,
                'duracion_horas' => $duracion,
                'tipo_bloque'    => $tipoBloque,
                'instructor'     => $instructor,
                'ambiente'       => mb_substr($ambiente, 0, 45),
                'competencia'    => mb_substr($competencia, 0, 45)
            ];
        }
    }

    /**
     * Parsea la celda de clase extrayendo instructor, competencia y ambiente.
     */
    private function parsearCeldaClase(string $contenido): array
    {
        $lineas = explode("\n", str_replace("\r", "", $contenido));
        $instructor = trim($lineas[0] ?? 'Instructor Asignado');
        $detalle = trim($lineas[1] ?? ($lineas[0] ?? 'Formación Técnica'));

        $ambiente = 'Ambiente General';
        $competencia = $detalle;

        if (str_contains($detalle, '-')) {
            $trozos = array_map('trim', explode('-', $detalle));
            $ultimo = end($trozos);
            
            // Si el último trozo contiene números o palabras clave de aula/ambiente
            if (preg_match('/(convergentes|comercio|lab|aula|sala|ambiente|taller|campo|\d)/i', $ultimo)) {
                $ambiente = $ultimo;
                array_pop($trozos);
                $competencia = implode(' - ', $trozos);
            }
        }

        return [
            'instructor'  => $instructor,
            'ambiente'    => $ambiente ?: 'Ambiente SENA',
            'competencia' => $competencia ?: 'Competencia Técnica'
        ];
    }

    /**
     * Deducir si el programa pertenece a 'Tics' o 'comercio'.
     */
    private function deducirArea(string $programa): string
    {
        $progLower = mb_strtolower($programa);
        $palabrasClaveTics = ['software', 'sistema', 'redes', 'datos', 'tic', 'programacion', 'desarrollo', 'web', 'nube'];

        foreach ($palabrasClaveTics as $palabra) {
            if (str_contains($progLower, $palabra)) {
                return 'Tics';
            }
        }

        return 'comercio';
    }

    /**
     * Obtiene el ID de la ficha o la crea si no existe.
     */
    private function obtenerOCrearFicha(PDO $pdo, string $numeroFicha, string $programa, string $area): int
    {
        $stmt = $pdo->prepare("SELECT id_ficha FROM fichas WHERE numero_ficha = :num LIMIT 1");
        $stmt->execute([':num' => $numeroFicha]);
        $idFicha = $stmt->fetchColumn();

        if ($idFicha) {
            return (int)$idFicha;
        }

        $stmtIns = $pdo->prepare(
            "INSERT INTO fichas (numero_ficha, programa, estado, area, fecha_creacion)
             VALUES (:num, :prog, 'Activa', :area, NOW())"
        );
        $stmtIns->execute([
            ':num'  => $numeroFicha,
            ':prog' => mb_substr($programa, 0, 100),
            ':area' => $area
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Busca al instructor por nombre o lo crea automáticamente.
     */
    private function obtenerOCrearInstructor(PDO $pdo, string $nombreCompleto, string $area): int
    {
        $nombreLimpio = trim($nombreCompleto);

        // 1. Buscar coincidencia exacta o normalizada
        $stmt = $pdo->prepare(
            "SELECT i.id_instructor 
             FROM instructores i
             INNER JOIN usuarios u ON i.usuarios_id_usuario = u.id_usuario
             WHERE LOWER(TRIM(CONCAT(u.nombres, ' ', u.apellidos))) = LOWER(:nom1)
                OR LOWER(TRIM(CONCAT(u.apellidos, ' ', u.nombres))) = LOWER(:nom2)
             LIMIT 1"
        );
        $stmt->execute([':nom1' => $nombreLimpio, ':nom2' => $nombreLimpio]);
        $idInstructor = $stmt->fetchColumn();

        if ($idInstructor) {
            return (int)$idInstructor;
        }

        // 2. Si no existe, crear usuario con rol Instructor (id_rol = 2)
        $partes = preg_split('/\s+/', $nombreLimpio);
        if (count($partes) === 1) {
            $nombres = $partes[0];
            $apellidos = 'SENA';
        } elseif (count($partes) === 2) {
            $nombres = $partes[0];
            $apellidos = $partes[1];
        } elseif (count($partes) === 3) {
            $nombres = $partes[0];
            $apellidos = $partes[1] . ' ' . $partes[2];
        } else {
            $nombres = $partes[0] . ' ' . $partes[1];
            $apellidos = implode(' ', array_slice($partes, 2));
        }

        // Documento sintético único basado en hash del nombre
        $docHash = '9' . str_pad((string)(abs(crc32($nombreLimpio)) % 100000000), 8, '0', STR_PAD_LEFT);
        $correo = strtolower(preg_replace('/[^a-z0-9]/', '', $nombres . '.' . $apellidos)) . '@sena.edu.co';
        $passHash = password_hash('Sena2026*', PASSWORD_BCRYPT);

        $stmtUser = $pdo->prepare(
            "INSERT INTO usuarios (tipo_documento, numero_documento, nombres, apellidos, correo, contraseña, estado, fecha_creacion, roles_id_rol)
             VALUES ('CC', :doc, :nom, :ape, :cor, :pwd, 'Activo', NOW(), 2)"
        );
        $stmtUser->execute([
            ':doc' => mb_substr($docHash, 0, 20),
            ':nom' => mb_substr($nombres, 0, 150),
            ':ape' => mb_substr($apellidos, 0, 45),
            ':cor' => mb_substr($correo, 0, 150),
            ':pwd' => $passHash
        ]);
        $idUsuario = (int)$pdo->lastInsertId();

        // 3. Crear registro en la tabla instructores
        $stmtInst = $pdo->prepare(
            "INSERT INTO instructores (area, especialidad, usuarios_id_usuario)
             VALUES (:area, 'Especialidad Técnica', :id_u)"
        );
        $stmtInst->execute([
            ':area' => $area,
            ':id_u' => $idUsuario
        ]);

        return (int)$pdo->lastInsertId();
    }
}
