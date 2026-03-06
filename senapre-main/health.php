<?php
header('Content-Type: application/json');
require_once __DIR__ . '/api/config/Database.php';

$res = [
    'status' => 'ok',
    'php_version' => PHP_VERSION,
    'db' => 'unknown'
];

try {
    $db = Database::getInstance();
    $res['db'] = $db->getDbPath();
    $conn = $db->getConnection();
    $res['db_status'] = 'connected';
} catch (Exception $e) {
    $res['db_status'] = 'error: ' . $e->getMessage();
}

echo json_encode($res);
?>
