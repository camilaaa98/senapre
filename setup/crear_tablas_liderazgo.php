<?php
// Script para crear tablas de liderazgo
// Ejecutar una sola vez: https://senapre.onrender.com/setup/crear_tablas_liderazgo.php

try {
    $dbPath = __DIR__ . '/../database/senapre.db';
    $conn = new PDO('sqlite:' . $dbPath);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Leer y ejecutar el SQL
    $sql = file_get_contents(__DIR__ . '/crear_tablas_liderazgo.sql');
    
    // Separar las sentencias SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>🔧 Creando tablas de liderazgo...</h2>\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                echo "<p style='color: green;'>✅ Ejecutado: " . htmlspecialchars(substr($statement, 0, 50)) . "...</p>\n";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Error: " . $e->getMessage() . "</p>\n";
            }
        }
    }
    
    // Verificar tablas creadas
    echo "<h3>📋 Tablas verificadas:</h3>\n";
    $tables = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('voceros_enfoque', 'representantes')")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "<p style='color: blue;'>📊 Tabla '$table' existe</p>\n";
        
        // Mostrar datos de ejemplo
        $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p style='color: gray;'>   Registros: $count</p>\n";
    }
    
    echo "<h2 style='color: green;'>✅ ¡Configuración completada!</h2>\n";
    echo "<p><a href='../liderazgo-poblacion.html' style='background: #39A900; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Población</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
}
?>
