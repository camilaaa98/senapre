<?php
/**
 * GUÍA COMPLETA PARA CREAR NUEVA BASE DE DATOS SUPABASE
 * Y CONFIGURAR EL SISTEMA SENAPRE
 */

echo "=== GUÍA: CREAR NUEVA BASE DE DATOS SUPABASE ===\n\n";

echo "📋 PASO A PASO PARA SOLUCIONAR EL PROBLEMA:\n\n";

echo "🔥 PROBLEMA ACTUAL:\n";
echo "❌ La cuenta de Supabase fue eliminada/suspendida\n";
echo "❌ No hay DATABASE_URL configurada\n";
echo "❌ El sistema no puede conectar a producción\n\n";

echo "✅ SOLUCIÓN:\n";
echo "1. Crear nueva cuenta en Supabase\n";
echo "2. Crear nuevo proyecto\n";
echo "3. Configurar base de datos\n";
echo "4. Migrar datos existentes\n";
echo "5. Actualizar configuración\n\n";

echo "=== PASO 1: CREAR CUENTA SUPABASE ===\n";
echo "1. Ir a https://supabase.com\n";
echo "2. Click en 'Sign Up'\n";
echo "3. Usar correo: admin@senapre.com (o similar)\n";
echo "4. Verificar correo electrónico\n";
echo "5. Iniciar sesión\n\n";

echo "=== PASO 2: CREAR NUEVO PROYECTO ===\n";
echo "1. Dashboard > 'New Project'\n";
echo "2. Nombre: senapre\n";
echo "3. Contraseña: Crear una segura (guardarla)\n";
echo "4. Región: Seleccionar la más cercana (us-east-1)\n";
echo "5. Esperar creación (2-3 minutos)\n\n";

echo "=== PASO 3: OBTENER DATABASE_URL ===\n";
echo "1. Project > Settings > Database\n";
echo "2. Buscar 'Connection String'\n";
echo "3. Copiar la URL (formato: postgresql://...)\n";
echo "4. Se ve así: postgresql://postgres:[PASSWORD]@db.[PROJECT].supabase.co:5432/postgres\n\n";

echo "=== PASO 4: CONFIGURAR EN RENDER ===\n";
echo "1. Ir a https://dashboard.render.com\n";
echo "2. Seleccionar servicio 'senapre'\n";
echo "3. Environment > Add Environment Variable\n";
echo "4. Key: DATABASE_URL\n";
echo "5. Value: [pegar URL de Supabase]\n";
echo "6. Save Changes\n";
echo "7. Redeploy (automático)\n\n";

echo "=== PASO 5: MIGRAR DATOS (SI TIENES BACKUP) ===\n";
echo "Si tienes backup de datos anteriores:\n";
echo "1. Supabase > Table Editor\n";
echo "2. Crear tablas con el schema\n";
echo "3. Importar datos CSV/SQL\n";
echo "4. Verificar estructura\n\n";

echo "=== PASO 6: VERIFICAR CONEXIÓN ===\n";
echo "1. Ejecutar: php scripts/verificar-base-datos-supabase.php\n";
echo "2. Debe mostrar conexión exitosa\n";
echo "3. Probar login en producción\n\n";

echo "=== CONFIGURACIÓN LOCAL ===\n";
echo "Para trabajar localmente con la misma BD:\n";
echo "1. Copiar DATABASE_URL a .env.local\n";
echo "2. O configurar en variables de entorno\n\n";

echo "=== URL DE PRODUCCIÓN ===\n";
echo "🌐 https://senapre.onrender.com/\n";
echo "🔐 https://senapre.onrender.com/index.html\n\n";

echo "=== CREDENCIALES DE ACCESO ===\n";
echo "🔐 Usuario = Correo electrónico\n";
echo "🔑 Contraseña = Documento de identidad\n";
echo "🎯 Voceros = Solo con roles activos\n\n";

echo "=== BACKUP Y SEGURIDAD ===\n";
echo "📁 Hacer backup regular de Supabase\n";
echo "🔐 Guardar credenciales seguras\n";
echo "📧 Configurar notificaciones de uso\n";
echo "💳 Configurar plan gratuito (limites: 500MB BD, 2GB transferencia)\n\n";

echo "=== SI NO TIENES BACKUP DE DATOS ===\n";
echo "1. Crear estructura de tablas desde cero\n";
echo "2. Ejecutar scripts de setup\n";
echo "3. Ingresar datos manualmente\n";
echo "4. Crear usuarios de prueba\n\n";

echo "=== PLAN GRATUITO SUPABASE LIMITS ===\n";
echo "💾 Base de datos: 500MB\n";
echo "📤 Transferencia: 2GB/mes\n";
echo "👥 Usuarios: 50,000/mes\n";
echo "🔄 Conexiones: 60 simultáneas\n\n";

echo "=== RECOMENDACIONES ===\n";
echo "✅ Usar plan gratuito mientras crece\n";
echo "✅ Hacer backup semanal\n";
echo "✅ Monitorear uso de storage\n";
echo "✅ Configurar alertas de billing\n\n";

echo "=== CONTACTO SOPORTE ===\n";
echo "Si necesitas ayuda:\n";
echo "📧 support@supabase.com\n";
echo "💬 Discord: https://discord.supabase.com\n";
echo "📖 Docs: https://supabase.com/docs\n\n";

echo "¿Necesitas ayuda con algún paso específico?\n";
echo "Puedo guiarte paso a paso en cada fase.\n\n";

echo "✅ ¡VAMOS A RECUPERAR EL SISTEMA!\n";
?>
