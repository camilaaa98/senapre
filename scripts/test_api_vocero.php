<?php
// Diagnóstico con errores completos
ini_set('display_errors', 1);
error_reporting(E_ALL);

$_GET['ficha'] = '2995479';
$_GET['limit'] = '10';
$_GET['page']  = '1';

// Capturar salida y errores
ob_start();
include __DIR__ . '/api/aprendices.php';
$output = ob_get_clean();

echo "=== RESPUESTA API ===\n";
$data = json_decode($output, true);
if ($data === null) {
    echo "JSON INVÁLIDO. Output bruto:\n";
    echo substr($output, 0, 2000) . "\n";
} else {
    echo "success: " . ($data['success'] ? 'SI' : 'NO') . "\n";
    echo "total registros: " . count($data['data'] ?? []) . "\n";
    echo "paginacion total: " . ($data['pagination']['total'] ?? '?') . "\n";
    if (!empty($data['message'])) echo "message: {$data['message']}\n";
    foreach (array_slice($data['data'] ?? [], 0, 5) as $a) {
        echo "  {$a['documento']} | {$a['nombre']} {$a['apellido']} | {$a['estado']}\n";
    }
}
