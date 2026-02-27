<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $response = [];

    // Check aprendices table info
    $stmt = $conn->query("PRAGMA table_info(aprendices)");
    $response['aprendices_structure'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
