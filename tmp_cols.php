<?php
require 'api/config/Database.php';
$stmt = Database::getInstance()->getConnection()->query("PRAGMA table_info(aprendices)");
foreach ($stmt->fetchAll() as $row) {
    echo $row['name'] . "\n";
}
