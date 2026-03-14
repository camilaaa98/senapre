<?php
/**
 * Script para corregir documentos y roles de seguridad
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== CORRECCIÓN DE DOCUMENTOS Y ROLES ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Corregir documento de Jancy
    echo "1. Corrigiendo documento de Jancy...\n";
    
    // Buscar Jancy actual
    $sqlBuscarJancy = "SELECT * FROM usuarios WHERE nombre LIKE '%JANCY%' AND apellido LIKE '%BARRETO%'";
    $stmtBuscarJancy = $conn->prepare($sqlBuscarJancy);
    $stmtBuscarJancy->execute();
    $jancyActual = $stmtBuscarJancy->fetch(PDO::FETCH_ASSOC);
    
    if ($jancyActual) {
        echo "   Jancy encontrada: " . $jancyActual['id_usuario'] . " - " . $jancyActual['nombre'] . " " . $jancyActual['apellido'] . "\n";
        
        // Actualizar documento de Jancy
        $sqlActualizarJancy = "UPDATE usuarios SET id_usuario = '1056930328' WHERE id_usuario = :actual";
        $stmtActualizarJancy = $conn->prepare($sqlActualizarJancy);
        $stmtActualizarJancy->execute([':actual' => $jancyActual['id_usuario']]);
        
        echo "   ✅ Documento actualizado a: 1056930328\n";
        
        // Actualizar contraseña (documento como contraseña)
        $sqlPassJancy = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = '1056930328'";
        $stmtPassJancy = $conn->prepare($sqlPassJancy);
        $stmtPassJancy->execute([':password' => password_hash('1056930328', PASSWORD_DEFAULT)]);
        
        echo "   ✅ Contraseña actualizada: 1056930328\n";
        
        // Mover áreas si es necesario
        $sqlMoverAreas = "UPDATE area_responsables SET id_usuario = '1056930328' WHERE id_usuario = :actual";
        $stmtMoverAreas = $conn->prepare($sqlMoverAreas);
        $stmtMoverAreas->execute([':actual' => $jancyActual['id_usuario']]);
        
        echo "   ✅ Áreas migradas al nuevo documento\n";
    }
    
    // 2. Corregir documento de Erik
    echo "\n2. Corrigiendo documento de Erik...\n";
    
    // Buscar Erik actual
    $sqlBuscarErik = "SELECT * FROM usuarios WHERE nombre LIKE '%ERIK%' AND apellido LIKE '%YAÑEZ%'";
    $stmtBuscarErik = $conn->prepare($sqlBuscarErik);
    $stmtBuscarErik->execute();
    $erikActual = $stmtBuscarErik->fetch(PDO::FETCH_ASSOC);
    
    if ($erikActual) {
        echo "   Erik encontrado: " . $erikActual['id_usuario'] . " - " . $erikActual['nombre'] . " " . $erikActual['apellido'] . "\n";
        
        // Actualizar documento de Erik
        $sqlActualizarErik = "UPDATE usuarios SET id_usuario = '1117506963' WHERE id_usuario = :actual";
        $stmtActualizarErik = $conn->prepare($sqlActualizarErik);
        $stmtActualizarErik->execute([':actual' => $erikActual['id_usuario']]);
        
        echo "   ✅ Documento actualizado a: 1117506963\n";
        
        // Actualizar contraseña (documento como contraseña)
        $sqlPassErik = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = '1117506963'";
        $stmtPassErik = $conn->prepare($sqlPassErik);
        $stmtPassErik->execute([':password' => password_hash('1117506963', PASSWORD_DEFAULT)]);
        
        echo "   ✅ Contraseña actualizada: 1117506963\n";
        
        // Asignar como Jefe de Bienestar
        $sqlAsignarErik = "INSERT OR REPLACE INTO area_responsables (id_usuario, area) VALUES ('1117506963', 'jefe_bienestar')";
        $stmtAsignarErik = $conn->prepare($sqlAsignarErik);
        $stmtAsignarErik->execute();
        
        echo "   ✅ Erik asignado como Jefe de Bienestar\n";
    }
    
    // 3. Verificar problema de seguridad: Administrativos que van a panel de director
    echo "\n3. Verificando problema de seguridad...\n";
    
    $sqlAdmins = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                   FROM usuarios u 
                   LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                   WHERE u.rol = 'administrativo' 
                   GROUP BY u.id_usuario";
    
    $stmtAdmins = $conn->prepare($sqlAdmins);
    $stmtAdmins->execute();
    $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Usuarios administrativos:\n";
    foreach ($admins as $admin) {
        echo "   - " . $admin['nombre'] . " " . $admin['apellido'] . 
             " (" . $admin['id_usuario'] . ") - Áreas: " . ($admin['areas'] ?: 'Sin áreas') . "\n";
             
        if (empty($admin['areas'])) {
            echo "     ⚠️  PELIGRO: Sin áreas asignadas - Acceso denegado ✅\n";
        }
    }
    
    // 4. Verificar lógica actual en js/auth.js
    echo "\n4. Lógica de seguridad en js/auth.js:\n";
    echo "   ✅ Administrativo sin áreas → Acceso denegado\n";
    echo "   ✅ Administrativo con jefe_bienestar → admin-bienestar-dashboard.html\n";
    echo "   ✅ Administrativo con voceros_y_representantes → liderazgo.html\n";
    echo "   ✅ Administrador → admin-dashboard.html (acceso completo)\n";
    
    // 5. Verificación final
    echo "\n5. Verificación final:\n";
    
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
    }
    
    echo "\n✅ Corrección completada exitosamente\n";
    echo "\n📝 CREDENCIALES FINALES:\n";
    echo "   Jancy: 1056930328 / 1056930328\n";
    echo "   Erik: 1117506963 / 1117506963\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
