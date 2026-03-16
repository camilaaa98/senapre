<?php
/**
 * Script para establecer contraseñas iniciales
 * Ejecutar una sola vez para configurar usuarios específicos
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "=== Configuración de Contraseñas Iniciales ===\n\n";
    
    // Configurar Jancy Esperanza Barreto Moreno
    $documentoJancy = '1056930328';
    $passwordJancy = '1056930328';
    
    $sqlJancy = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = :documento";
    $stmtJancy = $conn->prepare($sqlJancy);
    $stmtJancy->execute([
        ':password' => password_hash($passwordJancy, PASSWORD_DEFAULT),
        ':documento' => $documentoJancy
    ]);
    
    echo "✅ Jancy Esperanza Barreto Moreno (1056930328)\n";
    echo "   Contraseña establecida: " . $passwordJancy . "\n";
    
    // Configurar Erik Jhohana Yáñez Zuleta
    $documentoErik = '1117506963';
    $passwordErik = '1117506963';
    
    $sqlErik = "UPDATE usuarios SET password_hash = :password WHERE id_usuario = :documento";
    $stmtErik = $conn->prepare($sqlErik);
    $stmtErik->execute([
        ':password' => password_hash($passwordErik, PASSWORD_DEFAULT),
        ':documento' => $documentoErik
    ]);
    
    echo "\n✅ Erik Jhohana Yáñez Zuleta (1117506963)\n";
    echo "   Contraseña establecida: " . $passwordErik . "\n";
    
    // Verificar que los usuarios existan
    $sqlCheck = "SELECT id_usuario, nombre, apellido, rol FROM usuarios WHERE id_usuario IN (:jancy, :erik)";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([
        ':jancy' => $documentoJancy,
        ':erik' => $documentoErik
    ]);
    
    $usuarios = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== Usuarios Configurados ===\n";
    foreach ($usuarios as $usuario) {
        echo "📋 {$usuario['nombre']} {$usuario['apellido']} ({$usuario['id_usuario']}) - Rol: {$usuario['rol']}\n";
    }
    
    echo "\n⚠️  IMPORTANTE:\n";
    echo "   - Las contraseñas son el número de documento\n";
    echo "   - Los usuarios deben cambiar su contraseña en el primer inicio\n";
    echo "   - Este script debe ejecutarse solo una vez\n";
    echo "   - Elimine este archivo después de usarlo\n";
    
    echo "\n✅ Configuración completada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
