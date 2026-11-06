<?php
require_once __DIR__ . '/../app/config/init.php';

$pdo = Conexion::obtener();

echo "=== TABLAS EN BASE DE DATOS ===\n";
$tables = [
    'roles', 'usuarios', 'fichas', 'horarios', 'instructores', 'aprendices', 
    'instructores_has_fichas', 'asistencia', 'excusas_medicas', 'detalle_excusas',
    'llamados_atencion', 'suplencias_horarios'
];
foreach ($tables as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo sprintf("%-25s: %d filas\n", $t, $count);
    } catch (Exception $e) {
        echo sprintf("%-25s: ERROR (%s)\n", $t, $e->getMessage());
    }
}
