<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id_usuario, correo, rol, estado FROM usuarios WHERE id_usuario = '1004417452'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "--- VERIFICACIÓN USUARIA 1004417452 ---\n";
    if ($user) {
        print_r($user);
    } else {
        echo "ERROR: Usuaria no encontrada en la tabla usuarios.\n";
    }

    // Verificar scopes
    $stmt = $conn->prepare("
        SELECT 'Ficha Vocero' as origen, numero_ficha as valor FROM fichas WHERE vocero_principal = '1004417452'
        UNION
        SELECT 'Poblacion Vocero' as origen, tipo_poblacion as valor FROM voceros_enfoque WHERE documento = '1004417452'
    ");
    $stmt->execute();
    echo "\n--- SCOPES DETECTADOS ---\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) { echo $e->getMessage(); }
?>
