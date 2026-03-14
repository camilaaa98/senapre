<?php
require_once 'api/config/Database.php';
$db = Database::getInstance()->getConnection();
$res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
$tables = $res->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) echo "$t\n";
?>
