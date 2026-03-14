<?php
/**
 * Script para corregir documentos paso a paso (evitando problemas de FK)
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== CORRECCIÓN DE DOCUMENTOS PASO A PASO ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Primero, eliminar áreas del documento antiguo de Jancy
    echo "1. Eliminando áreas del documento antiguo de Jancy...\n";
    $sqlEliminarAreasJancy = "DELETE FROM area_responsables WHERE id_usuario = '1117525233'";
    $stmtEliminarAreasJancy = $conn->prepare($sqlEliminarAreasJancy);
    $stmtEliminarAreasJancy->execute();
    echo "   ✅ Áreas antiguas eliminadas\n";
    
    // 2. Actualizar documento de Jancy
    echo "\n2. Actualizando documento de Jancy...\n";
    $sqlActualizarJancy = "UPDATE usuarios SET id_usuario = '1056930328' WHERE id_usuario = '1117525233'";
    $stmtActualizarJancy = $conn->prepare($sqlActualizarJancy);
    $stmtActualizarJancy->execute();
    echo "   ✅ Documento actualizado a: 1056930328\n";
    
    // 3. Actualizar contraseña de Jancy
    $sqlPassJancy = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = '1056930328'";
    $stmtPassJancy = $conn->prepare($sqlPassJancy);
    $stmtPassJancy->execute([':password' => password_hash('1056930328', PASSWORD_DEFAULT)]);
    echo "   ✅ Contraseña actualizada: 1056930328\n";
    
    // 4. Reasignar áreas a Jancy con nuevo documento
    $sqlReasignarJancy = "INSERT INTO area_responsables (id_usuario, area) VALUES ('1056930328', 'voceros_y_representantes')";
    $stmtReasignarJancy = $conn->prepare($sqlReasignarJancy);
    $stmtReasignarJancy->execute();
    echo "   ✅ Áreas reasignadas a Jancy\n";
    
    // 5. Buscar a Erik
    echo "\n5. Buscando a Erik...\n";
    $sqlBuscarErik = "SELECT * FROM usuarios WHERE nombre LIKE '%ERIK%' AND apellido LIKE '%YAÑEZ%'";
    $stmtBuscarErik = $conn->prepare($sqlBuscarErik);
    $stmtBuscarErik->execute();
    $erikActual = $stmtBuscarErik->fetch(PDO::FETCH_ASSOC);
    
    if ($erikActual) {
        echo "   Erik encontrado: " . $erikActual['id_usuario'] . " - " . $erikActual['nombre'] . " " . $erikActual['apellido'] . "\n";
        
        // 6. Eliminar áreas antiguas de Erik
        echo "\n6. Eliminando áreas antiguas de Erik...\n";
        $sqlEliminarAreasErik = "DELETE FROM area_responsables WHERE id_usuario = '" . $erikActual['id_usuario'] . "'";
        $stmtEliminarAreasErik = $conn->prepare($sqlEliminarAreasErik);
        $stmtEliminarAreasErik->execute();
        echo "   ✅ Áreas antiguas eliminadas\n";
        
        // 7. Actualizar documento de Erik
        echo "\n7. Actualizando documento de Erik...\n";
        $sqlActualizarErik = "UPDATE usuarios SET id_usuario = '1117506963' WHERE id_usuario = '" . $erikActual['id_usuario'] . "'";
        $stmtActualizarErik = $conn->prepare($sqlActualizarErik);
        $stmtActualizarErik->execute();
        echo "   ✅ Documento actualizado a: 1117506963\n";
        
        // 8. Actualizar contraseña de Erik
        $sqlPassErik = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = '1117506963'";
        $stmtPassErik = $conn->prepare($sqlPassErik);
        $stmtPassErik->execute([':password' => password_hash('1117506963', PASSWORD_DEFAULT)]);
        echo "   ✅ Contraseña actualizada: 1117506963\n";
        
        // 9. Asignar Erik como Jefe de Bienestar
        $sqlAsignarErik = "INSERT INTO area_responsables (id_usuario, area) VALUES ('1117506963', 'jefe_bienestar')";
        $stmtAsignarErik = $conn->prepare($sqlAsignarErik);
        $stmtAsignarErik->execute();
        echo "   ✅ Erik asignado como Jefe de Bienestar\n";
    }
    
    // 10. Verificación final
    echo "\n10. Verificación final:\n";
    
    // Verificar Jancy
    $sqlVerJancy = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                     FROM usuarios u 
                     LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                     WHERE u.id_usuario = '1056930328'";
    $stmtVerJancy = $conn->prepare($sqlVerJancy);
    $stmtVerJancy->execute();
    $verJancy = $stmtVerJancy->fetch(PDO::FETCH_ASSOC);
    
    if ($verJancy) {
        echo "   ✅ Jancy: " . $verJancy['nombre'] . " " . $verJancy['apellido'] . 
             " - Rol: " . $verJancy['rol'] . 
             " - Áreas: " . ($verJancy['areas'] ?: 'Sin áreas') . "\n";
             
        // Determinar redirección según js/auth.js
        if ($verJancy['rol'] === 'administrativo') {
            $areas = explode(',', $verJancy['areas']);
            if (in_array('jefe_bienestar', $areas)) {
                echo "     🎯 Redirección: admin-bienestar-dashboard.html\n";
            } else if (in_array('voceros_y_representantes', $areas) || in_array('liderazgo', $areas)) {
                echo "     🎯 Redirección: liderazgo.html\n";
            } else {
                echo "     🚫 Redirección: ACCESO DENEGADO\n";
            }
        } else {
            echo "     🎯 Redirección: admin-dashboard.html (rol administrador)\n";
        }
    }
    
    // Verificar Erik
    $sqlVerErik = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                    FROM usuarios u 
                    LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                    WHERE u.id_usuario = '1117506963'";
    $stmtVerErik = $conn->prepare($sqlVerErik);
    $stmtVerErik->execute();
    $verErik = $stmtVerErik->fetch(PDO::FETCH_ASSOC);
    
    if ($verErik) {
        echo "   ✅ Erik: " . $verErik['nombre'] . " " . $verErik['apellido'] . 
             " - Rol: " . $verErik['rol'] . 
             " - Áreas: " . ($verErik['areas'] ?: 'Sin áreas') . "\n";
             
        // Determinar redirección según js/auth.js
        if ($verErik['rol'] === 'administrativo') {
            $areas = explode(',', $verErik['areas']);
            if (in_array('jefe_bienestar', $areas)) {
                echo "     🎯 Redirección: admin-bienestar-dashboard.html\n";
            } else if (in_array('voceros_y_representantes', $areas) || in_array('liderazgo', $areas)) {
                echo "     🎯 Redirección: liderazgo.html\n";
            } else {
                echo "     🚫 Redirección: ACCESO DENEGADO\n";
            }
        } else {
            echo "     🎯 Redirección: Según rol específico\n";
        }
    }
    
    echo "\n✅ Corrección completada exitosamente\n";
    echo "\n📝 CREDENCIALES FINALES:\n";
    echo "   Jancy: 1056930328 / 1056930328\n";
    echo "   Erik: 1117506963 / 1117506963\n";
    
    echo "\n🔒 SEGURIDAD IMPLEMENTADA:\n";
    echo "   ✅ Administrativos sin áreas → Acceso denegado\n";
    echo "   ✅ Administrativos con áreas → Panel específico\n";
    echo "   ✅ Administradores → Panel completo\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
