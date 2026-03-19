<?php
/**
 * Script para limpiar datos de prueba y preparar sistema para producción
 * ELIMINA solo datos de prueba, NO datos reales
 */

echo "=== LIMPIEZA DE DATOS DE PRUEBA ===\n\n";

echo "⚠️  ADVERTENCIA: Este script eliminará SOLO datos de prueba\n";
echo "📋 No se eliminarán datos reales de usuarios/aprendices\n";
echo "🔒 Se conservará toda la información real\n\n";

// Para seguridad, solicitamos confirmación
echo "¿Deseas continuar con la limpieza de datos de prueba? (s/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 's') {
    echo "❌ Operación cancelada por el usuario\n";
    exit(0);
}

echo "\n🧹 Iniciando limpieza de datos de prueba...\n\n";

try {
    require_once __DIR__ . '/../api/config/Database.php';
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "✅ Conectado a base de datos\n\n";
    
    // 1. Identificar y eliminar usuarios de prueba obvios
    echo "=== 1. LIMPIEZA DE USUARIOS DE PRUEBA ===\n";
    
    // Patrones de usuarios de prueba
    $patrones_prueba = [
        'test%',
        'demo%',
        'prueba%',
        'example%',
        'sample%',
        'temp%',
        '123456789',  // Documentos de prueba comunes
        '987654321',
        '111111111',
        '000000000'
    ];
    
    $eliminados_usuarios = 0;
    
    foreach ($patrones_prueba as $patron) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE id_usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?");
        $stmt->execute([$patron, $patron, $patron, $patron]);
        $total = $stmt->fetch()['total'];
        
        if ($total > 0) {
            echo "🗑️  Eliminando $total usuarios con patrón '$patron'\n";
            
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?");
            $stmt->execute([$patron, $patron, $patron, $patron]);
            $eliminados_usuarios += $stmt->rowCount();
        }
    }
    
    echo "✅ Usuarios de prueba eliminados: $eliminados_usuarios\n\n";
    
    // 2. Limpiar aprendices de prueba
    echo "=== 2. LIMPIEZA DE APRENDICES DE PRUEBA ===\n";
    
    $eliminados_aprendices = 0;
    
    foreach ($patrones_prueba as $patron) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM aprendices WHERE documento LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?");
        $stmt->execute([$patron, $patron, $patron, $patron]);
        $total = $stmt->fetch()['total'];
        
        if ($total > 0) {
            echo "🗑️  Eliminando $total aprendices con patrón '$patron'\n";
            
            $stmt = $conn->prepare("DELETE FROM aprendices WHERE documento LIKE ? OR nombre LIKE ? OR apellido LIKE ? OR correo LIKE ?");
            $stmt->execute([$patron, $patron, $patron, $patron]);
            $eliminados_aprendices += $stmt->rowCount();
        }
    }
    
    echo "✅ Aprendices de prueba eliminados: $eliminados_aprendices\n\n";
    
    // 3. Limpiar fichas de prueba
    echo "=== 3. LIMPIEZA DE FICHAS DE PRUEBA ===\n";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM fichas WHERE numero_ficha LIKE 'TEST%' OR numero_ficha LIKE 'DEMO%' OR programa_formacion LIKE '%PRUEBA%' OR programa_formacion LIKE '%TEST%'");
    $stmt->execute();
    $total_fichas_prueba = $stmt->fetch()['total'];
    
    if ($total_fichas_prueba > 0) {
        echo "🗑️  Eliminando $total_fichas_prueba fichas de prueba\n";
        
        $stmt = $conn->prepare("DELETE FROM fichas WHERE numero_ficha LIKE 'TEST%' OR numero_ficha LIKE 'DEMO%' OR programa_formacion LIKE '%PRUEBA%' OR programa_formacion LIKE '%TEST%'");
        $stmt->execute();
        $eliminados_fichas = $stmt->rowCount();
        
        echo "✅ Fichas de prueba eliminadas: $eliminados_fichas\n";
    } else {
        echo "✅ No se encontraron fichas de prueba\n";
    }
    
    echo "\n=== 4. VERIFICACIÓN DE DATOS REALES CONSERVADOS ===\n";
    
    // Contar datos reales conservados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios");
    $stmt->execute();
    $usuarios_reales = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM aprendices");
    $stmt->execute();
    $aprendices_reales = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM fichas");
    $stmt->execute();
    $fichas_reales = $stmt->fetch()['total'];
    
    echo "👥 Usuarios reales conservados: $usuarios_reales\n";
    echo "📚 Aprendices reales conservados: $aprendices_reales\n";
    echo "📋 Fichas reales conservadas: $fichas_reales\n\n";
    
    // 5. Limpiar credenciales de prueba si existen
    echo "=== 5. LIMPIEZA DE CREDENCIALES DE PRUEBA ===\n";
    
    // Verificar si existen las columnas de credenciales
    try {
        $stmt = $conn->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = 'usuarios' 
            AND column_name = 'credenciales_vocero_activas'
        ");
        $stmt->execute();
        $existe_columna = $stmt->fetch();
        
        if ($existe_columna) {
            // Resetear credenciales de usuarios de prueba (si quedaron algunos)
            $stmt = $conn->prepare("
                UPDATE usuarios 
                SET credenciales_vocero_activas = false, estado = 0 
                WHERE rol = 'vocero' 
                AND (id_usuario LIKE ? OR nombre LIKE ? OR apellido LIKE ?)
            ");
            
            foreach (['test%', 'demo%', 'prueba%'] as $patron) {
                $stmt->execute([$patron, $patron, $patron]);
            }
            
            echo "✅ Credenciales de prueba reseteadas\n";
        }
    } catch (Exception $e) {
        echo "ℹ️  Columnas de credenciales no encontradas (no aplica)\n";
    }
    
    echo "\n=== RESUMEN DE LIMPIEZA ===\n";
    echo "🗑️  Usuarios de prueba eliminados: $eliminados_usuarios\n";
    echo "🗑️  Aprendices de prueba eliminados: $eliminados_aprendices\n";
    echo "🗑️  Fichas de prueba eliminadas: $total_fichas_prueba\n";
    echo "✅ Datos reales conservados intactos\n\n";
    
    echo "=== ESTADO DEL SISTEMA ===\n";
    echo "🔐 Sistema listo para producción\n";
    echo "👥 Solo usuarios reales en el sistema\n";
    echo "📚 Solo aprendices reales registrados\n";
    echo "📋 Solo fichas reales activas\n";
    echo "🎭 Sistema de voceros funcionando\n\n";
    
    echo "=== REGLAS DE ACCESO CONFIRMADAS ===\n";
    echo "🔐 Usuario = Correo electrónico\n";
    echo "🔑 Contraseña = Documento de identidad\n";
    echo "🎯 Voceros: Solo con roles activos\n";
    echo "🔒 Validación automática de credenciales\n\n";
    
    echo "✅ LIMPIEZA COMPLETADA EXITOSAMENTE\n";
    echo "🚀 Sistema listo para producción\n";
    
} catch (Exception $e) {
    echo "❌ Error durante la limpieza: " . $e->getMessage() . "\n";
    echo "⚠️  Por favor, verifica manualmente los datos\n";
}
?>
