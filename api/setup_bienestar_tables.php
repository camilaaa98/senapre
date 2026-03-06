<?php
require_once __DIR__ . '/config/Database.php';

try {
    $conn = Database::getInstance()->getConnection();
    
    // Tabla para responsables de áreas
    $conn->exec("CREATE TABLE IF NOT EXISTS area_responsables (
        id SERIAL PRIMARY KEY,
        id_usuario INTEGER,
        area VARCHAR(50),
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabla para reuniones de bienestar (Liderazgo)
    $conn->exec("CREATE TABLE IF NOT EXISTS bienestar_reuniones (
        id SERIAL PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL,
        fecha DATE NOT NULL,
        hora TIME DEFAULT '08:00',
        lugar VARCHAR(255),
        estado VARCHAR(20) DEFAULT 'programada',
        id_creador INTEGER,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabla para asistencia a reuniones
    $conn->exec("CREATE TABLE IF NOT EXISTS bienestar_asistencia (
        id SERIAL PRIMARY KEY,
        id_reunion INTEGER REFERENCES bienestar_reuniones(id),
        id_aprendiz VARCHAR(20),
        estado VARCHAR(20) DEFAULT 'ausente',
        nota TEXT,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo json_encode(['success' => true, 'message' => 'Tablas de Bienestar y Liderazgo verificadas']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
