<?php
/**
 * Script para analizar el conteo real de población en la base de datos
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== ANÁLISIS DE POBLACIÓN REAL ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Verificar estructura de tabla aprendices
    echo "1. ESTRUCTURA DE TABLA APRENDICES:\n";
    $sqlEstructura = "PRAGMA table_info(aprendices)";
    $stmtEstructura = $conn->prepare($sqlEstructura);
    $stmtEstructura->execute();
    $columnas = $stmtEstructura->fetchAll(PDO::FETCH_ASSOC);
    
    $tieneTipoPoblacion = false;
    foreach ($columnas as $columna) {
        if ($columna['name'] === 'tipo_poblacion') {
            $tieneTipoPoblacion = true;
            echo "   ✅ tipo_poblacion: " . $columna['type'] . "\n";
        }
    }
    
    if (!$tieneTipoPoblacion) {
        echo "   ❌ No existe columna tipo_poblacion\n";
    }
    
    // 2. Analizar valores de tipo_poblacion
    echo "\n2. ANÁLISIS DE TIPO_POBLACIÓN:\n";
    if ($tieneTipoPoblacion) {
        $sqlTipos = "SELECT DISTINCT tipo_poblacion FROM aprendices WHERE tipo_poblacion IS NOT NULL AND tipo_poblacion != '' LIMIT 20";
        $stmtTipos = $conn->prepare($sqlTipos);
        $stmtTipos->execute();
        $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Valores encontrados:\n";
        foreach ($tipos as $tipo) {
            echo "   - '" . $tipo['tipo_poblacion'] . "'\n";
        }
    }
    
    // 3. Conteo por categorías usando LIKE
    echo "\n3. CONTEO REAL POR CATEGORÍAS (LECTIVA):\n";
    $categorias = [
        'mujer' => ['%mujer%', '%mujeres%'],
        'indigena' => ['%indigena%', '%indígena%'],
        'narp' => ['%narp%'],
        'campesino' => ['%campesino%', '%campesina%'],
        'lgbtiq' => ['%lgbtiq%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%'],
        'discapacidad' => ['%discapacidad%', '%discapacitad%', '%discapacitado%']
    ];
    
    foreach ($categorias as $categoria => $patterns) {
        $sql = "SELECT COUNT(*) as total FROM aprendices WHERE UPPER(estado) = 'LECTIVA' AND (";
        $likeConditions = [];
        foreach ($patterns as $pattern) {
            $likeConditions[] = "UPPER(tipo_poblacion) LIKE UPPER('" . $pattern . "')";
        }
        $sql .= implode(" OR ", $likeConditions) . ")";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo "   " . ucfirst($categoria) . ": " . $total . "\n";
    }
    
    // 4. Conteo total de aprendices LECTIVA
    echo "\n4. TOTAL APRENDICES LECTIVA:\n";
    $sqlTotal = "SELECT COUNT(*) as total FROM aprendices WHERE UPPER(estado) = 'LECTIVA'";
    $stmtTotal = $conn->prepare($sqlTotal);
    $stmtTotal->execute();
    $totalLectiva = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total: " . $totalLectiva . "\n";
    
    // 5. Análisis de género (si existe columna genero)
    echo "\n5. ANÁLISIS POR GÉNERO:\n";
    $sqlGenero = "SELECT COUNT(*) as total FROM aprendices WHERE UPPER(estado) = 'LECTIVA' AND UPPER(genero) = 'F'";
    try {
        $stmtGenero = $conn->prepare($sqlGenero);
        $stmtGenero->execute();
        $totalMujeresGenero = $stmtGenero->fetch(PDO::FETCH_ASSOC)['total'];
        echo "   Mujeres por género (F): " . $totalMujeresGenero . "\n";
    } catch (Exception $e) {
        echo "   ❌ No existe columna genero o error: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ ANÁLISIS COMPLETADO\n";
    echo "\n📊 RECOMENDACIONES:\n";
    echo "   1. Los conteos del API deben usar los mismos patrones LIKE\n";
    echo "   2. Considerar normalizar los datos de tipo_poblacion\n";
    echo "   3. Verificar si los datos están completos en producción\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
