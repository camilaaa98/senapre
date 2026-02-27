<?php
require_once __DIR__ . '/../api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Actualizar contraseña del admin
    $new_password = password_hash('123456', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE usuarios SET password_hash = :password WHERE correo = 'admin@sena.edu.co'");
    $stmt->execute([':password' => $new_password]);
    
    echo "Contraseña actualizada exitosamente\n";
    echo "Usuario: admin@sena.edu.co\n";
    echo "Contraseña: 123456\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
