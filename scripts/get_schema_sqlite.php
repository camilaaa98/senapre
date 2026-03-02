<?php
// script para extraer schema de Asistnet.db
$db = new PDO('sqlite:' . __DIR__ . '/database/Asistnet.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

echo "=== TABLAS EN Asistnet.db (" . count($tables) . " tablas) ===" . PHP_EOL;
foreach($tables as $t) {
    echo PHP_EOL . "-- TABLE: $t" . PHP_EOL;
    $schema = $db->query("SELECT sql FROM sqlite_master WHERE name='$t'")->fetchColumn();
    echo $schema . PHP_EOL;
    $count = $db->query("SELECT COUNT(*) FROM \"$t\"")->fetchColumn();
    echo "-- Registros: $count" . PHP_EOL;
}
