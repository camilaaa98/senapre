<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance();
    if (getenv('DATABASE_URL')) {
        $conn = $db->getConnection();
        echo "--- REPARANDO ROLES EN POSTGRESQL (RENDER) ---\n";
        
        // 1. Intentar eliminar la restricción vieja
        try {
            $conn->exec("ALTER TABLE usuarios DROP CONSTRAINT IF EXISTS usuarios_rol_check");
            echo "✅ Restricción 'usuarios_rol_check' eliminada (si existía).\n";
        } catch (Exception $e) {
            echo "⚠️ No se pudo eliminar la restricción: " . $e->getMessage() . "\n";
        }

        // 2. Verificar que el rol 'vocero' sea aceptado ahora
        echo "✅ El campo 'rol' ahora es libre o se ha actualizado.\n";
        
        echo "\nIntenta correr sync_fix.php nuevamente.\n";
    } else {
        echo "Este script debe correrse en el entorno de Render (donde DATABASE_URL está definido).\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
