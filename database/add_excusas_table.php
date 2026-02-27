<?php
/**
 * Script de Migración: Sistema de Excusas
 * Crea tabla para gestionar excusas de asistencia
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== MIGRACIÓN: TABLA DE EXCUSAS ===\n\n";
    
    // Crear tabla de excusas
    echo "Creando tabla excusas_asistencia...\n";
    $conn->exec("DROP TABLE IF EXISTS excusas_asistencia");
    $conn->exec("CREATE TABLE excusas_asistencia (
        id_excusa INTEGER PRIMARY KEY AUTOINCREMENT,
        documento TEXT NOT NULL,
        numero_ficha TEXT NOT NULL,
        fecha_falta DATE NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        motivo TEXT NOT NULL,
        archivo_adjunto TEXT,
        estado TEXT DEFAULT 'PENDIENTE',
        evaluado_por TEXT,
        fecha_evaluacion DATETIME,
        observaciones_admin TEXT,
        UNIQUE(documento, numero_ficha, fecha_falta)
    )");
    echo "✓ Tabla excusas_asistencia creada\n\n";
    
    // Crear índices
    echo "Creando índices...\n";
    $conn->exec("CREATE INDEX idx_excusas_documento ON excusas_asistencia(documento)");
    $conn->exec("CREATE INDEX idx_excusas_estado ON excusas_asistencia(estado)");
    $conn->exec("CREATE INDEX idx_excusas_fecha ON excusas_asistencia(fecha_falta)");
    echo "✓ Índices creados\n\n";
    
    // Verificar tabla de asistencias y agregar campos necesarios
    echo "Verificando tabla asistencias...\n";
    try {
        $stmt = $conn->query("PRAGMA table_info(asistencias)");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        
        if (!empty($cols)) {
            echo "✓ Tabla asistencias existe\n";
            
            // Verificar si tiene columna hora
            if (!in_array('hora', $cols)) {
                echo "Agregando columna 'hora' a asistencias...\n";
                $conn->exec("ALTER TABLE asistencias ADD COLUMN hora TIME");
                echo "✓ Columna 'hora' agregada\n";
            }
            
            // Crear índice único para prevenir duplicados
            try {
                $conn->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_asistencia_unica 
                            ON asistencias(documento, id_usuario, fecha)");
                echo "✓ Índice único creado\n";
            } catch (Exception $e) {
                echo "⚠ Índice único ya existe\n";
            }
        } else {
            echo "⚠ Tabla asistencias no existe, se creará al usarse\n";
        }
    } catch (Exception $e) {
        echo "⚠ Error verificando asistencias: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
