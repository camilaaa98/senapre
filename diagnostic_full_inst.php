<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->query("
        SELECT u.id_usuario, u.nombre, u.apellido, i.nombres, i.apellidos 
        FROM usuarios u
        JOIN instructores i ON u.id_usuario = i.id_usuario
        WHERE u.rol = 'instructor'
    ");
    
    $results = "COMPARATIVA COMPLETA DE INSTRUCTORES\n";
    $results .= str_repeat("=", 80) . "\n";
    $results .= sprintf("%-5s | %-25s | %-25s | %-25s | %-25s\n", "ID", "U.NOMBRE", "U.APELLIDO", "I.NOMBRES", "I.APELLIDOS");
    $results .= str_repeat("-", 80) . "\n";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results .= sprintf("%-5s | %-25s | %-25s | %-25s | %-25s\n", 
            $row['id_usuario'], 
            substr($row['nombre'], 0, 25), 
            substr($row['apellido'], 0, 25), 
            substr($row['nombres'], 0, 25), 
            substr($row['apellidos'], 0, 25)
        );
    }
    
    file_put_contents('instructores_compare_full.txt', $results);
    echo "Resultados guardados en instructores_compare_full.txt\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
