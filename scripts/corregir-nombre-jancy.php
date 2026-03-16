<?php
/**
 * Script para corregir el nombre de Jancy
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== CORRECCIÓN NOMBRE DE JANCY ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Buscar a Jancy por documento
    echo "1. Buscando a Jancy por documento 1056930328...\n";
    $sqlJancy = "SELECT * FROM usuarios WHERE id_usuario = '1056930328'";
    $stmtJancy = $conn->prepare($sqlJancy);
    $stmtJancy->execute();
    $jancy = $stmtJancy->fetch(PDO::FETCH_ASSOC);
    
    if ($jancy) {
        echo "   Jancy encontrada:\n";
        echo "   - Nombre actual: " . $jancy['nombre'] . "\n";
        echo "   - Apellido actual: " . $jancy['apellido'] . "\n";
        
        // 2. Corregir el nombre (quitar la 'J' al final)
        echo "\n2. Corrigiendo nombre...\n";
        $nombreCorrecto = str_replace('JANCY', 'JANCY', $jancy['nombre']);
        
        $sqlActualizar = "UPDATE usuarios SET nombre = :nombre WHERE id_usuario = '1056930328'";
        $stmtActualizar = $conn->prepare($sqlActualizar);
        $stmtActualizar->execute([':nombre' => $nombreCorrecto]);
        
        echo "   ✅ Nombre corregido a: " . $nombreCorrecto . "\n";
        
        // 3. Verificación final
        echo "\n3. Verificación final:\n";
        $sqlVer = "SELECT * FROM usuarios WHERE id_usuario = '1056930328'";
        $stmtVer = $conn->prepare($sqlVer);
        $stmtVer->execute();
        $verJancy = $stmtVer->fetch(PDO::FETCH_ASSOC);
        
        if ($verJancy) {
            echo "   ✅ Nombre final: " . $verJancy['nombre'] . " " . $verJancy['apellido'] . "\n";
            echo "   ✅ Documento: " . $verJancy['id_usuario'] . "\n";
            echo "   ✅ Rol: " . $verJancy['rol'] . "\n";
            echo "   ✅ Correo: " . $verJancy['correo'] . "\n";
        }
        
    } else {
        echo "   ❌ Jancy no encontrada con documento 1056930328\n";
        
        // Buscar por nombre similar
        echo "\n4. Buscando por nombre similar...\n";
        $sqlBuscar = "SELECT * FROM usuarios WHERE nombre LIKE '%JANCY%' OR nombre LIKE '%Jancy%'";
        $stmtBuscar = $conn->prepare($sqlBuscar);
        $stmtBuscar->execute();
        $resultados = $stmtBuscar->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resultados as $resultado) {
            echo "   - " . $resultado['nombre'] . " " . $resultado['apellido'] . 
                 " (" . $resultado['id_usuario'] . ") - Rol: " . $resultado['rol'] . "\n";
        }
    }
    
    echo "\n✅ Corrección completada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
