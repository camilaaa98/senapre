<?php
// Script para corregir todos los problemas de la página de población
require_once 'api/config/Database.php';

echo "🔧 INICIANDO CORRECCIÓN COMPLETA DE POBLACIÓN...\n\n";

try {
    // 1. Verificar conexión a la base de datos
    echo "1. Verificando conexión a PostgreSQL...\n";
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "✅ Conexión establecida\n\n";
    
    // 2. Crear tablas necesarias
    echo "2. Creando tablas de voceros y representantes...\n";
    
    // Tabla voceros_enfoque
    $sql = "CREATE TABLE IF NOT EXISTS voceros_enfoque (
        id SERIAL PRIMARY KEY,
        tipo_poblacion VARCHAR(50) NOT NULL UNIQUE,
        documento VARCHAR(20) NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "✅ Tabla voceros_enfoque creada\n";
    
    // Tabla representantes
    $sql = "CREATE TABLE IF NOT EXISTS representantes (
        id SERIAL PRIMARY KEY,
        tipo_jornada VARCHAR(20) NOT NULL UNIQUE,
        documento VARCHAR(20) NOT NULL,
        fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "✅ Tabla representantes creada\n";
    
    // 3. Crear índices
    echo "\n3. Creando índices para mejor rendimiento...\n";
    $indices = [
        "CREATE INDEX IF NOT EXISTS idx_voceros_poblacion ON voceros_enfoque(tipo_poblacion)",
        "CREATE INDEX IF NOT EXISTS idx_voceros_documento ON voceros_enfoque(documento)",
        "CREATE INDEX IF NOT EXISTS idx_representantes_jornada ON representantes(tipo_jornada)",
        "CREATE INDEX IF NOT EXISTS idx_representantes_documento ON representantes(documento)"
    ];
    
    foreach ($indices as $index) {
        $conn->exec($index);
    }
    echo "✅ Índices creados\n";
    
    // 4. Insertar datos de ejemplo
    echo "\n4. Insertando datos de ejemplo...\n";
    
    // Voceros de ejemplo
    $sql = "INSERT INTO voceros_enfoque (tipo_poblacion, documento) VALUES
        ('mujer', '1056930328'),
        ('indigena', '1117506963'),
        ('narp', '1234567890'),
        ('campesino', '0987654321'),
        ('lgbtiq', '1122334455'),
        ('discapacidad', '5566778899')
    ON CONFLICT (tipo_poblacion) DO NOTHING";
    $conn->exec($sql);
    echo "✅ Voceros de ejemplo insertados\n";
    
    // Representantes de ejemplo
    $sql = "INSERT INTO representantes (tipo_jornada, documento) VALUES
        ('diurna', '1056930328'),
        ('mixta', '1117506963')
    ON CONFLICT (tipo_jornada) DO NOTHING";
    $conn->exec($sql);
    echo "✅ Representantes de ejemplo insertados\n";
    
    // 5. Verificar datos
    echo "\n5. Verificando datos insertados...\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM voceros_enfoque");
    $result = $stmt->fetch();
    echo "📊 Voceros de enfoque: {$result['total']} registros\n";
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM representantes");
    $result = $stmt->fetch();
    echo "📊 Representantes: {$result['total']} registros\n";
    
    // 6. Verificar aprendices con estado LECTIVA
    echo "\n6. Verificando aprendices en estado LECTIVA...\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM aprendices WHERE estado = 'LECTIVA'");
    $result = $stmt->fetch();
    echo "📊 Aprendices LECTIVA: {$result['total']} registros\n";
    
    // 7. Mostrar algunos aprendices de ejemplo
    $stmt = $conn->query("SELECT documento, nombre, apellido, tipo_poblacion 
                         FROM aprendices 
                         WHERE estado = 'LECTIVA' 
                         AND tipo_poblacion IS NOT NULL 
                         LIMIT 5");
    $aprendices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n7. Ejemplo de aprendices con población:\n";
    foreach ($aprendices as $a) {
        echo "- {$a['documento']}: {$a['nombre']} {$a['apellido']} ({$a['tipo_poblacion']})\n";
    }
    
    echo "\n🎉 ¡CORRECCIÓN COMPLETADA CON ÉXITO!\n";
    echo "\n📋 RESUMEN:\n";
    echo "✅ Base de datos conectada\n";
    echo "✅ Tablas creadas\n";
    echo "✅ Índices optimizados\n";
    echo "✅ Datos de ejemplo insertados\n";
    echo "✅ Sistema listo para funcionar\n";
    
    echo "\n🌐 Ahora puedes acceder a:\n";
    echo "http://localhost/senapre/liderazgo-poblacion.html\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n";
    
    if (strpos($e->getMessage(), 'DATABASE_URL') !== false) {
        echo "\n🔧 SOLUCIÓN:\n";
        echo "1. Ejecuta: setup/configurar_postgresql.php\n";
        echo "2. Configura la variable de entorno DATABASE_URL\n";
        echo "3. Luego ejecuta este script nuevamente\n";
    }
}
?>
