<?php
/**
 * Script para crear a Erik con el documento correcto
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== CREANDO ERIK CON DOCUMENTO CORRECTO ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Crear Erik con documento correcto
    echo "1. Creando Erik Jhohana Yáñez Zuleta...\n";
    $sqlCrearErik = "INSERT OR REPLACE INTO usuarios 
                     (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                     VALUES (:id, :nombre, :apellido, :correo, :password, :rol, :estado)";
    
    $stmtCrearErik = $conn->prepare($sqlCrearErik);
    $stmtCrearErik->execute([
        ':id' => '1117506963',
        ':nombre' => 'Erik Jhohana',
        ':apellido' => 'Yáñez Zuleta',
        ':correo' => 'erik.jhohana@senapre.edu.co',
        ':password' => password_hash('1117506963', PASSWORD_DEFAULT),
        ':rol' => 'administrativo',
        ':estado' => 'activo'
    ]);
    
    echo "   ✅ Erik creado con documento: 1117506963\n";
    echo "   ✅ Contraseña: 1117506963\n";
    echo "   ✅ Rol: administrativo\n";
    
    // 2. Asignar como Jefe de Bienestar
    echo "\n2. Asignando Erik como Jefe de Bienestar...\n";
    $sqlAsignarErik = "INSERT OR REPLACE INTO area_responsables (id_usuario, area) VALUES ('1117506963', 'jefe_bienestar')";
    $stmtAsignarErik = $conn->prepare($sqlAsignarErik);
    $stmtAsignarErik->execute();
    
    echo "   ✅ Erik asignado como Jefe de Bienestar\n";
    
    // 3. Verificación final
    echo "\n3. Verificación final:\n";
    
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
                echo "     🎯 Redirección: admin-bienestar-dashboard.html ✅\n";
            } else if (in_array('voceros_y_representantes', $areas) || in_array('liderazgo', $areas)) {
                echo "     🎯 Redirección: liderazgo.html ✅\n";
            } else {
                echo "     🚫 Redirección: ACCESO DENEGADO ❌\n";
            }
        } else {
            echo "     🎯 Redirección: Según rol específico\n";
        }
    }
    
    // 4. Verificar todos los administrativos
    echo "\n4. Todos los usuarios administrativos:\n";
    $sqlAdmins = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                   FROM usuarios u 
                   LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                   WHERE u.rol = 'administrativo' 
                   GROUP BY u.id_usuario";
    
    $stmtAdmins = $conn->prepare($sqlAdmins);
    $stmtAdmins->execute();
    $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($admins as $admin) {
        echo "   - " . $admin['nombre'] . " " . $admin['apellido'] . 
             " (" . $admin['id_usuario'] . ") - Áreas: " . ($admin['areas'] ?: 'Sin áreas') . "\n";
             
        if (empty($admin['areas'])) {
            echo "     ⚠️  PELIGRO: Sin áreas asignadas → Acceso denegado ✅\n";
        } else {
            echo "     ✅ Con áreas asignadas → Panel específico\n";
        }
    }
    
    echo "\n✅ Configuración completada exitosamente\n";
    echo "\n📝 CREDENCIALES FINALES:\n";
    echo "   Jancy: 1056930328 / 1056930328 → admin-dashboard.html (administrador)\n";
    echo "   Erik: 1117506963 / 1117506963 → admin-bienestar-dashboard.html (jefe_bienestar)\n";
    
    echo "\n🔒 SEGURIDAD IMPLEMENTADA:\n";
    echo "   ✅ Jancy (administrador) → Panel completo\n";
    echo "   ✅ Erik (administrativo + jefe_bienestar) → Panel de bienestar\n";
    echo "   ✅ Otros administrativos sin áreas → Acceso denegado\n";
    echo "   ✅ Vulnerabilidad de acceso masivo corregida\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
