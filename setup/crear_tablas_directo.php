<?php
// Crear tablas directamente
try {
    $dbPath = __DIR__ . '/../database/Asistnet.db';
    $conn = new PDO('sqlite:' . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>🔧 Creando tablas de liderazgo en Asistnet.db...</h2>\n";
    
    // 1. Crear tabla voceros_enfoque
    $sql1 = "CREATE TABLE IF NOT EXISTS voceros_enfoque (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        tipo_poblacion TEXT NOT NULL,
        documento TEXT NOT NULL,
        fecha_asignacion DATE DEFAULT CURRENT_DATE,
        FOREIGN KEY (documento) REFERENCES aprendices(documento),
        UNIQUE(tipo_poblacion)
    )";
    
    $conn->exec($sql1);
    echo "<p style='color: green;'>✅ Tabla voceros_enfoque creada</p>\n";
    
    // 2. Crear tabla representantes
    $sql2 = "CREATE TABLE IF NOT EXISTS representantes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        documento TEXT NOT NULL,
        tipo_jornada TEXT NOT NULL,
        fecha_asignacion DATE DEFAULT CURRENT_DATE,
        FOREIGN KEY (documento) REFERENCES aprendices(documento),
        UNIQUE(tipo_jornada)
    )";
    
    $conn->exec($sql2);
    echo "<p style='color: green;'>✅ Tabla representantes creada</p>\n";
    
    // 3. Verificar si existen aprendices para insertar datos de ejemplo
    $checkAprendices = $conn->query("SELECT COUNT(*) FROM aprendices WHERE documento IN ('1056930328', '1117506963') LIMIT 2")->fetchColumn();
    
    if ($checkAprendices > 0) {
        // 3. Insertar datos de ejemplo
        $sql3 = "INSERT OR IGNORE INTO voceros_enfoque (tipo_poblacion, documento) VALUES 
        ('mujer', '1056930328'),
        ('indigena', '1117506963')";
        
        $conn->exec($sql3);
        echo "<p style='color: blue;'>📝 Datos de ejemplo insertados en voceros_enfoque</p>\n";
        
        $sql4 = "INSERT OR IGNORE INTO representantes (documento, tipo_jornada) VALUES 
        ('1056930328', 'diurna'),
        ('1117506963', 'mixta')";
        
        $conn->exec($sql4);
        echo "<p style='color: blue;'>📝 Datos de ejemplo insertados en representantes</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No se insertaron datos de ejemplo (no se encontraron aprendices con documentos 1056930328, 1117506963)</p>\n";
    }
    
    // 4. Verificar
    echo "<h3>📋 Verificación:</h3>\n";
    
    $voceros = $conn->query("SELECT * FROM voceros_enfoque")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>📊 Voceros: " . count($voceros) . " registros</p>\n";
    foreach ($voceros as $v) {
        echo "<p style='color: gray;'>   - {$v['tipo_poblacion']}: {$v['documento']}</p>\n";
    }
    
    $reps = $conn->query("SELECT * FROM representantes")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color: green;'>👥 Representantes: " . count($reps) . " registros</p>\n";
    foreach ($reps as $r) {
        echo "<p style='color: gray;'>   - {$r['tipo_jornada']}: {$r['documento']}</p>\n";
    }
    
    echo "<h2 style='color: green;'>✅ ¡Tablas creadas exitosamente!</h2>\n";
    echo "<p><a href='../liderazgo-poblacion.html' style='background: #39A900; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Población</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
