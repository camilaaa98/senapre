<?php
header('Content-Type: application/json');

// Force disable opcode cache for this script just in case (though it might be too late if file is already compiled, but this is a new file)
ini_set('opcache.enable', 0);

require_once __DIR__ . '/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $search = '3312828';
    $results = [];

    // Check in fichas
    $stmt = $conn->prepare("SELECT * FROM fichas WHERE numero_ficha LIKE ?");
    $stmt->execute(["%$search%"]);
    $results['in_fichas_table'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check in programas_formacion
    $stmt = $conn->prepare("SELECT * FROM programas_formacion WHERE nombre_programa LIKE ?");
    $stmt->execute(["%$search%"]);
    $results['in_programas_table'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total rows
    $results['total_programas'] = $conn->query("SELECT COUNT(*) FROM programas_formacion")->fetchColumn();
    $results['total_fichas'] = $conn->query("SELECT COUNT(*) FROM fichas")->fetchColumn();
    
    // Get absolute path of database file
    $results['db_file_used_by_php'] = $db->getDbPath();
    $results['db_file_exists'] = file_exists($results['db_file_used_by_php']);
    $results['db_file_size'] = $results['db_file_exists'] ? filesize($results['db_file_used_by_php']) : 0;
    $results['db_file_realpath'] = realpath($results['db_file_used_by_php']);
    $results['last_modified'] = $results['db_file_exists'] ? date("Y-m-d H:i:s", filemtime($results['db_file_used_by_php'])) : 'N/A';

    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
