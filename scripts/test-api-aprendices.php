<?php
/**
 * Script para probar la API de aprendices y diagnosticar el problema
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== TEST API APRENDICES ===\n\n";
    
    // 1. Verificar si hay aprendices en la base de datos
    echo "1. Verificando aprendices en la base de datos...\n";
    $sqlCount = "SELECT COUNT(*) as total FROM aprendices WHERE estado = 'LECTIVA'";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute();
    $total = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total aprendices LECTIVA: " . $total . "\n";
    
    if ($total == 0) {
        echo "   ❌ No hay aprendices en estado LECTIVA\n";
        echo "   🔍 Verificando otros estados...\n";
        
        $sqlEstados = "SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado";
        $stmtEstados = $conn->prepare($sqlEstados);
        $stmtEstados->execute();
        $estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($estados as $estado) {
            echo "   - Estado '" . $estado['estado'] . "': " . $estado['total'] . " aprendices\n";
        }
    }
    
    // 2. Verificar si hay fichas asignadas
    echo "\n2. Verificando fichas...\n";
    $sqlFichas = "SELECT DISTINCT numero_ficha FROM aprendices WHERE estado = 'LECTIVA' LIMIT 5";
    $stmtFichas = $conn->prepare($sqlFichas);
    $stmtFichas->execute();
    $fichas = $stmtFichas->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($fichas)) {
        echo "   ❌ No hay fichas con aprendices LECTIVA\n";
    } else {
        echo "   ✅ Fichas encontradas: " . implode(', ', $fichas) . "\n";
        
        // 3. Probar la API con una ficha real
        $fichaTest = $fichas[0];
        echo "\n3. Probando API con ficha " . $fichaTest . "...\n";
        
        // Simular la llamada a la API
        $_GET['ficha'] = $fichaTest;
        $_GET['limit'] = '-1';
        
        // Cargar el archivo de la API
        ob_start();
        include 'api/aprendices.php';
        $response = ob_get_clean();
        
        echo "   Respuesta de la API:\n";
        echo "   " . $response . "\n";
        
        // Intentar decodificar JSON
        $data = json_decode($response, true);
        if ($data) {
            echo "   ✅ JSON válido\n";
            echo "   Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (isset($data['data'])) {
                echo "   Cantidad de datos: " . count($data['data']) . "\n";
            }
            if (isset($data['message'])) {
                echo "   Mensaje: " . $data['message'] . "\n";
            }
        } else {
            echo "   ❌ JSON inválido o error en la API\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
