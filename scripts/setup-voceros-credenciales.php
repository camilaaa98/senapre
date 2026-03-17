<?php
/**
 * Script para configurar credenciales iniciales de voceros
 * Ejecutar una sola vez para setup inicial
 */
require_once __DIR__ . '/../api/config/Database.php';

echo "=== CONFIGURACIÓN DE CREDENCIALES DE VOCEROS ===\n\n";

try {
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    // 1. Agregar columnas si no existen
    echo "1. Verificando columnas en tabla usuarios...\n";
    
    try {
        $conn->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS credenciales_vocero_activas BOOLEAN DEFAULT FALSE");
        $conn->exec("ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS fecha_actualizacion_credenciales TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "   ✅ Columnas verificadas/creadas\n";
    } catch (Exception $e) {
        echo "   ⚠️  Error al crear columnas: " . $e->getMessage() . "\n";
    }
    
    // 2. Obtener todos los voceros
    echo "\n2. Analizando voceros registrados...\n";
    
    $sql = "
        SELECT 
            u.id_usuario as documento,
            u.nombre,
            u.apellido,
            u.correo,
            u.estado as estado_actual,
            -- Contar roles activos
            (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_principal) = u.id_usuario) as es_principal,
            (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_suplente) = u.id_usuario) as es_suplente,
            (SELECT COUNT(*) FROM voceros_enfoque WHERE TRIM(documento) = u.id_usuario) as es_enfoque,
            (SELECT COUNT(*) FROM representantes_jornada WHERE TRIM(documento) = u.id_usuario) as es_representante
        FROM usuarios u
        WHERE u.rol = 'vocero'
        ORDER BY u.nombre, u.apellido
    ";
    
    $stmt = $conn->query($sql);
    $voceros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   📊 Total voceros encontrados: " . count($voceros) . "\n\n";
    
    // 3. Procesar cada vocero
    $actualizados = 0;
    $activados = 0;
    $desactivados = 0;
    
    foreach ($voceros as $vocero) {
        $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                              $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
        
        $documento = $vocero['documento'];
        $password = $documento; // La contraseña es el documento
        
        // Actualizar credenciales
        $updateSql = "
            UPDATE usuarios 
            SET estado = :estado, 
                password_hash = :password,
                credenciales_vocero_activas = :tiene_credenciales,
                fecha_actualizacion_credenciales = CURRENT_TIMESTAMP
            WHERE id_usuario = :documento
        ";
        
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->execute([
            ':estado' => $tieneRolActivo ? 1 : 0,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':tiene_credenciales' => $tieneRolActivo,
            ':documento' => $documento
        ]);
        
        $actualizados++;
        
        if ($tieneRolActivo) {
            $activados++;
            echo "   ✅ ACTIVADO: {$vocero['nombre']} {$vocero['apellido']} ({$documento})\n";
            echo "      Roles: ";
            $roles = [];
            if ($vocero['es_principal'] > 0) $roles[] = 'Principal';
            if ($vocero['es_suplente'] > 0) $roles[] = 'Suplente';
            if ($vocero['es_enfoque'] > 0) $roles[] = 'Enfoque';
            if ($vocero['es_representante'] > 0) $roles[] = 'Representante';
            echo empty($roles) ? 'Ninguno' : implode(', ', $roles);
            echo "\n";
            echo "      Usuario: {$documento}\n";
            echo "      Contraseña: {$password}\n\n";
        } else {
            $desactivados++;
            echo "   ❌ DESACTIVADO: {$vocero['nombre']} {$vocero['apellido']} ({$documento})\n";
            echo "      Motivo: Sin roles activos asignados\n\n";
        }
    }
    
    // 4. Resumen final
    echo "=== RESUMEN DE CONFIGURACIÓN ===\n";
    echo "📊 Total voceros procesados: {$actualizados}\n";
    echo "✅ Credenciales activadas: {$activados}\n";
    echo "❌ Credenciales desactivadas: {$desactivados}\n\n";
    
    // 5. Generar archivo de credenciales para referencia
    $credencialesFile = __DIR__ . '/voceros_credenciales_' . date('Y-m-d_H-i-s') . '.txt';
    $contenido = "CREDENCIALES DE VOCEROS - " . date('Y-m-d H:i:s') . "\n";
    $contenido .= "=====================================\n\n";
    
    foreach ($voceros as $vocero) {
        $tieneRolActivo = ($vocero['es_principal'] > 0 || $vocero['es_suplente'] > 0 || 
                              $vocero['es_enfoque'] > 0 || $vocero['es_representante'] > 0);
        
        if ($tieneRolActivo) {
            $contenido .= "NOMBRE: {$vocero['nombre']} {$vocero['apellido']}\n";
            $contenido .= "DOCUMENTO: {$vocero['documento']}\n";
            $contenido .= "CORREO: {$vocero['correo']}\n";
            $contenido .= "CONTRASEÑA: {$vocero['documento']}\n";
            $contenido .= "ESTADO: ACTIVO\n";
            
            $roles = [];
            if ($vocero['es_principal'] > 0) $roles[] = 'Principal';
            if ($vocero['es_suplente'] > 0) $roles[] = 'Suplente';
            if ($vocero['es_enfoque'] > 0) $roles[] = 'Enfoque';
            if ($vocero['es_representante'] > 0) $roles[] = 'Representante';
            
            $contenido .= "ROLES: " . implode(', ', $roles) . "\n";
            $contenido .= str_repeat("-", 50) . "\n\n";
        }
    }
    
    file_put_contents($credencialesFile, $contenido);
    echo "📄 Archivo de credenciales generado: {$credencialesFile}\n\n";
    
    // 6. Enlace local para acceso
    $baseUrl = "http://localhost/senapre";
    echo "=== ENLACES DE ACCESO ===\n";
    echo "🌐 Sistema Principal: {$baseUrl}/\n";
    echo "🔐 Login: {$baseUrl}/index.html\n";
    echo "👥 Gestión Credenciales: {$baseUrl}/admin-voceros-credenciales.html\n\n";
    
    echo "=== INSTRUCCIONES ===\n";
    echo "1. Los voceros con roles activos ya pueden ingresar\n";
    echo "2. Usuario = Documento de identidad\n";
    echo "3. Contraseña = Documento de identidad\n";
    echo "4. Para gestionar credenciales, accede a admin-voceros-credenciales.html\n";
    echo "5. Las credenciales se actualizan automáticamente al cambiar roles\n\n";
    
    echo "✅ ¡CONFIGURACIÓN COMPLETADA EXITOSAMENTE!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
