<?php
require_once 'api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "--- ROLES JANCY ---\n";
    $q = $db->query("SELECT id_usuario, nombre, apellido, rol FROM usuarios WHERE nombre LIKE '%Jancy%'");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) { echo $e->getMessage(); }
?>
