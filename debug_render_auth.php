<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "--- DIAGNÓSTICO DE BASE DE DATOS EN " . $db->getDbPath() . " ---\n";
    
    $correo = 'campguevara@gmail.com';
    $stmt = $conn->prepare("SELECT id_usuario, correo, rol, estado, password_hash FROM usuarios WHERE correo = :cor");
    $stmt->execute([':cor' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "USUARIO ENCONTRADO:\n";
        echo "ID: " . $user['id_usuario'] . "\n";
        echo "Rol: " . $user['rol'] . "\n";
        echo "Estado: " . $user['estado'] . "\n";
        echo "Hash: " . substr($user['password_hash'], 0, 10) . "...\n";
        
        // Probar verificación de contraseña '123456'
        $pass = '123456';
        $isValid = password_verify($pass, $user['password_hash']) || ($pass === $user['password_hash']);
        echo "Contraseña '123456' válida?: " . ($isValid ? "SÍ" : "NO") . "\n";
        
    } else {
        echo "ERROR: Correo '$correo' NO encontrado.\n";
        
        $total = $conn->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        echo "Total de usuarios en tabla: $total\n";
    }

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
?>
