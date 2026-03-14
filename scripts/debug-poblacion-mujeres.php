<?php
/**
 * Script para depurar discrepancia en conteo de mujeres
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== DEPURACIÓN POBLACIÓN MUJERES ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Verificar qué devuelve el API actual
    echo "1. Respuesta del API actual:\n";
    $sqlAPI = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 OR tipo_poblacion LIKE '%mujer%'";
    $stmtAPI = $conn->prepare($sqlAPI);
    $stmtAPI->execute();
    $resultadoAPI = $stmtAPI->fetch(PDO::FETCH_ASSOC);
    echo "   API (mujer=1 OR tipo_poblacion LIKE '%mujer%'): " . $resultadoAPI['total'] . "\n";
    
    // 2. Verificar solo por género
    echo "\n2. Conteo solo por género (mujer = 1):\n";
    $sqlGenero = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 AND estado = 'LECTIVA'";
    $stmtGenero = $conn->prepare($sqlGenero);
    $stmtGenero->execute();
    $resultadoGenero = $stmtGenero->fetch(PDO::FETCH_ASSOC);
    echo "   Género mujer = 1: " . $resultadoGenero['total'] . "\n";
    
    // 3. Verificar solo por tipo_poblacion
    echo "\n3. Conteo solo por tipo_poblacion:\n";
    $sqlTipo = "SELECT COUNT(*) as total FROM aprendices WHERE tipo_poblacion LIKE '%mujer%' AND estado = 'LECTIVA'";
    $stmtTipo = $conn->prepare($sqlTipo);
    $stmtTipo->execute();
    $resultadoTipo = $stmtTipo->fetch(PDO::FETCH_ASSOC);
    echo "   tipo_poblacion LIKE '%mujer%': " . $resultadoTipo['total'] . "\n";
    
    // 4. Verificar combinación OR exacta
    echo "\n4. Conteo con OR exacto:\n";
    $sqlOR = "SELECT COUNT(*) as total FROM aprendices WHERE (mujer = 1 OR tipo_poblacion LIKE '%mujer%') AND estado = 'LECTIVA'";
    $stmtOR = $conn->prepare($sqlOR);
    $stmtOR->execute();
    $resultadoOR = $stmtOR->fetch(PDO::FETCH_ASSOC);
    echo "   (mujer = 1 OR tipo_poblacion LIKE '%mujer%') AND estado = 'LECTIVA': " . $resultadoOR['total'] . "\n";
    
    // 5. Mostrar ejemplos de mujeres
    echo "\n5. Ejemplos de aprendices mujeres:\n";
    $sqlEjemplos = "SELECT documento, nombre, apellido, mujer, tipo_poblacion, estado 
                        FROM aprendices 
                        WHERE mujer = 1 AND estado = 'LECTIVA' 
                        LIMIT 10";
    $stmtEjemplos = $conn->prepare($sqlEjemplos);
    $stmtEjemplos->execute();
    $ejemplos = $stmtEjemplos->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($ejemplos as $ejemplo) {
        echo "   - " . $ejemplo['nombre'] . " " . $ejemplo['apellido'] . 
             " | CC: " . $ejemplo['documento'] . 
             " | mujer: " . $ejemplo['mujer'] . 
             " | tipo_poblacion: " . ($ejemplo['tipo_poblacion'] ?: 'NULL') . 
             " | estado: " . $ejemplo['estado'] . "\n";
    }
    
    // 6. Verificar todas las mujeres (incluyendo otros estados)
    echo "\n6. Todas las aprendices mujeres (todos los estados):\n";
    $sqlTodas = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1";
    $stmtTodas = $conn->prepare($sqlTodas);
    $stmtTodas->execute();
    $resultadoTodas = $stmtTodas->fetch(PDO::FETCH_ASSOC);
    echo "   Total mujeres (todos los estados): " . $resultadoTodas['total'] . "\n";
    
    // 7. Verificar mujeres LECTIVA exacto
    echo "\n7. Verificación final - Mujeres LECTIVA:\n";
    $sqlFinal = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 AND UPPER(estado) = 'LECTIVA'";
    $stmtFinal = $conn->prepare($sqlFinal);
    $stmtFinal->execute();
    $resultadoFinal = $stmtFinal->fetch(PDO::FETCH_ASSOC);
    echo "   Mujeres LECTIVA: " . $resultadoFinal['total'] . "\n";
    
    echo "\n✅ Depuración completada\n";
    echo "\n📊 RESUMEN:\n";
    echo "   API actual: " . $resultadoAPI['total'] . "\n";
    echo "   Género mujer=1: " . $resultadoGenero['total'] . "\n";
    echo "   Tipo población LIKE '%mujer%': " . $resultadoTipo['total'] . "\n";
    echo "   OR exacto: " . $resultadoOR['total'] . "\n";
    echo "   Mujeres LECTIVA: " . $resultadoFinal['total'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
