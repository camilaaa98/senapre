<?php
/**
 * Script de migración para el módulo de Liderazgo Estudiantil en PostgreSQL (Render)
 * Ejecutar una sola vez para crear las tablas necesarias.
 */
require_once __DIR__ . '/api/config/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Iniciando migración de base de datos PostgreSQL...\n";

    $queries = [
        "CREATE TABLE IF NOT EXISTS bienestar_reuniones (
            id SERIAL PRIMARY KEY,
            titulo VARCHAR(255) NOT NULL,
            fecha DATE NOT NULL,
            hora TIME,
            lugar VARCHAR(255),
            estado VARCHAR(50) DEFAULT 'pendiente'
        )",
        "CREATE TABLE IF NOT EXISTS bienestar_asistencia (
            id SERIAL PRIMARY KEY,
            id_reunion INTEGER REFERENCES bienestar_reuniones(id),
            id_aprendiz VARCHAR(50),
            estado VARCHAR(50), -- asistio, ausente, justificado
            nota TEXT,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS bienestar_excusas (
            id SERIAL PRIMARY KEY,
            id_asistencia INTEGER REFERENCES bienestar_asistencia(id),
            motivo TEXT,
            estado_excusa VARCHAR(50) DEFAULT 'pendiente'
        )",
        "CREATE TABLE IF NOT EXISTS voceros_enfoque (
            id SERIAL PRIMARY KEY,
            documento VARCHAR(50) REFERENCES aprendices(documento),
            tipo_poblacion VARCHAR(100)
        )",
        "CREATE TABLE IF NOT EXISTS representantes_jornada (
            id SERIAL PRIMARY KEY,
            documento VARCHAR(50) REFERENCES aprendices(documento),
            jornada VARCHAR(100)
        )"
    ];

    foreach ($queries as $sql) {
        $db->exec($sql);
        echo "Ejecutado: " . substr($sql, 0, 50) . "...\n";
    }

    echo "\nMigración completada con éxito.\n";
    echo "RECUERDA ELIMINAR ESTE ARCHIVO POR SEGURIDAD.";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
