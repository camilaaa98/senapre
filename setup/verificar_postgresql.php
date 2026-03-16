<?php
// Verificar base de datos PostgreSQL Senapre
require_once __DIR__ . '/../api/config/Database.php';

try {
    // Forzar conexión PostgreSQL
    $database_url = getenv('DATABASE_URL');
    
    if (!$database_url) {
        // Para desarrollo local, simular variables de entorno
        putenv('DATABASE_URL=postgresql://user:password@localhost:5432/Senapre');
        $database_url = getenv('DATABASE_URL');
    }
    
    echo "<h2>🔍 Verificando Base de Datos PostgreSQL</h2>\n";
    echo "<p><strong>URL:</strong> " . str_replace('password', '***', $database_url) . "</p>\n";
    
    // Intentar conexión PostgreSQL
    $parsed = parse_url($database_url);
    $host    = $parsed['host'];
    $port    = $parsed['port'] ?? 5432;
    $db      = ltrim($parsed['path'], '/');
    $user    = $parsed['user'];
    $pass    = $parsed['pass'];
    
    echo "<p><strong>Host:</strong> $host</p>\n";
    echo "<p><strong>Puerto:</strong> $port</p>\n";
    echo "<p><strong>Base de Datos:</strong> $db</p>\n";
    echo "<p><strong>Usuario:</strong> $user</p>\n";
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ]);
    
    echo "<h3 style='color: green;'>✅ Conexión exitosa a PostgreSQL</h3>\n";
    
    // Verificar tablas existentes
    echo "<h3>📋 Tablas en la base de datos '$db':</h3>\n";
    
    $tables = $conn->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "<p style='color: blue;'>📊 $table</p>\n";
            
            // Contar registros
            try {
                $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p style='color: gray; margin-left: 20px;'>   Registros: $count</p>\n";
            } catch (Exception $e) {
                echo "<p style='color: orange; margin-left: 20px;'>   Error al contar: " . $e->getMessage() . "</p>\n";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No se encontraron tablas en la base de datos</p>\n";
    }
    
    // Verificar tablas específicas de liderazgo
    echo "<h3>🔍 Verificando tablas de liderazgo:</h3>\n";
    
    $liderazgoTables = ['voceros_enfoque', 'representantes'];
    foreach ($liderazgoTables as $table) {
        if (in_array($table, $tables)) {
            echo "<p style='color: green;'>✅ Tabla '$table' existe</p>\n";
            $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p style='color: gray; margin-left: 20px;'>   Registros: $count</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Tabla '$table' no existe</p>\n";
        }
    }
    
    // Verificar si hay aprendices
    if (in_array('aprendices', $tables)) {
        echo "<h3>👥 Verificando tabla aprendices:</h3>\n";
        $countAprendices = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
        echo "<p style='color: blue;'>📊 Total aprendices: $countAprendices</p>\n";
        
        // Mostrar algunos datos de ejemplo
        if ($countAprendices > 0) {
            $sample = $conn->query("SELECT documento, nombre, apellido, estado FROM aprendices LIMIT 5")->fetchAll();
            echo "<h4>Ejemplo de aprendices:</h4>\n";
            foreach ($sample as $a) {
                echo "<p style='color: gray; margin-left: 20px;'>{$a['documento']} - {$a['nombre']} {$a['apellido']} ({$a['estado']})</p>\n";
            }
        }
    }
    
    echo "<h2 style='color: green;'>✅ Verificación completada</h2>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error de conexión:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    
    // Intentar con SQLite local como fallback
    echo "<h3>🔄 Intentando con SQLite local:</h3>\n";
    try {
        $dbPath = __DIR__ . '/../database/Asistnet.db';
        $connLocal = new PDO("sqlite:$dbPath");
        $connLocal->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tablesLocal = $connLocal->query("
            SELECT name FROM sqlite_master 
            WHERE type='table' 
            ORDER BY name
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p style='color: green;'>✅ Conectado a SQLite local</p>\n";
        echo "<h4>Tablas locales:</h4>\n";
        
        foreach ($tablesLocal as $table) {
            $count = $connLocal->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<p style='color: blue;'>📊 $table ($count registros)</p>\n";
        }
        
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ Error con SQLite: " . $e2->getMessage() . "</p>\n";
    }
}
?>
