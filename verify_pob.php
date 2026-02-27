<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = new Database();
    $conn = $db->getConnection();
    $doc = '1004417452';
    
    echo "--- VOCERO ENFOQUE ---\n";
    $stmt = $conn->prepare("SELECT * FROM voceros_enfoque WHERE documento = :id");
    $stmt->execute([':id' => $doc]);
    print_r($stmt->fetchAll());

} catch (Exception $e) { echo $e->getMessage(); }
?>
