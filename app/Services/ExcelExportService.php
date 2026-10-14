<?php
/**
 * Servicio de Exportación Ejecutiva a Excel (ExcelExportService)
 * Sistema de Control de Asistencia y Permanencia SENA
 * 
 * Responsabilidad:
 * 1. Generar libros binarios nativos de Microsoft Excel (.xlsx) utilizando PhpOffice\PhpSpreadsheet.
 * 2. Aplicar estándares profesionales de diseño de información y manual de identidad visual SENA:
 *    - Tipografía sobria (Segoe UI / Calibri), jerarquía de títulos y márgenes equilibrados.
 *    - Paleta institucional: Verde SENA (#39A900), Pizarra/Bosque Corporativo (#0B3B24 y #00324D),
 *      superficies neutras (#F8FAFC) y estados sutiles en tonos pastel para evitar estéticas artificiales.
 *    - Bloque de membrete y matriz de control documental (Auditoría oficial).
 *    - Tarjetas superiores de KPIs con bordes finos y métricas destacadas.
 *    - Grilla de datos con paneles congelados (Freeze Panes), autofiltros y anchos calculados.
 *    - Fórmulas dinámicas de totales (=SUM) con formato contable de doble subrayado.
 *    - Configuración de página lista para impresión (Horizontal / Landscape, ajustar a 1 página de ancho).
 */

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Throwable;

class ExcelExportService
{
    // Constantes de color corporativo institucional
    private const COLOR_SENA_GREEN    = '39A900'; // Verde Institucional SENA
    private const COLOR_DARK_FOREST   = '0B3B24'; // Verde Bosque Ejecutivo
    private const COLOR_SLATE_HEADER  = '00324D'; // Azul Pizarra SENA
    private const COLOR_LIGHT_ZEBRA   = 'F8FAFC'; // Tintado tenue para filas alternas
    private const COLOR_BORDER_FINE   = 'D1D5DB'; // Borde sutil gris claro
    private const COLOR_TEXT_DARK     = '111827'; // Texto principal oscuro
    private const COLOR_TEXT_MUTED    = '4B5563'; // Texto secundario gris
    private const COLOR_DANGER_BG     = 'FEE2E2'; // Alerta / Riesgo fondo suave
    private const COLOR_DANGER_TXT    = '991B1B'; // Alerta / Riesgo texto oscuro
    private const COLOR_SUCCESS_BG    = 'DCFCE7'; // Normalidad fondo suave
    private const COLOR_SUCCESS_TXT   = '166534'; // Normalidad texto oscuro
    private const COLOR_WARN_BG       = 'FEF3C7'; // Advertencia / Retardo suave
    private const COLOR_WARN_TXT      = '92400E'; // Advertencia / Retardo texto

    /**
     * Exporta el informe consolidado general de asistencias (Módulo Administrador).
     *
     * @param array $reporte  Listado tabular de aprendices y métricas.
     * @param array $metricas Resumen global de KPIs.
     * @param array $filtros  Criterios aplicados (desde, hasta, ficha, área, etc.).
     */
    public function exportarConsolidadoGeneral(array $reporte, array $metricas, array $filtros): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Consolidado de Asistencia');

        // Configuración de visualización de cuadrícula y página
        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        // Metadatos del archivo
        $spreadsheet->getProperties()
            ->setCreator('Servicio Nacional de Aprendizaje - SENA')
            ->setLastModifiedBy('Sistema de Gestión de Asistencias')
            ->setTitle('Informe Consolidado Oficial de Asistencias')
            ->setSubject('Auditoría y Control de Permanencia')
            ->setDescription('Reporte oficial generado para auditoría de asistencia, inasistencias y retardos.')
            ->setCategory('Reporte Académico');

        // Tipografía global base
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        // 1. BARRA SUPERIOR DECORATIVA DE MARCA (Fila 1)
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1:K1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF' . self::COLOR_SENA_GREEN);
        $sheet->getRowDimension(1)->setRowHeight(6);

        // 2. ENCABEZADO INSTITUCIONAL (Filas 2 - 4)
        $sheet->setCellValue('A2', 'SERVICIO NACIONAL DE APRENDIZAJE - SENA');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setSize(14)->setBold(true)->getColor()->setARGB('FF' . self::COLOR_DARK_FOREST);
        $sheet->getRowDimension(2)->setRowHeight(24);

        $sheet->setCellValue('A3', 'CONSOLIDADO GENERAL DE ASISTENCIA Y AUDITORÍA DISCIPLINARIA');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getFont()->setSize(11)->setBold(true)->getColor()->setARGB('FF' . self::COLOR_SLATE_HEADER);
        $sheet->getRowDimension(3)->setRowHeight(18);

        $sheet->setCellValue('A4', 'Dirección de Formación Profesional • Sistema Integrado de Control de Asistencias');
        $sheet->mergeCells('A4:H4');
        $sheet->getStyle('A4')->getFont()->setSize(9)->setItalic(true)->getColor()->setARGB('FF' . self::COLOR_TEXT_MUTED);
        $sheet->getRowDimension(4)->setRowHeight(16);

        // CAJA DE CONTROL Y AUDITORÍA (Columnas I a K, Filas 2 - 4)
        $sheet->mergeCells('I2:K2');
        $sheet->mergeCells('I3:K3');
        $sheet->mergeCells('I4:K4');

        $sheet->setCellValue('I2', 'DOCUMENTO OFICIAL DE AUDITORÍA');
        $sheet->setCellValue('I3', 'Fecha de Emisión: ' . date('d/m/Y h:i A'));
        $sheet->setCellValue('I4', 'Formato: SENA-ASI-REG-04 • V3.2');

        $styleBoxMeta = [
            'font' => ['size' => 8.5, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_MUTED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('I2:K4')->applyFromArray($styleBoxMeta);
        $sheet->getStyle('I2')->getFont()->setBold(true)->getColor()->setARGB('FF' . self::COLOR_DARK_FOREST);

        // 3. BLOQUE DE PARÁMETROS / FILTROS (Filas 5 - 6)
        $desde = $filtros['desde'] ?? date('Y-m-01');
        $hasta = $filtros['hasta'] ?? date('Y-m-d');
        $area  = !empty($filtros['area']) && $filtros['area'] !== 'todas' ? ucfirst($filtros['area']) : 'Todas las áreas técnicas';
        $ficha = !empty($filtros['ficha']) ? $filtros['ficha'] : 'Todas las fichas activas';

        $sheet->setCellValue('A5', 'Rango Evaluado: ' . $desde . '  al  ' . $hasta);
        $sheet->setCellValue('E5', 'Área Técnica: ' . $area);
        $sheet->setCellValue('I5', 'Filtro Ficha: ' . $ficha);

        $sheet->mergeCells('A5:D5');
        $sheet->mergeCells('E5:H5');
        $sheet->mergeCells('I5:K5');

        $sheet->getStyle('A5:K5')->applyFromArray([
            'font' => ['size' => 9, 'bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_DARK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_LIGHT_ZEBRA]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // 4. TARJETAS DE RESUMEN EJECUTIVO / KPIS (Filas 7 - 8)
        $kpiData = [
            ['rango' => 'A7:B7', 'rangoVal' => 'A8:B8', 'label' => 'APRENDICES EVALUADOS', 'val' => (int)($metricas['totalAprendices'] ?? 0), 'color' => self::COLOR_SLATE_HEADER],
            ['rango' => 'C7:D7', 'rangoVal' => 'C8:D8', 'label' => 'TOTAL INASISTENCIAS', 'val' => (int)($metricas['totalInasistencias'] ?? 0), 'color' => self::COLOR_DANGER_TXT],
            ['rango' => 'E7:F7', 'rangoVal' => 'E8:F8', 'label' => 'RETARDOS REGISTRADOS', 'val' => (int)($metricas['totalRetardos'] ?? 0), 'color' => self::COLOR_WARN_TXT],
            ['rango' => 'G7:H7', 'rangoVal' => 'G8:H8', 'label' => 'MINUTOS DE RETARDO', 'val' => (int)($metricas['totalMinutosRetardo'] ?? 0), 'color' => self::COLOR_SLATE_HEADER, 'suffix' => ' min'],
            ['rango' => 'I7:K7', 'rangoVal' => 'I8:K8', 'label' => 'APRENDICES EN RIESGO (≥3 FALTAS)', 'val' => (int)($metricas['aprendicesEnRiesgo'] ?? 0), 'color' => self::COLOR_DANGER_TXT],
        ];

        foreach ($kpiData as $kpi) {
            $sheet->mergeCells($kpi['rango']);
            $sheet->mergeCells($kpi['rangoVal']);

            $primeraCeldaLabel = explode(':', $kpi['rango'])[0];
            $primeraCeldaVal   = explode(':', $kpi['rangoVal'])[0];

            $sheet->setCellValue($primeraCeldaLabel, $kpi['label']);
            $valFinal = $kpi['val'] . ($kpi['suffix'] ?? '');
            $sheet->setCellValue($primeraCeldaVal, $valFinal);

            // Estilos tarjeta KPI
            $sheet->getStyle($kpi['rango'])->applyFromArray([
                'font' => ['size' => 8, 'bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_MUTED]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                    'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                    'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                ]
            ]);

            $sheet->getStyle($kpi['rangoVal'])->applyFromArray([
                'font' => ['size' => 14, 'bold' => true, 'color' => ['argb' => 'FF' . $kpi['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFFFFF']],
                'borders' => [
                    'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF' . $kpi['color']]],
                    'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                    'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]],
                ]
            ]);
        }

        $sheet->getRowDimension(7)->setRowHeight(16);
        $sheet->getRowDimension(8)->setRowHeight(24);

        // Fila de amortiguación visual
        $sheet->getRowDimension(9)->setRowHeight(12);

        // 5. ENCABEZADOS DE LA TABLA (Fila 10)
        $headers = [
            'A' => '#',
            'B' => 'Aprendiz',
            'C' => 'Documento',
            'D' => 'Correo Institucional',
            'E' => 'Ficha y Programa Formativo',
            'F' => 'Área Técnica',
            'G' => 'Instructores Asignados',
            'H' => 'Días Inasistencia',
            'I' => 'Retardos',
            'J' => 'Minutos Retardo',
            'K' => 'Estado Disciplinario'
        ];

        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col . '10', $title);
        }

        $styleHeaders = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9.5],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF' . self::COLOR_SLATE_HEADER]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF2D3748']]
            ]
        ];
        $sheet->getStyle('A10:K10')->applyFromArray($styleHeaders);
        $sheet->getRowDimension(10)->setRowHeight(30);

        // 6. VOLCADO DE DATOS (Fila 11 en adelante)
        $fila = 11;
        $itemIndex = 1;

        if (empty($reporte)) {
            $sheet->setCellValue('A11', 'No se encontraron registros de asistencias con los filtros seleccionados.');
            $sheet->mergeCells('A11:K11');
            $sheet->getStyle('A11:K11')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_MUTED]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(11)->setRowHeight(35);
            $fila = 12;
        } else {
            foreach ($reporte as $item) {
                $inasistencias = (int)($item['diasInasistencia'] ?? 0);
                $retardos      = (int)($item['totalRetardos'] ?? 0);
                $minutos       = (int)($item['minutosRetardo'] ?? 0);
                $esRiesgo      = ($inasistencias >= 3);

                $sheet->setCellValue('A' . $fila, $itemIndex++);
                $sheet->setCellValue('B' . $fila, $item['aprendiz'] ?? 'Desconocido');
                $sheet->setCellValueExplicit('C' . $fila, (string)($item['documento'] ?? 'N/A'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('D' . $fila, $item['correo'] ?? 'N/A');
                $sheet->setCellValue('E' . $fila, $item['ficha'] ?? 'Sin Ficha');
                $sheet->setCellValue('F' . $fila, !empty($item['area']) ? ucfirst($item['area']) : 'General');
                $sheet->setCellValue('G' . $fila, $item['instructor'] ?? 'Sin instructor asignado');
                $sheet->setCellValue('H' . $fila, $inasistencias);
                $sheet->setCellValue('I' . $fila, $retardos);
                $sheet->setCellValue('J' . $fila, $minutos);
                $sheet->setCellValue('K' . $fila, $esRiesgo ? 'EN RIESGO (≥3 Faltas)' : 'Normal');

                // Alineaciones específicas por columna
                $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('E' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('F' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('H' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('K' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Formatos numéricos
                $sheet->getStyle('H' . $fila . ':I' . $fila)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('J' . $fila)->getNumberFormat()->setFormatCode('#,##0 "min"');

                // Zebra striping tenue
                if ($fila % 2 === 0) {
                    $sheet->getStyle('A' . $fila . ':K' . $fila)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF' . self::COLOR_LIGHT_ZEBRA);
                }

                // Destacado condicional sobrio del Estado
                if ($esRiesgo) {
                    $sheet->getStyle('K' . $fila)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_DANGER_TXT], 'size' => 8.5],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_DANGER_BG]],
                    ]);
                    $sheet->getStyle('H' . $fila)->getFont()->setBold(true)->getColor()->setARGB('FF' . self::COLOR_DANGER_TXT);
                } else {
                    $sheet->getStyle('K' . $fila)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_SUCCESS_TXT], 'size' => 8.5],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_SUCCESS_BG]],
                    ]);
                }

                // Bordes de celda sutiles
                $sheet->getStyle('A' . $fila . ':K' . $fila)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FF' . self::COLOR_BORDER_FINE);

                $sheet->getRowDimension($fila)->setRowHeight(21);
                $fila++;
            }
        }

        // 7. FILA DE TOTALES / RESUMEN CON CONTABILIDAD (Fórmulas nativas)
        if (!empty($reporte)) {
            $filaFinDatos = $fila - 1;
            $sheet->setCellValue('A' . $fila, 'TOTALES CONSOLIDADOS');
            $sheet->mergeCells('A' . $fila . ':G' . $fila);

            $sheet->setCellValue('H' . $fila, "=SUM(H11:H{$filaFinDatos})");
            $sheet->setCellValue('I' . $fila, "=SUM(I11:I{$filaFinDatos})");
            $sheet->setCellValue('J' . $fila, "=SUM(J11:J{$filaFinDatos})");
            $sheet->setCellValue('K' . $fila, "Riesgo: " . (int)($metricas['aprendicesEnRiesgo'] ?? 0));

            $styleTotales = [
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF' . self::COLOR_DARK_FOREST]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE5E7EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF1F2937']],
                    'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['argb' => 'FF1F2937']],
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD1D5DB']],
                ]
            ];
            $sheet->getStyle('A' . $fila . ':K' . $fila)->applyFromArray($styleTotales);

            $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('H' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle('H' . $fila . ':I' . $fila)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $fila)->getNumberFormat()->setFormatCode('#,##0 "min"');

            $sheet->getRowDimension($fila)->setRowHeight(25);
            $fila++;
        }

        // 8. NOTA AL PIE Y SUSTENTO LEGAL (Filas posteriores)
        $fila++;
        $sheet->setCellValue('A' . $fila, 'Nota de Confidencialidad y Validez Normativa:');
        $sheet->mergeCells('A' . $fila . ':K' . $fila);
        $sheet->getStyle('A' . $fila)->getFont()->setBold(true)->setSize(8.5)->getColor()->setARGB('FF' . self::COLOR_TEXT_MUTED);

        $fila++;
        $sheet->setCellValue('A' . $fila, 'Este consolidado es emitido por el Sistema Integrado de Control de Asistencias SENA con carácter oficial para seguimiento pedagógico y disciplinario. Toda acumulación de 3 o más inasistencias injustificadas activa los protocolos de deserción y comité de evaluación conforme al Reglamento del Aprendiz SENA (Acuerdo 007 de 2012).');
        $sheet->mergeCells('A' . $fila . ':K' . $fila);
        $sheet->getStyle('A' . $fila)->applyFromArray([
            'font' => ['italic' => true, 'size' => 8, 'color' => ['argb' => 'FF6B7280']],
            'alignment' => ['wrapText' => true]
        ]);
        $sheet->getRowDimension($fila)->setRowHeight(30);

        // 9. AJUSTE DE ANCHOS DE COLUMNA Y CONGELAMIENTO
        // Congelar paneles para mantener visible la cabecera y KPI al desplazarse verticalmente
        $sheet->freezePane('A11');

        // Habilitar autofiltro en la fila de encabezados
        if (!empty($reporte)) {
            $sheet->setAutoFilter('A10:K' . ($fila - 4));
        }

        // Anchos proporcionales equilibrados
        $columnWidths = [
            'A' => 6,   // #
            'B' => 28,  // Aprendiz
            'C' => 15,  // Documento
            'D' => 28,  // Correo
            'E' => 32,  // Ficha y Programa
            'F' => 16,  // Área
            'G' => 30,  // Instructores
            'H' => 15,  // Días Inasistencia
            'I' => 13,  // Retardos
            'J' => 16,  // Minutos Retardo
            'K' => 22   // Estado
        ];

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // 10. DESCARGA DIRECTA POR STREAMING
        $nombreArchivo = 'Consolidado_Asistencias_SENA_' . $desde . '_al_' . $hasta . '.xlsx';
        $this->descargarSpreadsheet($spreadsheet, $nombreArchivo);
    }

    /**
     * Exporta la planilla diaria oficial de toma de asistencia (Módulo Instructor).
     *
     * @param array  $ingresos   Listado de marcaciones registradas para el día y ficha.
     * @param array  $fichaInfo  Datos de la ficha (programa, código, jornada).
     * @param string $fecha      Fecha de la planilla (Y-m-d).
     * @param string $instructor Nombre del instructor titular.
     */
    public function exportarIngresosDia(array $ingresos, array $fichaInfo, string $fecha, string $instructor): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Planilla de Asistencias');

        $sheet->setShowGridLines(true);
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setFitToWidth(1);

        $spreadsheet->getProperties()
            ->setCreator('SENA - Control de Asistencias')
            ->setTitle('Planilla Diaria de Formación')
            ->setSubject('Registro de Asistencia');

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Segoe UI')->setSize(10);

        // Barra superior decorativa
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF' . self::COLOR_SENA_GREEN);
        $sheet->getRowDimension(1)->setRowHeight(5);

        // Membrete
        $sheet->setCellValue('A2', 'SERVICIO NACIONAL DE APRENDIZAJE - SENA');
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setSize(13)->setBold(true)->getColor()->setARGB('FF' . self::COLOR_DARK_FOREST);

        $sheet->setCellValue('A3', 'PLANILLA OFICIAL DIARIA DE CONTROL DE ASISTENCIA');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getFont()->setSize(10.5)->setBold(true)->getColor()->setARGB('FF' . self::COLOR_SLATE_HEADER);

        $sheet->setCellValue('F2', 'FECHA: ' . date('d/m/Y', strtotime($fecha)));
        $sheet->mergeCells('F2:G2');
        $sheet->setCellValue('F3', 'EMISIÓN: ' . date('h:i A'));
        $sheet->mergeCells('F3:G3');
        $sheet->getStyle('F2:G3')->applyFromArray([
            'font' => ['size' => 9, 'bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_MUTED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);

        // Información de la sesión formativa
        $sheet->setCellValue('A5', 'Ficha: ' . ($fichaInfo['numero_ficha'] ?? 'Todas las Fichas'));
        $sheet->setCellValue('C5', 'Programa: ' . ($fichaInfo['programa'] ?? 'Formación Titulada'));
        $sheet->setCellValue('F5', 'Instructor: ' . $instructor);

        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('C5:E5');
        $sheet->mergeCells('F5:G5');

        $sheet->getStyle('A5:G5')->applyFromArray([
            'font' => ['size' => 9, 'bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_DARK]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_LIGHT_ZEBRA]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF' . self::COLOR_BORDER_FINE]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
        ]);
        $sheet->getRowDimension(5)->setRowHeight(22);

        // Encabezados
        $headers = [
            'A' => '#',
            'B' => 'Documento / RFID',
            'C' => 'Nombre del Aprendiz',
            'D' => 'Ficha Formativa',
            'E' => 'Hora Entrada',
            'F' => 'Hora Salida',
            'G' => 'Estado'
        ];
        foreach ($headers as $col => $title) {
            $sheet->setCellValue($col . '7', $title);
        }
        $sheet->getStyle('A7:G7')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9.5],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_SLATE_HEADER]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF2D3748']]]
        ]);
        $sheet->getRowDimension(7)->setRowHeight(26);

        // Filas de datos
        $fila = 8;
        $idx = 1;
        if (empty($ingresos)) {
            $sheet->setCellValue('A8', 'No se registran marcaciones para la fecha y ficha seleccionada.');
            $sheet->mergeCells('A8:G8');
            $sheet->getStyle('A8:G8')->applyFromArray([
                'font' => ['italic' => true, 'color' => ['argb' => 'FF' . self::COLOR_TEXT_MUTED]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ]);
            $sheet->getRowDimension(8)->setRowHeight(30);
            $fila = 9;
        } else {
            foreach ($ingresos as $ing) {
                $estado = strtolower($ing['estado'] ?? 'a_tiempo');
                $estadoTexto = match($estado) {
                    'retardo' => 'Retardo',
                    'inasistencia' => 'Inasistencia',
                    default => 'A Tiempo'
                };

                $sheet->setCellValue('A' . $fila, $idx++);
                $sheet->setCellValueExplicit('B' . $fila, (string)($ing['codigo_rfid'] ?? ($ing['documento'] ?? 'N/A')), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue('C' . $fila, $ing['aprendiz'] ?? 'Desconocido');
                $sheet->setCellValue('D' . $fila, $ing['ficha'] ?? 'N/A');
                $sheet->setCellValue('E' . $fila, !empty($ing['hora_entrada']) ? date('h:i A', strtotime($ing['hora_entrada'])) : '--:--');
                $sheet->setCellValue('F' . $fila, !empty($ing['hora_salida']) ? date('h:i A', strtotime($ing['hora_salida'])) : '--:--');
                $sheet->setCellValue('G' . $fila, $estadoTexto);

                $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($fila % 2 === 0) {
                    $sheet->getStyle('A' . $fila . ':G' . $fila)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF' . self::COLOR_LIGHT_ZEBRA);
                }

                if ($estado === 'retardo') {
                    $sheet->getStyle('G' . $fila)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_WARN_TXT], 'size' => 8.5],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_WARN_BG]],
                    ]);
                } elseif ($estado === 'inasistencia') {
                    $sheet->getStyle('G' . $fila)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_DANGER_TXT], 'size' => 8.5],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_DANGER_BG]],
                    ]);
                } else {
                    $sheet->getStyle('G' . $fila)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FF' . self::COLOR_SUCCESS_TXT], 'size' => 8.5],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF' . self::COLOR_SUCCESS_BG]],
                    ]);
                }

                $sheet->getStyle('A' . $fila . ':G' . $fila)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('FF' . self::COLOR_BORDER_FINE);

                $sheet->getRowDimension($fila)->setRowHeight(20);
                $fila++;
            }
        }

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(18);

        $sheet->freezePane('A8');

        $nombreArchivo = 'Planilla_Asistencias_SENA_' . $fecha . '.xlsx';
        $this->descargarSpreadsheet($spreadsheet, $nombreArchivo);
    }

    /**
     * Transmite el archivo de hoja de cálculo al navegador forzando su descarga.
     */
    private function descargarSpreadsheet(Spreadsheet $spreadsheet, string $nombreArchivo): void
    {
        // Limpiar buffers previos de salida para evitar corrupción del archivo binario
        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($nombreArchivo) . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
