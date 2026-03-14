<?php
require_once 'api/config/Database.php';
$db = Database::getInstance()->getConnection();
echo "--- REPRESENTANTES_JORNADA ---\n";
try {
    $q = $db->query("SELECT * FROM representantes_jornada");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo $e->getMessage() . "\n"; }

echo "\n--- VOCEROS_ENFOQUE ---\n";
try {
    $q = $db->query("SELECT * FROM voceros_enfoque");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) { echo $e->getMessage() . "\n"; }
?>
