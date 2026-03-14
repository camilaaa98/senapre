<?php
require_once 'api/config/Database.php';

$db = Database::getInstance()->getConnection();

echo "=== ESTRUCTURA TABLA AREA_RESPONSABLES ===\n";
$sql = 'PRAGMA table_info(area_responsables)';
$stmt = $db->prepare($sql);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo "- " . $column['name'] . " (" . $column['type'] . ")\n";
}

echo "\n=== DATOS EXISTENTES ===\n";
$sql2 = "SELECT * FROM area_responsables";
$stmt2 = $db->prepare($sql2);
$stmt2->execute();
$data = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($data as $row) {
    echo "- ID: " . $row['id_usuario'] . " - Área: " . $row['area'] . "\n";
}
?>
