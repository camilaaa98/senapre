<?php
require_once 'api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "--- AREAS JANCY (FINAL) ---\n";
    $q = $db->query("SELECT * FROM area_responsables WHERE id_usuario = '1117525233'");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage(); }
?>
