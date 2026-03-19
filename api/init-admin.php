<?php
/**
 * SENAPRE High-Priority Users Initialization Script
 * Purpose: Create Master Admin, Jancy Barreto, and Erik Yanez in new environments.
 */
header('Content-Type: text/plain');
require_once __DIR__ . '/config/Database.php';

try {
    echo "=== Iniciando Inicialización de Usuarios Críticos ===\n";
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    $conn->beginTransaction();
    
    // Función para crear un usuario robustamente
    $addUser = function($conn, $id, $nombre, $apellido, $correo, $pass, $rol, $area = null) {
        echo "Procesando $nombre ($id)... ";
        
        // Limpiar si ya existe para evitar duplicados
        $conn->prepare("DELETE FROM area_responsables WHERE id_usuario = :id")->execute([':id' => $id]);
        $conn->prepare("DELETE FROM usuarios WHERE id_usuario = :id OR correo = :correo")->execute([':id' => $id, ':correo' => $correo]);
        
        $passHash = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (id_usuario, nombre, apellido, correo, password_hash, rol, estado) 
                VALUES (:id, :nom, :ape, :cor, :pass, :rol, 'activo')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nom' => $nombre,
            ':ape' => $apellido,
            ':cor' => $correo,
            ':pass' => $passHash,
            ':rol' => $rol
        ]);
        
        // Asignar área si es necesario (para que redirija a liderazgo.html o similar)
        if ($area) {
            $sqlA = "INSERT INTO area_responsables (id_usuario, area) VALUES (:id, :area)";
            $conn->prepare($sqlA)->execute([':id' => $id, ':area' => $area]);
        }
        
        echo "✅ OK.\n";
    };

    // 1. EL ADMINISTRADOR MAESTRO (Director)
    $addUser($conn, 1, 'Administrador', 'SENA', 'admin@sena.edu.co', 'Sena2026*Master', 'director', 'reportes');

    // 2. JANCY ESPERANZA BARRETO MORENO (Liderazgo)
    $addUser($conn, 1056930328, 'Jancy Esperanza', 'Barreto Moreno', 'jancy.barreto@sena.edu.co', '1056930328', 'administrativo', 'voceros_y_representantes');
    
    // 3. ERIK JHOHANA YÁÑEZ ZULETA
    $addUser($conn, 1117506963, 'Erik Jhohana', 'Yáñez Zuleta', 'erik.yanez@sena.edu.co', '1117506963', 'administrativo', 'fichas');

    // 4. EL VOCERO DE PRUEBA (Ficha 2995479)
    $addUser($conn, 999999, 'Aprendiz', 'Prueba', 'vocero@ejemplo.com', '999999', 'vocero');

    $conn->commit();
    echo "=== Usuarios Creados con Éxito ===\n\n";
    echo "Resumen de Credenciales:\n";
    echo "--------------------------\n";
    echo "Director: admin@sena.edu.co / Sena2026*Master\n";
    echo "Jancy (Liderazgo): jancy.barreto@sena.edu.co / 1056930328\n";
    echo "Erik: erik.yanez@sena.edu.co / 1117506963\n";
    echo "--------------------------\n";

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    error_log("User Init Error: " . $e->getMessage());
}
