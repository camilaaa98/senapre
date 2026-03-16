<?php
/**
 * Script para verificar roles permitidos
 */

require_once 'api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== VERIFICACIÓN DE ROLES PERMITIDOS ===\n";
    echo "Base de datos: " . $database->getDbPath() . "\n\n";
    
    // 1. Verificar estructura de tabla usuarios
    echo "1. Estructura de tabla usuarios:\n";
    $sqlEstructura = "PRAGMA table_info(usuarios)";
    $stmtEstructura = $conn->prepare($sqlEstructura);
    $stmtEstructura->execute();
    $columnas = $stmtEstructura->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columnas as $columna) {
        echo "   - " . $columna['name'] . " (" . $columna['type'] . ")";
        if ($columna['name'] === 'rol') {
            echo " ← ROL";
        }
        echo "\n";
    }
    
    // 2. Verificar roles existentes
    echo "\n2. Roles existentes en el sistema:\n";
    $sqlRoles = "SELECT DISTINCT rol FROM usuarios";
    $stmtRoles = $conn->prepare($sqlRoles);
    $stmtRoles->execute();
    $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($roles as $rol) {
        echo "   - " . $rol . "\n";
    }
    
    // 3. Verificar si hay CHECK constraint
    echo "\n3. Intentando crear Erik con rol 'administrador'...\n";
    
    try {
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
            ':rol' => 'administrador', // Usar 'administrador' en lugar de 'administrativo'
            ':estado' => 'activo'
        ]);
        
        echo "   ✅ Erik creado con rol: administrador\n";
        
        // Asignar como Jefe de Bienestar
        $sqlAsignarErik = "INSERT OR REPLACE INTO area_responsables (id_usuario, area) VALUES ('1117506963', 'jefe_bienestar')";
        $stmtAsignarErik = $conn->prepare($sqlAsignarErik);
        $stmtAsignarErik->execute();
        
        echo "   ✅ Erik asignado como Jefe de Bienestar\n";
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        
        // Intentar con rol 'instructor'
        echo "\n   Intentando con rol 'instructor'...\n";
        try {
            $stmtCrearErik->execute([
                ':id' => '1117506963',
                ':nombre' => 'Erik Jhohana',
                ':apellido' => 'Yáñez Zuleta',
                ':correo' => 'erik.jhohana@senapre.edu.co',
                ':password' => password_hash('1117506963', PASSWORD_DEFAULT),
                ':rol' => 'instructor',
                ':estado' => 'activo'
            ]);
            
            echo "   ✅ Erik creado con rol: instructor\n";
            
            // Asignar como Jefe de Bienestar
            $sqlAsignarErik = "INSERT OR REPLACE INTO area_responsables (id_usuario, area) VALUES ('1117506963', 'jefe_bienestar')";
            $stmtAsignarErik = $conn->prepare($sqlAsignarErik);
            $stmtAsignarErik->execute();
            
            echo "   ✅ Erik asignado como Jefe de Bienestar\n";
            
        } catch (Exception $e2) {
            echo "   ❌ Error también con instructor: " . $e2->getMessage() . "\n";
        }
    }
    
    // 4. Verificación final
    echo "\n4. Verificación final:\n";
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
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
