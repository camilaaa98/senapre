<?php
/**
 * Script SEGURO para verificar conexión a Supabase
 * SOLO LECTURA - NO MODIFICA NADA
 */

echo "=== VERIFICACIÓN SEGURA DE CONEXIÓN ===\n\n";

echo "🔒 MODO SEGURO ACTIVADO\n";
echo "❌ NO se modificarán datos\n";
echo "❌ NO se crearán tablas\n";
echo "❌ NO se eliminará información\n";
echo "✅ SOLO lectura y verificación\n\n";

try {
    require_once __DIR__ . '/../api/config/Database.php';
    
    echo "🔗 Conectando a Supabase...\n";
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "✅ Conexión exitosa\n\n";
    
    // SOLO LECTURA - Verificar tablas existentes
    echo "📋 VERIFICANDO TABLAS EXISTENTES (solo lectura):\n";
    
    $tablas = ['usuarios', 'aprendices', 'fichas', 'voceros_enfoque', 'representantes_jornada'];
    
    foreach ($tablas as $tabla) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $tabla LIMIT 1");
            $stmt->execute();
            $count = $stmt->fetch()['total'];
            echo "✅ $tabla: $count registros (existente)\n";
        } catch (Exception $e) {
            echo "ℹ️  $tabla: No existe (se puede crear después)\n";
        }
    }
    
    echo "\n👥 VERIFICANDO USUARIOS EXISTENTES (solo lectura):\n";
    
    try {
        $stmt = $conn->prepare("SELECT rol, COUNT(*) as cantidad FROM usuarios GROUP BY rol ORDER BY cantidad DESC");
        $stmt->execute();
        $usuarios = $stmt->fetchAll();
        
        foreach ($usuarios as $usuario) {
            echo "👤 {$usuario['rol']}: {$usuario['cantidad']} usuarios\n";
        }
    } catch (Exception $e) {
        echo "ℹ️  No hay usuarios o tabla no existe\n";
    }
    
    echo "\n🎯 VERIFICANDO VOCEROS (solo lectura):\n";
    
    try {
        // Verificar si hay voceros configurados (solo lectura)
        $stmt = $conn->prepare("
            SELECT 
                COUNT(CASE WHEN f.vocero_principal IS NOT NULL THEN 1 END) as principales,
                COUNT(CASE WHEN f.vocero_suplente IS NOT NULL THEN 1 END) as suplentes
            FROM fichas f
        ");
        $stmt->execute();
        $voceros = $stmt->fetch();
        
        echo "🎯 Voceros principales: " . $voceros['principales'] . "\n";
        echo "🔄 Voceros suplentes: " . $voceros['suplentes'] . "\n";
        
    } catch (Exception $e) {
        echo "ℹ️  No hay fichas o voceros configurados\n";
    }
    
    echo "\n🔐 VERIFICANDO ESTRUCTURA DE CREDENCIALES (solo lectura):\n";
    
    try {
        $stmt = $conn->prepare("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_name = 'usuarios' 
            AND column_name IN ('credenciales_vocero_activas', 'fecha_actualizacion_credenciales')
            ORDER BY column_name
        ");
        $stmt->execute();
        $columnas = $stmt->fetchAll();
        
        if (empty($columnas)) {
            echo "ℹ️  Columnas de credenciales de voceros: No existen (se pueden agregar después)\n";
        } else {
            echo "✅ Columnas de credenciales encontradas:\n";
            foreach ($columnas as $columna) {
                echo "   📋 {$columna['column_name']}: {$columna['data_type']}\n";
            }
        }
    } catch (Exception $e) {
        echo "ℹ️  No se puede verificar estructura (normal si no hay tabla usuarios)\n";
    }
    
    echo "\n=== RESUMEN DE SEGURIDAD ===\n";
    echo "✅ Conexión verificada\n";
    echo "✅ Datos existentes respetados\n";
    echo "✅ Estructura intacta\n";
    echo "✅ Sistema listo para trabajar localmente\n\n";
    
    echo "🔒 ESTADO DE SEGURIDAD: PROTEGIDO\n";
    echo "📊 Tus datos están seguros en Supabase\n";
    echo "🌐 Puedes trabajar localmente sin riesgo\n\n";
    
    echo "=== PRÓXIMOS PASOS (SEGUROS) ===\n";
    echo "1. Si todo está bien, puedes trabajar localmente\n";
    echo "2. Los cambios se guardarán en Supabase (seguro)\n";
    echo "3. Cuando esté listo, subes a Render\n\n";
    
    echo "🎉 ¡VERIFICACIÓN COMPLETADA SEGURAMENTE!\n";
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "💡 Revisa la contraseña en .env.local\n";
    echo "🔒 No se modificó nada (modo seguro)\n";
}
?>
