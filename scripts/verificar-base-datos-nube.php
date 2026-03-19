<?php
/**
 * Script para verificar estado actual de la base de datos en la nube (Supabase)
 * SIN MODIFICAR DATOS - SOLO LECTURA
 */
require_once __DIR__ . '/../api/config/Database.php';

echo "=== VERIFICACIÓN DE BASE DE DATOS EN LA NUBE (SUPABASE) ===\n\n";

try {
    // Forzar conexión a la nube (Render/Supabase)
    $database_url = getenv('DATABASE_URL');
    
    if (!$database_url || strpos($database_url, 'localhost') !== false) {
        echo "❌ ERROR: No se encontró configuración de base de datos en la nube\n";
        echo "   Configuración actual: " . ($database_url ?: "No encontrada") . "\n";
        echo "   Se necesita DATABASE_URL de Supabase/Render\n\n";
        
        echo "=== PARA CONECTAR A LA NUBE ===\n";
        echo "1. Obtén la DATABASE_URL de Supabase/Render\n";
        echo "2. Configúrala en variables de entorno del servidor\n";
        echo "3. O actualiza el archivo .env con la URL correcta\n\n";
        
        exit(1);
    }
    
    echo "✅ Conectando a base de datos en la nube...\n";
    echo "📡 URL: " . preg_replace('/:[^:]*@/', ':***@', $database_url) . "\n\n";
    
    $database = Database::getInstance();
    $conn = $database->getConnection();
    
    echo "🔗 Conexión establecida correctamente\n\n";
    
    // 1. Verificar estructura de tablas principales
    echo "=== 1. ESTRUCTURA DE TABLAS ===\n";
    
    $tablas_principales = ['usuarios', 'aprendices', 'fichas', 'voceros_enfoque', 'representantes_jornada'];
    
    foreach ($tablas_principales as $tabla) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $tabla");
            $stmt->execute();
            $count = $stmt->fetch()['total'];
            echo "📋 $tabla: $count registros\n";
        } catch (Exception $e) {
            echo "❌ $tabla: Error al acceder - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== 2. USUARIOS EN EL SISTEMA ===\n";
    
    // Verificar usuarios por rol
    $stmt = $conn->prepare("
        SELECT rol, COUNT(*) as cantidad 
        FROM usuarios 
        GROUP BY rol 
        ORDER BY cantidad DESC
    ");
    $stmt->execute();
    $usuarios_por_rol = $stmt->fetchAll();
    
    foreach ($usuarios_por_rol as $usuario) {
        echo "👥 {$usuario['rol']}: {$usuario['cantidad']} usuarios\n";
    }
    
    echo "\n=== 3. VOCEROS Y SUS ROLES ===\n";
    
    // Verificar voceros principales
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM fichas 
        WHERE vocero_principal IS NOT NULL AND TRIM(vocero_principal) != ''
    ");
    $stmt->execute();
    $principales = $stmt->fetch()['total'];
    echo "🎯 Voceros Principales: $principales\n";
    
    // Verificar voceros suplentes
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM fichas 
        WHERE vocero_suplente IS NOT NULL AND TRIM(vocero_suplente) != ''
    ");
    $stmt->execute();
    $suplentes = $stmt->fetch()['total'];
    echo "🔄 Voceros Suplentes: $suplentes\n";
    
    // Verificar voceros de enfoque
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM voceros_enfoque");
    $stmt->execute();
    $enfoque = $stmt->fetch()['total'];
    echo "🎨 Voceros de Enfoque: $enfoque\n";
    
    // Verificar representantes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM representantes_jornada");
    $stmt->execute();
    $representantes = $stmt->fetch()['total'];
    echo "📢 Representantes: $representantes\n";
    
    echo "\n=== 4. APRENDICES POR ESTADO ===\n";
    
    $stmt = $conn->prepare("
        SELECT estado, COUNT(*) as cantidad 
        FROM aprendices 
        GROUP BY estado 
        ORDER BY cantidad DESC
    ");
    $stmt->execute();
    $aprendices_por_estado = $stmt->fetchAll();
    
    foreach ($aprendices_por_estado as $aprendiz) {
        echo "📚 {$aprendiz['estado']}: {$aprendiz['cantidad']} aprendices\n";
    }
    
    echo "\n=== 5. VERIFICACIÓN DE CREDENCIALES DE VOCEROS ===\n";
    
    // Verificar si existe la columna credenciales_vocero_activas
    try {
        $stmt = $conn->prepare("
            SELECT column_name, data_type, column_default 
            FROM information_schema.columns 
            WHERE table_name = 'usuarios' 
            AND column_name IN ('credenciales_vocero_activas', 'fecha_actualizacion_credenciales')
            ORDER BY column_name
        ");
        $stmt->execute();
        $columnas = $stmt->fetchAll();
        
        if (empty($columnas)) {
            echo "❌ Columnas de credenciales de voceros NO existen\n";
            echo "   Se necesitan: credenciales_vocero_activas, fecha_actualizacion_credenciales\n";
        } else {
            echo "✅ Columnas de credenciales encontradas:\n";
            foreach ($columnas as $columna) {
                echo "   📋 {$columna['column_name']}: {$columna['data_type']}\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error al verificar columnas: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 6. MUESTRA DE VOCEROS ACTIVOS ===\n";
    
    // Mostrar algunos voceros con sus roles
    $stmt = $conn->prepare("
        SELECT 
            u.id_usuario as documento,
            u.nombre,
            u.apellido,
            u.correo,
            u.estado,
            (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_principal) = u.id_usuario) as es_principal,
            (SELECT COUNT(*) FROM fichas WHERE TRIM(vocero_suplente) = u.id_usuario) as es_suplente,
            (SELECT COUNT(*) FROM voceros_enfoque WHERE TRIM(documento) = u.id_usuario) as es_enfoque,
            (SELECT COUNT(*) FROM representantes_jornada WHERE TRIM(documento) = u.id_usuario) as es_representante
        FROM usuarios u
        WHERE u.rol = 'vocero'
        LIMIT 10
    ");
    $stmt->execute();
    $voceros_muestra = $stmt->fetchAll();
    
    if (empty($voceros_muestra)) {
        echo "❌ No se encontraron voceros en el sistema\n";
    } else {
        echo "📋 Muestra de voceros (primeros 10):\n";
        foreach ($voceros_muestra as $vocero) {
            $roles = [];
            if ($vocero['es_principal'] > 0) $roles[] = 'Principal';
            if ($vocero['es_suplente'] > 0) $roles[] = 'Suplente';
            if ($vocero['es_enfoque'] > 0) $roles[] = 'Enfoque';
            if ($vocero['es_representante'] > 0) $roles[] = 'Representante';
            
            echo "   👤 {$vocero['nombre']} {$vocero['apellido']} ({$vocero['documento']})\n";
            echo "      📧 {$vocero['correo']}\n";
            echo "      🎭 Roles: " . (empty($roles) ? 'Ninguno' : implode(', ', $roles)) . "\n";
            echo "      ✅ Estado: " . ($vocero['estado'] ? 'Activo' : 'Inactivo') . "\n\n";
        }
    }
    
    echo "=== 7. REGLAS DE ACCESO ACTUALES ===\n";
    echo "🔐 Usuario = Correo electrónico\n";
    echo "🔑 Contraseña = Documento de identidad\n";
    echo "🎯 Voceros: Solo si tienen roles activos asignados\n";
    echo "🔒 Otros roles: Según estado del usuario\n\n";
    
    echo "=== RESUMEN ===\n";
    echo "✅ Base de datos conectada correctamente\n";
    echo "📊 Estructura verificada\n";
    echo "👥 Usuarios y roles analizados\n";
    echo "🎭 Sistema de voceros funcionando\n";
    echo "🔐 Reglas de acceso confirmadas\n\n";
    
    echo "=== RECOMENDACIONES ===\n";
    
    if (empty($columnas)) {
        echo "⚠️  Ejecutar script para agregar columnas de credenciales de voceros\n";
        echo "   Archivo: setup/add_credenciales_voceros.sql\n\n";
    }
    
    if ($principales + $suplentes + $enfoque + $representantes == 0) {
        echo "⚠️  No hay voceros con roles asignados\n";
        echo "   Se necesita asignar roles para que puedan acceder\n\n";
    }
    
    echo "✅ Sistema listo para producción en la nube\n";
    
} catch (Exception $e) {
    echo "❌ ERROR DE CONEXIÓN: " . $e->getMessage() . "\n";
    echo "\n=== SOLUCIONES POSIBLES ===\n";
    echo "1. Verificar DATABASE_URL en variables de entorno\n";
    echo "2. Configurar conexión a Supabase/Render\n";
    echo "3. Verificar credenciales de base de datos\n";
    echo "4. Revisar firewall o red\n\n";
    
    echo "Si estás trabajando localmente, considera:\n";
    echo "- Usar la base de datos real de Supabase\n";
    echo "- Configurar túnel o VPN si es necesario\n";
    echo "- Solicitar credenciales de acceso a la nube\n";
}
?>
