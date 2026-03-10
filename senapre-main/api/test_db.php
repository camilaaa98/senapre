<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $tables = [];
    $isPg = strpos($db->getDbPath(), 'PostgreSQL') !== false;
    
    if ($isPg) {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    } else {
        $sql = "SELECT name as table_name FROM sqlite_master WHERE type='table'";
    }
    
    $stmt = $conn->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tableName = $row['table_name'];
        $count = 0;
        try {
            $count = $conn->query("SELECT COUNT(*) FROM \"$tableName\"")->fetchColumn();
        } catch (Exception $e) {}
        
        $tables[] = [
            'name' => $tableName,
            'count' => $count
        ];
    }
    
    echo json_encode([
        'success' => true,
        'environment' => $db->getDbPath(),
        'tables' => $tables,
        'php_version' => PHP_VERSION,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
