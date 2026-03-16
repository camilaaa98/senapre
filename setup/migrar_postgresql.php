<?php
// Script para migrar tablas de liderazgo a PostgreSQL
require_once __DIR__ . '/../api/config/Database.php';

try {
    echo "<h2>🔧 Migrando tablas de liderazgo a PostgreSQL</h2>\n";
    
    // Forzar conexión PostgreSQL
    $database_url = getenv('DATABASE_URL');
    
    if (!$database_url) {
        // Para desarrollo local, configurar variables
        // AJUSTAR ESTOS VALORES SEGÚN TU CONFIGURACIÓN LOCAL
        putenv('DATABASE_URL=postgresql://postgres:password@localhost:5432/Senapre');
        $database_url = getenv('DATABASE_URL');
    }
    
    echo "<p><strong>URL:</strong> " . str_replace('password', '***', $database_url) . "</p>\n";
    
    // Conectar a PostgreSQL
    $parsed = parse_url($database_url);
    $host    = $parsed['host'];
    $port    = $parsed['port'] ?? 5432;
    $db      = ltrim($parsed['path'], '/');
    $user    = $parsed['user'];
    $pass    = $parsed['pass'];
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=prefer";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ]);
    
    echo "<h3 style='color: green;'>✅ Conexión exitosa a PostgreSQL</h3>\n";
    echo "<p><strong>Base de datos:</strong> $db</p>\n";
    echo "<p><strong>Host:</strong> $host:$port</p>\n";
    
    // Leer y ejecutar el SQL
    $sqlFile = __DIR__ . '/crear_tablas_postgresql.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Separar las sentencias SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h3>🔄 Ejecutando sentencias SQL:</h3>\n";
    
    foreach ($statements as $i => $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $conn->exec($statement);
                echo "<p style='color: green;'>✅ Sentencia " . ($i + 1) . ": Ejecutada correctamente</p>\n";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠️ Sentencia " . ($i + 1) . ": " . $e->getMessage() . "</p>\n";
            }
        }
    }
    
    // Verificar tablas creadas
    echo "<h3>📋 Verificando tablas creadas:</h3>\n";
    
    $tables = $conn->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name IN ('voceros_enfoque', 'representantes')
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "<p style='color: blue;'>📊 Tabla '$table' existe</p>\n";
        
        // Contar registros
        $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<p style='color: gray; margin-left: 20px;'>   Registros: $count</p>\n";
        
        // Mostrar estructura
        if ($count > 0) {
            $sample = $conn->query("SELECT * FROM $table LIMIT 3")->fetchAll();
            echo "<p style='color: gray; margin-left: 20px;'>   Estructura de ejemplo:</p>\n";
            foreach ($sample as $row) {
                echo "<p style='color: gray; margin-left: 40px; font-family: monospace; font-size: 0.8rem;'>";
                echo json_encode($row, JSON_UNESCAPED_UNICODE);
                echo "</p>\n";
            }
        }
    }
    
    // Verificar si hay aprendices para relacionar
    echo "<h3>👥 Verificando tabla aprendices:</h3>\n";
    try {
        $countAprendices = $conn->query("SELECT COUNT(*) FROM aprendices")->fetchColumn();
        echo "<p style='color: blue;'>📊 Total aprendices: $countAprendices</p>\n";
        
        if ($countAprendices > 0) {
            // Verificar si existen los documentos de ejemplo
            $checkDocs = $conn->query("
                SELECT COUNT(*) FROM aprendices 
                WHERE documento IN ('1056930328', '1117506963')
            ")->fetchColumn();
            
            if ($checkDocs > 0) {
                echo "<p style='color: green;'>✅ Aprendices de ejemplo encontrados para relacionar</p>\n";
            } else {
                echo "<p style='color: orange;'>⚠️ Aprendices de ejemplo no encontrados. Los datos de ejemplo no se insertaron.</p>\n";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠️ No se pudo verificar tabla aprendices: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h2 style='color: green;'>✅ Migración completada exitosamente</h2>\n";
    echo "<p style='color: blue;'>📝 NOTA: El sistema ahora está configurado para usar PostgreSQL exclusivamente.</p>\n";
    echo "<p><a href='../liderazgo-poblacion.html' style='background: #39A900; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Población</a></p>\n";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error en migración:</h2>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    
    echo "<h3>🔧 Configuración requerida:</h3>\n";
    echo "<p>1. Asegúrate de que PostgreSQL esté corriendo</p>\n";
    echo "<p>2. Configura la variable de entorno DATABASE_URL</p>\n";
    echo "<p>3. O ajusta los valores en este script para desarrollo local</p>\n";
    echo "<p>Ejemplo: postgresql://usuario:password@localhost:5432/Senapre</p>\n";
}
?>
