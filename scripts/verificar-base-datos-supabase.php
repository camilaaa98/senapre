<?php
/**
 * Script para verificar base de datos de Supabase
 * SIN MODIFICAR DATOS - SOLO LECTURA
 */

echo "=== VERIFICACIÓN DE BASE DE DATOS SUPABASE ===\n\n";

// URLs comunes de Supabase (ajustar según configuración real)
$supabase_urls = [
    "postgresql://postgres.abc123:password@db.abc123.supabase.co:5432/postgres",
    "postgresql://postgres:password@db.abcdef.supabase.co:5432/postgres",
    // Agregar aquí la URL real si la conoces
];

echo "🔍 Intentando conectar a Supabase...\n";
echo "📡 Probando configuraciones conocidas...\n\n";

$conexion_exitosa = false;

foreach ($supabase_urls as $index => $database_url) {
    echo "🌐 Intento " . ($index + 1) . ":\n";
    echo "   URL: " . preg_replace('/:[^:]*@/', ':***@', $database_url) . "\n";
    
    try {
        $parsed = parse_url($database_url);
        $host    = $parsed['host'];
        $port    = $parsed['port'];
        $db      = ltrim($parsed['path'], '/');
        $user    = $parsed['user'];
        $pass    = $parsed['pass'];

        $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
        
        $conn = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_TIMEOUT           => 5, // Timeout corto
        ]);
        
        echo "   ✅ CONEXIÓN EXITOSA\n\n";
        
        // Si llegamos aquí, tenemos conexión
        $conexion_exitosa = true;
        
        // Verificar estructura básica
        echo "=== ESTRUCTURA DE BASE DE DATOS ===\n";
        
        $tablas = ['usuarios', 'aprendices', 'fichas'];
        foreach ($tablas as $tabla) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $tabla LIMIT 1");
                $stmt->execute();
                $count = $stmt->fetch()['total'];
                echo "📋 $tabla: $count registros\n";
            } catch (Exception $e) {
                echo "❌ $tabla: No existe o error de acceso\n";
            }
        }
        
        // Verificar usuarios
        echo "\n=== USUARIOS ===\n";
        try {
            $stmt = $conn->prepare("SELECT rol, COUNT(*) as cantidad FROM usuarios GROUP BY rol");
            $stmt->execute();
            $usuarios = $stmt->fetchAll();
            foreach ($usuarios as $usuario) {
                echo "👥 {$usuario['rol']}: {$usuario['cantidad']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error al consultar usuarios\n";
        }
        
        // Verificar voceros
        echo "\n=== VOCEROS ===\n";
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'vocero'");
            $stmt->execute();
            $voceros = $stmt->fetch()['total'];
            echo "🎭 Voceros totales: $voceros\n";
            
            // Verificar roles asignados
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(CASE WHEN f.vocero_principal IS NOT NULL THEN 1 END) as principales,
                    COUNT(CASE WHEN f.vocero_suplente IS NOT NULL THEN 1 END) as suplentes
                FROM fichas f
                WHERE f.vocero_principal IS NOT NULL OR f.vocero_suplente IS NOT NULL
            ");
            $stmt->execute();
            $roles = $stmt->fetch();
            echo "   🎯 Principales: " . $roles['principales'] . "\n";
            echo "   🔄 Suplentes: " . $roles['suplentes'] . "\n";
            
        } catch (Exception $e) {
            echo "❌ Error al consultar voceros\n";
        }
        
        echo "\n=== REGLAS DE ACCESO ===\n";
        echo "🔐 Usuario = Correo electrónico\n";
        echo "🔑 Contraseña = Documento de identidad\n";
        echo "🎯 Voceros: Requieren roles activos\n\n";
        
        echo "✅ VERIFICACIÓN COMPLETADA\n";
        break;
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
        continue;
    }
}

if (!$conexion_exitosa) {
    echo "❌ NO SE PUDO CONECTAR A SUPABASE CON LAS CONFIGURACIONES CONOCIDAS\n\n";
    
    echo "=== SOLUCIONES ===\n";
    echo "1. Obtener la DATABASE_URL real de Supabase\n";
    echo "2. Configurar variables de entorno en el servidor\n";
    echo "3. Verificar que la base de datos esté activa\n\n";
    
    echo "=== PARA OBTENER LA URL DE SUPABASE ===\n";
    echo "1. Ir a https://supabase.com/dashboard\n";
    echo "2. Seleccionar tu proyecto\n";
    echo "3. Ir a Settings > Database\n";
    echo "4. Copiar la Connection String (URI)\n";
    echo "5. Formato: postgresql://[user]:[password]@[host]:[port]/[database]\n\n";
    
    echo "=== CONFIGURACIÓN EN EL SERVIDOR ===\n";
    echo "En Render (producción):\n";
    echo "- Environment Variables > Add Variable\n";
    echo "- Name: DATABASE_URL\n";
    echo "- Value: [pegar la URL de Supabase]\n\n";
    
    echo "En desarrollo local (.env):\n";
    echo "DATABASE_URL=[pegar la URL de Supabase]\n\n";
}

echo "=== RECOMENDACIONES ===\n";
echo "✅ Trabajar siempre con la base de datos real (Supabase)\n";
echo "✅ No usar datos de prueba en producción\n";
echo "✅ Mantener sincronización local-nube\n";
echo "✅ Verificar credenciales antes de deploy\n\n";

echo "=== ESTADO ACTUAL ===\n";
if ($conexion_exitosa) {
    echo "✅ Base de datos: Conectada\n";
    echo "📊 Estructura: Verificada\n";
    echo "👥 Usuarios: Analizados\n";
    echo "🎭 Voceros: Listos\n";
    echo "🔐 Sistema: Funcionando\n";
} else {
    echo "❌ Base de datos: No conectada\n";
    echo "⚠️  Se necesita configuración de Supabase\n";
    echo "🔄 Sistema: En espera de conexión\n";
}
?>
