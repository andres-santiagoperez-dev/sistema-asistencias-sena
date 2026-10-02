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
     * Procesa una plantilla de horario SENA (.xlsx) y la importa a la base de datos.
     *
     * @param string $rutaArchivo Ruta absoluta al archivo .xlsx
     * @return array Resumen detallado de la operación
     * @throws RuntimeException Si el archivo es inválido o no contiene datos válidos
     */
    public function procesarArchivoSena(string $rutaArchivo): array
    {
        if (!file_exists($rutaArchivo)) {
            throw new RuntimeException("El archivo de horario no existe o no pudo ser leído.");
        }

        // 1. Carga del archivo con PhpSpreadsheet
        try {
            $spreadsheet = IOFactory::load($rutaArchivo);
            $sheet = $spreadsheet->getActiveSheet();
        } catch (Throwable $e) {
            error_log("HorarioExcelService::procesarArchivoSena - Carga fallida: " . $e->getMessage());
            throw new RuntimeException("El formato del archivo no es un Excel válido o está dañado.");
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

        $pdo = Conexion::obtener();
        $pdo->beginTransaction();

        try {
            // 3. Garantizar la existencia de la ficha en la base de datos
            $idFicha = $this->obtenerOCrearFicha($pdo, $numeroFicha, $programa, $area);

            // 4. Extracción de los bloques de horario recorriendo la matriz completa
            $bloquesExtraidos = $this->extraerBloquesMatriz($sheet);

            if (empty($bloquesExtraidos)) {
                throw new RuntimeException("No se encontraron bloques de clase programados en la matriz del archivo.");
            }

            // 5. Mapeo o creación de instructores y persistencia de horarios
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

                // Verificar si ya existe un bloque idéntico en la ficha
                $stmtCheck = $pdo->prepare(
                    "SELECT id_horario FROM horarios 
                     WHERE fichas_id_ficha = :id_f 
                       AND dias_semana = :dia 
                       AND hora_entrada = :ent 
                       AND hora_salida = :sal 
                       AND instructores_id_instructor = :id_inst 
                     LIMIT 1"
                );
                $stmtCheck->execute([
                    ':id_f'    => $idFicha,
                    ':dia'     => $b['dia'],
                    ':ent'     => $b['hora_entrada'],
                    ':sal'     => $b['hora_salida'],
                    ':id_inst' => $idInstructor
                ]);
                $idHorarioExistente = $stmtCheck->fetchColumn();

                if ($idHorarioExistente) {
                    // Actualizar ambiente, competencia y activar
                    $stmtUpdate = $pdo->prepare(
                        "UPDATE horarios 
                         SET ambiente = :amb, competencia = :comp, estado = 'Activo', duracion_horas = :dur, tipo_bloque = :tipo
                         WHERE id_horario = :id_h"
                    );
                    $stmtUpdate->execute([
                        ':amb'   => $b['ambiente'],
                        ':comp'  => $b['competencia'],
                        ':dur'   => $b['duracion_horas'],
                        ':tipo'  => $b['tipo_bloque'],
                        ':id_h'  => $idHorarioExistente
                    ]);
                    $actualizados++;
                } else {
                    // Insertar nuevo bloque
                    $stmtInsert = $pdo->prepare(
                        "INSERT INTO horarios (dias_semana, hora_entrada, hora_salida, duracion_horas, tipo_bloque, ambiente, competencia, estado, fichas_id_ficha, instructores_id_instructor)
                         VALUES (:dia, :ent, :sal, :dur, :tipo, :amb, :comp, 'Activo', :id_f, :id_inst)"
                    );
                    $stmtInsert->execute([
                        ':dia'     => $b['dia'],
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
                }

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
                'success'            => true,
                'numero_ficha'       => $numeroFicha,
                'programa'           => $programa,
                'area'               => $area,
                'total_bloques'      => count($bloquesExtraidos),
                'nuevos_insertados'  => $guardados,
                'actualizados'       => $actualizados,
                'total_instructores' => count($instructoresCache),
                'instructores'       => array_keys($instructoresCache),
                'bloques'            => $bloquesResumen
            ];

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("HorarioExcelService::procesarArchivoSena - Error en transacción: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recorre la cuadrícula del Excel y extrae los bloques únicos consolidados.
     */
    private function extraerBloquesMatriz($sheet): array
    {
        $maxRow = $sheet->getHighestRow();
        $bloquesMap = [];

        for ($r = 4; $r <= $maxRow; $r++) {
            $valD = strtoupper(trim((string)$sheet->getCell('D' . $r)->getFormattedValue()));

            // Se identifica una fila de inicio de semana
            if ($valD === 'LUNES') {
                $filaHeader = $r;
                $filaInicioHoras = $filaHeader + 2;

                foreach (self::DIAS_COLUMNAS as $col => $nomDia) {
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

                        // Si hay clase y no es festivo
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
                                // Extender el bloque continuo
                                $hFin = sprintf("%02d:00:00", $valC);
                            } else {
                                // Cambio de clase o docente en el mismo día
                                $this->agregarBloqueConsolidado($bloquesMap, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                                $currentInstructor = $inst;
                                $currentAmbiente   = $amb;
                                $currentComp       = $comp;
                                $hIni              = sprintf("%02d:00:00", $valB);
                                $hFin              = sprintf("%02d:00:00", $valC);
                            }
                        } else {
                            if ($currentInstructor !== null) {
                                $this->agregarBloqueConsolidado($bloquesMap, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                                $currentInstructor = null;
                                $currentAmbiente   = null;
                                $currentComp       = null;
                                $hIni              = null;
                                $hFin              = null;
                            }
                        }
                    }

                    if ($currentInstructor !== null) {
                        $this->agregarBloqueConsolidado($bloquesMap, $nomDia, $hIni, $hFin, $currentInstructor, $currentAmbiente, $currentComp);
                    }
                }
            }
        }

        return array_values($bloquesMap);
    }

    /**
     * Agrega un bloque al listado consolidado deduplicado.
     */
    private function agregarBloqueConsolidado(
        array &$bloquesMap,
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

        $tipoBloque = ($duracion >= 6) ? '6H_CONTINUO' : '3H_INDIVIDUAL';
        $key = "{$dia}|{$hIni}|{$hFin}|{$instructor}|{$ambiente}|{$competencia}";

        if (!isset($bloquesMap[$key])) {
            $bloquesMap[$key] = [
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
