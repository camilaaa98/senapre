<?php
/**
 * Script para registrar a Erik como Jefe de Bienestar
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== Registro de Erik como Jefe de Bienestar ===\n\n";
    
    // Datos de Erik
    $erikData = [
        'id_usuario' => '1117506963',
        'nombre' => 'Erik Jhohana',
        'apellido' => 'Yáñez Zuleta',
        'correo' => 'erik.jhohana@senapre.edu.co', // correo tentativo
        'password_hash' => password_hash('1117506963', PASSWORD_DEFAULT),
        'rol' => 'administrativo',
        'estado' => 1
    ];
    
    // 1. Insertar usuario
    $sqlUsuario = "INSERT OR IGNORE INTO usuarios 
                  (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                  VALUES (:doc, :nombre, :apellido, :correo, :password, :rol, :estado)";
    
    $stmtUsuario = $conn->prepare($sqlUsuario);
    $stmtUsuario->execute([
        ':doc' => $erikData['id_usuario'],
        ':nombre' => $erikData['nombre'],
        ':apellido' => $erikData['apellido'],
        ':correo' => $erikData['correo'],
        ':password' => $erikData['password_hash'],
        ':rol' => $erikData['rol'],
        ':estado' => $erikData['estado']
    ]);
    
    echo "✅ Usuario Erik creado/actualizado\n";
    
    // 2. Asignar como Jefe de Bienestar
    $sqlArea = "INSERT OR IGNORE INTO area_responsables (id_usuario, area) 
                VALUES (:id_usuario, :area)";
    
    $stmtArea = $conn->prepare($sqlArea);
    $stmtArea->execute([
        ':id_usuario' => $erikData['id_usuario'],
        ':area' => 'jefe_bienestar'
    ]);
    
    echo "✅ Erik asignada como Jefe de Bienestar\n";
    
    // 3. Verificar registro
    $sqlCheck = "SELECT u.*, GROUP_CONCAT(ar.area) as areas 
                 FROM usuarios u 
                 LEFT JOIN area_responsables ar ON u.id_usuario = ar.id_usuario 
                 WHERE u.id_usuario = :documento";
    
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([':documento' => $erikData['id_usuario']]);
    $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    echo "\n=== Verificación ===\n";
    echo "📋 Usuario: " . $result['nombre'] . " " . $result['apellido'] . "\n";
    echo "📋 Documento: " . $result['id_usuario'] . "\n";
    echo "📋 Rol: " . $result['rol'] . "\n";
    echo "📋 Áreas: " . $result['areas'] . "\n";
    echo "📋 Contraseña: 1117506963\n";
    
    echo "\n✅ Configuración completada exitosamente\n";
    echo "\n📝 NOTAS IMPORTANTES:\n";
    echo "   - Erik puede ingresar con documento 1117506963 y contraseña 1117506963\n";
    echo "   - Será redirigida a admin-bienestar-dashboard.html\n";
    echo "   - Tendrá acceso completo al módulo de bienestar\n";
    echo "   - Puede gestionar áreas y responsables\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
