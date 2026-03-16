<?php
/**
 * Script para depurar exactamente qué aprendices LECTIVA existen
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== DEPURACIÓN APRENDICES LECTIVA ===\n\n";
    
    // 1. Verificar aprendices LECTIVA exactamente
    echo "1. Aprendices en estado LECTIVA:\n";
    $sqlLectiva = "SELECT COUNT(*) as total FROM aprendices WHERE estado = 'LECTIVA'";
    $stmtLectiva = $conn->prepare($sqlLectiva);
    $stmtLectiva->execute();
    $totalLectiva = $stmtLectiva->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total LECTIVA: " . $totalLectiva . "\n";
    
    // 2. Verificar aprendices EN FORMACION
    echo "\n2. Aprendices en estado EN FORMACION:\n";
    $sqlFormacion = "SELECT COUNT(*) as total FROM aprendices WHERE estado = 'EN FORMACION'";
    $stmtFormacion = $conn->prepare($sqlFormacion);
    $stmtFormacion->execute();
    $totalFormacion = $stmtFormacion->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total EN FORMACION: " . $totalFormacion . "\n";
    
    // 3. Mostrar algunos ejemplos de LECTIVA si existen
    if ($totalLectiva > 0) {
        echo "\n3. Ejemplos de aprendices LECTIVA:\n";
        $sqlEjemplos = "SELECT numero_ficha, nombre, apellido, documento, estado 
                        FROM aprendices 
                        WHERE estado = 'LECTIVA' 
                        LIMIT 5";
        $stmtEjemplos = $conn->prepare($sqlEjemplos);
        $stmtEjemplos->execute();
        $ejemplos = $stmtEjemplos->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($ejemplos as $ejemplo) {
            echo "   - Ficha: " . $ejemplo['numero_ficha'] . 
                 " | " . $ejemplo['nombre'] . " " . $ejemplo['apellido'] . 
                 " | CC: " . $ejemplo['documento'] . 
                 " | Estado: " . $ejemplo['estado'] . "\n";
        }
        
        // 4. Probar la API con una ficha real LECTIVA
        echo "\n4. Probando API con ficha LECTIVA...\n";
        if (!empty($ejemplos)) {
            $fichaTest = $ejemplos[0]['numero_ficha'];
            
            // Simular llamada a la API
            $_GET['ficha'] = $fichaTest;
            $_GET['limit'] = '5';
            
            ob_start();
            include 'api/aprendices.php';
            $response = ob_get_clean();
            
            echo "   Ficha de prueba: " . $fichaTest . "\n";
            echo "   Respuesta API: " . $response . "\n";
            
            $data = json_decode($response, true);
            if ($data && isset($data['success'])) {
                echo "   ✅ API Success: " . ($data['success'] ? 'true' : 'false') . "\n";
                if (isset($data['data'])) {
                    echo "   📊 Cantidad devuelta: " . count($data['data']) . "\n";
                }
            } else {
                echo "   ❌ Error en respuesta API\n";
            }
        }
    }
    
    // 5. Verificar todos los estados posibles
    echo "\n5. Todos los estados en la base de datos:\n";
    $sqlEstados = "SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC";
    $stmtEstados = $conn->prepare($sqlEstados);
    $stmtEstados->execute();
    $estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estados as $estado) {
        echo "   - '" . $estado['estado'] . "': " . $estado['total'] . " aprendices\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
