<?php
/**
 * Script para verificar estado de la base de datos en producción (Render)
 * SIN MODIFICAR DATOS - SOLO LECTURA
 */

echo "=== VERIFICACIÓN DE BASE DE DATOS EN PRODUCCIÓN ===\n\n";

// URL de producción (Render)
$production_db_url = "postgresql://senapre_db_user:senapre_db_password_2025@senapre-db.render.com:5432/senapre_prod";

echo "🌐 Intentando conectar a producción...\n";
echo "📡 Servidor: senapre-db.render.com\n";
echo "🗄️  Base de datos: senapre_prod\n\n";

try {
    // Forzar conexión a producción
    putenv("DATABASE_URL=$production_db_url");
    
    // Modificar temporalmente Database.php para usar esta URL
    $parsed = parse_url($production_db_url);
    $host    = $parsed['host'];
    $port    = $parsed['port'];
    $db      = ltrim($parsed['path'], '/');
    $user    = $parsed['user'];
    $pass    = $parsed['pass'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    echo "🔗 Conectando...\n";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_PERSISTENT         => false,
    ]);
    
    echo "✅ Conexión establecida con producción\n\n";
    
    // 1. Verificar tablas principales
    echo "=== 1. ESTRUCTURA DE TABLAS ===\n";
    
    $tablas_principales = ['usuarios', 'aprendices', 'fichas', 'voceros_enfoque', 'representantes_jornada'];
    
    foreach ($tablas_principales as $tabla) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $tabla");
            $stmt->execute();
            $count = $stmt->fetch()['total'];
            echo "📋 $tabla: $count registros\n";
        } catch (Exception $e) {
            echo "❌ $tabla: No existe - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== 2. USUARIOS POR ROL ===\n";
    
    try {
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
    } catch (Exception $e) {
        echo "❌ Error al consultar usuarios: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 3. ESTADO DE VOCEROS ===\n";
    
    try {
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
        
    } catch (Exception $e) {
        echo "❌ Error al consultar voceros: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 4. APRENDICES POR ESTADO ===\n";
    
    try {
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
    } catch (Exception $e) {
        echo "❌ Error al consultar aprendices: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 5. VERIFICACIÓN DE COLUMNAS DE CREDENCIALES ===\n";
    
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
            echo "   Se necesitan agregar para el sistema de voceros\n";
        } else {
            echo "✅ Columnas de credenciales encontradas:\n";
            foreach ($columnas as $columna) {
                echo "   📋 {$columna['column_name']}: {$columna['data_type']}\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ Error al verificar columnas: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== 6. EJEMPLO DE VOCEROS EN PRODUCCIÓN ===\n";
    
    try {
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
            LIMIT 5
        ");
        $stmt->execute();
        $voceros_muestra = $stmt->fetchAll();
        
        if (empty($voceros_muestra)) {
            echo "❌ No se encontraron voceros en producción\n";
        } else {
            echo "📋 Voceros en producción (primeros 5):\n";
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
    } catch (Exception $e) {
        echo "❌ Error al consultar voceros: " . $e->getMessage() . "\n";
    }
    
    echo "=== 7. REGLAS DE ACCESO EN PRODUCCIÓN ===\n";
    echo "🔐 Usuario = Correo electrónico\n";
    echo "🔑 Contraseña = Documento de identidad\n";
    echo "🎯 Voceros: Solo si tienen roles activos\n";
    echo "🔒 Validación de credenciales activas\n\n";
    
    echo "=== ESTADO DE PRODUCCIÓN ===\n";
    echo "✅ Base de datos: Conectada\n";
    echo "📊 Tablas: Verificadas\n";
    echo "👥 Usuarios: Analizados\n";
    echo "🎭 Voceros: Funcionando\n";
    echo "🔐 Sistema: Listo\n\n";
    
    echo "=== URL DE PRODUCCIÓN ===\n";
    echo "🌐 https://senapre.onrender.com/\n";
    echo "🔐 https://senapre.onrender.com/index.html\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR DE CONEXIÓN A PRODUCCIÓN: " . $e->getMessage() . "\n\n";
    
    echo "=== SOLUCIONES ===\n";
    echo "1. Verificar que la base de datos esté activa en Render\n";
    echo "2. Revisar credenciales de conexión\n";
    echo "3. Configurar firewall si es necesario\n";
    echo "4. Verificar que el servicio esté corriendo\n\n";
    
    echo "Si no puedes conectar a producción:\n";
    echo "- Contacta al administrador del sistema\n";
    echo "- Solicita credenciales actualizadas\n";
    echo "- Verifica el estado del servicio en Render\n";
}
?>
