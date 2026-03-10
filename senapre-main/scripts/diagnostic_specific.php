<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $searchNames = ['MARIA ALEJANDRA', 'PEDRO', 'PINZON', 'ARDILA', 'ALEJANDRA'];
    
    echo "--- BUSCANDO CASOS ESPECÍFICOS ---\n";
    foreach ($searchNames as $name) {
        $stmt = $conn->prepare("SELECT id_usuario, nombre, apellido, rol FROM usuarios WHERE nombre LIKE ? OR apellido LIKE ?");
        $stmt->execute(["%$name%", "%$name%"]);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "USUARIO ID: {$row['id_usuario']} | NOMBRE: [{$row['nombre']}] | APELLIDO: [{$row['apellido']}] | ROL: {$row['rol']}\n";
            
            // Ver en tabla instructores
            $stmtInst = $conn->prepare("SELECT nombres, apellidos FROM instructores WHERE id_usuario = ?");
            $stmtInst->execute([$row['id_usuario']]);
            if ($inst = $stmtInst->fetch(PDO::FETCH_ASSOC)) {
                echo "  INSTRUCTOR -> NOMBRES: [{$inst['nombres']}] | APELLIDOS: [{$inst['apellidos']}]\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
