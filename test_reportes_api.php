<?php
require_once __DIR__ . '/api/config/Database.php';

function testReportesAPI() {
    echo "--- Probando API de Reportes ---\n";
    $_GET['limit'] = -1;
    
    ob_start();
    include __DIR__ . '/api/reportes.php';
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    
    if ($result['success']) {
        echo "[OK] API de reportes ejecutada exitosamente.\n";
        echo "Resumen:\n";
        print_r($result['data']['resumen']);
    } else {
        echo "[ERROR] Fallo en la API de reportes: " . ($result['message'] ?? 'Error desconocido') . "\n";
    }
}

testReportesAPI();
