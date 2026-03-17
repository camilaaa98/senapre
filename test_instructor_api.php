<?php
require_once __DIR__ . '/api/config/Database.php';

function testInstructorAPI() {
    echo "--- Probando API del Instructor ---\n";
    
    // Simular un ID de instructor
    $idInstructor = '12345678'; // Reemplazar con uno real si se conoce
    
    // 1. Dashboard
    $_GET['id_usuario'] = $idInstructor;
    ob_start();
    include __DIR__ . '/api/instructor-dashboard.php';
    $out1 = ob_get_clean();
    $res1 = json_decode($out1, true);
    echo "1. Dashboard: " . ($res1['success'] ? "[OK]" : "[ERROR] " . $res1['message']) . "\n";

    // 2. Fichas
    ob_start();
    include __DIR__ . '/api/instructor-fichas.php';
    $out2 = ob_get_clean();
    $res2 = json_decode($out2, true);
    echo "2. Fichas: " . ($res2['success'] ? "[OK]" : "[ERROR] " . $res2['message']) . "\n";
    if ($res2['success'] && !empty($res2['data'])) {
        $ficha = $res2['data'][0]['numero_ficha'];
        echo "   Usando ficha: $ficha\n";
        
        // 3. Aprendices
        $_GET['ficha'] = $ficha;
        $_GET['limit'] = -1;
        ob_start();
        include __DIR__ . '/api/aprendices.php';
        $out3 = ob_get_clean();
        $res3 = json_decode($out3, true);
        echo "3. Aprendices: " . ($res3['success'] ? "[OK]" : "[ERROR] " . $res3['message']) . "\n";
    }
}

testInstructorAPI();
