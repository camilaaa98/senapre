<?php
require_once 'api/config/Database.php';
$db = Database::getInstance()->getConnection();
$tables = ['fichas', 'aprendices', 'representantes_jornada', 'voceros_enfoque'];
foreach($tables as $t) {
    echo "\nTable: $t\n";
    $q = $db->query("PRAGMA table_info($t)");
    while($r = $q->fetch(PDO::FETCH_ASSOC)) {
        echo " - {$r['name']}: {$r['type']}\n";
    }
}
?>
