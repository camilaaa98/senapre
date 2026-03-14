<?php
/**
 * Script para verificar Jancy con el documento correcto
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== VERIFICACIÓN JANCY CORRECTO ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Buscar a Jancy por documento correcto
    echo "1. Buscando Jancy con documento 1117525233...\n";
    $sqlJancy = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                 FROM usuarios u 
                 LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                 WHERE u.id_usuario = '1117525233' 
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
        
        // 2. Verificar si tiene áreas asignadas
        echo "\n2. Verificando áreas asignadas:\n";
        $sqlAreas = "SELECT area FROM area_responsables WHERE id_usuario = '1117525233'";
        $stmtAreas = $conn->prepare($sqlAreas);
        $stmtAreas->execute();
        $areasUsuario = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($areasUsuario)) {
            echo "   ❌ No tiene áreas asignadas\n";
        } else {
            foreach ($areasUsuario as $area) {
                echo "   ✅ Área asignada: " . $area . "\n";
            }
        }
        
        // 3. Determinar redirección según js/auth.js
        echo "\n3. Lógica de redirección (según js/auth.js):\n";
        if ($jancy['rol'] === 'administrador') {
            echo "   🎯 Redirección: admin-dashboard.html (rol administrador)\n";
        } else if ($jancy['rol'] === 'administrativo') {
            if (in_array('jefe_bienestar', $areasUsuario)) {
                echo "   🎯 Redirección: admin-bienestar-dashboard.html\n";
            } else if (in_array('voceros_y_representantes', $areasUsuario) || in_array('liderazgo', $areasUsuario)) {
                echo "   🎯 Redirección: liderazgo.html\n";
            } else {
                echo "   🚫 Redirección: ACCESO DENEGADO (sin áreas asignadas)\n";
            }
        } else {
            echo "   🎯 Redirección: Según rol específico (" . $jancy['rol'] . ")\n";
        }
        
        // 4. Asignar área de voceros_y_representantes si no tiene
        if (empty($areasUsuario)) {
            echo "\n4. Asignando área de voceros_y_representantes...\n";
            $sqlAsignar = "INSERT OR IGNORE INTO area_responsables (id_usuario, area) VALUES (:id, :area)";
            $stmtAsignar = $conn->prepare($sqlAsignar);
            $stmtAsignar->execute([
                ':id' => '1117525233',
                ':area' => 'voceros_y_representantes'
            ]);
            
            echo "   ✅ Área voceros_y_representantes asignada\n";
        }
        
    } else {
        echo "❌ Jancy no encontrada\n";
    }
    
    // 5. Verificar todos los administrativos/administradores
    echo "\n5. Todos los usuarios administrativos/administradores:\n";
    $sqlAdmins = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                   FROM usuarios u 
                   LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                   WHERE u.rol IN ('administrativo', 'administrador') 
                   GROUP BY u.id_usuario";
    
    $stmtAdmins = $conn->prepare($sqlAdmins);
    $stmtAdmins->execute();
    $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($admins as $admin) {
        echo "   - " . $admin['nombre'] . " " . $admin['apellido'] . 
             " (" . $admin['id_usuario'] . ") - Rol: " . $admin['rol'] . 
             " - Áreas: " . ($admin['areas'] ?: 'Sin áreas') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
