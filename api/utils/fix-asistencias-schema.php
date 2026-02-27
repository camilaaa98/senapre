<?php
/**
 * Script para verificar y actualizar el esquema de la tabla asistencias
 */

require_once __DIR__ . '/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Verificando esquema de la tabla asistencias...\n\n";
    
    // Obtener información de la tabla actual
    $stmt = $conn->query("PRAGMA table_info(asistencias)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "❌ La tabla asistencias no existe.\n";
        echo "Creando tabla asistencias con el esquema correcto...\n\n";
        
        $conn->exec("CREATE TABLE asistencias (
            id_asistencia INTEGER PRIMARY KEY AUTOINCREMENT,
            documento_aprendiz TEXT NOT NULL,
            numero_ficha TEXT NOT NULL,
            fecha DATE NOT NULL,
            estado TEXT NOT NULL DEFAULT 'Presente',
            observaciones TEXT,
            id_instructor INTEGER,
            creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (documento_aprendiz) REFERENCES aprendices(documento),
            FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
        )");
        
        echo "✅ Tabla asistencias creada exitosamente.\n";
    } else {
        echo "Columnas actuales:\n";
        foreach ($columns as $col) {
            echo "  - {$col['name']} ({$col['type']})\n";
        }
        
        // Verificar si falta la columna numero_ficha
        $tieneNumeroFicha = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'numero_ficha') {
                $tieneNumeroFicha = true;
                break;
            }
        }
        
        if (!$tieneNumeroFicha) {
            echo "\n❌ Falta la columna 'numero_ficha'\n";
            echo "Reconstruyendo tabla con el esquema correcto...\n\n";
            
            // Respaldar datos existentes
            $conn->exec("ALTER TABLE asistencias RENAME TO asistencias_backup");
            
            // Crear nueva tabla con el esquema correcto
            $conn->exec("CREATE TABLE asistencias (
                id_asistencia INTEGER PRIMARY KEY AUTOINCREMENT,
                documento_aprendiz TEXT NOT NULL,
                numero_ficha TEXT NOT NULL,
                fecha DATE NOT NULL,
                estado TEXT NOT NULL DEFAULT 'Presente',
                observaciones TEXT,
                id_instructor INTEGER,
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (documento_aprendiz) REFERENCES aprendices(documento),
                FOREIGN KEY (numero_ficha) REFERENCES fichas(numero_ficha)
            )");
            
            // Intentar migrar datos si es posible (asumiendo que había id_ficha)
            try {
                $conn->exec("INSERT INTO asistencias 
                    (id_asistencia, documento_aprendiz, numero_ficha, fecha, estado, observaciones, id_instructor, creado_en)
                    SELECT 
                        a.id_asistencia, 
                        a.documento_aprendiz,
                        COALESCE(f.numero_ficha, '0000000'),
                        a.fecha,
                        a.estado,
                        a.observaciones,
                        a.id_instructor,
                        a.creado_en
                    FROM asistencias_backup a
                    LEFT JOIN fichas f ON a.id_ficha = f.id_ficha");
                
                echo "✅ Datos migrados exitosamente.\n";
                echo "✅ Tabla asistencias actualizada.\n";
                
                // Opcional: Eliminar backup
                // $conn->exec("DROP TABLE asistencias_backup");
                echo "\n⚠️  La tabla anterior se respaldó como 'asistencias_backup'.\n";
                
            } catch (Exception $e) {
                echo "⚠️  No se pudieron migrar los datos antiguos: " . $e->getMessage() . "\n";
                echo "✅ Tabla asistencias creada sin datos previos.\n";
            }
        } else {
            echo "\n✅ La tabla tiene todas las columnas necesarias.\n";
        }
    }
    
    echo "\n=== Esquema final ===\n";
    $stmt = $conn->query("PRAGMA table_info(asistencias)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['name']} ({$col['type']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
