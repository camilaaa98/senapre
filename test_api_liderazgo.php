<?php
// Script para probar los endpoints de liderazgo
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "<h2>🔍 Verificando datos de voceros y representantes</h2>\n";
    
    // 1. Verificar voceros_enfoque
    echo "<h3>📋 Voceros de Enfoque:</h3>\n";
    $sqlV = "SELECT v.tipo_poblacion, v.documento, a.nombre, a.apellido 
              FROM voceros_enfoque v
              LEFT JOIN aprendices a ON TRIM(CAST(v.documento AS TEXT)) = TRIM(CAST(a.documento AS TEXT))";
    
    $stmtV = $conn->query($sqlV);
    $voceros = $stmtV->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($voceros) > 0) {
        foreach ($voceros as $v) {
            $nombre = $v['nombre'] ? $v['nombre'] . ' ' . $v['apellido'] : 'No encontrado';
            echo "<p style='color: green;'>✅ {$v['tipo_poblacion']}: {$v['documento']} - {$nombre}</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No hay voceros registrados (las tablas están vacías)</p>\n";
    }
    
    // 2. Verificar representantes
    echo "<h3>👥 Representantes:</h3>\n";
    $sqlR = "SELECT r.tipo_jornada, r.documento, a.nombre, a.apellido 
              FROM representantes r
              LEFT JOIN aprendices a ON TRIM(CAST(r.documento AS TEXT)) = TRIM(CAST(a.documento AS TEXT))";
    
    $stmtR = $conn->query($sqlR);
    $representantes = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($representantes) > 0) {
        foreach ($representantes as $r) {
            $nombre = $r['nombre'] ? $r['nombre'] . ' ' . $r['apellido'] : 'No encontrado';
            echo "<p style='color: green;'>✅ {$r['tipo_jornada']}: {$r['documento']} - {$nombre}</p>\n";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No hay representantes registrados (las tablas están vacías)</p>\n";
    }
    
    // 3. Probar API getPoblacionStats
    echo "<h3>🌐 Probando API getPoblacionStats:</h3>\n";
    
    $categorias = [
        'mujer' => ['%mujer%', '%mujeres%', '%femenino%', '%femenina%', '%F%', '%muj%'],
        'indigena' => ['%indigena%', '%indígena%', '%etnia%', '%pueblos%', '%indígenas%', '%etnico%'],
        'narp' => ['%narp%', '%negro%', '%afro%', '%afrodescendiente%', '%raizal%', '%palenquero%', '%afro%'],
        'campesino' => ['%campesino%', '%campesina%', '%rural%', '%campo%', '%camp%'],
        'lgbtiq' => ['%lgbti%', '%lgbt%', '%trans%', '%gay%', '%lesbiana%', '%bisexual%', '%queer%', '%homosexual%', '+'],
        'discapacidad' => ['%discapacidad%', '%discapacitado%', '%discapacitada%', '%capacidad%', '%disc%']
    ];
    
    $stats = [];
    foreach ($categorias as $cat => $patterns) {
        $sql = "SELECT COUNT(*) as total FROM aprendices WHERE UPPER(estado) = 'LECTIVA' AND (";
        $likeConditions = [];
        foreach ($patterns as $pattern) {
            $likeConditions[] = "UPPER(tipo_poblacion) LIKE UPPER('" . $pattern . "')";
        }
        $sql .= implode(" OR ", $likeConditions) . ")";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stats[$cat] = $stmt->fetch()['total'];
        echo "<p style='color: blue;'>📊 {$cat}: {$stats[$cat]} aprendices</p>\n";
    }
    
    // 4. Simular respuesta completa del API
    echo "<h3>🔄 Respuesta API Completa:</h3>\n";
    $vocerosMap = [];
    foreach ($voceros as $v) {
        $nombre = $v['nombre'] ? $v['nombre'] . ' ' . $v['apellido'] : 'No asignado';
        $vocerosMap[strtolower($v['tipo_poblacion'])] = $nombre;
    }
    
    $apiResponse = [
        'success' => true,
        'counts' => $stats,
        'voceros' => $vocerosMap
    ];
    
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>\n";
    
    // 5. Probar getRepresentantes
    echo "<h3>🎯 Probando getRepresentantes:</h3>\n";
    $sqlRep = "SELECT r.tipo_jornada, a.documento, a.nombre, a.apellido 
               FROM representantes r
               LEFT JOIN aprendices a ON TRIM(CAST(r.documento AS TEXT)) = TRIM(CAST(a.documento AS TEXT))
               ORDER BY r.tipo_jornada";
    
    $stmtRep = $conn->query($sqlRep);
    $representantesData = $stmtRep->fetchAll(PDO::FETCH_ASSOC);
    
    $repResponse = [
        'success' => true,
        'data' => $representantesData
    ];
    
    echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo json_encode($repResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>\n";
    
    echo "<h2 style='color: green;'>✅ Verificación completada</h2>\n";
    echo "<p style='color: blue;'>📝 NOTA: Las tablas están creadas pero vacías. Los datos se cargarán cuando asignes voceros y representantes desde la interfaz.</p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
