<?php
require_once __DIR__ . '/../app/config/init.php';

$pdo = Conexion::obtener();

echo "=== TABLE ROW COUNTS ===\n";
foreach (['roles', 'usuarios', 'fichas', 'horarios', 'instructores', 'aprendices', 'instructores_has_fichas'] as $t) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo sprintf("%-25s: %d filas\n", $t, $count);
    } catch (Exception $e) {
        echo sprintf("%-25s: ERROR (%s)\n", $t, $e->getMessage());
    }
}
