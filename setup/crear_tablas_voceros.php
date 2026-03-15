<?php
// Script para crear tablas de voceros y representantes
require_once 'api/config/Database.php';

try {
    echo "🔧 Creando tablas de voceros y representantes...\n";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/crear_tablas_voceros_representantes.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Conectar a la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Ejecutar el SQL
    $conn->exec($sql);
    
    echo "✅ Tablas creadas exitosamente\n";
    
    // Verificar las tablas
    $tables = ['voceros_enfoque', 'representantes'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "📊 Tabla '$table': {$result['count']} registros\n";
    }
    
    echo "\n🎉 ¡Configuración completada!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    
    // Si es error de conexión, mostrar instrucciones
    if (strpos($e->getMessage(), 'DATABASE_URL') !== false) {
        echo "\n🔧 Solución:\n";
        echo "1. Ejecuta: setup/configurar_postgresql.php\n";
        echo "2. O configura la variable de entorno DATABASE_URL\n";
        echo "3. Luego ejecuta este script nuevamente\n";
    }
}
?>
