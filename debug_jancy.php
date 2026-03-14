<?php
require_once 'api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "--- AREA RESPONSABLES (CLEAN) ---\n";
    $qArea = $db->query("SELECT * FROM area_responsables");
    while($row = $qArea->fetch(PDO::FETCH_ASSOC)) {
        echo "USR: " . $row['id_usuario'] . " | AREA: " . $row['area'] . "\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
?>
