<?php
/**
 * Script para verificar aprendices LECTIVA en PostgreSQL (Supabase)
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== VERIFICACIÓN POSTGRESQL (SUPABASE) ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Verificar aprendices LECTIVA en PostgreSQL
    echo "1. Aprendices en estado LECTIVA (PostgreSQL):\n";
    $sqlLectiva = "SELECT COUNT(*) as total FROM aprendices WHERE estado = 'LECTIVA'";
    $stmtLectiva = $conn->prepare($sqlLectiva);
    $stmtLectiva->execute();
    $totalLectiva = $stmtLectiva->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total LECTIVA: " . $totalLectiva . "\n";
    
    // 2. Verificar aprendices EN FORMACION
    echo "\n2. Aprendices en estado EN FORMACION (PostgreSQL):\n";
    $sqlFormacion = "SELECT COUNT(*) as total FROM aprendices WHERE estado = 'EN FORMACION'";
    $stmtFormacion = $conn->prepare($sqlFormacion);
    $stmtFormacion->execute();
    $totalFormacion = $stmtFormacion->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total EN FORMACION: " . $totalFormacion . "\n";
    
    // 3. Mostrar ejemplos si hay aprendices LECTIVA
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
        
        // 4. Probar la API con PostgreSQL
        echo "\n4. Probando API con PostgreSQL...\n";
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
                if (isset($data['message'])) {
                    echo "   Mensaje: " . $data['message'] . "\n";
                }
            } else {
                echo "   ❌ Error en respuesta API\n";
            }
        }
    }
    
    // 5. Verificar todos los estados en PostgreSQL
    echo "\n5. Todos los estados en PostgreSQL:\n";
    $sqlEstados = "SELECT estado, COUNT(*) as total FROM aprendices GROUP BY estado ORDER BY total DESC";
    $stmtEstados = $conn->prepare($sqlEstados);
    $stmtEstados->execute();
    $estados = $stmtEstados->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($estados as $estado) {
        echo "   - '" . $estado['estado'] . "': " . $estado['total'] . " aprendices\n";
    }
    
    // 6. Verificar estructura de tabla
    echo "\n6. Estructura de tabla aprendices (PostgreSQL):\n";
    $sqlEstructura = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'aprendices' ORDER BY ordinal_position";
    $stmtEstructura = $conn->prepare($sqlEstructura);
    $stmtEstructura->execute();
    $columnas = $stmtEstructura->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columnas as $columna) {
        echo "   - " . $columna['column_name'] . " (" . $columna['data_type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
