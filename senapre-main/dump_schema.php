<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    $schema = [];
    $tables = ['aprendices', 'fichas', 'usuarios', 'area_responsables'];
    foreach ($tables as $t) {
        $cols = $db->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC);
        $schema[$t] = array_map(function($c) { return $c['name']; }, $cols);
    }
    file_put_contents('schema.json', json_encode($schema, JSON_PRETTY_PRINT));
} catch (Exception $e) {}
