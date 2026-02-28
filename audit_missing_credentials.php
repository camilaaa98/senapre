<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "--- LEADERS WITHOUT USERS ---\n";
    
    // Voceros Principales
    $sql = "SELECT 'Principal' as tipo, f.numero_ficha, f.vocero_principal as doc, a.nombre, a.apellido 
            FROM fichas f
            JOIN aprendices a ON f.vocero_principal = a.documento
            LEFT JOIN usuarios u ON a.documento = u.id_usuario
            WHERE u.id_usuario IS NULL";
    $stmt = $conn->query($sql);
    print_r($stmt->fetchAll());

    // Voceros Suplentes
    $sql = "SELECT 'Suplente' as tipo, f.numero_ficha, f.vocero_suplente as doc, a.nombre, a.apellido 
            FROM fichas f
            JOIN aprendices a ON f.vocero_suplente = a.documento
            LEFT JOIN usuarios u ON a.documento = u.id_usuario
            WHERE u.id_usuario IS NULL";
    $stmt = $conn->query($sql);
    print_r($stmt->fetchAll());

    // Voceros Enfoque
    $sql = "SELECT 'Enfoque' as tipo, ve.tipo_poblacion, ve.documento as doc, a.nombre, a.apellido 
            FROM voceros_enfoque ve
            JOIN aprendices a ON ve.documento = a.documento
            LEFT JOIN usuarios u ON a.documento = u.id_usuario
            WHERE u.id_usuario IS NULL";
    $stmt = $conn->query($sql);
    print_r($stmt->fetchAll());

} catch (Exception $e) { echo $e->getMessage(); }
?>
