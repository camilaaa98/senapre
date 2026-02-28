<?php
require_once __DIR__ . '/api/config/Database.php';

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "Verificando/Creando tablas en PostgreSQL...\n";

    // Tabla administrador
    $conn->exec("CREATE TABLE IF NOT EXISTS administrador (
        id_usuario INTEGER PRIMARY KEY,
        nombres TEXT NOT NULL,
        apellidos TEXT NOT NULL,
        correo TEXT NOT NULL,
        telefono TEXT,
        estado TEXT DEFAULT 'activo',
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    )");
    echo "Tabla 'administrador' OK.\n";

    // Tabla instructores
    $conn->exec("CREATE TABLE IF NOT EXISTS instructores (
        id_usuario INTEGER PRIMARY KEY,
        nombres TEXT NOT NULL,
        apellidos TEXT NOT NULL,
        correo TEXT NOT NULL,
        telefono TEXT,
        estado TEXT DEFAULT 'activo',
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    )");
    echo "Tabla 'instructores' OK.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
