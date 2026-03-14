<?php
require_once 'api/config/Database.php';
$db = Database::getInstance()->getConnection();

function listCols($db, $table) {
    echo "--- COLUMNAS $table ---\n";
    try {
        if (getenv('DATABASE_URL')) {
            $q = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$table'");
        } else {
            $q = $db->query("PRAGMA table_info($table)");
        }
        $cols = $q->fetchAll(PDO::FETCH_ASSOC);
        if (empty($cols)) {
            echo "No se encontraron columnas o la tabla no existe.\n";
            return;
        }
        foreach ($cols as $c) echo ($c['column_name'] ?? $c['name']) . ", ";
        echo "\n";
    } catch(Exception $e) { echo $e->getMessage() . "\n"; }
}

listCols($db, 'fichas');
listCols($db, 'representantes_jornada');
listCols($db, 'voceros_enfoque');
?>
