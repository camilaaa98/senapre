<?php
/**
 * Script de migración: Reestructuración de Población a 6 Tablas Independientes
 * Tablas: Mujer, indígena, narp, campesino, lgbtiq, discapacidad
 */

require_once 'api/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // 1. Crear las 6 tablas individuales
    $tables = [
        'Mujer',
        'indígena',
        'narp',
        'campesino',
        'lgbtiq',
        'discapacidad'
    ];

    foreach ($tables as $table) {
        $conn->exec("CREATE TABLE IF NOT EXISTS `$table` (
            documento VARCHAR(20) PRIMARY KEY,
            FOREIGN KEY (documento) REFERENCES aprendices(documento) ON DELETE CASCADE
        )");
        echo "Tabla `$table` verificada/creada.\n";
    }

    // 2. Migrar datos desde TipoPoblacion (si existe)
    // Mapeo select -> tabla nueva
    $mapping = [
        'mujer' => 'Mujer',
        'indigena' => 'indígena',
        'afro' => 'narp', // Afro se unifica en NARP
        'campesina' => 'campesino',
        'nap' => 'narp', // NAP se unifica en NARP
        'lgbti' => 'lgbtiq',
        'discapacidad' => 'discapacidad'
        // 'victima' se descarta según la nueva lista de 6
    ];

    $check = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='TipoPoblacion'")->fetch();
    
    if ($check) {
        $stmt = $conn->query("SELECT * FROM TipoPoblacion");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            $doc = $row['documento'];
            
            foreach ($mapping as $col => $table) {
                if (isset($row[$col]) && $row[$col] == 1) {
                    $ins = $conn->prepare("INSERT OR IGNORE INTO `$table` (documento) VALUES (:doc)");
                    $ins->execute([':doc' => $doc]);
                }
            }
        }
        echo "Datos migrados desde TipoPoblacion.\n";
        
        // 3. Eliminar tabla obsoleta
        $conn->exec("DROP TABLE TipoPoblacion");
        echo "Tabla TipoPoblacion eliminada.\n";
    } else {
        echo "No se encontró la tabla TipoPoblacion, saltando migración de datos.\n";
    }

    // 4. Limpiar columna obsoleta en aprendices (Opcional, SQLite no soporta DROP COLUMN fácilmente sin recrear)
    // Por ahora la dejamos pero la API dejará de usarla.

    echo "MIGRACIÓN COMPLETADA EXITOSAMENTE.\n";

} catch (Exception $e) {
    echo "ERROR EN LA MIGRACIÓN: " . $e->getMessage() . "\n";
}
