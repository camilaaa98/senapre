<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $output = "--- MUESTRA TABLA APRENDICES ---\n";
    $stmt = $conn->query("SELECT nombre, apellido FROM aprendices LIMIT 10");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= "Nombre: [{$row['nombre']}] | Apellido: [{$row['apellido']}]\n";
    }
    
    $output .= "\n--- MUESTRA TABLA USUARIOS (INSTRUCTORES) ---\n";
    $stmt = $conn->query("SELECT nombre, apellido, rol FROM usuarios WHERE rol = 'instructor' LIMIT 10");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= "Nombre: [{$row['nombre']}] | Apellido: [{$row['apellido']}] | Rol: {$row['rol']}\n";
    }

    $output .= "\n--- MUESTRA TABLA INSTRUCTORES ---\n";
    $stmt = $conn->query("SELECT nombres, apellidos FROM instructores LIMIT 10");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= "Nombres: [{$row['nombres']}] | Apellidos: [{$row['apellidos']}]\n";
    }
    
    file_put_contents('compare_results.txt', $output);
    echo "Diagnóstico guardado en compare_results.txt\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
