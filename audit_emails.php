<?php
require_once __DIR__ . '/api/config/Database.php';
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "--- CORREOS ACTUALES DE LÍDERES EN TABLA APRENDICES ---\n";
    $sql = "SELECT documento, nombre, apellido, correo 
            FROM aprendices 
            WHERE documento IN (
                SELECT vocero_principal FROM fichas WHERE vocero_principal IS NOT NULL
                UNION
                SELECT vocero_suplente FROM fichas WHERE vocero_suplente IS NOT NULL
                UNION
                SELECT documento FROM voceros_enfoque
            ) LIMIT 20"; // Solo una muestra
    $stmt = $conn->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $r) {
        $email = !empty($r['correo']) ? $r['correo'] : "[VACÍO]";
        echo "Doc: {$r['documento']} | Nombre: {$r['nombre']} | Correo: $email\n";
    }
    
    $emptyCount = $conn->query("SELECT COUNT(*) FROM aprendices WHERE correo IS NULL OR correo = ''")->fetchColumn();
    echo "\nTotal de aprendices sin correo registrado: $emptyCount\n";

} catch (Exception $e) { echo $e->getMessage(); }
?>
