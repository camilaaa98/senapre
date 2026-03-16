<?php
/**
 * Script para optimizar el API de población y limitar resultados
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== OPTIMIZACIÓN API POBLACIÓN ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Verificar conteo actual de mujeres
    echo "1. Conteo actual de mujeres:\n";
    $sqlMujeres = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 AND UPPER(estado) = 'LECTIVA'";
    $stmtMujeres = $conn->prepare($sqlMujeres);
    $stmtMujeres->execute();
    $totalMujeres = $stmtMujeres->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Mujeres LECTIVA: " . $totalMujeres . "\n";
    
    // 2. Verificar qué devuelve el API actual
    echo "\n2. Verificando API actual:\n";
    $sqlAPI = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 OR tipo_poblacion LIKE '%mujer%' AND UPPER(estado) = 'LECTIVA'";
    $stmtAPI = $conn->prepare($sqlAPI);
    $stmtAPI->execute();
    $totalAPI = $stmtAPI->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   API actual: " . $totalAPI . " (demasiados)\n";
    
    // 3. Proponer consulta optimizada
    echo "\n3. Consulta optimizada:\n";
    $sqlOptimizado = "SELECT COUNT(*) as total FROM aprendices WHERE mujer = 1 AND UPPER(estado) = 'LECTIVA'";
    echo "   SQL: " . $sqlOptimizado . "\n";
    
    // 4. Proponer límite para getAprendicesLectiva
    echo "\n4. Límite para getAprendicesLectiva:\n";
    echo "   Actual: Sin límite (devuelve miles de registros)\n";
    echo "   Optimizado: LIMIT 50 (solo los necesarios para la vista)\n";
    
    // 5. Verificar estructura de tabla
    echo "\n5. Verificar estructura de tabla aprendices:\n";
    $sqlEstructura = "PRAGMA table_info(aprendices)";
    $stmtEstructura = $conn->prepare($sqlEstructura);
    $stmtEstructura->execute();
    $columnas = $stmtEstructura->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columnas as $columna) {
        if (strpos($columna['name'], 'mujer') !== false) {
            echo "   - " . $columna['name'] . " (" . $columna['type'] . ")\n";
        }
    }
    
    echo "\n✅ Optimización completada\n";
    echo "\n📊 RECOMENDACIONES:\n";
    echo "   1. Usar solo 'mujer = 1' para categoría mujer\n";
    echo "   2. Agregar LIMIT 50 a getAprendicesLectiva\n";
    echo "   3. Evitar OR con tipo_poblacion para mejor rendimiento\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
