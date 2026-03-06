<?php
/**
 * Script de Migración: Tablas de Biometría (Versión Simplificada)
 * Crea las tablas necesarias para almacenar embeddings faciales
 */

require_once __DIR__ . '/../api/config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== MIGRACIÓN: TABLAS DE BIOMETRÍA ===\n\n";
    
    // 1. Tabla de biometría para usuarios (instructores/administradores)
    echo "Creando tabla biometria_usuarios...\n";
    $conn->exec("DROP TABLE IF EXISTS biometria_usuarios");
    $conn->exec("CREATE TABLE biometria_usuarios (
        id_biometria INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario TEXT NOT NULL UNIQUE,
        embedding_facial BLOB NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Tabla biometria_usuarios creada\n\n";
    
    // 2. Tabla de biometría para aprendices
    echo "Creando tabla biometria_aprendices...\n";
    $conn->exec("DROP TABLE IF EXISTS biometria_aprendices");
    $conn->exec("CREATE TABLE biometria_aprendices (
        id_biometria INTEGER PRIMARY KEY AUTOINCREMENT,
        documento TEXT NOT NULL UNIQUE,
        embedding_facial BLOB NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✓ Tabla biometria_aprendices creada\n\n";
    
    // 3. Agregar índices para mejorar rendimiento
    echo "Creando índices...\n";
    $conn->exec("CREATE INDEX idx_biometria_usuarios_id ON biometria_usuarios(id_usuario)");
    $conn->exec("CREATE INDEX idx_biometria_aprendices_doc ON biometria_aprendices(documento)");
    echo "✓ Índices creados\n\n";
    
    echo "=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
