<?php
/**
 * Script para verificar el rol y áreas de Jancy
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== VERIFICACIÓN ROL DE JANCY ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Buscar a Jancy por documento
    echo "1. Buscando a Jancy...\n";
    $sqlJancy = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                 FROM usuarios u 
                 LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                 WHERE u.id_usuario = '1056930328' 
                 GROUP BY u.id_usuario";
    
    $stmtJancy = $conn->prepare($sqlJancy);
    $stmtJancy->execute();
    $jancy = $stmtJancy->fetch(PDO::FETCH_ASSOC);
    
    if ($jancy) {
        echo "✅ Jancy encontrada:\n";
        echo "   ID: " . $jancy['id_usuario'] . "\n";
        echo "   Nombre: " . $jancy['nombre'] . " " . $jancy['apellido'] . "\n";
        echo "   Correo: " . $jancy['correo'] . "\n";
        echo "   Rol: " . $jancy['rol'] . "\n";
        echo "   Estado: " . $jancy['estado'] . "\n";
        echo "   Áreas: " . ($jancy['areas'] ?: 'Sin áreas asignadas') . "\n";
        
        // 2. Verificar si tiene áreas de liderazgo
        echo "\n2. Verificando áreas de liderazgo:\n";
        $areas = $jancy['areas'] ? explode(',', $jancy['areas']) : [];
        
        $esLiderazgo = false;
        $esJefeBienestar = false;
        
        foreach ($areas as $area) {
            $area = trim($area);
            if ($area === 'voceros_y_representantes' || $area === 'liderazgo') {
                $esLiderazgo = true;
                echo "   ✅ Área de liderazgo: " . $area . "\n";
            }
            if ($area === 'jefe_bienestar') {
                $esJefeBienestar = true;
                echo "   ✅ Área de Jefe de Bienestar: " . $area . "\n";
            }
        }
        
        // 3. Determinar redirección según js/auth.js
        echo "\n3. Lógica de redirección (según js/auth.js):\n";
        if ($jancy['rol'] === 'administrativo') {
            if ($esJefeBienestar) {
                echo "   🎯 Redirección: admin-bienestar-dashboard.html\n";
            } else if ($esLiderazgo) {
                echo "   🎯 Redirección: liderazgo.html\n";
            } else {
                echo "   🚫 Redirección: ACCESO DENEGADO (sin áreas asignadas)\n";
            }
        } else {
            echo "   🎯 Redirección: Según rol específico (" . $jancy['rol'] . ")\n";
        }
        
        // 4. Verificar contraseña
        echo "\n4. Verificación de contraseña:\n";
        if (password_verify('1056930328', $jancy['password_hash'])) {
            echo "   ✅ Contraseña correcta: 1056930328\n";
        } else {
            echo "   ❌ Contraseña incorrecta\n";
        }
        
    } else {
        echo "❌ Jancy no encontrada en el sistema\n";
        
        // 5. Buscar por nombre
        echo "\n5. Buscando por nombre...\n";
        $sqlNombre = "SELECT * FROM usuarios WHERE nombre LIKE '%Jancy%' OR apellido LIKE '%Jancy%'";
        $stmtNombre = $conn->prepare($sqlNombre);
        $stmtNombre->execute();
        $resultados = $stmtNombre->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados as $resultado) {
            echo "   - " . $resultado['nombre'] . " " . $resultado['apellido'] . 
                 " (" . $resultado['id_usuario'] . ") - Rol: " . $resultado['rol'] . "\n";
        }
    }
    
    // 6. Verificar todas las áreas disponibles
    echo "\n6. Áreas disponibles en el sistema:\n";
    $sqlAreas = "SELECT DISTINCT area FROM area_responsables ORDER BY area";
    $stmtAreas = $conn->prepare($sqlAreas);
    $stmtAreas->execute();
    $areasDisponibles = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($areasDisponibles as $area) {
        echo "   - " . $area . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
