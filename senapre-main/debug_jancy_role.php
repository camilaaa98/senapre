<?php
try {
    $db_file = 'c:/wamp64/www/YanguasEjercicios/senapre/senapre-main/database/Asistnet.db';
    $conn = new PDO("sqlite:$db_file");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("SELECT DISTINCT rol FROM usuarios");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "ROLES EN LA BD:\n";
    foreach($results as $r) {
        echo "- $r\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
