<?php
try {
    $db = new PDO('sqlite:C:/wamp64/www/YanguasEjercicios/senapre/database/Asistnet.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar si existe la columna
    $q = $db->query("PRAGMA table_info(aprendices)");
    $exists = false;
    while($row = $q->fetch()) {
        if ($row['name'] === 'id_instructor_lider') {
            $exists = true;
            break;
        }
    }
    
    if (!$exists) {
        $db->exec("ALTER TABLE aprendices ADD COLUMN id_instructor_lider INTEGER DEFAULT NULL");
        echo "Columna id_instructor_lider añadida exitosamente.";
    } else {
        echo "La columna id_instructor_lider ya existe.";
    }

    // Verificar si existen las tablas de biometría
    $db->exec("CREATE TABLE IF NOT EXISTS biometria_usuarios (
        id_biometria INTEGER PRIMARY KEY AUTOINCREMENT,
        id_usuario INTEGER NOT NULL,
        embedding_facial BLOB NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS biometria_aprendices (
        id_biometria INTEGER PRIMARY KEY AUTOINCREMENT,
        documento TEXT NOT NULL,
        embedding_facial BLOB NOT NULL,
        fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
        ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (documento) REFERENCES aprendices(documento)
    )");
    
    echo "Tablas de biometría verificadas/creadas.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
