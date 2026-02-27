<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'getLideres';
$_GET['filtro'] = 'todos';

ob_start();
require_once __DIR__ . '/api/bienestar.php';
$output = ob_get_clean();

$data = json_decode($output, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERROR JSON: " . json_last_error_msg() . "\n";
    echo "SALIDA RAW:\n" . $output . "\n";
} else {
    echo "SUCCESS\n";
    echo "Conteo: " . count($data['data']) . "\n";
    if (count($data['data']) > 0) {
        echo "Muestra (primeros 3):\n";
        foreach (array_slice($data['data'], 0, 3) as $l) {
            echo "- {$l['nombre']} {$l['apellido']} ({$l['tipo']})\n";
        }
    } else {
        echo "La API devolvió 0 líderes.\n";
    }
}
