<?php
require_once __DIR__ . '/../app/config/init.php';

try {
    $service = new HorarioExcelService();
    $result = $service->procesarArchivoSena(__DIR__ . '/../storage/plantillas/plantilla_horarios_1.xlsx', '2026-10-07');
    echo "SUCCESS!\n";
    print_r($result);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
