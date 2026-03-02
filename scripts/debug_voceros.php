<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec("PRAGMA busy_timeout = 30000");

    echo "--- FICHAS LÍDERES ---\n";
    $fichas = $conn->query("SELECT numero_ficha, vocero_principal, vocero_suplente FROM fichas WHERE vocero_principal IS NOT NULL OR vocero_suplente IS NOT NULL")->fetchAll();
    print_r($fichas);

    echo "\n--- VOCEROS ENFOQUE ---\n";
    $enfoque = $conn->query("SELECT * FROM voceros_enfoque")->fetchAll();
    print_r($enfoque);

    echo "\n--- REPRESENTANTES ---\n";
    $reps = $conn->query("SELECT * FROM representantes_jornada")->fetchAll();
    print_r($reps);

    echo "\n--- USERS (VOCEROS) ---\n";
    $users = $conn->query("SELECT id_usuario, correo, rol FROM usuarios WHERE rol = 'vocero'")->fetchAll();
    print_r($users);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
