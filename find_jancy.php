    echo "--- AREA RESPONSABLES (SIMPLE) ---\n";
    $qArea = $db->query("SELECT * FROM area_responsables");
    while($row = $qArea->fetch(PDO::FETCH_ASSOC)) {
        echo "USR: " . $row['id_usuario'] . " | AREA: " . $row['area'] . "\n";
    }

    echo "\n--- NOMBRES USUARIOS ---\n";
    $qU = $db->query("SELECT id_usuario, nombre, apellido FROM usuarios");
    while($u = $qU->fetch(PDO::FETCH_ASSOC)) {
        if(stripos($u['nombre'], 'jancy') !== false) {
             echo "JANCY ID: " . $u['id_usuario'] . "\n";
        }
    }
    
    echo "\n--- AREA RESPONSABLES (COUNT) ---\n";
    $count = $db->query("SELECT COUNT(*) FROM area_responsables")->fetchColumn();
    echo "Total registros: $count\n";
    $qArea = $db->query("SELECT * FROM area_responsables");
    while($row = $qArea->fetch(PDO::FETCH_ASSOC)) {
        echo "ID_USR: " . $row['id_usuario'] . " | AREA: " . $row['area'] . "\n";
    }

    echo "\n--- TODOS LOS USUARIOS (COMPLETO) ---\n";
    $qAll = $db->query("SELECT id_usuario, nombre, apellido, rol FROM usuarios");
    $allUsers = $qAll->fetchAll(PDO::FETCH_ASSOC);
    foreach($allUsers as $u) {
        if (stripos($u['nombre'], 'jancy') !== false || stripos($u['apellido'], 'jancy') !== false) {
             echo "!!! ENCONTRADA: ID: {$u['id_usuario']} | Rol: {$u['rol']} | Name: {$u['nombre']} {$u['apellido']}\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
