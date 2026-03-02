<?php
require_once __DIR__ . '/api/config/Database.php';
// Simular llamada al endpoint
$_GET['id_usuario'] = '1004417452';
ob_start();
include __DIR__ . '/api/vocero/dashboard.php';
$out = ob_get_clean();
$data = json_decode($out, true);

if (!$data) { echo "ERROR:\n$out\n"; exit; }
echo "success: " . ($data['success'] ? 'SI' : 'NO') . "\n";
echo "message: " . $data['message'] . "\n";
if ($data['success']) {
    echo "Ficha: " . $data['data']['ficha']['numero_ficha'] . "\n";
    echo "Tipo Vocero: " . $data['data']['tipo_vocero'] . "\n";
    echo "Total aprendices: " . $data['data']['total'] . "\n";
    echo "Resumen:\n";
    foreach ($data['data']['resumen'] as $est => $cnt) echo "  $est: $cnt\n";
}
