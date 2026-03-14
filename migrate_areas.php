<?php
require_once 'api/config/Database.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "--- MIGRANDO AREA RESPONSABLES ---\n";
    
    // 1. Convertir 'vocero' o 'liderazgo' a 'voceros_y_representantes'
    $db->exec("UPDATE area_responsables SET area = 'voceros_y_representantes' WHERE area IN ('vocero', 'liderazgo')");
    
    // 2. Asegurar que Jancy (1117525233) sea voceros_y_representantes
    $stmt = $db->prepare("SELECT COUNT(*) FROM area_responsables WHERE id_usuario = '1117525233' AND area = 'voceros_y_representantes'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $db->exec("DELETE FROM area_responsables WHERE id_usuario = '1117525233'");
        $db->exec("INSERT INTO area_responsables (id_usuario, area) VALUES ('1117525233', 'voceros_y_representantes')");
        echo "Jancy asignada a voceros_y_representantes.\n";
    }

    echo "--- RESULTADO FINAL ---\n";
    $q = $db->query("SELECT * FROM area_responsables");
    print_r($q->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) { echo $e->getMessage(); }
?>
