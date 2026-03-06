<?php
require_once __DIR__ . '/../api/config/Database.php';
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query('SELECT COUNT(*) as total FROM aprendices');
echo 'Total aprendices en DB: ' . $stmt->fetch()['total'] . PHP_EOL;
